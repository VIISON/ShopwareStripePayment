<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use Shopware\Plugins\StripePayment\Util;

class Frontend implements SubscriberInterface
{
    /**
     * The path Apple Pay expects the domain association file to be found at.
     */
    const APPLE_PAY_DOMAIN_ASSOCIATION_FILE_PATH = '/.well-known/apple-developer-merchantid-domain-association';

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Front_RouteStartup' => [
                'onRouteStartup',
                -10000,
            ],
            'Enlight_Controller_Front_RouteShutdown' => 'onRouteShutdown',
        ];
    }

    /**
     * Checks whether the 'stripeWebhook' action is requested as wellas if the webhook event
     * is a 'source.chargeable'. If so, the showpare session that is associated with the Stripe
     * event source is loaded to recreate the context the source was created in.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onRouteStartup(\Enlight_Event_EventArgs $args)
    {
        // Check for an incoming Stripe webhook
        $request = $args->getRequest();
        if ($request->getPathInfo() !== '/StripePayment/stripeWebhook') {
            return;
        }

        // Verify the webhook event
        try {
            $event = Util::verifyWebhookRequest($request);
        } catch (\Exception $e) {
            // Just swallow the exception
            return;
        }

        // Check for a 'source.chargeable' event
        if ($event->type !== 'source.chargeable') {
            return;
        }

        // Try to find the session in whose context the source was created and load it, if available
        $sessionId = $event->data->object->metadata->shopware_session_id;
        if (!$sessionId) {
            return;
        }
        $session = Shopware()->Container()->get('db')->fetchRow(
            'SELECT *
            FROM s_core_sessions
            WHERE id = :sessionId',
            [
                'sessionId' => $sessionId,
            ]
        );
        if ($session) {
            session_id($sessionId);
        }
    }

    /**
     * Checks whether the the Apple Pay domain association file is request and, if it is,
     * reoroutes the request to '/StripePaymentApplePay/domainAssociationFile'.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onRouteShutdown(\Enlight_Event_EventArgs $args)
    {
        $request = $args->getRequest();
        if ($request->getPathInfo() === self::APPLE_PAY_DOMAIN_ASSOCIATION_FILE_PATH) {
            $request->setControllerName('StripePaymentApplePay');
            $request->setActionName('domainAssociationFile');
        }
    }
}
