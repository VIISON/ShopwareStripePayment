<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Plugins\StripePayment\Controllers\StripeCheckout;
use Shopware\Plugins\StripePayment\Util;

class Shopware_Controllers_Frontend_StripePayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    use StripeCheckout;

    /**
     * @inheritdoc
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'stripeWebhook'
        ];
    }

    /**
     * Creates a source using the selected Stripe payment method class and completes its payment
     * flow. That is, if the source is already chargeable, the charge is created and the order is
     * saved. If however the source requires a flow like 'redirect', the flow is executed without
     * charing the source or creating an order (these steps will be peformed by the flow).
     */
    public function indexAction()
    {
        $stripeSession = Util::getStripeSession();

        // Create a source using the selected Stripe payment method
        try {
            $source = $this->getStripePaymentMethod()->createStripeSource(
                $this->getAmountInCents(),
                $this->getCurrencyShortName()
            );
        } catch (Exception $e) {
            $this->get('pluginlogger')->error(
                'StripePayment: Failed to create source',
                [
                    'exception' => $e,
                    'trace' => $e->getTrace(),
                ]
            );
            $message = $this->getStripePaymentMethod()->getErrorMessage($e);
            $this->cancelCheckout($message);

            return;
        }

        // Trigger the payment flow if required
        if ($source->flow === 'redirect') {
            if ($source->redirect->status === 'failed') {
                $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/redirect/failed');
                $this->cancelCheckout($message);

                return;
            }

            // Mark the session as processing the payment, which will help to handle webhook events
            $stripeSession->processingSourceId = $source->id;

            // Perform a redirect to complete the payment flow
            $stripeSession->redirectClientSecret = $source->client_secret;
            $this->redirect($source->redirect->url);
        } elseif ($source->status === 'chargeable') {
            // No special flow required, hence use the source to create the charge and save the order
            try {
                $charge = $this->createCharge($source);
                $order = $this->saveOrderWithCharge($charge);
            } catch (Exception $e) {
                $this->get('pluginlogger')->error(
                    'StripePayment: Failed to create charge',
                    [
                        'exception' => $e,
                        'trace' => $e->getTrace(),
                        'sourceId' => $source->id,
                    ]
                );
                $message = $this->getStripePaymentMethod()->getErrorMessage($e);
                $this->cancelCheckout($message);

                return;
            }

            $this->finishCheckout($order);
        } else {
            // Unable to process payment
            $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/source_declined');
            $this->cancelCheckout($message);
        }
    }

    /**
     * Note: Only use this action for creating the return URL of a Stripe redirect flow.
     *
     * Compares the 'client_secret' contained in the redirect request with the session and,
     * if valid, fetches the respective source and charges it with the order amount. Finally
     * the order is saved and the checkout is finished.
     */
    public function completeRedirectFlowAction()
    {
        Util::initStripeAPI();
        // Compare the client secrets
        $clientSecret = $this->Request()->getParam('client_secret');
        if (!$clientSecret || $clientSecret !== Util::getStripeSession()->redirectClientSecret) {
            $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/redirect/internal_error');
            $this->cancelCheckout($message);

            return;
        }

        // Try to get the Stripe source
        $sourceId = $this->Request()->getParam('source');
        $source = Stripe\Source::retrieve($sourceId);
        if (!$source) {
            $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/redirect/internal_error');
            $this->cancelCheckout($message);

            return;
        }
        if ($source->status !== 'chargeable') {
            $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/redirect/source_not_chargeable');
            $this->cancelCheckout($message);

            return;
        }

        // Use the source to create the charge and save the order
        try {
            $charge = $this->createCharge($source);
            $order = $this->saveOrderWithCharge($charge);
        } catch (Exception $e) {
            $message = $this->getStripePaymentMethod()->getErrorMessage($e);
            $this->cancelCheckout($message);

            return;
        }

        $this->finishCheckout($order);
    }

    /**
     * Validates the webhook event and, if valid, tries to process the event based on its type.
     * Currently the following event types are supported:
     *
     *  - charge.failed
     *  - charge.succeeded
     *  - source.chargeable
     */
    public function stripeWebhookAction()
    {
        Util::initStripeAPI();
        // Disable the default renderer to supress errors caused by the template engine
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        try {
            $event = Util::verifyWebhookRequest($this->Request());
        } catch (\Exception $e) {
            // Invalid event
            return;
        }

        try {
            switch ($event->type) {
                case 'charge.failed':
                    $this->processChargeFailedEvent($event);
                    break;
                case 'charge.succeeded':
                    $this->processChargeSucceededEvent($event);
                    break;
                case 'source.chargeable':
                    $this->processSourceChargeableEvent($event);
                    break;
            }
        } catch (\Exception $e) {
            // Log the error and respond with 'ERROR' to make debugging easier
            $this->get('pluginlogger')->error(
                'StripePayment: Failed to process Stripe webhook',
                [
                    'exception' => $e,
                    'trace' => $e->getTrace(),
                    'eventId' => $event->id,
                ]
            );
            echo 'ERROR';

            return;
        }

        // Just respond with 'OK' to make debugging easier
        echo 'OK';
    }

    /**
     * Creates and returns a Stripe charge for the order, whose checkout is handled by this
     * controller, using the provided Stripe $source.
     *
     * @param Stripe\Source $source
     * @return Stripe\Charge
     * @throws Exception If creating the charge failed.
     */
    protected function createCharge(Stripe\Source $source)
    {
        // Get the necessary user info and shop info
        $user = $this->getUser();
        $userEmail = $user['additional']['user']['email'];
        $customerNumber = $user['additional']['user']['customernumber'];

        // Prepare the charge data
        $chargeData = [
            'source' => $source->id,
            'amount' => $this->getAmountInCents(),
            'currency' => $this->getCurrencyShortName(),
            'description' => sprintf('%s / Customer %s', $userEmail, $customerNumber),
            'metadata' => [
                'platform_name' => Util::STRIPE_PLATFORM_NAME,
            ],
        ];
        // Add a statement descriptor, if necessary
        $paymentMethod = $this->getStripePaymentMethod();
        if ($paymentMethod->includeStatmentDescriptorInCharge()) {
            $chargeData['statement_descriptor'] = mb_substr($paymentMethod->getStatementDescriptor(), 0, 22);
        }
        // Try to add a customer reference to the charge
        $stripeCustomer = Util::getStripeCustomer();
        if ($source->customer && $stripeCustomer) {
            $chargeData['customer'] = $stripeCustomer->id;
        }
        // Enable receipt emails, if configured
        $sendReceiptEmails = $this->get('plugins')->get('Frontend')->get('StripePayment')->Config()->get('sendStripeChargeEmails');
        if ($sendReceiptEmails) {
            $chargeData['receipt_email'] = $userEmail;
        }

        return Stripe\Charge::create($chargeData);
    }

    /**
     * Saves the order in the database adding both the ID of the given $charge (as 'transactionId')
     * and the charge's 'balance_transaction' (as 'paymentUniqueId' aka 'temporaryID'). We use the
     * 'balance_transaction' as 'paymentUniqueId', because altough the column in the backend order
     * list is named 'Transaktion' or 'tranaction', it DOES NOT display the transactionId, but the
     * field 'temporaryID', to which the 'paymentUniqueId' is written. Additionally the
     * 'balance_transaction' is displayed in the shop owner's Stripe account, so it can be used to
     * easily identify an order. Finally the cleared date of the order is set to the current date
     * and the order number is saved in the $charge.
     *
     * @param Stripe\Charge $charge
     * @return Order
     */
    protected function saveOrderWithCharge(Stripe\Charge $charge)
    {
        // Save the payment details in the order. Use the source ID as the paymentUniqueId, because altough the column
        // in the backend order list is named 'Transaktion' or 'tranaction', it displays NOT the transactionId, but the
        // field 'temporaryID', to which the paymentUniqueId is written. Additionally the balance_transaction is
        // displayed in the shop owner's Stripe account, so it can be used to easily identify an order.
        $orderNumber = $this->saveOrder(
            $charge->id, // transactionId
            $charge->source->id, // paymentUniqueId
            ($charge->status === 'succeeded') ? Status::PAYMENT_STATE_COMPLETELY_PAID : Status::PAYMENT_STATE_OPEN // paymentStatusId
        );
        if (!$orderNumber) {
            // Order creation failed
            return null;
        }

        // Update the cleared date
        $order = $this->get('models')->getRepository('Shopware\\Models\\Order\\Order')->findOneBy([
            'number' => $orderNumber,
        ]);
        $order->setClearedDate(new \DateTime());
        $this->get('models')->flush($order);

        try {
            // Save the order number in the charge description
            $charge->description .= ' / Order ' . $orderNumber;
            $charge->save();
        } catch (Exception $e) {
            $this->get('pluginlogger')->error(
                'StripePayment: Failed to update charge description with order number',
                [
                    'exception' => $e,
                    'trace' => $e->getTrace(),
                    'paymentIntendId' => $charge->id,
                    'orderId' => $order->getId(),
                ]
            );
        }

        return $order;
    }

    /**
     * Tries to find the order the event belongs to and, if found, update its payment status
     * to 'review necessary'.
     *
     * @param Stripe\Event $event
     */
    protected function processChargeFailedEvent(Stripe\Event $event)
    {
        $order = $this->findOrderForWebhookEvent($event);
        if (!$order) {
            return;
        }
        $paymentStatus = $this->get('models')->find(Status::class, Status::PAYMENT_STATE_REVIEW_NECESSARY);
        $order->setPaymentStatus($paymentStatus);
        $this->get('models')->flush($order);
    }

    /**
     * Tries to find the order the event belongs to and, if found, update its payment status
     * to 'completely paid'.
     *
     * @param Stripe\Event $event
     */
    protected function processChargeSucceededEvent(Stripe\Event $event)
    {
        $order = $this->findOrderForWebhookEvent($event);
        if (!$order) {
            return;
        }
        $paymentStatus = $this->get('models')->find(Status::class, Status::PAYMENT_STATE_COMPLETELY_PAID);
        $order->setPaymentStatus($paymentStatus);
        $this->get('models')->flush($order);
    }

    /**
     * First checks the Shopware session for the 'stripePayment->processingSourceId' field and,
     * if set, makes sure the ID matches the source contained in the event. Then waits for five
     * seconds to prevent timing issues caused by webhooks arriving earlier than e.g. a redirect
     * during the payment process. That is, if completing the  payment process involves e.g.
     * a redirect to the payment provider, the 'source.chargeable' event might arrive at the shop
     * earlier than the redirect returns. By pausing the webhook handler, we give the redirect a
     * head start to complete the order creation. After waiting, the database is checked for an
     * order that used the event's source. If no such order is found, the source is used to
     * create a charge and the session's order is saved to the database.
     *
     * @param Stripe\Event $event
     */
    protected function processSourceChargeableEvent(Stripe\Event $event)
    {
        // Check whether the webhook event is allowed to create an order
        $source = $event->data->object;
        $stripeSession = Util::getStripeSession();
        if ($source->id !== $stripeSession->processingSourceId) {
            return;
        }

        // Wait for five seconds
        sleep(5);

        // Make sure the source has not already been used to create an order, e.g. by completing
        // a redirect
        $order = $this->findOrderForWebhookEvent($event);
        if ($order) {
            return;
        }

        // Use the source to create the charge and save the order
        $charge = $this->createCharge($event->data->object);
        $order = $this->saveOrderWithCharge($charge);
        $this->get('pluginlogger')->info(
            'StripePayment: Created order after receiving "source.chargeable" webhook event',
            [
                'orderId' => $order->getId(),
                'eventId' => $event->id,
            ]
        );
    }

    /**
     * @param Stripe\Event $event
     * @return Shopware\Models\Order\Order|null
     */
    protected function findOrderForWebhookEvent(Stripe\Event $event)
    {
        // Determine the Stripe source
        if ($event->data->object instanceof Stripe\Source) {
            $source = $event->data->object;
        } elseif ($event->data->object instanceof Stripe\Charge) {
            $source = $event->data->object->source;
        } else {
            // Not supported
            return null;
        }

        // Find the order that references the source ID
        $order = $this->get('models')->getRepository('Shopware\\Models\\Order\\Order')->findOneBy([
            'temporaryId' => $source->id,
        ]);

        return $order;
    }
}
