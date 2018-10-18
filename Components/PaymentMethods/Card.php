<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use Shopware\Plugins\StripePayment\Util;
use Stripe;

class Card extends AbstractStripePaymentMethod
{
    /**
     * @inheritdoc
     */
    public function createStripeSource($amountInCents, $currencyCode)
    {
        Util::initStripeAPI();

        // Determine the card source
        $stripeSession = Util::getStripeSession();
        if (!$stripeSession->selectedCard) {
            throw new \Exception($this->getSnippet('payment_error/message/no_card_selected'));
        } elseif ($stripeSession->selectedCard['token_id']) {
            // Use the token to create a new Stripe card source
            $source = Stripe\Source::create([
                'type' => 'card',
                'token' => $stripeSession->selectedCard['token_id'],
                'metadata' => $this->getSourceMetadata(),
            ]);

            // Remove the token from the selected card, since it can only be consumed once
            unset($stripeSession->selectedCard['token_id']);

            if ($stripeSession->saveCardForFutureCheckouts) {
                // Add the card to the Stripe customer
                $stripeCustomer = Util::getStripeCustomer();
                if (!$stripeCustomer) {
                    $stripeCustomer = Util::createStripeCustomer();
                }
                $source = $stripeCustomer->sources->create([
                    'source' => $source->id,
                ]);
                unset($stripeSession->saveCardForFutureCheckouts);
            }

            // Overwrite the card's id to allow using it again in case of an error
            $stripeSession->selectedCard['id'] = $source->id;
        } else {
            // Try to find the source corresponding to the selected card
            $source = Stripe\Source::retrieve($stripeSession->selectedCard['id']);
        }
        if (!$source) {
            throw new \Exception($this->getSnippet('payment_error/message/transaction_not_found'));
        }

        // Check the created/retrieved source
        $paymentMethod = $this->get('session')->sOrderVariables->sPayment['name'];
        if ($source->card->three_d_secure === 'required' || ($source->card->three_d_secure !== 'not_supported' && $paymentMethod === 'stripe_payment_card_three_d_secure')) {
            // The card requires the 3D-Secure flow or supports it and the selected payment method requires it,
            // hence create a new 3D-Secure source that is based on the card source
            $returnUrl = $this->assembleShopwareUrl([
                'controller' => 'StripePayment',
                'action' => 'completeRedirectFlow',
            ]);
            try {
                $source = Stripe\Source::create([
                    'type' => 'three_d_secure',
                    'amount' => $amountInCents,
                    'currency' => $currencyCode,
                    'three_d_secure' => [
                        'card' => $source->id,
                    ],
                    'redirect' => [
                        'return_url' => $returnUrl,
                    ],
                    'metadata' => $this->getSourceMetadata(),
                ]);
            } catch (\Exception $e) {
                throw new \Exception($this->getErrorMessage($e), 0, $e);
            }
        }

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function includeStatmentDescriptorInCharge()
    {
        // Card sources can be reused several times and hence should contain a statement descriptor in charge
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
