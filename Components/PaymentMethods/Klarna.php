<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use Shopware\Plugins\StripePayment\Util;
use Stripe;

class Klarna extends AbstractStripePaymentMethod
{
    /**
     * @inheritdoc
     */
    public function createStripeSource($amountInCents, $currencyCode)
    {
        Util::initStripeAPI();
        // Create a new Klarna source
        $returnUrl = $this->assembleShopwareUrl([
            'controller' => 'StripePayment',
            'action' => 'completeRedirectFlow',
        ]);
        $basket = $this->get('session')->sOrderVariables->sBasket;
        $tax = [
            'type' => 'tax',
            'description' => 'Taxes',
            'currency' => $currencyCode,
            'amount' => round($basket['sAmountTax'] * 100),
        ];
        $shipping = [
            'type' => 'shipping',
            'description' => 'Shipping',
            'currency' => $currencyCode,
            'amount' => round($basket['sShippingcostsNet'] * 100),
        ];

        $items = array_map(function ($item) use ($currencyCode) {
            return [
                'type' => 'sku',
                'description' => $item['articlename'],
                'quantity' => $item['quantity'],
                'currency' => $currencyCode,
                'amount' => round($item['amountnetNumeric'] * 100),
            ];
        }, $basket['content']);

        $items[] = $tax;
        $items[] = $shipping;

        $source = Stripe\Source::create([
            'type' => 'klarna',
            'flow' => 'redirect',
            'amount' => $amountInCents,
            'currency' => $currencyCode,
            'klarna' => [
                'product' => 'payment',
                'purchase_country' => $this->get('session')->sOrderVariables->sCountry['countryiso'],
            ],
            'source_order' => [
                'items' => array_values($items),
            ],
            'statement_descriptor' => $this->getStatementDescriptor(),
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
    public function includeStatmentDescriptorInCharge()
    {
        // Klarna payments require the statement descriptor to be part of their source
        return false;
    }
}
