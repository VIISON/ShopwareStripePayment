<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber\Backend;

use Enlight\Event\SubscriberInterface;
use Shopware\Plugins\StripePayment\Util;
use \Shopware_Plugins_Frontend_StripePayment_Bootstrap as Bootstrap;

/**
 * The subscriber for backend controllers.
 */
class Customer implements SubscriberInterface
{
    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Customer::performOrderAction::after' => 'onAfterCustomerPerformOrderAction',
        ];
    }

    /**
     * Hooks the performOrderAction of the customer backend controller in
     * order to enable/disable MOTO transactions.
     *
     * @return void
     */
    public function onAfterCustomerPerformOrderAction()
    {
        $stripeSession = Util::getStripeSession();
        $enableMotoTransactions = Shopware()->Container()->get('plugins')->get('Frontend')->get('StripePayment')->Config()->get('enableMotoTransactions');
        $stripeSession->isMotoTransaction = $enableMotoTransactions;
    }
}
