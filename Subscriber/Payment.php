<?php
namespace Shopware\Plugins\StripePayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Models\Order\Order;
use Shopware\Plugins\StripePayment\Components\StripePaymentMethod;

/**
 * The subscriber for adding the custom StripePaymentMethod path.
 *
 * @copyright Copyright (c) 2015, VIISON GmbH
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
            'StripePayment_Capture_Order'                          => 'onCaptureOrder',
        ];
    }

    /**
     * Adds the path to the Stripe payment method class to the return value,
     * if a Shopware 5 theme is used in the active shop.
     *
     * @param args The arguments passed by the method triggering the event.
     */
    public function onAddPaymentClass(\Enlight_Event_EventArgs $args)
    {
        if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
            $dirs = $args->getReturn();
            $dirs['StripePaymentMethod'] = 'Shopware\Plugins\StripePayment\Components\StripePaymentMethod';
            $args->setReturn($dirs);
        }
    }

    public function onCaptureOrder(\Enlight_Event_EventArgs $args)
    {
        /** @var Order $order */
        $order = $args->getReturn();
        $stripePaymentMethod = new StripePaymentMethod();
        $result = $stripePaymentMethod->captureOrder($order);
        $args->setReturn($result);
    }
}
