{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_error_messages"}
    {$smarty.block.parent}

    {include file="frontend/checkout/stripe_payment_error.tpl"}

    {if $sUserData.additional.payment.class == "StripePaymentDigitalWallets"}
        {* Add a hidden error message component *}
        <div id="stripe-payment-payment-request-api-error-box" class="alert is--error is--rounded" style="display: none;">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content error-content"></div>
        </div>
    {/if}
{/block}

{block name="frontend_index_header_javascript_jquery"}
    {$smarty.block.parent}

    {if ($sUserData.additional.payment.class == "StripePaymentCard" && $stripePayment.selectedCard) || ($sUserData.additional.payment.class == "StripePaymentSepa" && $stripePayment.sepaSource)}
        <script type="text/javascript">
            document.stripeJQueryReady(function() {
                // Add special class to body to trigger custom CSS rules in Shopware versions >= 5.0 and < 5.2.
                // Starting from Shopware 5.2, the enclosing container uses content based autoresizing, which
                // would break, if we enabled the custom CSS rule.
                if ($('#confirm--form .information--panel-wrapper').attr('data-panel-auto-resizer') !== 'true') {
                    $('body').addClass('is--stripe-payment-selected');
                }

                // Insert a new element right below the general payment information showing details of the selected payment method
                var paymentMethodClass = '{$sUserData.additional.payment.class|escape:"javascript"}',
                    content;
                if (paymentMethodClass === 'StripePaymentCard') {
                    // Show details of selected credit card
                    content = '{$stripePayment.selectedCard.name|escape:"javascript"|escape:"htmlall"} | {$stripePayment.selectedCard.brand|escape:"javascript"|escape:"htmlall"} | &bull;&bull;&bull;&bull;{$stripePayment.selectedCard.last4|escape:"javascript"|escape:"htmlall"} | {$stripePayment.selectedCard.exp_month|string_format:"%02d"|escape:"javascript"|escape:"htmlall"}/{$stripePayment.selectedCard.exp_year|escape:"javascript"|escape:"htmlall"}';
                } else if (paymentMethodClass === 'StripePaymentSepa') {
                    // Show details of SEPA source
                    content = '{$stripePayment.sepaSource.owner.name|escape:"javascript"|escape:"htmlall"} | {$stripePayment.sepaSource.sepa_debit.country|escape:"javascript"|escape:"htmlall"}&bull;&bull;&bull;&bull;{$stripePayment.sepaSource.sepa_debit.last4|escape:"javascript"|escape:"htmlall"} | {$stripePayment.sepaSource.sepa_debit.mandate_reference|escape:"javascript"|escape:"htmlall"}';
                }
                var element = $('<p class="stripe-payment-details is--bold">' + content + '</p>');
                element.insertAfter('.payment--panel .payment--content .payment--method-info');
            });
        </script>
    {elseif $sUserData.additional.payment.class == "StripePaymentDigitalWallets"}
        {* Include and set up the Stripe SDK *}
        <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
        <script type="text/javascript">
            {**
             * Uncomment the following line the speed up development by including the custom
             * Stripe payment library instead of loading it from the compiled Javascript file.
             *}
            {* {include file="frontend/_public/src/javascript/stripe_payment_digital_wallets.js"} *}

            document.stripeJQueryReady(function() {
                // Define the StripePaymentApplePay configuration
                var stripePublicKey = '{$stripePayment.publicKey|escape:"javascript"}';
                var stripePaymentSnippets = {
                    error: {
                        connectionNotSecure: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/digital_wallets name=error/connection_not_secure}{/stripe_snippet}',
                        invalidConfig: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/digital_wallets name=error/invalid_config}{/stripe_snippet}',
                        notAvailable: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/digital_wallets name=error/not_available}{/stripe_snippet}',
                        paymentCancelled: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/digital_wallets name=error/payment_cancelled}{/stripe_snippet}',
                        title: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/digital_wallets name=error/title}{/stripe_snippet}',
                    },
                    shippingCost: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/digital_wallets name=shipping_cost}{/stripe_snippet}',
                };
                var paymentMethod = '{$sUserData.additional.payment.name|escape:"javascript"}'.replace('stripe_payment_', '');
                var paymentMethodName = '';
                switch (paymentMethod) {
                    case 'apple_pay':
                        paymentMethodName = 'Apple Pay';
                        break;
                    case 'google_pay':
                        paymentMethodName = 'Google Pay';
                        break;
                }
                stripePaymentSnippets.error.connectionNotSecure = stripePaymentSnippets.error.connectionNotSecure.replace('[0]', paymentMethodName);
                stripePaymentSnippets.error.invalidConfig = stripePaymentSnippets.error.invalidConfig.replace('[0]', paymentMethodName);
                stripePaymentSnippets.error.notAvailable = stripePaymentSnippets.error.notAvailable.replace('[0]', paymentMethodName);

                var stripePaymentConfig = {
                    countryCode: '{$sUserData.additional.country.countryiso|escape:"javascript"}',
                    currencyCode: '{$stripePayment.currency|escape:"javascript"}',
                    statementDescriptor: '{$stripePayment.digitalWalletsStatementDescriptor|escape:"javascript"}',
                    amount: '{$sAmount|escape:"javascript"}',
                    basketContent: {$sBasket.content|json_encode},
                    shippingCost: {$sBasket.sShippingcosts|escape:"javascript"},
                };

                // Initialize StripePaymentDigitalWallets once the DOM is ready
                $(document).ready(function() {
                    StripePaymentDigitalWallets.snippets = stripePaymentSnippets;
                    StripePaymentDigitalWallets.init(stripePublicKey, stripePaymentConfig, paymentMethod);
                });
            });
        </script>
    {/if}
{/block}
