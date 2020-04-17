<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Subscriber\Frontend;

use Enlight\Event\SubscriberInterface;
use Shopware\Plugins\StripePayment\Util;
use \Shopware_Plugins_Frontend_StripePayment_Bootstrap as Bootstrap;

/**
 * The subscriber for frontend controllers.
 */
class Checkout implements SubscriberInterface
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
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchSecure',
            // Hook on both the 'saveShippingPayment' and 'payment' actions, because credit card and SEPA are processed
            // in the former, while Apple Pay is processed in the latter
            'Shopware_Controllers_Frontend_Checkout::paymentAction::after' => 'onAfterPaymentAction',
            'Shopware_Controllers_Frontend_Checkout::saveShippingPaymentAction::after' => 'onAfterPaymentAction',
        ];
    }

    /**
     * Handles different tasks during the checkout process. First it adds the custom templates,
     * which are required for the stripe payment form. If the requested action is the 'confirm' action,
     * the configured stripe public key is passed to the view, so that it can be rendered accordingly.
     * Additionally it checks the session for possible stripe errors and passes them to the view,
     * by setting the variable and appending the template.
     * Furthermore this method checks for a stripe transaction token in the request parameters,
     * and, if present, adds it to the current session. Finally the 'sRegisterFinished' flag is
     * set to 'false', to make the detailed list of payment methods visible in the 'confirm' view.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchSecure(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $stripeSession = Util::getStripeSession();

        // Unmark the session as processing the payment to prevent incoming source webhooks from
        // trying to create charges
        unset($stripeSession->processingSourceId);

        // Prepare the view
        $actionName = $args->getSubject()->Request()->getActionName();
        if (in_array($actionName, ['confirm', 'shippingPayment', 'saveShippingPayment'])) {
            $stripeViewParams = [];
            // Set the stripe public key and some plugin configuration
            $stripeViewParams['publicKey'] = Util::stripePublicKey();
            $stripeViewParams['allowSavingCreditCard'] = Shopware()->Container()->get('plugins')->get('Frontend')->get('StripePayment')->Config()->get('allowSavingCreditCard', true);
            $stripeViewParams['showPaymentProviderLogos'] = Shopware()->Container()->get('plugins')->get('Frontend')->get('StripePayment')->Config()->get('showPaymentProviderLogos', true);

            // Check for an error
            if ($stripeSession->paymentError) {
                $stripeViewParams['error'] = $stripeSession->paymentError;
                unset($stripeSession->paymentError);
            }

            if (!$stripeSession->selectedCard) {
                // Load the default card and safe it in the session
                try {
                    $stripeSession->selectedCard = Util::getDefaultStripeCard();
                } catch (\Exception $e) {
                    unset($stripeSession->selectedCard);
                }
            }

            $session = Shopware()->Container()->get('session');
            if ($actionName === 'confirm' && $session->sOrderVariables->sPayment['class'] === 'StripePaymentDigitalWallets') {
                // Add the payment method's statement descriptor to the view
                $modules = Shopware()->Container()->get('modules');
                $paymentMethod = $modules->Admin()->sInitiatePaymentClass($session->sOrderVariables->sPayment);
                $stripeViewParams['digitalWalletsStatementDescriptor'] = $paymentMethod->getStatementDescriptor();
            }

            // Add name of SEPA creditor (company or shop name as fallback)
            $stripeViewParams['sepaCreditor'] = (Shopware()->Container()->get('config')->get('company')) ?: Shopware()->Container()->get('shop')->getName();

            // Add the countries configured for SEPA payments to the view
            $stripeViewParams['sepaCountryList'] = Shopware()->Container()->get('modules')->Admin()->sGetCountryList();
            $sepaCountyIds = Shopware()->Container()->get('db')->fetchCol(
                'SELECT pc.countryID
                FROM s_core_paymentmeans_countries pc
                LEFT JOIN s_core_paymentmeans c
                    ON c.id = pc.paymentID
                WHERE c.class = \'StripePaymentSepa\''
            );
            if (count($sepaCountyIds)) {
                $stripeViewParams['sepaCountryList'] = array_filter($stripeViewParams['sepaCountryList'], function ($country) use ($sepaCountyIds) {
                    return in_array($country['id'], $sepaCountyIds);
                });
            }

            // Add the shop's currency and locale
            $shop = Shopware()->Container()->get('shop');
            $stripeViewParams['currency'] = $shop->getCurrency()->getCurrency();
            $stripeViewParams['currency'] = mb_strtolower($stripeViewParams['currency']);
            $locale = $shop->getLocale()->getLocale();
            $locale = explode('_', $locale);
            $stripeViewParams['locale'] = $locale[0];

            // Update view parameters
            try {
                $stripeViewParams['availableCards'] = Util::getAllStripeCards();
            } catch (\Exception $e) {
                $stripeViewParams['availableCards'] = [];
            }
            if ($stripeSession->selectedCard) {
                // Write the card info to the template both JSON encoded and in a form usable by smarty
                $stripeViewParams['rawSelectedCard'] = json_encode($stripeSession->selectedCard);
                $stripeViewParams['selectedCard'] = $stripeSession->selectedCard;

                // Make sure the selected card is part of the list of available cards
                foreach ($stripeViewParams['availableCards'] as $card) {
                    if ($card['id'] === $stripeSession->selectedCard['id']) {
                        $cardExists = true;
                        break;
                    }
                }
                if (!$cardExists) {
                    $stripeViewParams['availableCards'][] = $stripeSession->selectedCard;
                }
            }
            $stripeViewParams['rawAvailableCards'] = json_encode($stripeViewParams['availableCards']);
            if ($stripeSession->sepaSource) {
                // Write the source info to the template both JSON encoded and in a form usable by smarty
                $stripeViewParams['rawSepaSource'] = json_encode($stripeSession->sepaSource);
                $stripeViewParams['sepaSource'] = $stripeSession->sepaSource;
            }
            $view->stripePayment = $stripeViewParams;
        }
        if ($actionName === 'finish' && $view->sPayment['class'] === 'StripePaymentSepa' && isset($view->sPayment['data']['sepaSource']['sepa_debit']['mandate_url'])) {
            // Add the SEPA mandate URL to the view
            $view->stripePaymentSepatMandateUrl = $view->sPayment['data']['sepaSource']['sepa_debit']['mandate_url'];
        }

        $customer = Util::getCustomer();
        if ($customer) {
            // Add the account mode to the view
            $view->customerAccountMode = $customer->getAccountMode();
        }
    }

    /**
     * Checks the request for stripe parameters and saves them in the session for later use.
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onAfterPaymentAction(\Enlight_Hook_HookArgs $args)
    {
        $request = $args->getSubject()->Request();
        $stripeSession = Util::getStripeSession();
        if ($request->getParam('stripeSelectedCard')) {
            $stripeSession->selectedCard = json_decode($request->getParam('stripeSelectedCard'), true);
        }
        if ($request->getParam('stripeSaveCard')) {
            $stripeSession->saveCardForFutureCheckouts = $request->getParam('stripeSaveCard') === 'on';
        }
        if ($request->getParam('stripeSepaSource')) {
            $stripeSession->sepaSource = json_decode($request->getParam('stripeSepaSource'), true);
        }
        if ($request->getParam('stripePaymentMethodId')) {
            $stripeSession->paymentMethodId = $request->getParam('stripePaymentMethodId');
        }

        // Reset parts of the stripe session, if no stripe payment method is selected
        if ($request->getParam('payment')) {
            $selectedPaymentMethod = Shopware()->Container()->get('models')->find('Shopware\\Models\\Payment\\Payment', $request->getParam('payment'));
            if ($selectedPaymentMethod && $selectedPaymentMethod->getAction() !== 'StripePaymentIntent') {
                unset($stripeSession->paymentMethodId);
            }
        }
    }
}
