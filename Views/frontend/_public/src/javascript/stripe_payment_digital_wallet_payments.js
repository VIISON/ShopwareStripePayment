// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

/**
 * A common utility object for handling digital wallet payments using Stripe.js in the StripePayment plugin.
 */
var StripePaymentDigitalWalletPayments = {
    /**
     * The payment request containing all information for processing the payment upon checkout.
     */
    paymentRequest: null,

    /**
     * A boolean indicating whether the payment API used for digital wallet payments is available in the current browser environment.
     */
    paymentApiAvailable: false,

    /**
     * The selected Stripe payment method id used for completing the checkout.
     */
    paymentMethodId: null,

    /**
     * The selected payment method in the checkout flow.
     */
    selectedPaymentMethodName: null,

    /**
     * A list of items to show in the payment requests popup.
     */
    paymentDisplayItems: [],

    /**
     * The snippets used for Stripe error descriptions.
     */
    snippets: {
        error: {
            connectionNotSecure: 'Your connection to this shop is not secure. Apple Pay only works when served via SSL/TLS (HTTPS). Please select a different payment method.',
            invalidConfig: 'The Apple Pay session cannot be configured. Please refresh this page.',
            notAvailable: 'Apple Pay is not available on this device/ in this browser. Please select a different payment method.',
            paymentCancelled: 'The payment was cancelled.',
            title: 'Error',
        },
        shippingCost: 'Shipping cost',
    },

    /**
     * Uses the Stripe client to create a new digital wallet payment request for the passed config and adds a listener
     * on the main form's submit button.
     *
     * @param String stripePublicKey
     * @param Object config
     * @param String selectedPaymentMethod
     */
    init: function (stripePublicKey, config, selectedPaymentMethod) {
        var me = this;
        this.selectedPaymentMethod = selectedPaymentMethod;

        // Validate config
        if (!config.countryCode || !config.currencyCode || !config.amount || !config.basketContent) {
            me.handleStripeError(me.snippets.error.invalidConfig);

            return undefined;
        }

        this.paymentDisplayItems = config.basketContent.map(function (item) {
            return {
                label: item.articlename,
                amount: Math.round(item.amountNumeric * 100),
            };
        });
        if (config.shippingCost) {
            this.paymentDisplayItems.push({
                label: this.snippets.shippingCost,
                amount: Math.round(config.shippingCost * 100),
            });
        }

        me.createPaymentRequest(
            stripePublicKey,
            config.countryCode,
            config.currencyCode,
            config.statementDescriptor || '',
            config.amount
        );

        // Save the original submit button content and add a listener on the preloader event to be able to reset it
        me.submitButtonContent = me.findSubmitButton().html();
        $.subscribe('plugin/swPreloaderButton/onShowPreloader', function (event, button) {
            if (me.shouldResetSubmitButton) {
                me.shouldResetSubmitButton = false;
                me.resetSubmitButton(button.$el);
            }
        });

        // Add a listener on the form
        me.findForm().on('submit', { scope: me }, me.onFormSubmission);
    },

    /**
     * Creates the payment request and evaluates whether the payment API is available in the current browser environment.
     *
     * @param String stripePublicKey
     * @param String countryCode
     * @param String currencyCode
     * @param String statementDescriptor
     * @param String amount
     */
    createPaymentRequest: function (stripePublicKey, countryCode, currencyCode, statementDescriptor, amount) {
        var me = this;
        var stripeClient = Stripe(stripePublicKey);
        me.paymentRequest = stripeClient.paymentRequest({
            country: countryCode.toUpperCase(),
            currency: currencyCode.toLowerCase(),
            total: {
                label: statementDescriptor,
                amount: Math.round(amount * 100),
            },
            displayItems: me.paymentDisplayItems,
        });

// Check for availability of the payment API
        me.paymentRequest.canMakePayment().then(function (result) {
            if (me.selectedPaymentMethod === 'apple_pay') {
                me.paymentApiAvailable = result && result.applePay;
            } else {
                me.paymentApiAvailable = !!result && !result.applePay;
            }
            if (!me.paymentApiAvailable) {
                if (!me.isSecureConnection()) {
                    me.handleStripeError(me.snippets.error.connectionNotSecure);

                    return undefined;
                }
                me.handleStripeError(me.snippets.error.notAvailable);
            }
        });

        // Add a listener for once the payment is created. This happens once the user selects "pay" in their browser
        // specific payment popup
        me.paymentRequest.on('paymentmethod', function (paymentResponse) {
            me.paymentMethodId = paymentResponse.paymentMethod.id;

            // Complete the browser's payment flow
            paymentResponse.complete('success');

            // Add the created Stripe token to the form and submit it
            var form = me.findForm();
            $('input[name="stripePaymentMethodId"]').remove();
            $('<input type="hidden" name="stripePaymentMethodId" />')
                .val(me.paymentMethodId)
                .appendTo(form);
            form.submit();
        });

        // Add listener for cancelled payment flow
        me.paymentRequest.on('cancel', function () {
            me.paymentMethodId = null;
            me.handleStripeError(me.snippets.error.paymentCancelled);
            me.resetSubmitButton(me.findSubmitButton());
        });
    },

    /**
     * First validates the form and payment state and, if the main form can be submitted, does nothing further. If
     * however the main form cannot be submitted, because no paymentMethodId exist, the digital wallet payments flow
     * is triggered.
     *
     * @param Event event
     */
    onFormSubmission: function (event) {
        var me = event.data.scope;

        // Make sure the AGB checkbox is checked, if it exists. Shopware 5 themes validate the checkbox before
        // submitting the checkout form. This validation however does not work on mobile (e.g. iOS Safari), which makes
        // it necessary to always check ourselves.
        if ($('input#sAGB').length === 1 && !$('input#sAGB').is(':checked')) {
            return undefined;
        }

        // Check if a Stripe payment method was generated and hence the form can be submitted
        if (me.paymentMethodId) {
            return undefined;
        }

        // Prevent the form from being submitted until a new Stripe payment method is generated and received
        event.preventDefault();

        // We have to manually check whether this site is served via HTTPS before checking the digital wallet payments availability
        // using Stripe.js. Even though Stripe.js checks the used protocol and declines the payment if not served via
        // HTTPS, only a generic 'not available' error message is returned and the HTTPS warning is logged to the
        // console. We however want to show a specific error message that informs about the lack of security.
        if (!me.isSecureConnection()) {
            me.shouldResetSubmitButton = true;
            me.handleStripeError(me.snippets.error.connectionNotSecure);

            return undefined;
        }

        // Check for general availability of the digital wallet payments
        if (!me.paymentApiAvailable) {
            me.shouldResetSubmitButton = true;
            me.handleStripeError(me.snippets.error.notAvailable);

            return undefined;
        }

        $('#stripe-payment-payment-request-api-error-box').hide();

        // Process the payment
        me.paymentRequest.show();
    },

    /**
     * Finds the submit button on the page and resets it by removing the 'disabled' attribute as well as the loading
     * indicator.
     */
    resetSubmitButton: function (button) {
        button.html(this.submitButtonContent).removeAttr('disabled').find('.js--loading').remove();
    },

    /**
     * Sets the given message in the general error box and scrolls the page to make it visible.
     *
     * @param String message A Stripe error message.
     */
    handleStripeError: function (message) {
        // Display the error message and scroll to its position
        var errorBox = $('#stripe-payment-payment-request-api-error-box');
        errorBox.show().find('.error-content').html(this.snippets.error.title + ': ' + message);
        $('body').animate({
            scrollTop: (errorBox.offset().top - 50),
        }, 500);
    },

    /**
     * @return jQuery The main checkout form element.
     */
    findForm: function () {
        // Determine the form that is submitted by the 'submit' button
        var submitButton = this.findSubmitButton();
        var formId = 'form#' + submitButton.attr('form');

        return $(formId);
    },

    /**
     * @return jQuery The button element that submits the checkout.
     */
    findSubmitButton: function () {
        return $('.confirm--content .main--actions button[type="submit"]');
    },

    /**
     * @return Boolean True, if the page is served via HTTPS. Otherwise false.
     */
    isSecureConnection: function () {
        return window.location.protocol === 'https:';
    },
};
