<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber;

use Enlight\Event\SubscriberInterface;

/**
 * The subscriber for adding the custom StripePaymentMethod path.
 */
class Payment implements SubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_InitiatePaymentClass_AddClass' => 'onAddPaymentClass',
        ];
    }

    /**
     * Adds the path to the Stripe payment method class to the return value.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onAddPaymentClass(\Enlight_Event_EventArgs $args)
    {
        $dirs = $args->getReturn();
        $dirs['StripePaymentApplePay'] = 'Shopware\\Plugins\\StripePayment\\Components\\PaymentMethods\\ApplePay';
        $dirs['StripePaymentBancontact'] = 'Shopware\\Plugins\\StripePayment\\Components\\PaymentMethods\\Bancontact';
        $dirs['StripePaymentCard'] = 'Shopware\\Plugins\\StripePayment\\Components\\PaymentMethods\\Card';
        $dirs['StripePaymentIdeal'] = 'Shopware\\Plugins\\StripePayment\\Components\\PaymentMethods\\Ideal';
        $dirs['StripePaymentGiropay'] = 'Shopware\\Plugins\\StripePayment\\Components\\PaymentMethods\\Giropay';
        $dirs['StripePaymentSepa'] = 'Shopware\\Plugins\\StripePayment\\Components\\PaymentMethods\\Sepa';
        $dirs['StripePaymentSofort'] = 'Shopware\\Plugins\\StripePayment\\Components\\PaymentMethods\\Sofort';
        $dirs['StripePaymentKlarna'] = 'Shopware\\Plugins\\StripePayment\\Components\\PaymentMethods\\Klarna';
        $args->setReturn($dirs);
    }
}
