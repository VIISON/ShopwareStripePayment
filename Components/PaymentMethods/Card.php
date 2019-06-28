<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use Shopware\Plugins\StripePayment\Util;
use Stripe;

class Card extends AbstractStripePaymentIntentPaymentMethod
{
    /**
     * @inheritdoc
     */
    public function createStripePaymentIntent($amountInCents, $currencyCode)
    {
        Util::initStripeAPI();

        // Determine the card
        $stripeSession = Util::getStripeSession();
        if (!$stripeSession->selectedCard || !isset($stripeSession->selectedCard['id'])) {
            throw new \Exception($this->getSnippet('payment_error/message/no_card_selected'));
        }

        $stripeCustomer = Util::getStripeCustomer();
        if (!$stripeCustomer) {
            $stripeCustomer = Util::createStripeCustomer();
        }
        $user = Shopware()->Session()->sOrderVariables['sUserData'];
        $userEmail = $user['additional']['user']['email'];
        $customerNumber = $user['additional']['user']['customernumber'];

        // Use the token to create a new Stripe card payment intend
        $paymentIntentConfig = [
            'amount' => $amountInCents,
            'currency' => $currencyCode,
            'payment_method' => $stripeSession->selectedCard['id'],
            'confirmation_method' => 'automatic',
            'metadata' => $this->getSourceMetadata(),
            'customer' => $stripeCustomer->id,
            'description' => sprintf('%s / Customer %s', $userEmail, $customerNumber),
        ];
        if ($this->includeStatmentDescriptorInCharge()) {
            $paymentIntentConfig['statement_descriptor'] = mb_substr($this->getStatementDescriptor(), 0, 22);
        }

        // Enable receipt emails, if configured
        $sendReceiptEmails = $this->get('plugins')->get('Frontend')->get('StripePayment')->Config()->get('sendStripeChargeEmails');
        if ($sendReceiptEmails) {
            $paymentIntentConfig['receipt_email'] = $userEmail;
        }
        if ($stripeSession->saveCardForFutureCheckouts) {
            // Add the card to the Stripe customer
            $paymentIntentConfig['save_payment_method'] = $stripeSession->saveCardForFutureCheckouts;
            unset($stripeSession->saveCardForFutureCheckouts);
        }

        $paymentIntent = Stripe\PaymentIntent::create($paymentIntentConfig);
        if (!$paymentIntent) {
            throw new \Exception($this->getSnippet('payment_error/message/transaction_not_found'));
        }

        $returnUrl = $this->assembleShopwareUrl([
            'controller' => 'StripePaymentIntent',
            'action' => 'completeRedirectFlow',
        ]);
        $paymentIntent->confirm([
            'return_url' => $returnUrl,
        ]);

        return $paymentIntent;
    }

    /**
     * @inheritdoc
     */
    public function includeStatmentDescriptorInCharge()
    {
        // Card payment methods can be reused several times and hence should contain a statement descriptor in charge
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getSnippet($name)
    {
        return ($this->get('snippets')->getNamespace('frontend/plugins/payment/stripe_payment/card')->get($name)) ?: parent::getSnippet($name);
    }

    /**
     * @inheritdoc
     */
    public function validate($paymentData)
    {
        // Check the payment data for a selected card
        if (empty($paymentData['selectedCard'])) {
            return [
                'STRIPE_CARD_VALIDATION_FAILED'
            ];
        }

        return [];
    }
}
