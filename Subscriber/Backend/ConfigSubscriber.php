<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Config\Element;
use Shopware\Models\Config\Form;
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
     * Handle the creation and editing of new coupon document types
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onAfterSaveFormAction(\Enlight_Hook_HookArgs $args)
    {
        $subject = $args->getSubject();
        $data = $subject->Request()->getParam('elements');

        if ($data[0]['name'] !== 'stripeSecretKey' && $data[0]['value'] === '') {
            return;
        }

        $entityManager = Shopware()->Container()->get('models');

        // Check for a saved default grid label template
        $configValue = $entityManager->getRepository('Shopware\\Models\\Config\\Element')->findOneBy([
            'name' => 'stripeAccountCountryIso',
        ]);

        if (!$configValue) {
            // Create new config element using a fake form, since all elements must belong to a form
            $configValue = new Element('string', 'stripeAccountCountryIso', []);
            /** @var Form $fakeForm */
            $fakeForm = $entityManager->getPartialReference('Shopware\\Models\\Config\\Form', 0);
            $configValue->setForm($fakeForm);
            $entityManager->persist($configValue);
        }

        // Save the value
        $configValue->setValue(Util::getAccount()->country);
        $entityManager->flush($configValue);
    }
}
