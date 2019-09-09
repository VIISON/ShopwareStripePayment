<?php
// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

namespace Shopware\Plugins\StripePayment\Classes;

use InvalidArgumentException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Config\Element;
use Shopware\Models\Config\ElementTranslation;
use Shopware\Models\Config\Form;
use Shopware\Models\Config\FormTranslation;
use Shopware\Models\Shop\Locale;

/**
 * An idempotent installation helper for config forms. Right now, this mostly contains methods that help manage config
 * form translations in an idempotent manner.
 *
 * Note: This class is a copy of `Shopware\Plugins\ViisonCommon\Classes\Installation\ConfigForm\InstallationHelper`.
 */
class ConfigFormInstallationHelper
{
    /** @var ModelManager */
    private $modelManager;

    /**
     * @param ModelManager $modelManager
     */
    public function __construct($modelManager)
    {
        $this->modelManager = $modelManager;
    }

    /**
     * Sort the $form elements of a form in the order given by $formElementOrder
     *
     * @param Form $form
     * @param string[] $formElementOrder The names of the form elements in the order they should be sorted to
     */
    public function updateOrderOfFormElements(Form $form, array $formElementOrder)
    {
        // Force the array to be an array with numeric elements
        $formElementOrder = array_values($formElementOrder);
        foreach ($formElementOrder as $position => $formElementName) {
            $formElement = $form->getElement($formElementName);
            if (!$formElement) {
                continue;
            }
            $formElement->setPosition($position);
        }
        // This persist call is needed to prevent ORMInvalidArgumentExceptions ("A new entity was found through the
        // relationship ...") in the fresh installation scenario
        $this->modelManager->persist($form);
        $this->modelManager->flush($form);
    }

    /**
     * Sets the translations of all elements of a form from a snippet-like ini file. The ini-file format looks like
     * this:
     *
     * <code>
     * [de_DE]
     * elementName/label = "Deutsches Label"
     * elementName/description = "Deutscher Hilfetext"
     * # Optional store element translation
     * elementName/store/value1 = "Deutsche Beschriftung für Wert 1"
     * elementName/store/value2 = "Deutsche Beschriftung für Wert 2"
     *
     * [en_GB]
     * elementName/label = "English Label"
     * elementName/description = "English help text"
     * # Optional store element translation
     * elementName/store/value1 = "English label for value1"
     * elementName/store/value2 = "English label for value2"
     * </code>
     *
     * @param Form $form the form for which to apply the element translations
     * @param string $translationFile the name of an ini file containing the form translations
     */
    public function updateElementTranslations($form, $translationFile)
    {
        $parsedIniFile = parse_ini_file($translationFile, true);

        if (!$parsedIniFile) {
            return;
        }

        foreach ($parsedIniFile as $localeIdentifier => $elementTranslations) {
            /** @var Locale $locale */
            $locale = $this->modelManager->getRepository(Locale::class)->findOneBy(
                ['locale' => $localeIdentifier]
            );
            if (!$locale) {
                throw new InvalidArgumentException(sprintf('Locale \'%1$s\' does not exist.', $localeIdentifier));
            }

            foreach ($form->getElements() as $formElement) {
                $this->translateElementLabelAndDescription($formElement, $locale, $elementTranslations);
                $this->translateStoreValueLabels($formElement, $locale, $elementTranslations);
            }
        }

        // This persist call is needed to prevent ORMInvalidArgumentExceptions ("A new entity was found through the
        // relationship ...") in the fresh installation scenario
        $this->modelManager->persist($form);
        $this->modelManager->flush($form);
    }

    /**
     * Adds all translations matching the name of $element in language $locale found in the $translations ini-section to
     * the element.
     *
     * @param Element $element a config form element which to translate
     * @param Locale $locale a Shopware locale for which to add the translations
     * @param array $translations a number of key-value pairs read from the form.ini file
     */
    private function translateElementLabelAndDescription($element, $locale, $translations)
    {
        $name = $element->getName();
        $labelTranslation = isset($translations[$name . '/label']) ? $translations[$name . '/label'] : null;
        $descriptionTranslation = isset($translations[$name . '/description']) ? $translations[$name . '/description'] : null;

        if ($labelTranslation || $descriptionTranslation) {
            $translation = $this->getElementTranslation($element, $locale);
            $translation->setLabel($labelTranslation);
            $translation->setDescription($descriptionTranslation);
        }
    }

    /**
     * Finds an ElementTranslation for a specific element-locale combination or creates a new one if it does not exist
     * yet.
     *
     * @param Element $formElement
     * @param Locale $locale
     * @return ElementTranslation the translation for the given element into the given locale
     */
    private function getElementTranslation($formElement, $locale)
    {
        /** @var ElementTranslation $translation */
        foreach ($formElement->getTranslations() as $translation) {
            if ($translation->getLocale()->getId() === $locale->getId()) {
                return $translation;
            }
        }

        // Create a new translation
        $translation = new ElementTranslation();
        $formElement->addTranslation($translation);
        $translation->setLocale($locale);
        $this->modelManager->persist($translation);

        return $translation;
    }

    /**
     * Transforms a config value store (key 'store' in the $element->getOptions) by adding translations read from a
     * form.ini file.
     *
     * @param Element $element the element for which the config store should be transformed
     * @param Locale $locale a Shopware locale for which to add the translations
     * @param array $translations a number of key-value pairs read from the form.ini file
     */
    private function translateStoreValueLabels($element, $locale, $translations)
    {
        $name = $element->getName();
        $storeValueTranslations = [];
        foreach ($translations as $key => $translatedValue) {
            $storePrefix = $name . '/store/';
            if (mb_strpos($key, $storePrefix) === 0) {
                $storeValueTranslations[str_replace($storePrefix, '', $key)] = $translatedValue;
            }
        }

        $options = $element->getOptions();
        $store = isset($options['store']) ? $options['store'] : null;
        if (!is_array($store) || empty($storeValueTranslations)) {
            return;
        }

        foreach ($store as &$storeItem) {
            $valueId = $storeItem[0];
            $valueLabel = $storeItem[1];

            if (!is_array($valueLabel)) {
                // Value label not translated - assume default is German
                $storeItem[1] = ['de_DE' => $valueLabel];
            }

            if (isset($storeValueTranslations[$valueId])) {
                $storeItem[1][$locale->getLocale()] = $storeValueTranslations[$valueId];
            }
        }

        $options['store'] = $store;
        $element->setOptions($options);
    }
}
