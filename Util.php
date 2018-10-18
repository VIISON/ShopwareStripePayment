<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment;

use Shopware\Models\Attribute\Customer as CustomerAttribute;
use Shopware\Models\Customer\Customer;
use Stripe;

/**
 * Utility functions used across this plugin.
 */
class Util
{
    /**
     * The platform name used as meta data e.g. when creating a new charge.
     */
    const STRIPE_PLATFORM_NAME = 'UMXJ4nBknsWR3LN_shopware_v50';

    /**
     * This field is used as a cache for the Stripe customer object of the
     * currently logged in user.
     *
     * @var Stripe\Customer
     */
    private static $stripeCustomer;

    /**
     * Sets the Stripe secret key.
     */
    public static function initStripeAPI()
    {
        // Set the Stripe API key
        $stripeSecretKey = self::stripeSecretKey();
        Stripe\Stripe::setApiKey($stripeSecretKey);

        // Set API version manually to make all plugin versions working, no matter which
        // version is selected in the Stripe app settings
        Stripe\Stripe::setApiVersion('2016-07-06');

        // Set some plugin info that will be added to every Stripe request
        $defaultShop = Shopware()->Models()->getRepository('Shopware\\Models\\Shop\\Shop')->getActiveDefault();
        Stripe\Stripe::setAppInfo(
            self::STRIPE_PLATFORM_NAME,
            Shopware()->Plugins()->Frontend()->StripePayment()->getVersion(),
            ($defaultShop) ? $defaultShop->getHost() : null
        );
    }

    /**
     * @return string The Stripe public key set in the plugin configuration for the currently active shop.
     */
    public static function stripePublicKey()
    {
        return Shopware()->Plugins()->Frontend()->StripePayment()->Config()->get('stripePublicKey');
    }

    /**
     * @return string The Stripe secret key set in the plugin configuration for the currently active shop.
     */
    public static function stripeSecretKey()
    {
        return Shopware()->Plugins()->Frontend()->StripePayment()->Config()->get('stripeSecretKey');
    }

    /**
     * Uses the Stripe customer id of the active user to retrieve the customer from Stripe
     * and returns the customer's credit cards.
     *
     * @return array An array containing information about all loaded credit cards.
     * @throws \Exception if loading the Stripe customer fails.
     */
    public static function getAllStripeCards()
    {
        // Get the Stripe customer
        $customer = self::getStripeCustomer();
        if ($customer === null || isset($customer->deleted)) {
            return [];
        }

        // Get information about all card sources
        $cardSources = array_filter($customer->sources->data, function ($source) {
            return $source->type === 'card';
        });
        $cards = array_map(function ($source) {
            return [
                'id' => $source->id,
                'name' => $source->owner->name,
                'brand' => $source->card->brand,
                'last4' => $source->card->last4,
                'exp_month' => $source->card->exp_month,
                'exp_year' => $source->card->exp_year,
            ];
        }, $cardSources);

        // Sort the cards by id (which correspond to the date, the card was created/added)
        usort($cards, function ($cardA, $cardB) {
            return strcmp($cardA['id'], $cardB['id']);
        });

        return $cards;
    }

    /**
     * Uses the Stripe customer id of the active user to retrieve the customer from Stripe
     * and returns the customer's default credit card.
     *
     * @return array|null The default credit card or null, if no cards exist.
     * @throws \Exception exception, if loading the Stripe customer fails.
     */
    public static function getDefaultStripeCard()
    {
        // Get the Stripe customer
        $customer = self::getStripeCustomer();
        if ($customer === null || isset($customer->deleted)) {
            return null;
        }

        // Get all cards and try to find the one matching the default id
        $cards = self::getAllStripeCards();
        /** @var array $card */
        foreach ($cards as $card) {
            if ($card['id'] === $customer->default_source) {
                // Return the default card
                return $card;
            }
        }

        // Just return the last card of the list, if possible
        return array_pop($cards);
    }

    /**
     * First tries to find currently logged in user in the database and checks their stripe customer id.
     * If found, the customer information is loaded from Stripe and returned.
     *
     * @return Stripe\Customer|null The customer, which was loaded from Stripe or null, if e.g. the customer does not exist.
     * @throws \Exception An exception, if Stripe could not load the customer.
     */
    public static function getStripeCustomer()
    {
        self::initStripeAPI();
        // Check if customer is already loaded
        if (self::$stripeCustomer !== null) {
            return self::$stripeCustomer;
        }

        // Get the current logged in customer
        $customer = self::getCustomer();
        if ($customer === null || $customer->getAccountMode() === 1 || $customer->getAttribute() === null || $customer->getAttribute()->getStripeCustomerId() === null) {
            // Customer not found, without permanent user account or has no stripe customer associated with it
            return null;
        }

        // Load, save and return the customer
        $stripeCustomerId = $customer->getAttribute()->getStripeCustomerId();
        try {
            self::$stripeCustomer = Stripe\Customer::retrieve($stripeCustomerId);
            if (self::$stripeCustomer && isset(self::$stripeCustomer->deleted)) {
                throw new \Exception('Customer deleted');
            }
        } catch (\Exception $e) {
            // Customer cannot be found, hence remove the saved customer ID from the databse
            self::$stripeCustomer = null;
            $customer->getAttribute()->setStripeCustomerId(null);
            Shopware()->Models()->flush($customer->getAttribute());
        }

        return self::$stripeCustomer;
    }

    /**
     * Creates a new Stripe customer for the currently logged in user/customer and saves
     * the respective ID in the customer attributes.
     *
     * @return Stripe\Customer|null
     */
    public static function createStripeCustomer()
    {
        self::initStripeAPI();
        $em = Shopware()->Container()->get('models');
        // Get the current logged in customer
        $customer = self::getCustomer();
        if ($customer === null || $customer->getAccountMode() === 1) {
            // Customer not found, without permanent user account
            return null;
        }
        // Make sure the customer has attributes
        if ($customer->getAttribute() === null) {
            $customerAttribute = new CustomerAttribute();
            $customerAttribute->setCustomer($customer);
            $customer->setAttribute($customerAttribute);
            $em->persist($customerAttribute);
            $em->flush($customerAttribute);
            $em->flush($customer);
        }

        // Create a new Stripe customer and save it in the user's attributes
        try {
            self::$stripeCustomer = Stripe\Customer::create([
                'description' => self::getCustomerName(),
                'email' => $customer->getEmail(),
                'metadata' => [
                    'platform_name' => self::STRIPE_PLATFORM_NAME,
                ],
            ]);
            $customer->getAttribute()->setStripeCustomerId(self::$stripeCustomer->id);
            $em->flush($customer->getAttribute());
        } catch (\Exception $e) {
            return null;
        }

        return self::$stripeCustomer;
    }

    /**
     * Checks if a user/customer is currently logged in and tries to get and return that customer.
     *
     * @return Customer|null The customer object of the user who is logged in or null, if no user is logged in.
     */
    public static function getCustomer()
    {
        // Check if a user is logged in
        if (empty(Shopware()->Session()->sUserId)) {
            return null;
        }

        // Try to find the customer
        $customerId = Shopware()->Session()->sUserId;
        if ($customerId === null) {
            return null;
        }
        $customerRepository = Shopware()->Models()->getRepository('\\Shopware\\Models\\Customer\\Customer');
        /** @var Customer $customer */
        $customer = $customerRepository->find($customerId);

        return $customer;
    }

    /**
     * @return string|null The customers company name, if it exists. Otherwise their joined first and last name or null, if no user is logged in.
     */
    public static function getCustomerName()
    {
        $customer = self::getCustomer();
        if ($customer === null) {
            return null;
        }

        $billingAddress = (method_exists($customer, 'getDefaultBillingAddress')) ? $customer->getDefaultBillingAddress() : $customer->getBilling();

        // Check for company
        $company = $billingAddress->getCompany();
        if (!empty($company)) {
            return $company;
        }

        // Use first and last name
        return trim($billingAddress->getFirstName() . ' ' . $billingAddress->getLastName());
    }

    /**
     * Decodes the requests's JSON body and tries to retrieve the Stripe event whose
     * ID is contained in the request.
     *
     * @param \Enlight_Controller_Request_RequestHttp $request
     * @return Stripe\Event
     */
    public static function verifyWebhookRequest(\Enlight_Controller_Request_RequestHttp $request)
    {
        self::initStripeAPI();
        // Try to parse the request payload
        $rawBody = $request->getRawBody();
        $eventJson = \Zend_Json::decode($rawBody);

        // Verify the event by fetching it from Stripe
        $event = Stripe\Event::retrieve($eventJson['id']);

        return $event;
    }

    /**
     * @return \ArrayObject
     */
    public static function getStripeSession()
    {
        $session = Shopware()->Container()->get('session');
        if (!$session->stripePayment) {
            self::resetStripeSession();
        }

        return $session->stripePayment;
    }

    /**
     * Replaces the 'stripePayment' element in the session with an empty ArrayObject.
     */
    public static function resetStripeSession()
    {
        Shopware()->Container()->get('session')->stripePayment = new \ArrayObject([], \ArrayObject::STD_PROP_LIST);
    }
}
