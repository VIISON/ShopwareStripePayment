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
                // Check for any Stripe payment methods
                $builder = $this->get('models')->createQueryBuilder();
                $builder->select('payment')
                        ->from('Shopware\\Models\\Payment\\Payment', 'payment')
                        ->where('payment.name LIKE \'stripe_payment%\'');
                $stripePaymentMethods = $builder->getQuery()->getResult();
                if (count($stripePaymentMethods) === 0) {
                    // No Stripe payment methods exist yet, hence create the (old) stripe payment method,
                    // which will be migrated in a later step
                    $this->createPayment([
                        'active' => 0,
                        'name' => 'stripe_payment',
                        'description' => 'Kreditkarte (via Stripe)',
                        'template' => 'stripe_payment.tpl',
                        'action' => 'stripe_payment',
                        'class' => 'StripePaymentMethod',
                        'additionalDescription' => '',
                    ]);
                }

                // Add a config element for the stripe secret key
                $this->Form()->setElement(
                    'text',
                    'stripeSecretKey',
                    [
                        'label' => 'Stripe Secret Key',
                        'description' => 'Tragen Sie hier Ihren geheimen Schlüssel ("Secret Key") ein. Diesen finden Sie im Stripe Dashboard unter "Account Settings" > "API Keys" im Feld "Live Secret Key".',
                        'value' => '',
                    ]
                );
                // Add a config element for the stripe public key
                $this->Form()->setElement(
                    'text',
                    'stripePublicKey',
                    [
                        'label' => 'Stripe Publishable Key',
                        'description' => 'Tragen Sie hier Ihren öffentlichen Schlüssel ("Publishable Key") ein. Diesen finden Sie im Stripe Dashboard unter "Account Settings" > "API Keys" im Feld "Live Publishable Key".',
                        'value' => '',
                    ]
                );

                // Add an attribute to the user for storing the Stripe customer id
                $this->addColumnIfNotExists('s_user_attributes', 'stripe_customer_id', 'varchar(255) DEFAULT NULL');

                // Rebuild the user attributes model
                $this->get('models')->generateAttributeModels([
                    's_user_attributes'
                ]);
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
            case '1.1.0':
                // Add static event subscriber to make sure the plugin is loaded upon running console commands
                $this->subscribeEvent(
                    'Shopware_Console_Add_Command',
                    'onAddConsoleCommand'
                );
            case '1.1.1':
                $this->get('models')->persist($this->Form());
                // Update descriptions of config elements
                $this->Form()->getElement('stripeSecretKey')->setDescription('Tragen Sie hier Ihren geheimen Schlüssel ("Secret Key") ein. Diesen finden Sie im Stripe Dashboard unter "API" im Feld "Live Secret Key".');
                $this->Form()->getElement('stripePublicKey')->setDescription('Tragen Sie hier Ihren öffentlichen Schlüssel ("Publishable Key") ein. Diesen finden Sie im Stripe Dashboard unter "API" im Feld "Live Publishable Key".');
                // Add static event subscriber on the earliest event possible
                $this->subscribeEvent(
                    'Enlight_Controller_Front_StartDispatch',
                    'onStartDispatch'
                );
                // Clear all stripe customer IDs from the user accountes to remove references to now incompatible
                // stripe cards
                $this->get('db')->query(
                    'UPDATE s_user_attributes
                    SET stripe_customer_id = NULL'
                );
                // Rename the original payment method to 'stripe_payment_card'
                $stripePaymentMethod = $this->get('models')->getRepository('Shopware\\Models\\Payment\\Payment')->findOneBy([
                    'name' => 'stripe_payment',
                ]);
                if ($stripePaymentMethod) {
                    $stripePaymentMethod->setName('stripe_payment_card');
                    $stripePaymentMethod->setTemplate('stripe_payment_card.tpl');
                    $stripePaymentMethod->setClass('StripePaymentCard');
                    $stripePaymentMethod->setAction('StripePayment');
                    $this->get('models')->flush($stripePaymentMethod);
                }
                // Add a payment method for credit card payments with 3D-Secure
                $this->createPayment([
                    'active' => 0,
                    'name' => 'stripe_payment_card_three_d_secure',
                    'description' => 'Kreditkarte (mit 3D-Secure, via Stripe)',
                    'template' => 'stripe_payment_card.tpl',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentCard',
                    'additionalDescription' => '',
                ]);
                // Add a payment method for SOFORT payments
                $this->createPayment([
                    'active' => 0,
                    'name' => 'stripe_payment_sofort',
                    'description' => 'SOFORT Überweisung (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentSofort',
                    'additionalDescription' => '',
                ]);
                // Add a payment method for iDEAL payments
                $this->createPayment([
                    'active' => 0,
                    'name' => 'stripe_payment_ideal',
                    'description' => 'iDEAL (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentIdeal',
                    'additionalDescription' => '',
                ]);
                // Add a payment method for Bancontact payments
                $this->createPayment([
                    'active' => 0,
                    'name' => 'stripe_payment_bancontact',
                    'description' => 'Bancontact (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentBancontact',
                    'additionalDescription' => '',
                ]);
                // Add a payment method for Giropay payments
                $this->createPayment([
                    'active' => 0,
                    'name' => 'stripe_payment_giropay',
                    'description' => 'Giropay (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentGiropay',
                    'additionalDescription' => '',
                ]);
                // Add a payment method for SEPA payments
                $this->createPayment([
                    'active' => 0,
                    'name' => 'stripe_payment_sepa',
                    'description' => 'SEPA-Lastschrift (via Stripe)',
                    'template' => 'stripe_payment_sepa.tpl',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentSepa',
                    'additionalDescription' => '',
                ]);
                // Add a payment method for Apple Pay payments
                $this->createPayment([
                    'active' => 0,
                    'name' => 'stripe_payment_apple_pay',
                    'description' => 'Apple Pay (via Stripe)',
                    'template' => '',
                    'action' => 'StripePayment',
                    'class' => 'StripePaymentApplePay',
                    'additionalDescription' => '',
                ]);
            case '2.0.0':
                // Nothing to do
            case '2.0.1':
                // Find duplicate order numbers created since the release of v2.0.0
                $duplicateOrderNumbers = $this->get('db')->fetchCol(
                    'SELECT ordernumber
                    FROM s_order
                    WHERE ordernumber != 0
                    AND ordertime > \'2017-03-15 00:00:00\'
                    GROUP BY ordernumber
                    HAVING COUNT(id) > 1'
                );
                $orderModule = $this->get('modules')->Order();
                foreach ($duplicateOrderNumbers as $orderNumber) {
                    // Change the order number of all but the oldest orders having the same order number
                    $orderIds = $this->get('db')->fetchCol(
                        'SELECT id
                        FROM s_order
                        WHERE ordernumber = :orderNumber',
                        [
                            'orderNumber' => $orderNumber,
                        ]
                    );
                    array_shift($orderIds);
                    foreach ($orderIds as $orderId) {
                        // Generate a new order number and save it both in the order and its details
                        $newOrderNumber = $orderModule->sGetOrderNumber();
                        $this->get('db')->query(
                            'UPDATE s_order o
                            INNER JOIN s_order_details od
                                ON od.orderID = o.id
                            SET o.ordernumber = :newOrderNumber, od.ordernumber = :newOrderNumber
                            WHERE o.id = :orderId',
                            [
                                'orderId' => $orderId,
                                'newOrderNumber' => $newOrderNumber,
                            ]
                        );
                    }
                }
            case '2.0.2':
                // Nothing to do
            case '2.0.3':
                // Nothing to do
            case '2.0.4':
                // Nothing to do
            case '2.0.5':
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
            case '2.0.6':
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
            case '2.1.0':
                // Remove all single quote escaping from stripe snippets in the database
                $this->get('db')->query(
                    "UPDATE s_core_snippets
                    SET value = REPLACE(value, '\\\\\'', '\'')
                    WHERE namespace LIKE '%stripe_payment%'"
                );
            case '2.1.1':
                // Nothing to do
            case '2.1.2':
                // Nothing to do
            case '2.1.3':
                // Nothing to do
            case '2.1.4':
                // Nothing to do
            case '2.2.0':
                // Nothing to do
            case '2.2.1':
                // Nothing to do
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
