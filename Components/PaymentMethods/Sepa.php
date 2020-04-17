<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use Shopware\Plugins\StripePayment\Util;
use Stripe;

class Sepa extends AbstractStripePaymentMethod
{
    /**
     * @inheritdoc
     */
    public function createStripeSource($amountInCents, $currencyCode)
    {
        Util::initStripeAPI();

        // Try to find the SEPA source saved in the session and validate it using the client secret
        $stripeSession = Util::getStripeSession();
        if (!$stripeSession->sepaSource) {
            throw new \Exception($this->getSnippet('payment_error/message/transaction_not_found'));
        }
        $source = Stripe\Source::retrieve($stripeSession->sepaSource['id']);
        if (!$source) {
            unset($stripeSession->sepaSource);
            throw new \Exception($this->getSnippet('payment_error/message/transaction_not_found'));
        } elseif ($source->client_secret !== $stripeSession->sepaSource['client_secret']) {
            unset($stripeSession->sepaSource);
            throw new \Exception($this->getSnippet('payment_error/message/processing_error'));
        }

        // Update the source's metadata
        $source->metadata = $this->getSourceMetadata();
        $source->save();

        return $source;
    }

    /**
     * @inheritdoc
     */
    public function includeStatementDescriptorInCharge()
    {
        // SEPA sources can be reused several times and hence should contain a statement descriptor in the charge
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getSnippet($name)
    {
        return ($this->get('snippets')->getNamespace('frontend/plugins/payment/stripe_payment/sepa')->get($name)) ?: parent::getSnippet($name);
    }

    /**
     * @inheritdoc
     */
    public function validate($paymentData)
    {
        // Check the payment data for a SEPA source
        if (empty($paymentData['sepaSource'])) {
            return [
                'STRIPE_SEPA_VALIDATION_FAILED'
            ];
        }

        return [];
    }
}
