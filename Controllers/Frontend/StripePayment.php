<?php
use Shopware\Plugins\StripePayment\Util;
use Shopware\Models\Order\Status;

/**
 * The controller handling the main payment process using the stripe API.
 *
 * @copyright Copyright (c) 2015, VIISON GmbH
 */
class Shopware_Controllers_Frontend_StripePayment extends Shopware_Controllers_Frontend_Payment
{
    /**
     * The platform name used as meta data when creating a new charg.e
     */
    const STRIPE_PLATFORM_NAME = 'UMXJ4nBknsWR3LN_shopware_v50';

    /**
     * Retrieves the generated stripe transaction token and uses it to
     * charge the customer via the stripe API. After a successful payment,
     * the stripe transaction id is safed in the order and its status is updated to
     * 'payed'. Finally the user is redirected to the 'finish' action of the checkout process.
     */
    public function indexAction()
    {
        try {
            // Prepare the charge
            $chargeData = $this->getChargeData();

            // Init the stripe payment
            Util::initStripeAPI();
            $charge = Stripe\Charge::create($chargeData);
        } catch (Exception $e) {
            // Determine error message
            $namespace = $this->get('snippets')->getNamespace('frontend/plugins/payment/stripe_payment');
            $message = ($e instanceof Stripe\Error\Card && $e->stripeCode === 'incorrect_cvc') ? $namespace->get('payment_error/message/charge_failed/incorrect_cvc') : $e->getMessage();
            $message = ($message) ?: $e->getMessage();
            // Save the exception message in the session and redirect to the checkout confirm/index view
            Shopware()->Session()->stripePaymentError = (($namespace->get('payment_error/message/charge_failed')) ?: 'Payment process failed, because an error occurred: ') . ' ' . $message;
            $this->redirect(array(
                'controller' => 'checkout',
                'action' => (Shopware()->Shop()->getTemplate()->getVersion() < 3) ? 'confirm' : 'index'
            ));
            return;
        }

        // Save the payment details in the order
        // Use the balance_transaction as the paymentUniqueId, because altough the column in the backend
        // order list is named 'Transaktion' or 'tranaction', it displays NOT the transactionId, but
        // the field 'temporaryID', to which the paymentUniqueId is written. Additionally the
        // balance_transaction is displayed in the shop owner's Stripe account, so it can
        // be used to easily identify an order.
        $paymentUniqueId = $charge->balance_transaction;
        $paymentStatus = Status::PAYMENT_STATE_COMPLETELY_PAID;
        // For cases where capture is false, balance_transaction will be empty because the money has not been moved.
        // That will happen later with the actual capture.  For now, just use the $charge->id
        if (empty($paymentUniqueId) && ! $this->isCapture()) {
            $paymentUniqueId = $charge->id;
            $paymentStatus = Status::PAYMENT_STATE_RESERVED;
        }
        $orderNumber = $this->saveOrder($charge->id, $paymentUniqueId, $paymentStatus); // transactionId, paymentUniqueId, [paymentStatusId, [sendStatusMail]]
        if ($orderNumber) {
            try {
                // Save the order number in the description of the charge
                $charge->description .= ' / Bestell-Nr.: ' . $this->getOrderNumber();
                $charge->save();
            } catch (Exception $e) {
                // Ignore exceptions in this case, because the order has already been created
                // and adding the order number is not essential to identify the payment
            }

            // Try to update the cleared date
            /** @var \Shopware\Models\Order\Order $order */
            $order = $this->get('models')->getRepository('Shopware\Models\Order\Order')->findOneBy(array(
                'number' => $orderNumber
            ));
            $order->setClearedDate(new \DateTime());
            $this->get('models')->flush($order);
        }

        if (Shopware()->Session()->stripeDeleteCardAfterPayment === true) {
            // Delete the Stripe card
            try {
                Util::deleteStripeCard($charge->source->id);
            } catch (Exception $e) {
                // Ignore exceptions in this case, because the order has already been created
                // and deleting the credit card is assumed to be an optional operation
            }
        }

        // Unset the values stored in the session
        unset(Shopware()->Session()->stripeDeleteCardAfterPayment);
        unset(Shopware()->Session()->stripeTransactionToken);
        unset(Shopware()->Session()->stripeCardId);
        unset(Shopware()->Session()->stripeCard);
        unset(Shopware()->Session()->allStripeCards);

        // Finish the checkout process
        // By passing the 'paymentUniqueId' (aka 'temporaryID') to 'sUniqueID', we allow an early return of
        // the 'Checkout' controller's 'finishAction()'. The order is created by calling 'saveOrder()' on
        // this controller earlier, so it definitely exists after the redirect. However, 'finishAction()'
        // can only find the order, if we pass the 'sUniqueID' here. If we don't pass the 'paymentUniqueId',
        // there are apparently some shops that fail to display the order summary, although a vanilla
        // Shopware 5 or 5.1 installation works correctly. That is, because the basket is empty after creating
        // the order, the session's sOrderVariables are assigned to the view and NO redirect to the confirm action
        // is performed (see https://github.com/shopware/shopware/blob/6e8b58477c1a9aa873328c258139fa6085238b4b/engine/Shopware/Controllers/Frontend/Checkout.php#L272-L275).
        // Anyway, setting 'sUniqueID' seems to be the safe way to display the order summary.
        $this->redirect(array(
            'controller' => 'checkout',
            'action' => 'finish',
            'sUniqueID' => $paymentUniqueId
        ));
    }

    /**
     * Renders the content of cvc info popup.
     */
    public function cvcInfoAction() {}

    /**
     * Gathers all the data, which is needed to create a new Stripe charge, from the
     * active session. If the a Stripe card id is found, it is used to retrieve the
     * corresponding Stripe customer instance.
     *
     * @return array - An array containing the charge data.
     * @throws Exception - An exception, if the found Stripe credit card was not found or neither a payment token, nor a card id was found.
     */
    public function getChargeData()
    {
        // Get the necessary user info
        $user = $this->getUser();
        $userEmail = $user['additional']['user']['email'];
        if (Shopware()->Plugins()->Frontend()->StripePayment()->assertMinimumVersion('5.2.0')) {
            $customerNumber = $user['additional']['user']['customernumber'];
        } else {
            $customerNumber = $user['billingaddress']['customernumber'];
        }

        // Prepare the charge data
        $chargeData = array(
            'amount' => ($this->getAmount() * 100), // Amount has to be in cents!
            'currency' => $this->getCurrencyShortName(),
            'description' => ($userEmail . ' / Kunden-Nr.: ' . $customerNumber),
            'metadata' => array(
                'platform_name' => self::STRIPE_PLATFORM_NAME
            ),
            'capture' => $this->isCapture(),
        );

        if (Shopware()->Session()->stripeTransactionToken !== null) {
            // Create a new charge using the transaction token
            $chargeData['source'] = Shopware()->Session()->stripeTransactionToken;
        } elseif (Shopware()->Session()->stripeCardId !== null) {
            // Create a new charge using the selected card and the customer
            $chargeData['source'] = Shopware()->Session()->stripeCardId;
            try {
                $stripeCustomer = Util::getStripeCustomer();
                $chargeData['customer'] = $stripeCustomer->id;
            } catch (Exception $e) {
                // The Stripe customer couldn't be loaded
                $message = ($this->get('snippets')->getNamespace('frontend/plugins/payment/stripe_payment')->get('payment_error/message/card_not_found')) ?: 'Card not found.';
                throw new Exception($message);
            }
        } else {
            // No payment information provided
            $message = ($this->get('snippets')->getNamespace('frontend/plugins/payment/stripe_payment')->get('payment_error/message/transaction_not_found')) ?: 'Transaction not found.';
            throw new Exception($message);
        }

        return $chargeData;
    }


    /**
     * @return boolean - default true - meaning the charge should be captured.  False means reserve the payment, but do not capture.
     */
    public function isCapture() {
        // Default to true
        return Shopware()->Session()->get('stripeCapture', true);
    }
}
