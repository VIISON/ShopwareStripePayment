<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use \Shopware_Plugins_Frontend_StripePayment_Bootstrap as Bootstrap;

/**
 * The subscriber for frontend controllers.
 */
class Account implements SubscriberInterface
{
    /**
     * @var string $path
     */
    private $path;

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->path = $bootstrap->Path();
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Account' => 'onPostDispatchSecure',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_StripePaymentAccount' => 'onPostDispatchSecure',
            'Enlight_Controller_Action_PostDispatchSecure_Widgets_Checkout' => 'onPostDispatchSecure',
        ];
    }

    /**
     * Adds views of this plugin to the account template.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchSecure(\Enlight_Event_EventArgs $args)
    {
        // Only show the card management entry in the account menu, if saving credit cards is enabled
        $pluginConfig = Shopware()->Container()->get('plugins')->get('Frontend')->get('StripePayment')->Config();
        $args->getSubject()->View()->stripeCardManagementEnabled = $pluginConfig->get('allowSavingCreditCard', true);
    }
}
