<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use Shopware\Plugins\StripePayment\Util;
use Stripe;

class Bancontact extends AbstractStripePaymentMethod
{
    /**
     * @inheritdoc
     */
    public function createStripeSource($amountInCents, $currencyCode)
    {
        Util::initStripeAPI();
        // Create a new Bancontact source
        $returnUrl = $this->assembleShopwareUrl([
            'controller' => 'StripePayment',
            'action' => 'completeRedirectFlow',
        ]);
        $source = Stripe\Source::create([
            'type' => 'bancontact',
            'amount' => $amountInCents,
            'currency' => $currencyCode,
            'owner' => [
                'name' => Util::getCustomerName(),
            ],
            'bancontact' => [
                'statement_descriptor' => $this->getStatementDescriptor(),
            ],
            'redirect' => [
                'return_url' => $returnUrl,
            ],
            'metadata' => $this->getSourceMetadata(),
        ]);

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function includeStatementDescriptorInCharge()
    {
        // Bancontact payments require the statement descriptor to be part of their source
        return false;
    }
}
