<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Controllers;

use Shopware\Models\Order\Order;
use Shopware\Plugins\StripePayment\Util;

trait StripePaymentTrait
{
    /**
     * @return int
     */
    protected function getAmountInCents()
    {
        return round($this->getAmount() * 100);
    }

    /**
     * Finishes the checkout process by redirecting to the checkout's finish page. By passing the
     * 'paymentUniqueId' (aka 'temporaryID') to 'sUniqueID', we allow an early return of the 'Checkout'
     * controller's 'finishAction()'. The order is created by calling 'saveOrder()' on this controller
     * earlier, so it definitely exists after the redirect. However, 'finishAction()' can only find
     * the order, if we pass the 'sUniqueID' here. If we don't pass the 'paymentUniqueId', there are
     * apparently some shops that fail to display the order summary, although a vanilla Shopware 5 installation works
     * correctly. That is, because the basket is empty after creating the order, the session's sOrderVariables are
     * assigned to the view and NO redirect to the confirm action is performed (see
     * https://github.com/shopware/shopware/blob/6e8b58477c1a9aa873328c258139fa6085238b4b/engine/Shopware/Controllers/Frontend/Checkout.php#L272-L275).
     * Anyway, setting 'sUniqueID' seems to be the safe way to display the order summary.
     *
     * @param Order $order
     */
    protected function finishCheckout(Order $order)
    {
        Util::resetStripeSession();
        $this->redirect([
            'controller' => 'checkout',
            'action' => 'finish',
            'sUniqueID' => $order->getTemporaryId(),
        ]);
    }

    /**
     * Cancles the checkout process by redirecting (back) to the checkout's confirm page. If the optional
     * parameter $errorMessage is set, it is prefixed added to the session so that it will be displayed on the
     * confirm page after the redirect.
     *
     * @param string|null $errorMessage
     */
    protected function cancelCheckout($errorMessage = null)
    {
        if ($errorMessage) {
            $prefix = $this->get('snippets')->getNamespace('frontend/plugins/payment/stripe_payment/base')->get('payment_error/message/charge_failed');
            Util::getStripeSession()->paymentError = $prefix . ' ' . $errorMessage;
        }
        $this->redirect([
            'controller' => 'checkout',
            'action' => 'index',
        ]);
    }
}
