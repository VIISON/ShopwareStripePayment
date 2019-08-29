<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}

use Shopware\Models\Config\Element;
use Shopware\Models\Payment\Payment as PaymentMethod;
use Shopware\Plugins\StripePayment\Classes\SmartyPlugins;
use Shopware\Plugins\StripePayment\Subscriber;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This plugin offers a credit card payment method using Stripe.
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
     * {@inheritdoc}
     */
    public function getCapabilities()
    {
        $capabilities = parent::getCapabilities();
        $capabilities['secureUninstall'] = true;

        return $capabilities;
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
                // Add static event subscribers to make sure the plugin is always loaded
                $this->subscribeEvent(
                    'Enlight_Controller_Front_StartDispatch',
                    'onStartDispatch'
                );
                $this->subscribeEvent(
                    'Shopware_Console_Add_Command',
                    'onAddConsoleCommand'
                );

                // Add a config element for the stripe secret key
                $this->Form()->setElement(
                    'text',
                    'stripeSecretKey',
                    [
                        'label' => 'Stripe Secret Key',
                        'description' => 'Tragen Sie hier Ihren geheimen Schlüssel ("Secret Key") ein. Diesen finden Sie im Stripe Dashboard unter "API" im Feld "Live Secret Key".',
                        'value' => '',
                        'scope' => Element::SCOPE_SHOP,
                    ]
                );
                // Add a config element for the stripe public key
                $this->Form()->setElement(
                    'text',
                    'stripePublicKey',
                    [
                        'label' => 'Stripe Publishable Key',
                        'description' => 'Tragen Sie hier Ihren öffentlichen Schlüssel ("Publishable Key") ein. Diesen finden Sie im Stripe Dashboard unter "API" im Feld "Live Publishable Key".',
                        'value' => '',
                        'scope' => Element::SCOPE_SHOP,
                    ]
                );
                // Add a config element for showing/hiding the 'save credit card' checkbox for card payment methods
                $this->Form()->setElement(
                    'checkbox',
                    'allowSavingCreditCard',
                    [
                        'label' => '"Kreditkarte speichern" anzeigen',
                        'description' => 'Aktivieren Sie diese Feld, um beim Bezahlvorgang das Speichern der Kreditkarte zu erlauben',
                        'value' => true,
                        'scope' => Element::SCOPE_SHOP,
                    ]
                );
                // Add a config element for showing/hiding the payment provider logos
                $this->Form()->setElement(
                    'checkbox',
                    'showPaymentProviderLogos',
                    [
                        'label' => 'Logos der Zahlungsarten anzeigen',
                        'description' => 'Aktivieren Sie diese Feld, um in der Liste der verfügbaren Zahlungsarten die Logos der von diesem Plugin zur Verfügung gestellten Zahlungsarten anzuzeigen.',
                        'value' => true,
                        'scope' => Element::SCOPE_SHOP,
                    ]
                );
                // Add a config element for the custom statement descriptor suffix
                $this->Form()->setElement(
                    'text',
                    'statementDescriptorSuffix',
                    [
                        'label' => 'Verwendungszweck',
                        'description' => 'Tragen Sie hier einen eigenen Verwendungszweck ein, der zusammen mit der Nummer der Bestellung an die Zahlungsdienstleister übermittelt wird. Bitte beachten Sie, dass nur Buchstaben, Zahlen sowie Punkt, Komma und Leerzeichen erlaubt sind.',
                        'value' => '',
                        'scope' => Element::SCOPE_SHOP,
                        'maxLength' => 23,
                    ]
                );

                // Add an attribute to the user for storing the Stripe customer id
                $this->addColumnIfNotExists('s_user_attributes', 'stripe_customer_id', 'varchar(255) DEFAULT NULL');

                // Rebuild the user attributes model
                $this->get('models')->generateAttributeModels([
                    's_user_attributes'
                ]);

                // Add an inactive payment method for credit card payments
                $this->createPaymentMethodIfNotExists([
                    'name' => 'stripe_payment_card',
                    'description' => 'Kreditkarte (via Stripe)',
                    'template' => 'stripe_payment_card.tpl',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentCard',
                    'additionalDescription' => '',
                    'active' => false,
                ]);
                // Add an inactive payment method for credit card payments with 3D-Secure
                $this->createPaymentMethodIfNotExists([
                    'name' => 'stripe_payment_card_three_d_secure',
                    'description' => 'Kreditkarte (mit 3D-Secure, via Stripe)',
                    'template' => 'stripe_payment_card.tpl',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentCard',
                    'additionalDescription' => '',
                    'active' => false,
                ]);
                // Add an inactive payment method for SOFORT payments
                $this->createPaymentMethodIfNotExists([
                    'name' => 'stripe_payment_sofort',
                    'description' => 'SOFORT Überweisung (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentSofort',
                    'additionalDescription' => '',
                    'active' => false,
                ]);
                // Add an inactive payment method for iDEAL payments
                $this->createPaymentMethodIfNotExists([
                    'name' => 'stripe_payment_ideal',
                    'description' => 'iDEAL (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentIdeal',
                    'additionalDescription' => '',
                    'active' => false,
                ]);
                // Add an inactive payment method for Bancontact payments
                $this->createPaymentMethodIfNotExists([
                    'name' => 'stripe_payment_bancontact',
                    'description' => 'Bancontact (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentBancontact',
                    'additionalDescription' => '',
                    'active' => false,
                ]);
                // Add an inactive payment method for Giropay payments
                $this->createPaymentMethodIfNotExists([
                    'name' => 'stripe_payment_giropay',
                    'description' => 'Giropay (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentGiropay',
                    'additionalDescription' => '',
                    'active' => false,
                ]);
                // Add an inactive payment method for SEPA payments
                $this->createPaymentMethodIfNotExists([
                    'name' => 'stripe_payment_sepa',
                    'description' => 'SEPA-Lastschrift (via Stripe)',
                    'template' => 'stripe_payment_sepa.tpl',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentSepa',
                    'additionalDescription' => '',
                    'active' => false,
                ]);
                // Add an inactive payment method for Apple Pay payments
                $this->createPaymentMethodIfNotExists([
                    'name' => 'stripe_payment_apple_pay',
                    'description' => 'Apple Pay (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentApplePay',
                    'additionalDescription' => '',
                    'active' => false,
                ]);
            case '3.0.0':
                // Nothing to do
            case '3.0.1':
                // Nothing to do
            case '3.0.2':
                // Nothing to do
            case '3.0.3':
                $this->Form()->setElement(
                    'checkbox',
                    'sendStripeChargeEmails',
                    [
                        'label' => 'Stripe-Belege via E-Mail versenden',
                        'description' => 'Aktivieren Sie diese Feld, damit Stripe automatisch Zahlungsbelege an den Kunden zu senden.',
                        'value' => false,
                        'scope' => Element::SCOPE_SHOP,
                    ]
                );
            case '3.1.0':
                // Nothing to do
            case '3.1.1':
                // Nothing to do
            case '3.1.2':
                // Nothing to do
            case '3.1.3':
                // Nothing to do
            case '3.2.0':
                // Nothing to do
            case '4.0.0':
                // Update the card payment methods to use the payment intent controller
                $cardPaymentMethod = $this->get('models')->getRepository(PaymentMethod::class)->findOneBy([
                    'name' => 'stripe_payment_card',
                ]);
                if ($cardPaymentMethod) {
                    $cardPaymentMethod->setAction('StripePaymentIntent');
                    $this->get('models')->flush($cardPaymentMethod);
                }
                $card3DSecurePaymentMethod = $this->get('models')->getRepository(PaymentMethod::class)->findOneBy([
                    'name' => 'stripe_payment_card_three_d_secure',
                ]);
                if ($card3DSecurePaymentMethod) {
                    if ($oldVersion === 'install') {
                        // If this is a first install remove the 3D secure payment method here because the new payment
                        // intents API handles 3D secure cards itself. The decision is not ours to make anymore.
                        $this->get('models')->remove($card3DSecurePaymentMethod);
                    } else {
                        $card3DSecurePaymentMethod->setAction('StripePaymentIntent');
                    }
                    $this->get('models')->flush($card3DSecurePaymentMethod);
                }
            case '5.0.0':
                // Nothing to do
            case '5.1.0':
                // Nothing to do
            case '5.1.1':
                // Next release

                break;
            default:
                return false;
        }

        $this->removeObsoletePluginFiles();

        return [
            'success' => true,
            'message' => 'Bitte leeren Sie den gesamten Shop Cache, aktivieren Sie das Plugin und Kompilieren Sie anschließend die Shop Themes neu. Aktivieren Sie abschließend die Zahlart "Stripe Kreditkarte", um sie verfügbar zu machen.',
            'invalidateCache' => [
                'backend',
                'frontend',
                'config',
            ],
        ];
    }

    /**
     * Default uninstall method.
     *
     * @return True if uninstallation was successful, otherwise false.
     */
    public function uninstall()
    {
        // Remove database columns
        $this->dropColumnIfExists('s_user_attributes', 'stripe_customer_id');

        // Rebuild the user attributes model
        $this->get('models')->generateAttributeModels([
            's_user_attributes'
        ]);

        return true;
    }

    /**
     * Adds all subscribers to the event manager.
     */
    public function onStartDispatch()
    {
        $this->get('events')->addSubscriber(new Subscriber\Payment());
        $this->get('events')->addSubscriber(new Subscriber\Backend\Index($this));
        $this->get('events')->addSubscriber(new Subscriber\Backend\Order($this));
        $this->get('events')->addSubscriber(new Subscriber\Controllers($this));
        $this->get('events')->addSubscriber(new Subscriber\Frontend\Account($this));
        $this->get('events')->addSubscriber(new Subscriber\Frontend\Checkout($this));
        $this->get('events')->addSubscriber(new Subscriber\Frontend\Frontend());
        $this->get('events')->addSubscriber(new Subscriber\Theme($this));

        // Register the custom smarty plugins
        $smartyPlugins = new SmartyPlugins(Shopware()->Container());
        $smartyPlugins->register();
    }

    /**
     * Adds the theme subscriber to the event manager.
     */
    public function onAddConsoleCommand()
    {
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

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $columnSpecification
     */
    private function addColumnIfNotExists($tableName, $columnName, $columnSpecification)
    {
        if ($this->doesColumnExist($tableName, $columnName)) {
            return;
        }

        $sql = 'ALTER TABLE ' . $this->get('db')->quoteIdentifier($tableName)
            . ' ADD ' . $this->get('db')->quoteIdentifier($columnName)
            . ' ' . $columnSpecification;
        $this->get('db')->exec($sql);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     */
    private function dropColumnIfExists($tableName, $columnName)
    {
        if (!$this->doesColumnExist($tableName, $columnName)) {
            return;
        }

        $sql = 'ALTER TABLE ' . $this->get('db')->quoteIdentifier($tableName)
            . ' DROP COLUMN ' . $this->get('db')->quoteIdentifier($columnName);
        $this->get('db')->exec($sql);
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @return boolean
     */
    private function doesColumnExist($tableName, $columnName)
    {
        $hasColumn = $this->get('db')->fetchOne(
            'SELECT COUNT(COLUMN_NAME)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = (SELECT DATABASE())
                AND TABLE_NAME = :tableName
                AND COLUMN_NAME = :columnName',
            [
                'tableName' => $tableName,
                'columnName' => $columnName,
            ]
        );

        return $hasColumn === '1';
    }

    private function createPaymentMethodIfNotExists(array $paymentMethodValues)
    {
        $existingPaymentMethod = $this->get('models')->getRepository(PaymentMethod::class)->findOneBy([
            'name' => $paymentMethodValues['name'],
        ]);
        if (!$existingPaymentMethod) {
            $this->createPayment($paymentMethodValues);
        }
    }

    /**
     * Removes all obsolete plugin files using the PluginStructureIntegrity class.
     */
    private function removeObsoletePluginFiles()
    {
        try {
            // Try to find a 'plugin.summary' file
            $summaryFilePath = $this->Path() . 'plugin.summary';
            if (!file_exists($summaryFilePath)) {
                return;
            }

            // Read the paths of all required plugin files from the summary
            $requiredPluginFiles = [];
            $handle = fopen($summaryFilePath, 'r');
            if ($handle) {
                $line = fgets($handle);
                while ($line !== false) {
                    $requiredPluginFiles[] = str_replace('/./', '/', ($this->Path() . trim($line, "\n")));
                    $line = fgets($handle);
                }
                fclose($handle);
            } else {
                $this->get('pluginlogger')->error('StripePayment: Failed to read "plugin.summary" file.');

                return;
            }

            // Delete all files from the plugin directory that are not required (contained in the summary)
            $filesystem = new Filesystem();
            $fileIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->Path()),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($fileIterator as $file) {
                if (!$file->isFile() || in_array($file->getPathname(), $requiredPluginFiles)) {
                    continue;
                }
                try {
                    $filesystem->remove($file->getPathname());
                } catch (IOException $e) {
                    $this->get('pluginlogger')->error(
                        'StripePayment: Failed to remove obsolete file. ' . $e->getMessage(),
                        [
                            'exception' => $e,
                            'file' => $file->getPathname(),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->get('pluginlogger')->error(
                'StripePayment: Failed to remove obsolete plugin files.',
                ['exception' => $e]
            );
        }
    }
}
