// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

/**
 * A common utility object for handling SEPA payments using Stripe.js in the StripePayment plugin.
 */
var StripePaymentSepa = {
    /**
     * The Stripe.js instance used for creating the IBAN field and generating the payment source.
     */
    stripeClient: null,

    /**
     * The IBAN Stripe element.
     */
    ibanStripeElement: null,

    /**
     * The currency to use for creating Stripe SEPA sources.
     */
    currency: null,

    /**
     * The Stripe Source object used for completing the checkout.
     */
    sepaSource: null,

    /**
     * An object containing the names of all fields that are currently invalid and their resepctive error messages.
     */
    invalidFields: {},

    /**
     * The snippets used for Stripe error descriptions.
     */
    snippets: {
        error: {
            title: 'Error',
        },
    },

    /**
     * Initializes the Stripe service using the given public key, and triggers the initial setup of the payment form.
     *
     * @param String stripePublicKey
     * @param Object config
     */
    init: function (stripePublicKey, config) {
        var me = this;
        me.stripeClient = Stripe(stripePublicKey);
        // Save config
        me.currency = config.currency || null;
        me.sepaSource = config.sepaSource || null;

        me.setupForm();

        if (me.isShopware5Template()) {
            // Add listener on changes of the selected payment method to setup the form again
            $.subscribe('plugin/swShippingPayment/onInputChanged', function () {
                me.setupForm();
            });
        }
    },

    /**
     * Sets up the payment form by first unmounting all Stripe elements that might be already mounted to the DOM and
     * clearing all validation errors. Then, if a stripe SEPA payment method is selected, mounts new Stripe elements
     * fields to the form and adds some observers to the form.
     */
    setupForm: function () {
        // Reset form
        this.unmountStripeElements();
        this.invalidFields = {};
        this.updateValidationErrors();

        if (this.getActiveStripeSepaForm()) {
            // Mount Stripe form fields again to the now active form and add other observers
            this.mountStripeElements();
            this.observeForm();
        }
    },

    /**
     * Creates the Stripe elements IBAN field and mounts it to its resepctive node in the active Stripe SEPA
     * payment form.
     */
    mountStripeElements: function () {
        var me = this;

        // Copy the style from the account owner field
        var accountOwnerFieldEl = me.formEl('.stripe-sepa-account-owner');
        var elementStyle = {
            style: {
                base: {
                    color: accountOwnerFieldEl.css('color'),
                    fontFamily: accountOwnerFieldEl.css('font-family'),
                    fontSize: accountOwnerFieldEl.css('font-size'),
                    fontWeight: accountOwnerFieldEl.css('font-weight'),
                    lineHeight: (accountOwnerFieldEl.css('line-height') != 'normal') ? accountOwnerFieldEl.css('line-height') : '16px', // Use fallback to 16px in Shopware 4
                },
            },
        };

        // Create the IBAN element and add the change listener
        var elements = me.stripeClient.elements({
            locale: me.locale
        });
        me.ibanStripeElement = elements.create(
            'iban',
            {
                style: elementStyle,
                supportedCountries: ['SEPA'],
            }
        );
        me.ibanStripeElement.on('change', function (event) {
            if (event.error && event.error.type === 'validation_error') {
                me.markFieldInvalid('iban', event.error.code, event.error.message);
            } else {
                me.markFieldValid('iban');
            }
        });

        // Mount the IBAN field to the DOM
        var mountElement = me.formEl('.stripe-element-sepa-iban').get(0);
        me.ibanStripeElement.mount(mountElement);
    },

    /**
     * Unmounts all existing Stripe elements from the Stripe IBAN payment form they are currently mounted to.
     */
    unmountStripeElements: function () {
        if (this.ibanStripeElement) {
            this.ibanStripeElement.unmount();
            this.ibanStripeElement = null;
        }
    },

    /**
     * Adds change listeners to the SEPA form fields as well as a submission listener on the main payment form.
     */
    observeForm: function () {
        var me = this;
        // Add a listener on the form
        me.findForm().on('submit', { scope: me }, me.onFormSubmission);

        me.formEl('input[class^="stripe-sepa-"]').each(function () {
            // Save the current value and add listener
            var elem = $(this);
            elem.data('oldVal', elem.val());
            elem.on('propertychange keyup input paste', { scope: me }, me.onFieldChange);
        });
    },

    /**
     * Removes all validation errors for the field with the given 'fieldId' and triggers an update of the displayed
     * validation errors.
     *
     * @param String fieldId
     */
    markFieldValid: function (fieldId) {
        delete this.invalidFields[fieldId];
        this.updateValidationErrors();
    },

    /**
     * Determines the error message based on the given 'errorCode' and 'message' and triggers an update of the displayed
     * validation errors.
     *
     * @param String fieldId
     * @param String errorCode (optional) The code used to find a localised error message.
     * @param String message (optioanl) The fallback error message used in case no 'errorCode' is provided or no
     *        respective, localised description exists.
     */
    markFieldInvalid: function (fieldId, errorCode, message) {
        this.invalidFields[fieldId] = this.snippets.error[errorCode || ''] || message || 'Unknown error';
        this.updateValidationErrors();
    },

    /**
     * Checks the list of invalid fields for any entries and, if found, joins them to
     * an error message, which is then displayed in the error box. If no invalid fields
     * are found, the error box is hidden.
     */
    updateValidationErrors: function () {
        var me = this,
            errorBox = me.formEl('.stripe-payment-validation-error-box'),
            boxContent = errorBox.find('.error-content');
        boxContent.empty();
        if (Object.keys(me.invalidFields).length > 0) {
            // Update the error box message and make it visible
            var listEl = $('<ul></ul>')
                .addClass('alert--list')
                .appendTo(boxContent);
            Object.keys(me.invalidFields).forEach(function (key) {
                var row = $('<li></li>')
                    .addClass('list--entry')
                    .text(me.invalidFields[key])
                    .appendTo(listEl);
            });
            errorBox.show();
        } else {
            errorBox.hide();
        }
    },

    /**
     * First validates the form and payment state and, if the main form can be submitted, does nothing further.
     * If however the main form cannot be submitted, because no SEPA source exist, the fields are validated and, if
     * valid, a new Stripe SEPA source is generated using the data and saved in the form, before the submission is
     * triggered again.
     *
     * @param Event event
     */
    onFormSubmission: function (event) {
        var me = event.data.scope;
        var form = $(this);

        // Check if a Stripe source was generated and hence the form can be submitted
        if (me.sepaSource) {
            return undefined;
        }

        // Prevent the form from being submitted until a new Stripe token is generated and received
        event.preventDefault();

        // Trigger the validation of all fields
        me.formEl('input[class^="stripe-sepa-"]').each(function () {
            $(this).trigger('propertychange', [ true ]);
        });

        // Check for invalid fields
        if (Object.keys(me.invalidFields).length > 0) {
            return;
        }

        // Send the SEPA information to Stripe
        me.setSubmitButtonsLoading();
        me.stripeClient.createSource(
            me.ibanStripeElement,
            {
                type: 'sepa_debit',
                currency: me.currency,
                owner: {
                    name: me.formEl('input.stripe-sepa-account-owner').val(),
                    address: {
                        line1: me.formEl('input.stripe-sepa-street').val(),
                        city: me.formEl('input.stripe-sepa-zip-code').val(),
                        postal_code: me.formEl('input.stripe-sepa-city').val(),
                        country: me.formEl('select.stripe-sepa-country').val(),
                    },
                },
            }
        ).then(function (result) {
            if (result.error) {
                // Only reset the submit buttons in case of an error, because otherwise the form is submitted again
                // right away and hence we want the buttons to stay disabled
                me.resetSubmitButtons();

                // Display the error
                var message = me.snippets.error[result.error.code || ''] || result.error.message || 'Unknown error';
                me.handleStripeError(me.snippets.error.title + ': ' + message);
            } else {
                // Save the source information
                me.sepaSource = result.source;

                // Update/add the hidden SEPA source field in the main form
                $('input[name="stripeSepaSource"]').remove();
                $('<input type="hidden" name="stripeSepaSource" />')
                    .val(JSON.stringify(me.sepaSource))
                    .appendTo(me.findForm());

                // Submit the form again to finish the payment process
                form.submit();
            }
        });
    },

    /**
     * Validates the field value to not be empty. If the validation failes, the field is marked invalid.
     *
     * @param Event event
     * @param boolean|undefined force
     */
    onFieldChange: function (event, force) {
        var me = event.data.scope;
        var elem = $(this);
        var name = elem.attr('name');

        // Check if value has changed, if 'force' is not set
        if (!force && elem.data('oldVal') == elem.val()) {
            return;
        }
        elem.data('oldVal', elem.val());

        // Validate the field
        if (elem.val().trim().length === 0) {
            elem.addClass('instyle_error has--error');
            me.markFieldInvalid(name, ('invalid_' + name));
        } else {
            elem.removeClass('instyle_error has--error');
            me.markFieldValid(name);
        }
    },

    /**
     * Finds both submit buttons on the page and adds the 'disabled' attribute as well as the loading indicator to each
     * of them.
     *
     * Note: If called in a shopware environment < v5, this method does nothing.
     */
    setSubmitButtonsLoading: function () {
        if (!this.isShopware5Template()) {
            return;
        }

        // Reset the button first to prevent it from being added multiple loading indicators
        this.resetSubmitButtons();
        $('#shippingPaymentForm button[type="submit"], .confirm--actions button[form="shippingPaymentForm"]').each(function () {
            $(this).html($(this).text() + '<div class="js--loading"></div>').attr('disabled', 'disabled');
        });
    },

    /**
     * Finds both submit buttons on the page and resets them by removing the 'disabled' attribute as well as the loading
     * indicator.
     *
     * Note: If called in a shopware environment < v5, this method does nothing.
     */
    resetSubmitButtons: function () {
        if (!this.isShopware5Template()) {
            return;
        }

        $('#shippingPaymentForm button[type="submit"], .confirm--actions button[form="shippingPaymentForm"]').each(function () {
            $(this).removeAttr('disabled').find('.js--loading').remove();
        });
    },

    /**
     * Sets the given message in the general error box and scrolls the page to make it visible.
     *
     * @param String message A Stripe error message.
     */
    handleStripeError: function (message) {
        // Display the error information above the SEPA form and scroll to its position
        this.formEl('.stripe-payment-error-box').show().children('.error-content').html(message);
        $('body').animate({
            scrollTop: (this.getActiveStripeSepaForm().offset().top - 100),
        }, 500);
    },

    /**
     * Tries to find a stripe SEPA form for the currently active payment method. That is, if a stripe SEPA payment
     * method is selected, its form is returned, otherwise returns null.
     *
     * @return jQuery|null
     */
    getActiveStripeSepaForm: function () {
        var paymentMethodSelector = (this.isShopware5Template()) ? '.payment--method' : '.method';
        var form = $('input[id^="payment_mean"]:checked').closest(paymentMethodSelector).find('.stripe-payment-sepa-form');

        return (form.length > 0) ? form.first() : null;
    },

    /**
     * Applies a jQuery query on the DOM tree under the active stripe SEPA form using the given selector. This method
     * should be used when selecting any fields that are part of a Stripe SEPA payment form. If no Stripe SEPA form is
     * active, an empty query result is returned.
     *
     * @param String selector
     * @return jQuery
     */
    formEl: function (selector) {
        var form = this.getActiveStripeSepaForm();
        return (form) ? form.find(selector) : $('stripe_payment_sepa_not_found');
    },

    /**
     * @return jQuery The main payment selection form element.
     */
    findForm: function () {
        return (this.isShopware5Template()) ? $('#shippingPaymentForm') : $('#basketButton').closest('form');
    },

    /**
     * @return Boolean True, if a Shopware 5 template is laoded. Otherwise false.
     */
    isShopware5Template: function () {
        return typeof $.overlay !== 'undefined';
    }
};
