<?php
use Shopware\Plugins\StripePayment\Util;

/**
 * This controller provides two actions for listing all credit cards of the currently logged in user
 * and for deleting a selected credit card.
 *
 * @copyright Copyright (c) 2015, VIISON GmbH
 */
class Shopware_Controllers_Frontend_StripePaymentAccount extends Shopware_Controllers_Frontend_Account
{
    /**
     * @inheritdoc
     */
    public function preDispatch()
    {
        // Check if user is logged in
        if ($this->admin->sCheckUser()) {
            parent::preDispatch();
        } else {
            unset($this->View()->sUserData);
            $this->forward('login', 'account');
        }
    }

    /**
     * Loads all Stripe credit cards for the currently logged in user and
     * adds them to the custom template.
     */
    public function manageCreditCardsAction()
    {
        $stripeSession = Util::getStripeSession();
        // Load the template
        if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
            // Shopware 5
            $this->View()->loadTemplate('frontend/account/stripe_payment_credit_cards.tpl');
        } else {
            // Shopware 4
            $this->View()->loadTemplate('frontend/stripe_payment/account/credit_cards.tpl');
            $this->View()->extendsTemplate('frontend/stripe_payment/account/content_right.tpl');
        }

        try {
            // Load all cards of the customer
            $cards = Util::getAllStripeCards();
        } catch (Exception $e) {
            $error = $this->get('snippets')->getNamespace('frontend/plugins/stripe_payment/account')->get('credit_cards/error/list_cards', 'Failed to load credit cards.');
            if ($stripeSession->accountError) {
                $error = $stripeSession->accountError . "\n" . $error;
            }
            $stripeSession->accountError = $error;
        }

        // Set the view data
        $this->View()->stripePayment = array(
            'availableCards' => $cards,
            'error' => $stripeSession->accountError
        );
        unset($stripeSession->accountError);
    }

    /**
     * Gets the cardId from the request and tries to delete the card with that id
     * from the Stripe account, which is associated with the currently logged in user.
     * Finally it redirects to the 'manageCreditCards' action.
     */
    public function deleteCardAction()
    {
        $stripeSession = Util::getStripeSession();
        try {
            // Determine the ID of the card that shall be deleted
            $cardId = $this->Request()->getParam('cardId');
            if (!$cardId) {
                throw new Exception('Missing field "cardId".');
            }
            // Get the Stripe customer
            $customer = Util::getStripeCustomer();
            if (!$customer) {
                throw new Exception('Customer not found.');
            }
            // Delete the card with the given id from Stripe
            $customer->sources->retrieve($cardId)->delete();
        } catch (Exception $e) {
            $stripeSession->accountError = $this->get('snippets')->getNamespace('frontend/plugins/stripe_payment/account')->get('credit_cards/error/delete_card', 'Failed to delete credit card.');
        }

        // Clear all checkout related fields from the stripe session to avoid caching deleted credit cards
        unset($stripeSession->selectedCard);
        unset($stripeSession->saveCardForFutureCheckouts);

        // Redirect to the manage action
        $this->redirect(array(
            'controller' => $this->Request()->getControllerName(),
            'action' => 'manageCreditCards'
        ));
    }
}
