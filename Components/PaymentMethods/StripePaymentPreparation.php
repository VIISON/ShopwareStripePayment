<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Components\PaymentMethods;

use Shopware\Plugins\StripePayment\Util;

trait StripePaymentPreparation
{
    /**
     * Should return true, if the payment method supports charges of their sources/payment intents to contain a
     * statement descriptor.
     *
     * @return boolean
     */
    abstract public function includeStatementDescriptorInCharge();

    /**
     * @inheritdoc
     */
    public function getCurrentPaymentDataAsArray($userId)
    {
        return (array)Util::getStripeSession();
    }

    /**
     * Returns the localized error message for the given exception.
     *
     * @param \Exception $exception
     * @return string
     */
    public function getErrorMessage(\Exception $exception)
    {
        $message = 'Payment failed: ' . $exception->getMessage();
        if ($exception->stripeCode) {
            $message = ($this->getSnippet('payment_error/message/' . $exception->stripeCode)) ?: $message;
        }

        return $message;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getSnippet($name)
    {
        return $this->get('snippets')->getNamespace('frontend/plugins/payment/stripe_payment/base')->get($name);
    }

    /**
     * The format of the statement descriptor depends on the plugin config. If a custom statement descriptor
     * is set, it is used. Otherwise it is constructed from either the shop name or shop URL. Please note that
     * in any case, at most 35 characters of the statement descriptor are kept to meet the constraints of all
     * payment providers.
     *
     * @return string
     */
    public function getStatementDescriptor()
    {
        // Determine the suffix of the long descriptor
        $descriptor = $this->get('plugins')->get('Frontend')->get('StripePayment')->Config()->get('statementDescriptorSuffix');
        if (!$descriptor) {
            // Construct the suffix using the shop name
            $descriptor = $this->get('shop')->getName();
        }
        if (!$descriptor) {
            // Construct the suffix using the URL
            $shopUrl = parse_url(($this->get('front')->Request()->getHttpHost() . $this->get('front')->Request()->getBaseUrl()), PHP_URL_HOST);
            if ($shopUrl) {
                $descriptor = $shopUrl;
            }
        }

        // Strip all characters that are not allowed in statement descriptors
        $descriptor = preg_replace('/[\\<\\>\\/\\(\\)\\{\\}\'"]/', '', $descriptor);

        // Keep at most 35 characters
        $descriptor = mb_substr($descriptor, 0, 35);

        return $descriptor;
    }

    /**
     * @inheritdoc
     *
     * Validates the given payment data. If the data is invalid, an array containing error messages or codes
     * must be returned. By default this method returns an empty array, which indicates that the payment
     * is valid.
     */
    public function validate($paymentData)
    {
        return [];
    }

    /**
     * Assembles and returns a shopware URL that can be used e.g. for a redirect return.
     *
     * @param array $components
     * @return string
     */
    protected function assembleShopwareUrl(array $components)
    {
        $front = $this->get('front');
        $url = $front->Router()->assemble($components);
        if (!preg_match('#^https?://#', $url)) {
            if (mb_strpos($url, '/') !== 0) {
                $url = $front->Request()->getBaseUrl() . '/' . $url;
            }
            $uri = $front->Request()->getScheme() . '://' . $front->Request()->getHttpHost();
            $url = $uri . $url;
        }

        return $url;
    }

    /**
     * By default returns an array containing the platform name of this plugin as well as
     * the ID of the active shopware session.
     *
     * @return array
     */
    protected function getSourceMetadata()
    {
        return [
            'platform_name' => Util::STRIPE_PLATFORM_NAME,
            'shopware_session_id' => $this->get('SessionID'),
        ];
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function get($key)
    {
        return Shopware()->Container()->get($key);
    }
}
