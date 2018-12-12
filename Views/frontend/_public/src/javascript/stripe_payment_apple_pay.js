// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

/**
 * A common utility object for handling Apple Pay payments using Stripe.js in the StripePayment plugin.
 */
var StripePaymentApplePay = {
    /**
     * The payment request containing all information for processing the payment upon checkout.
     */
    paymentRequest: null,

    /**
     * A boolean indicating whether Apple Pay is available in the current browser environment.
     */
    applePayAvailable: false,

    /**
     * The Stripe Apple Pay token used for completing the checkout.
     */
    applePayToken: null,

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
    },

    /**
     * Uses the Stripe client to create a new Apple Pay payment request for the passed config and adds a listener on the
     * main form's submit button.
     *
     * @param String stripePublicKey
     * @param Object config
     */
    init: function (stripePublicKey, config) {
        var me = this;
        // Validate config
        if (!config.countryCode || !config.currencyCode || !config.amount) {
            me.handleStripeError(me.snippets.error.invalidConfig);

            return undefined;
        }

        me.createPaymentRequest(
            stripePublicKey,
            config.countryCode,
            config.currencyCode,
            config.statementDescriptor || null,
            config.amount
        );

        // Save the original submit button content and add a listiner on the preloader event to be able to reset it
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
     * Creates the Apple Pay payment request and evaluates whether Apple Pay is available in the current
     * browser environment.
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
        });

        // Check for availability of Apple Pay
        me.paymentRequest.canMakePayment().then(function (result) {
            me.applePayAvailable = result && result.applePay;
            if (!me.applePayAvailable) {
                me.handleStripeError(me.snippets.error.notAvailable);
            }
        });

        // Add listener for Apple Pay token creation
        me.paymentRequest.on('token', function (paymentResponse) {
            me.applePayToken = paymentResponse.token.id;

            // Complete the browser's payment flow
            paymentResponse.complete('success');

            // Add the created Stripe token to the form and submit it
            var form = me.findForm();
            $('input[name="stripeApplePayToken"]').remove();
            $('<input type="hidden" name="stripeApplePayToken" />')
                .val(me.applePayToken)
                .appendTo(form);
            form.submit();
        });

        // Add listener for cancelled payment flow
        me.paymentRequest.on('cancel', function () {
            me.applePayToken = null;
            me.shouldResetSubmitButton = true;
            me.handleStripeError(me.snippets.error.paymentCancelled);
        });
    },

    /**
     * First validates the form and payment state and, if the main form can be submitted, does nothing further. If
     * however the main form cannot be submitted, because no Apple Pay token exist, the Apple Pay flow is triggered.
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

        // Check if a Stripe Apple Pay token was generated and hence the form can be submitted
        if (me.applePayToken) {
            return undefined;
        }

        // Prevent the form from being submitted until a new Stripe Apple Pay token is generated and received
        event.preventDefault();

        // Check for general availability of Apple Pay
        if (!me.applePayAvailable) {
            me.shouldResetSubmitButton = true;
            me.handleStripeError(me.snippets.error.notAvailable);

            return undefined;
        }

        // We have to manually check whether this site is served via HTTPS before checking the Apple Pay availability
        // using Stripe.js. Even though Stripe.js checks the used protocol and declines the payment if not served via
        // HTTPS, only a generic 'not available' error message is returned and the HTTPS warning is logged to the
        // console. We however want to show a specific error message that informs about the lack of security.
        if (!me.isSecureConnection()) {
            me.shouldResetSubmitButton = true;
            me.handleStripeError(me.snippets.error.connectionNotSecure);

            return undefined;
        }

        $('#stripe-payment-apple-pay-error-box').hide();

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
        var errorBox = $('#stripe-payment-apple-pay-error-box');
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
