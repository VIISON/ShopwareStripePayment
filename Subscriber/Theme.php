<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Theme\LessDefinition;
use Doctrine\Common\Collections\ArrayCollection;
use \Shopware_Plugins_Frontend_StripePayment_Bootstrap as Bootstrap;

/**
 * The subscriber providing the theme/template extensions.
 */
class Theme implements SubscriberInterface
{
    /**
     * @var string $path
     */
    private $path;

    /**
     * @var Enlight_Template_Manager $templateManager
     */
    private $templateManager;

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->path = $bootstrap->Path();
        $this->templateManager = $bootstrap->get('template');
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure' => 'onPostDispatchSecure',
            'Theme_Compiler_Collect_Plugin_Javascript' => 'onCollectPluginJavascriptFiles',
            'Theme_Compiler_Collect_Plugin_Less' => 'onCollectPluginLESSFiles',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchSecure(\Enlight_Event_EventArgs $args)
    {
        $this->templateManager->addTemplateDir(
            $this->path . 'Views/',
            'stripePayment'
        );
    }

    /**
     * Adds Stripe's jQuery payment plugin as well as the custom Stripe payment library to the Javascript resources
     * which are minified.
     *
     * @return ArrayCollection
     */
    public function onCollectPluginJavascriptFiles()
    {
        return new ArrayCollection([
            $this->path . 'Views/frontend/_public/src/javascript/stripe_payment_digital_wallet_payments.js',
            $this->path . 'Views/frontend/_public/src/javascript/stripe_payment_card.js',
            $this->path . 'Views/frontend/_public/src/javascript/stripe_payment_sepa.js',
        ]);
    }

    /**
     * Adds this plugin's LESS files to the compile path.
     *
     * @return ArrayCollection
     */
    public function onCollectPluginLESSFiles()
    {
        return new ArrayCollection([
            new LessDefinition(
                [],
                [
                    $this->path . 'Views/frontend/_public/src/less/checkout.less',
                    $this->path . 'Views/frontend/_public/src/less/account.less',
                    $this->path . 'Views/frontend/_public/src/less/sidebar.less',
                ],
                $this->path . 'Views/frontend/'
            )
        ]);
    }
}
