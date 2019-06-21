<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use ShopwarePlugin\PaymentMethods\Components\GenericPaymentMethod;
use Stripe;

abstract class AbstractStripePaymentIntentPaymentMethod extends GenericPaymentMethod
{
    use StripePaymentMethodTrait;

    /**
     * Returns the paymentIntent that shall be used to create a Stripe charge during checkout.
     *
     * @param int $amountInCents
     * @param string $currencyCode
     * @return Stripe\PaymentIntent
     * @throws \Exception
     */
    abstract public function createStripePaymentIntent($amountInCents, $currencyCode);
}
