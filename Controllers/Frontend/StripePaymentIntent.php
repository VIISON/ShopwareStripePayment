<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Plugins\StripePayment\Controllers\StripeCheckout;
use Shopware\Plugins\StripePayment\Util;

class Shopware_Controllers_Frontend_StripePaymentIntent extends Shopware_Controllers_Frontend_Payment
{
    use StripeCheckout;

    /**
     * Creates a paymentIntent using the selected Stripe payment method class and completes its payment
     * flow. That is, if the paymentIntent is already chargeable, it is completed and the order is
     * saved. If however the paymentIntent requires a further action the redirect is executed without
     * charging the paymentMethod or creating an order.
     */
    public function indexAction()
    {
        $stripeSession = Util::getStripeSession();

        // Create a source using the selected Stripe payment method
        try {
            $paymentIntent = $this->getStripePaymentMethod()->createStripePaymentIntent(
                $this->getAmountInCents(),
                $this->getCurrencyShortName()
            );
        } catch (Exception $e) {
            $this->get('pluginlogger')->error(
                'StripePayment: Failed to create payment intent',
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
        if ($paymentIntent->status === 'requires_action') {
            if (!$paymentIntent->next_action || $paymentIntent->next_action->type !== 'redirect_to_url') {
                $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/redirect/failed');
                $this->cancelCheckout($message);

                return;
            }

            // Mark the session as processing the payment, which will help to handle webhook events
            $stripeSession->processingSourceId = $paymentIntent->id;

            // Perform a redirect to complete the payment flow
            $stripeSession->redirectClientSecret = $paymentIntent->client_secret;
            $this->redirect($paymentIntent->next_action->redirect_to_url->url);
        } elseif ($paymentIntent->status === 'succeeded') {
            // No special flow required, hence use the source to create the charge and save the order
            try {
                $order = $this->saveOrderWithPaymentIntent($paymentIntent);
            } catch (Exception $e) {
                $this->get('pluginlogger')->error(
                    'StripePayment: Failed to create charge',
                    [
                        'exception' => $e,
                        'trace' => $e->getTrace(),
                        'sourceId' => $paymentIntent->id,
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
     * if valid, fetches the respective paymentIntent and charges it with the order amount. Finally
     * the order is saved and the checkout is finished.
     */
    public function completeRedirectFlowAction()
    {
        Util::initStripeAPI();
        // Compare the client secrets
        $clientSecret = $this->Request()->getParam('payment_intent_client_secret');
        if (!$clientSecret || $clientSecret !== Util::getStripeSession()->redirectClientSecret) {
            $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/redirect/internal_error');
            $this->cancelCheckout($message);

            return;
        }

        // Try to get the Stripe source
        $paymentIntentId = $this->Request()->getParam('payment_intent');
        $paymentIntent = Stripe\PaymentIntent::retrieve($paymentIntentId);
        if (!$paymentIntent) {
            $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/redirect/internal_error');
            $this->cancelCheckout($message);

            return;
        } elseif ($paymentIntent->status !== 'succeeded') {
            $message = $this->getStripePaymentMethod()->getSnippet('payment_error/message/redirect/source_not_chargeable');
            if ($paymentIntent->last_payment_error && $paymentIntent->last_payment_error->code) {
                $message = ($this->getStripePaymentMethod()->getSnippet('error/' . $paymentIntent->last_payment_error->code)) ?: $message;
            }
            $this->cancelCheckout($message);

            return;
        }

        // Use the source to create the charge and save the order
        try {
            $order = $this->saveOrderWithPaymentIntent($paymentIntent);
        } catch (Exception $e) {
            $message = $this->getStripePaymentMethod()->getErrorMessage($e);
            $this->cancelCheckout($message);

            return;
        }

        $this->finishCheckout($order);
    }

    /**
     * Saves the order in the database adding both the ID of the given $charge (as 'transactionId')
     * and the paymentMethod id (as 'paymentUniqueId' aka 'temporaryID'). The charge id is displayed in the shop
     * owner's Stripe account, so it can be used to easily identify an order. Finally the cleared date of the order is
     * set to the current date and the order number is saved in the paymentIntent.
     *
     * @param Stripe\PaymentIntent $paymentIntent
     * @return Order
     */
    protected function saveOrderWithPaymentIntent(Stripe\PaymentIntent $paymentIntent)
    {
        // Save the payment details in the order. The charge id is displayed in the shop owner's Stripe account, so it
        // can be used to easily identify an order.
        $orderNumber = $this->saveOrder(
            $paymentIntent->charges->data[0]->id, // transactionId
            $paymentIntent->payment_method, // paymentUniqueId
            ($paymentIntent->status === 'succeeded') ? Status::PAYMENT_STATE_COMPLETELY_PAID : Status::PAYMENT_STATE_OPEN // paymentStatusId
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
            $paymentIntent->description .= ' / Order ' . $orderNumber;
            $paymentIntent->save();
        } catch (Exception $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCATCH
            // Ignore exceptions in this case, because the order has already been created
            // and adding the order number is not essential for identifying the payment
        }

        return $order;
    }

    /**
     * Returns an instance of a Stripe payment method, which is used e.g. to create
     * stripe paymentIntents.
     *
     * @return Shopware\Plugins\StripePayment\Components\PaymentMethods\AbstractStripePaymentIntentPaymentMethod
     */
    protected function getStripePaymentMethod()
    {
        $paymentMethod = $this->get('session')->sOrderVariables->sPayment;
        $adminModule = $this->get('modules')->Admin();

        return $adminModule->sInitiatePaymentClass($paymentMethod);
    }
}
