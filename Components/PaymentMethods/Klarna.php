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
        $basket = $this->get('session')->sOrderVariables->sBasket;
        $items = array_map(function ($item) use ($currencyCode) {
            return [
                'type' => 'sku',
                'description' => $item['articlename'],
                'quantity' => $item['quantity'],
                'currency' => $currencyCode,
                'amount' => round($item['amountNumeric'] * 100),
            ];
        }, $basket['content']);

        if (!$this->get('session')->sOrderVariables->sUserData['additional']['show_net']) {
            $items[] = [
                'type' => 'tax',
                'description' => 'Taxes',
                'currency' => $currencyCode,
                'amount' => round($basket['sAmountTax'] * 100),
            ];
        }
        $items[] = [
            'type' => 'shipping',
            'description' => 'Shipping',
            'currency' => $currencyCode,
            'amount' => round($basket['sShippingcosts'] * 100),
        ];

        $userData = $this->get('session')->sOrderVariables->sUserData;
        $customer = Util::getCustomer();

        $locale = str_replace('_', '-', $this->get('shop')->getLocale()->getLocale());

        // Create a new Klarna source
        return Stripe\Source::create([
            'type' => 'klarna',
            'amount' => $amountInCents,
            'currency' => $currencyCode,
            'flow' => 'redirect',
            'owner' => [
                'name' => Util::getCustomerName(),
                'email' => $customer->getEmail(),
                'address' => [
                    'line1' => $userData['billingaddress']['street'],
                    'line2' => '',
                    'city' => $userData['billingaddress']['city'],
                    'state' => $userData['additional']['state']['name'],
                    'postal_code' => $userData['billingaddress']['zipcode'],
                    'country' => $userData['additional']['country']['countryiso'],
                ],
            ],
            'klarna' => [
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'product' => 'payment',
                'purchase_country' => $this->get('session')->sOrderVariables->sCountry['countryiso'],
                'shipping_first_name' => $customer->getFirstname(),
                'shipping_last_name' => $customer->getLastname(),
                'locale' => $locale,
            ],
            'source_order' => [
                'items' => array_values($items),
                'shipping' => [
                    'address' => [
                        'line1' => $userData['shippingaddress']['street'],
                        'line2' => '',
                        'city' => $userData['shippingaddress']['city'],
                        'state' => $userData['additional']['state']['name'],
                        'postal_code' => $userData['shippingaddress']['zipcode'],
                        'country' => $userData['additional']['country']['countryiso'],
                    ],
                ],
            ],
            'statement_descriptor' => $this->getStatementDescriptor(),
            'redirect' => [
                'return_url' => $this->assembleShopwareUrl([
                    'controller' => 'StripePayment',
                    'action' => 'completeRedirectFlow',
                ]),
            ],
            'metadata' => $this->getSourceMetadata(),
        ]);
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
