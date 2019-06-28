<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use \Shopware_Plugins_Frontend_StripePayment_Bootstrap as Bootstrap;

/**
 * A subscriber returning the paths of custom controllers.
 */
class Controllers implements SubscriberInterface
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
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_StripePayment' => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_StripePayment' => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_StripePaymentAccount' => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_StripePaymentApplePay' => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_StripePaymentCard' => 'onGetControllerPath',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_StripePaymentIntent' => 'onGetControllerPath',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPath(\Enlight_Event_EventArgs $args)
    {
        $moduleName = $args->getRequest()->getModuleName();
        $moduleName = ucfirst($moduleName);
        $controllerName = $args->getRequest()->getControllerName();
        $controllerName = str_replace('_', '', ucwords($controllerName, '_'));

        return $this->path . 'Controllers/' . $moduleName . '/' . $controllerName . '.php';
    }
}
