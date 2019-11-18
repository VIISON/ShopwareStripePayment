<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use Shopware\Plugins\StripePayment\Util;
use Stripe;

class DigitalWalletPayments extends AbstractStripePaymentIntentPaymentMethod
{
    /**
     * @inheritdoc
     */
    public function createStripePaymentIntent($amountInCents, $currencyCode)
    {
        Util::initStripeAPI();

        // Determine the card source
        $stripeSession = Util::getStripeSession();
        if (!$stripeSession->paymentMethodId) {
            throw new \Exception($this->getSnippet('payment_error/message/transaction_not_found'));
        }

        $customer = Util::getCustomer();
        $stripeCustomer = null;
        if ($customer) {
            // Always create a new stripe customer
            $stripeCustomer = Stripe\Customer::create([
                'name' => Util::getCustomerName(),
                'description' => Util::getCustomerName(),
                'email' => $customer->getEmail(),
                'metadata' => [
                    'platform_name' => Util::STRIPE_PLATFORM_NAME,
                ],
            ]);
        }
        $user = $this->get('session')->sOrderVariables['sUserData'];
        $userEmail = $user['additional']['user']['email'];
        $customerNumber = $user['additional']['user']['customernumber'];

        // Use the token to create a new Stripe card payment intent
        $returnUrl = $this->assembleShopwareUrl([
            'controller' => 'StripePaymentIntent',
            'action' => 'completeRedirectFlow',
        ]);
        $paymentIntentConfig = [
            'amount' => $amountInCents,
            'currency' => $currencyCode,
            'payment_method' => $stripeSession->paymentMethodId,
            'confirmation_method' => 'automatic',
            'confirm' => true,
            'return_url' => $returnUrl,
            'metadata' => $this->getSourceMetadata(),
            'description' => sprintf('%s / Customer %s', $userEmail, $customerNumber),
        ];
        if ($stripeCustomer) {
            $paymentIntentConfig['customer'] = $stripeCustomer->id;
        }
        if ($this->includeStatmentDescriptorInCharge()) {
            $paymentIntentConfig['statement_descriptor'] = mb_substr($this->getStatementDescriptor(), 0, 22);
        }

        // Enable receipt emails, if configured
        $pluginConfig = $this->get('plugins')->get('Frontend')->get('StripePayment')->Config();
        if ($pluginConfig->get('sendStripeChargeEmails')) {
            $paymentIntentConfig['receipt_email'] = $userEmail;
        }

        $paymentIntent = Stripe\PaymentIntent::create($paymentIntentConfig);
        if (!$paymentIntent) {
            throw new \Exception($this->getSnippet('payment_error/message/transaction_not_found'));
        }

        return $paymentIntent;
    }

    /**
     * @inheritdoc
     */
    public function includeStatmentDescriptorInCharge()
    {
        // DigitalWalletPayments payment intents should contain a statement descriptor in the charge
        return true;
    }
}
