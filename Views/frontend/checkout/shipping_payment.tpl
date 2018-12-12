{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name="frontend_index_header"}
    {$smarty.block.parent}

    <style type="text/css">
        {* Include shared CSS for payment provider logo SVGs *}
        {include file="frontend/_public/css/stripe_payment_provider_logos.css"}
    </style>
{/block}

{block name="frontend_index_header_javascript_jquery"}
    {$smarty.block.parent}

    <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        {**
         * Uncomment the following lines the speed up development by including the custom
         * Stripe payment libraries instead of loading it from the compiled Javascript file
         *}
        {* {include file="frontend/_public/src/javascript/stripe_payment_card.js"} *}
        {* {include file="frontend/_public/src/javascript/stripe_payment_sepa.js"} *}

        document.stripeJQueryReady(function() {
            // Fix selectbox replacement for dynamically loaded payment forms
            // See also: https://github.com/shopware/shopware/pull/357
            $.subscribe('plugin/swShippingPayment/onInputChanged', function(event, shippingPayment) {
                shippingPayment.$el.find('select:not([data-no-fancy-select="true"])').swSelectboxReplacement();
                shippingPayment.$el.find('.stripe-card-cvc--help').swModalbox();
            });

            var stripePublicKey = '{$stripePayment.publicKey}';

            // Define the StripePaymentCard configuration
            var stripePaymentCardSnippets = {
                error: {
                    api_connection_error: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/api_connection_error}{/stripe_snippet}',
                    card_declined: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/card_declined}{/stripe_snippet}',
                    expired_card: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/expired_card}{/stripe_snippet}',
                    incomplete_card: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/incomplete_card}{/stripe_snippet}',
                    incomplete_cvc: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/incomplete_cvc}{/stripe_snippet}',
                    incomplete_expiry: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/incomplete_expiry}{/stripe_snippet}',
                    incomplete_number: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/incomplete_number}{/stripe_snippet}',
                    incorrect_cvc: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/incorrect_cvc}{/stripe_snippet}',
                    incorrect_number: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/incorrect_number}{/stripe_snippet}',
                    invalid_card_holder: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/invalid_card_holder}{/stripe_snippet}',
                    invalid_cvc: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/invalid_cvc}{/stripe_snippet}',
                    invalid_expiry_month: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/invalid_expiry_month}{/stripe_snippet}',
                    invalid_expiry_month_past: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/invalid_expiry_month_past}{/stripe_snippet}',
                    invalid_expiry_year: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/invalid_expiry_year}{/stripe_snippet}',
                    invalid_expiry_year_past: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/invalid_expiry_year_past}{/stripe_snippet}',
                    invalid_number: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/invalid_number}{/stripe_snippet}',
                    processing_error: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/processing_error}{/stripe_snippet}',
                    processing_error_intransient: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/processing_error_intransient}{/stripe_snippet}',
                    title: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/title}{/stripe_snippet}',
                    unexpected: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/card name=error/unexpected}{/stripe_snippet}'
                }
            };
            var stripePaymentCardConfig = {
                locale: '{$stripePayment.locale}'
            };
            if ('{$stripePayment.rawSelectedCard}') {
                stripePaymentCardConfig.card = JSON.parse('{$stripePayment.rawSelectedCard}');
            }
            if ('{$stripePayment.rawAvailableCards}') {
                stripePaymentCardConfig.allCards = JSON.parse('{$stripePayment.rawAvailableCards}');
            }

            // Initialize StripePaymentCard once the DOM is ready
            $(document).ready(function() {
                StripePaymentCard.snippets = stripePaymentCardSnippets;
                StripePaymentCard.init(stripePublicKey, stripePaymentCardConfig);
            });

            // Define the StripePaymentSepa configuration
            var stripePaymentSepaSnippets = {
                error: {
                    incomplete_iban: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/incomplete_iban}{/stripe_snippet}',
                    invalid_account_owner: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_account_owner}{/stripe_snippet}',
                    invalid_city: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_city}{/stripe_snippet}',
                    invalid_country: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_country}{/stripe_snippet}',
                    invalid_iban: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_iban}{/stripe_snippet}',
                    invalid_iban_country_code: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_iban_country_code}{/stripe_snippet}',
                    invalid_street: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_street}{/stripe_snippet}',
                    invalid_zip_code: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_zip_code}{/stripe_snippet}',
                    title: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/title}{/stripe_snippet}',
                }
            };
            var stripePaymentSepaConfig = {
                currency: '{$stripePayment.currency}',
                locale: '{$stripePayment.locale}',
            };
            if ('{$stripePayment.rawSepaSource}') {
                stripePaymentSepaConfig.sepaSource = JSON.parse('{$stripePayment.rawSepaSource}');
            }

            // Initialize StripePaymentSepa once the DOM is ready
            $(document).ready(function() {
                StripePaymentSepa.snippets = stripePaymentSepaSnippets;
                StripePaymentSepa.init(stripePublicKey, stripePaymentSepaConfig);
            });
        });
    </script>
{/block}
