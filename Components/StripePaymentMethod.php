<?php
namespace Shopware\Plugins\StripePayment\Components;

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use ShopwarePlugin\PaymentMethods\Components\GenericPaymentMethod;
use Shopware\Plugins\StripePayment\Util;
use Stripe\Charge;
use Stripe\Error\Base;
use Stripe\Error\Card;

/**
 * A simplified payment method instance that is only used to validate the Stripe payment
 * information like transaction token or card ID primarily to prevent quick checkout in
 * Shopware 5 when neither of those exist.
 *
 * @copyright Copyright (c) 2015, VIISON GmbH
 */
abstract class BaseStripePaymentMethod extends GenericPaymentMethod
{
    /**
     * Validates the given payment data by checking for a Stripe transaction token or card ID.
     *
     * @param array $paymentData
     * @return array List of fields containing errors
     */
    protected function doValidate(array $paymentData)
    {
        // Check the payment data for a Stripe transaction token or a selected card ID
        if (empty($paymentData['stripeTransactionToken']) && empty($paymentData['stripeCardId'])) {
            return array(
                'STRIPE_VALIDATION_FAILED'
            );
        }

        return array();
    }

    /**
     * Fetches the Stripe transaction token from the session as well as the selected Stripe card,
     * either from the session or as fallback directly from Stripe.
     *
     * @param userId The ID of the user.
     * @return array|null
     */
    public function getCurrentPaymentDataAsArray($userId)
    {
        // Try to get the Stripe token and/or the currently selected Stripe card
        $stripeTransactionToken = Shopware()->Session()->stripeTransactionToken;
        $allStripeCards = Util::getAllStripeCards();
        $stripeCardId = Shopware()->Session()->stripeCardId;
        if (empty($stripeCardId) && Util::getDefaultStripeCard() !== null) {
            // Use the default card instead
            $stripeCard = Util::getDefaultStripeCard();
            $stripeCardId = $stripeCard['id'];
        }

        return array(
            'stripeTransactionToken' => $stripeTransactionToken,
            'stripeCardId' => $stripeCardId
        );
    }


    /**
     * Captures the payment for a given order that was reserved (auth'd)
     *
     * @param Order $order
     * @return Order
     */
    public function captureOrder(Order $order) {
        $paymentMethod = $order->getPayment();
        $transactionId = $order->getTransactionId();
        if (empty($transactionId)
            ||$paymentMethod->getName() !== 'stripe_payment'
            || $order->getPaymentStatus()->getId() !== Status::PAYMENT_STATE_RESERVED
        ) {
            // nothing to do
            return $order;
        }

        $amount = $order->getInvoiceAmount();
        $errorMessage = '';
        $ok = false;
        try {
            Util::initStripeAPI();
            $charge = Charge::retrieve($order->getTransactionId());
            $charge->capture(array(
                'amount' => intval($amount * 100)
            ));
            $ok = $charge->captured;
        } catch (Base $e) {
            $errorMessage = 'Error: ';
            // Try to get the error response
            if ($e->getJsonBody() !== null) {
                $body = $e->getJsonBody();
                $errorMessage .= $body['error']['message'] . "\n";
            } else {
                $errorMessage .= $e->getMessage() . "\n";
            }
        }

        $date = date('d.m.Y, G:i:s');
        $amountFormatted = number_format($amount, 2, ',', '.');

        $tranMsg = '';
        if (isset($charge) && $charge->captured) {
            // Remember the balance transaction hash
            $tranMsg = "Transaction: {$charge->balance_transaction}\n";
        }

        // Add a new refund comment to the internal comment of the order
        $internalComment = $order->getInternalComment()
            . "\n--------------------------------------------------------------\n"
            . "Stripe Capture ($date)\n"
            . "Amount: $amountFormatted {$order->getCurrency()}\n"
            . $errorMessage
            . $tranMsg
            . "--------------------------------------------------------------\n";
        $order->setInternalComment($internalComment);
        if (isset($charge) && $charge->captured) {
            // Set the date to now
            $order->setClearedDate(new \DateTime());
        }
        Shopware()->Models()->flush($order);

        if ($ok) {
            $sOrder = Shopware()->Modules()->Order();
            $sOrder->setPaymentStatus($order->getId(), Status::PAYMENT_STATE_COMPLETELY_PAID, false, "Captured: $amountFormatted");
        }

        return $order;
    }

    /**
     * Refunds the charge for the amount of the order.
     *
     * @param Order $order
     * @return Order
     */
    public function refundOrder(Order $order) {
        $paymentMethod = $order->getPayment();
        $transactionId = $order->getTransactionId();
        if (empty($transactionId)
            ||$paymentMethod->getName() !== 'stripe_payment'
            || !in_array($order->getPaymentStatus()->getId(), [
                    Status::PAYMENT_STATE_OPEN, Status::PAYMENT_STATE_RESERVED, Status::PAYMENT_STATE_COMPLETELY_PAID
                ])
        ) {
            // nothing to do
            return $order;
        }

        $amount = $order->getInvoiceAmount();

        $errorMessage = '';
        $ok = false;
        $state = 'Error';
        try {
            Util::initStripeAPI();
            $charge = Charge::retrieve($order->getTransactionId());
            $state = $charge->captured ? 'Refund' : 'Release';
            $refundAmount = $charge->amount - $charge->amount_refunded;

            if (empty($refundAmount)) {
                // nothing to do.
                return $order;
            }

            $refund = $charge->refund(['amount' => $refundAmount]);

            $ok = $refund->amount_refunded === $refundAmount;
        } catch (Base $e) {
            $errorMessage = 'Error: ';
            // Try to get the error response
            if ($e->getJsonBody() !== null) {
                $body = $e->getJsonBody();
                $errorMessage .= $body['error']['message'] . "\n";
            } else {
                $errorMessage .= $e->getMessage() . "\n";
            }
        }

        $date = date('d.m.Y, G:i:s');
        $amountFormatted = number_format($amount, 2, ',', '.');

        // Add a new refund comment to the internal comment of the order
        $internalComment = $order->getInternalComment()
            . "\n--------------------------------------------------------------\n"
            . "Stripe $state ($date)\n"
            . "Amount: $amountFormatted {$order->getCurrency()}\n"
            . $errorMessage
            . "--------------------------------------------------------------\n";
        $order->setInternalComment($internalComment);
        if (isset($refund) && $refund->amount_refunded) {
            // Set the date to now
            $order->setClearedDate(null);
        }
        Shopware()->Models()->flush($order);

        if ($ok) {
            $sOrder = Shopware()->Modules()->Order();
            $sOrder->setPaymentStatus($order->getId(), Status::PAYMENT_STATE_RE_CREDITING, false, "$state: $amountFormatted");
        }

        return $order;
    }
}

/**
 * Returns true, if the signature of GenericPaymentMethod#validate appears consistent with Shopware before version
 * 5.0.4-RC1.
 *
 * Since version 5.0.4-RC1, the parameter must be an array (with no type hint).
 * Before, it was an \Enlight_Controller_Request_Request.
 *
 * The commit that changed the signature of #validate is
 * <https://github.com/shopware/shopware/commit/0608b1a7b05e071c93334b29ab6bd588105462d7>.
 */
function needs_legacy_validate_signature()
{
    $parentClass = new \ReflectionClass('ShopwarePlugin\PaymentMethods\Components\GenericPaymentMethod');
    /* @var $parameters \ReflectionParameter[] */
    $parameters = $parentClass->getMethod('validate')->getParameters();
    foreach ($parameters as $parameter) {
        // Newer Shopware versions use an array parameter named paymentData.
        if ($parameter->getName() === 'request') {
            return true;
        }
    }

    return false;
}

if (needs_legacy_validate_signature()) {
    class StripePaymentMethod extends BaseStripePaymentMethod
    {
        public function validate(\Enlight_Controller_Request_Request $request)
        {
            return parent::doValidate($request->getParams());
        }
    }
} else {
    class StripePaymentMethod extends BaseStripePaymentMethod
    {
        public function validate($paymentData)
        {
            return parent::doValidate($paymentData);
        }
    }
}
