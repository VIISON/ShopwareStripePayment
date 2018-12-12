// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

/**
 * A common utility object for handling card payments using Stripe.js in the StripePayment plugin.
 */
var StripePaymentCard = {

    /**
     * The Stripe.js instance used e.g. for creating form fields and generating tokens.
     */
    stripeClient: null,

    /**
     * An array of all available Stripe elements.
     */
    stripeElements: [],

    /**
     * The currently selected or created Stripe card. You should not set this property
     * directly but use setSelectedCard() instead.
     */
    selectedCard: null,

    /**
     * An array of cards available for selection.
     */
    allCards: [],

    /**
     * An object containing the names of all fields that are currently invalid and their resepctive error messages.
     */
    invalidFields: {},

    /**
     * The locale used to configure Stripe error messsages and placeholders.
     */
    locale: 'en',

    /**
     * The snippets used for Stripe error descriptions.
     */
    snippets: {
        error: {
            title: 'Error'
        }
    },

    /**
     * Initializes the Stripe service using the given public key, saves the values of the given config in this object
     * and triggers the initial setup of the payment form. Finally, a listener on the event for changing the method is
     * added, which will trigger the form setup again.
     *
     * @param String stripePublicKey
     * @param Object config
     */
    init: function(stripePublicKey, config) {
        var me = this;
        me.stripeClient = Stripe(stripePublicKey);
        // Save config
        me.setSelectedCard((typeof config.card !== 'undefined') ? config.card : null);
        me.allCards = (typeof config.allCards !== 'undefined') ? config.allCards : [];
        me.locale = config.locale || me.locale;

        // Setup form
        me.setupForm();

        // Add listener on changes of the selected payment method to setup the form again
        $.subscribe('plugin/swShippingPayment/onInputChanged', function() {
            me.setupForm();
        });
    },

    /**
     * Sets up the payment form by first unounting all Stripe elements that might be already mounted to the DOM and
     * clearing all validation errors. Then, if a stripe card payment method is selected, mounts new Stripe Elements
     * fields to the form and adds some observers to other fields as well as the form.
     */
    setupForm: function() {
        // Reset form
        this.unmountStripeElements();
        this.invalidFields = [];
        this.updateValidationErrors();

        if (this.getActiveStripeCardForm()) {
            // Mount Stripe form fields again to the now active form and add other observers
            this.mountStripeElements();
            this.observeForm();

            // Make sure the card selection matches the internal state
            if (this.selectedCard) {
                this.formEl('.stripe-saved-cards').val(this.selectedCard.id);
            }
            this.formEl('.stripe-saved-cards').trigger('change');
        }
    },

    /**
     * Creates the Stripe Elements fields for card number, expiry and CVC and mounts them to their resepctive nodes in
     * the active Stripe card payment form.
     */
    mountStripeElements: function() {
        var me = this;
        // Define options to apply to all fields when creating them
        var cardHolderFieldEl = me.formEl('.stripe-card-holder');
        var defaultOptions = {
            style: {
                base: {
                    color: cardHolderFieldEl.css('color'),
                    fontFamily: cardHolderFieldEl.css('font-family'),
                    fontSize: cardHolderFieldEl.css('font-size'),
                    fontWeight: cardHolderFieldEl.css('font-weight'),
                    lineHeight: cardHolderFieldEl.css('line-height'),
                }
            }
        };

        // Define a closure to create all elements using the same 'Elements' instance
        var elements = me.stripeClient.elements({
            locale: me.locale
        });
        var createAndMountStripeElement = function(type, mountSelector) {
            // Create the element and add the change listener
            var element = elements.create(type, defaultOptions);
            element.on('change', function(event) {
                if (event.error && event.error.type === 'validation_error') {
                    me.markFieldInvalid(type, event.error.code, event.error.message);
                } else {
                    me.markFieldValid(type);
                }
            });

            // Mount it to the DOM
            var mountElement = me.formEl(mountSelector).get(0);
            element.mount(mountElement);

            return element;
        };

        // Create all elements
        me.stripeElements = [
            createAndMountStripeElement('cardNumber', '.stripe-element-card-number'),
            createAndMountStripeElement('cardExpiry', '.stripe-element-card-expiry'),
            createAndMountStripeElement('cardCvc', '.stripe-element-card-cvc')
        ];
    },

    /**
     * Unmounts all existing Stripe elements from the Stripe card payment form they are currently mounted to.
     */
    unmountStripeElements: function() {
        this.stripeElements.forEach(function(element) {
            element.unmount();
        });
        this.stripeElements = [];
    },

    /**
     * Adds change listeners to the card selection and card holder field as well as a submission listener on the main
     * payment form.
     */
    observeForm: function() {
        // Add listeners
        this.findForm().on('submit', { scope: this }, this.onFormSubmission);
        this.formEl('.stripe-saved-cards').on('change', { scope: this }, this.onCardSelectionChange);

        // Save the current value and add listener
        var cardHolderElem = this.formEl('.stripe-card-holder');
        cardHolderElem.data('oldVal', cardHolderElem.val());
        cardHolderElem.on('propertychange keyup input paste', { scope: this }, this.onCardHolderChange);
    },

    /**
     * Removes all validation errors for the field with the given 'fieldId' and triggers an update of the displayed
     * validation errors.
     *
     * @param String fieldId
     */
    markFieldValid: function(fieldId) {
        delete this.invalidFields[fieldId];
        this.updateValidationErrors();
    },

    /**
     * Determines the error message based on the given 'errorCode' and 'message' and triggers
     * an update of the displayed validation errors.
     *
     * @param String fieldId
     * @param String errorCode (optional) The code used to find a localised error message.
     * @param String message (optioanl) The fallback error message used in case no 'errorCode' is provided or no
     *        respective, localised description exists.
     */
    markFieldInvalid: function(fieldId, errorCode, message) {
        this.invalidFields[fieldId] = this.snippets.error[errorCode || ''] || message || 'Unknown error';
        this.updateValidationErrors();
    },

    /**
     * Checks the list of invalid fields for any entries and, if found, joins them to an error message, which is then
     * displayed in the error box. If no invalid fields are found, the error box is hidden.
     */
    updateValidationErrors: function() {
        var me = this,
            errorBox = me.formEl('.stripe-payment-validation-error-box'),
            boxContent = errorBox.find('.error-content');
        boxContent.empty();
        if (Object.keys(me.invalidFields).length > 0) {
            // Update the error box message and make it visible
            var listEl = $('<ul></ul>')
                .addClass('alert--list')
                .appendTo(boxContent);
            Object.keys(me.invalidFields).forEach(function(key) {
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
     * Saves the given card and removes all hidden Stripe fields from the form. If the card exists, its ID as well as
     * its encoded data are added to the form as hidden fields.
     *
     * @param card A Stripe card object.
     */
    setSelectedCard: function(card) {
        this.selectedCard = card;
        // Remove the hidden card field from the main form
        $('input[name="stripeSelectedCard"]').remove();
        if (this.selectedCard) {
            // Add the data of the new card to the form
            $('<input type="hidden" name="stripeSelectedCard" />')
                .val(JSON.stringify(this.selectedCard))
                .appendTo(this.findForm());
        }
    },

    /**
     * First validates the form and payment state and, if the main form can be submitted, does nothing further.
     * If however the main form cannot be submitted, because no card is selected (or no token was created), a new Stripe
     * card and token are generated using the entered card data and saved in the form, before the submission is
     * triggered again.
     *
     * @param Event event
     */
    onFormSubmission: function(event) {
        var me = event.data.scope,
            form = $(this);

        // Check if a token/card was generated and hence the form can be submitted
        if (me.selectedCard) {
            return undefined;
        }

        // Prevent the form from being submitted until a new Stripe token is generated and received
        event.preventDefault();

        // Check for invalid fields
        if (Object.keys(me.invalidFields).length > 0) {
            return;
        }

        // Send the credit card information to Stripe
        me.setSubmitButtonsLoading();
        me.stripeClient.createToken(me.stripeElements[0], {
            name: me.formEl('.stripe-card-holder').val()
        }).then(function(result) {
            if (result.error) {
                // Only reset the submit buttons in case of an error, because otherwise the form is submitted again
                // right aways and hence we want the buttons to stay disabled
                me.resetSubmitButtons();

                // Display the error
                var message = me.snippets.error[result.error.code || ''] || result.error.message || 'Unknown error';
                me.handleStripeError(me.snippets.error.title + ': ' + message);
            } else {
                // Save the card information
                var card = result.token.card;
                card.token_id = result.token.id;
                me.setSelectedCard(card);

                // Save whether to save the credit card for future checkouts
                $('input[name="stripeSaveCard"]').remove();
                $('<input type="hidden" name="stripeSaveCard" />')
                    .val(me.formEl('.stripe-save-card').is(':checked') ? 'on' : 'off')
                    .appendTo(form);

                // Submit the form again to finish the payment process
                form.submit();
            }
        });
    },

    /**
     * Adds a subscriber to the card holder form field that is fired when its value is changed to validate the
     * entered value.
     *
     * @param Object event
     */
    onCardHolderChange: function(event) {
        var me = event.data.scope,
            elem = $(this);
        // Check if value has changed
        if (elem.data('oldVal') == elem.val()) {
            return;
        }
        elem.data('oldVal', elem.val());

        // Validate the field
        if (elem.val().trim().length === 0) {
            elem.addClass('instyle_error has--error');
            me.markFieldInvalid('cardHolder', 'invalid_card_holder');
        } else {
            elem.removeClass('instyle_error has--error');
            me.markFieldValid('cardHolder');
        }
    },

    /**
     * Adds a change observer to the card selection field. If an existing card is selected, all form fields are hidden
     * and the card's Stripe information is added to the form. If the 'new' option is selected, all fields made visible
     * and the Stripe card info is removed from the form.
     *
     * @param Object event
     */
    onCardSelectionChange: function(event) {
        var me = event.data.scope,
            elem = $(this);
        if (elem.val() === 'new') {
            // A new, empty card was selected
            me.setSelectedCard(null);

            // Make validation errors visible
            me.updateValidationErrors();

            // Show the save check box
            me.formEl('.stripe-card-field').show();
            me.formEl('.stripe-save-card').show().prop('checked', true);

            return;
        }

        // Find the selected card
        for (var i = 0; i < me.allCards.length; i++) {
            var selectedCard = me.allCards[i];
            if (selectedCard.id !== elem.val()) {
                continue;
            }

            // Save the card
            me.setSelectedCard(selectedCard);

            // Hide validation errors
            me.formEl('.stripe-payment-validation-error-box').hide();

            // Hide all card fields
            me.formEl('.stripe-card-field').hide();
            me.formEl('.stripe-save-card').hide();

            break;
        }
    },

    /**
     * Finds both submit buttons on the page and adds the 'disabled' attribute as well as the loading indicator to each
     * of them.
     */
    setSubmitButtonsLoading: function() {
        // Reset the button first to prevent it from being added multiple loading indicators
        this.resetSubmitButtons();
        $('#shippingPaymentForm button[type="submit"], .confirm--actions button[form="shippingPaymentForm"]').each(function() {
            $(this).html($(this).text() + '<div class="js--loading"></div>').attr('disabled', 'disabled');
        });
    },

    /**
     * Finds both submit buttons on the page and resets them by removing the 'disabled' attribute as well as the
     * loading indicator.
     */
    resetSubmitButtons: function() {
        $('#shippingPaymentForm button[type="submit"], .confirm--actions button[form="shippingPaymentForm"]').each(function() {
            $(this).removeAttr('disabled').find('.js--loading').remove();
        });
    },

    /**
     * Sets the given message in the general error box and scrolls the page to make it visible.
     *
     * @param String message A Stripe error message.
     */
    handleStripeError: function(message) {
        // Display the error information above the credit card form and scroll to its position
        this.formEl('.stripe-payment-error-box').show().children('.error-content').html(message);
        $('body').animate({
            scrollTop: (this.getActiveStripeCardForm().offset().top - 100)
        }, 500);
    },

    /**
     * Tries to find a stripe card form for the currently active payment method. That is, if a stripe card payment
     * method is selected, its form is returned, otherwise returns null.
     *
     * @return jQuery|null
     */
    getActiveStripeCardForm: function() {
        var form = $('input[id^="payment_mean"]:checked').closest('.payment--method').find('.stripe-payment-card-form');

        return (form.length > 0) ? form.first() : null;
    },

    /**
     * Applies a jQuery query on the DOM tree under the active stripe card form using the given selector. This method
     * should be used when selecting any fields that are part of a Stripe card payment form. If no Stripe card form is
     * active, an empty query result is returned.
     *
     * @param String selector
     * @return jQuery
     */
    formEl: function(selector) {
        var form = this.getActiveStripeCardForm();
        return (form) ? form.find(selector) : $('stripe_payment_card_not_found');
    },

    /**
     * @return jQuery The main payment selection form element.
     */
    findForm: function() {
        return $('#shippingPaymentForm');
    },
};
