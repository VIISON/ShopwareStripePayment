<?php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}

use Shopware\Models\Config\Element;
use Shopware\Plugins\StripePayment\Subscriber;

/**
 * This plugin offers a credit card payment method using Stripe.
 *
 * @copyright Copyright (c) 2015, VIISON GmbH
 */
class Shopware_Plugins_Frontend_StripePayment_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        $pluginJSON = $this->getPluginJSON();

        return $pluginJSON['currentVersion'];
    }

    /**
     * @inheritdoc
     */
    public function getInfo()
    {
        $info = $this->getPluginJSON();
        $info['version'] = $info['currentVersion'];
        $info['label'] = 'Stripe Payment';
        $info['description'] = file_get_contents(__DIR__ . '/description.html');
        unset($info['currentVersion']);
        unset($info['compatibility']);

        return $info;
    }

    /**
     * Default install method, which installs the plugin and its events.
     *
     * @return True if installation was successful, otherwise false.
     */
    public function install()
    {
        return $this->update('install');
    }

    /**
     * Adds new event subscriptions and configurations.
     *
     * @param $oldVersion The currently installed version of this plugin.
     * @return True if the update was successful, otherwise false.
     */
    public function update($oldVersion)
    {
        switch ($oldVersion) {
            case 'install':
                // Add static event subscribers
                $this->subscribeEvent(
                    'Enlight_Controller_Front_DispatchLoopStartup',
                    'onDispatchLoopStartup'
                );

                // Check whether the payment method already exists
                $stripePaymentMethod = $this->get('models')->getRepository('Shopware\Models\Payment\Payment')->findOneBy(array(
                    'action' => 'stripe_payment'
                ));
                if ($stripePaymentMethod === null) {
                    // Create the stripe payment method
                    $this->createPayment(
                        array(
                            'active' => 0,
                            'name' => 'stripe_payment',
                            'description' => 'Stripe Kreditkarte',
                            'template' => 'stripe_payment.tpl',
                            'action' => 'stripe_payment',
                            'class' => 'StripePaymentMethod',
                            'additionalDescription' => ''
                        )
                    );
                }

                // Add a config element for the stripe secret key
                $this->Form()->setElement(
                    'text',
                    'stripeSecretKey',
                    array(
                        'label' => 'Stripe Secret Key',
                        'description' => 'Tragen Sie hier Ihren geheimen Schlüssel ("Secret Key") ein. Diesen finden Sie im Stripe Dashboard unter "Account Settings" > "API Keys" im Feld "Live Secret Key".',
                        'value' => ''
                    )
                );
                // Add a config element for the stripe public key
                $this->Form()->setElement(
                    'text',
                    'stripePublicKey',
                    array(
                        'label' => 'Stripe Publishable Key',
                        'description' => 'Tragen Sie hier Ihren öffentlichen Schlüssel ("Publishable Key") ein. Diesen finden Sie im Stripe Dashboard unter "Account Settings" > "API Keys" im Feld "Live Publishable Key".',
                        'value' => ''
                    )
                );

                // Add an attribute to the user for storing the Stripe customer id
                $this->get('models')->addAttribute(
                    's_user_attributes',
                    'stripe',
                    'customer_id',
                    'varchar(255)'
                );

                // Rebuild the user attributes model
                $this->get('models')->generateAttributeModels(array(
                    's_user_attributes'
                ));
            case '1.0.0':
                // Nothing to do
            case '1.0.1':
                // Nothing to do
            case '1.0.2':
                // Nothing to do
            case '1.0.3':
                // Nothing to do
            case '1.0.4':
                // Nothing to do
            case '1.0.5':
                // Nothing to do
            case '1.0.6':
                // Nothing to do
            case '1.0.7':
                // Nothing to do
            case '1.0.8':
                // Nothing to do
            case '1.0.9':
                $this->get('models')->persist($this->Form());
                // Set the scope of all config elements to 'shop'
                foreach ($this->Form()->getElements() as $element) {
                    $element->setScope(Element::SCOPE_SHOP);
                }
                // Add a config element for the stripe secret key
                $this->Form()->setElement(
                    'checkbox',
                    'allowSavingCreditCard',
                    array(
                        'label' => '"Kreditkarte speichern" anzeigen',
                        'description' => 'Aktivieren Sie diese Feld, um beim Bezahlvorgang das Speichern der Kreditkarte zu erlauben',
                        'value' => true,
                        'scope' => Element::SCOPE_SHOP
                    )
                );
            case '1.1.0':
                // Next release
                break;
            default:
                return false;
        }

        return array(
            'success' => true,
            'message' => 'Bitte leeren Sie den gesamten Shop Cache, aktivieren Sie das Plugin und Kompilieren Sie anschließend die Shop Themes neu. Aktivieren Sie abschließend die Zahlart "Stripe Kreditkarte", um sie verfügbar zu machen.',
            'invalidateCache' => array(
                'backend',
                'frontend',
                'config'
            )
        );
    }

    /**
     * Default uninstall method.
     *
     * @return True if uninstallation was successful, otherwise false.
     */
    public function uninstall()
    {
        // Remove database columns
        $this->get('models')->removeAttribute(
            's_user_attributes',
            'stripe',
            'customer_id'
        );

        // Rebuild the user attributes model
        $this->get('models')->generateAttributeModels(array(
            's_user_attributes'
        ));

        return true;
    }

    /**
     * Registers the plugin's namespace.
     */
    public function afterInit()
    {
        $this->get('Loader')->registerNamespace(
            'Shopware\Plugins\StripePayment',
            $this->Path()
        );
    }

    /**
     * Adds all subscribers to the event manager.
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onDispatchLoopStartup(\Enlight_Event_EventArgs $args)
    {
        $this->get('events')->addSubscriber(new Subscriber\Payment());
        $this->get('events')->addSubscriber(new Subscriber\Backend($this));
        $this->get('events')->addSubscriber(new Subscriber\Frontend($this));
        $this->get('events')->addSubscriber(new Subscriber\Theme($this));
    }

    /**
     * @inheritdoc
     */
    public function assertMinimumVersion($requiredVersion)
    {
        return parent::assertMinimumVersion($requiredVersion);
    }

    /**
     * @return array
     */
    private function getPluginJSON()
    {
        $pluginJSON = file_get_contents(__DIR__ . '/plugin.json');
        $pluginJSON = json_decode($pluginJSON, true);

        return $pluginJSON;
    }

    public function getStripePaymentMethod()
    {
        return new \Shopware\Plugins\StripePayment\Components\StripePaymentMethod();
    }
}
