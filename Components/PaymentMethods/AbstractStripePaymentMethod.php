<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use ShopwarePlugin\PaymentMethods\Components\GenericPaymentMethod;

abstract class AbstractStripePaymentMethod extends GenericPaymentMethod
{
    use StripePaymentPreparation;

    /**
     * Returns the source that shall be used to create a Stripe charge during checkout.
     *
     * @param int $amountInCents
     * @param string $currencyCode
     * @return Stripe\Source
     * @throws \Exception
     */
    abstract public function createStripeSource($amountInCents, $currencyCode);
}
