<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Shopware\Plugins\StripePayment\Util;

class ConfigSubscriber implements SubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Config::saveFormAction::after' => 'onAfterSaveFormAction',
        ];
    }

    /**
     * Handle the retrieving of the stripe account country and saving it to a hidden config value
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onAfterSaveFormAction(\Enlight_Hook_HookArgs $args)
    {
        $subject = $args->getSubject();
        $isStripePaymentConfig = $subject->Request()->getParam('name') === 'StripePayment';
        if (!$isStripePaymentConfig) {
            return;
        }

        $secretKey = Util::stripeSecretKey();
        if ($secretKey === '') {
            return;
        }

        Util::setConfigValue('stripeAccountCountryIso', 'string', Util::getStripeAccount()->country);
    }
}
