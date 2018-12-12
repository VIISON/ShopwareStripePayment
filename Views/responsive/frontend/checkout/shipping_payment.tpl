{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name="frontend_index_header"}
    {$smarty.block.parent}

    <style type="text/css">
        {* Include shared CSS for payment provider logo SVGs *}
        {include file="frontend/stripe_payment/_resources/styles/stripe_payment_provider_logos.css"}
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
        {* {include file="frontend/stripe_payment/_resources/javascript/stripe_payment_card.js"} *}
        {* {include file="frontend/stripe_payment/_resources/javascript/stripe_payment_sepa.js"} *}

        document.stripeJQueryReady(function() {
            // Fix selectbox replacement for dynamically loaded payment forms
            // See also: https://github.com/shopware/shopware/pull/357
            $.subscribe('plugin/swShippingPayment/onInputChanged', function(event, shippingPayment) {
                shippingPayment.$el.find('select:not([data-no-fancy-select="true"])').swSelectboxReplacement();
                shippingPayment.$el.find('.stripe-card-cvc--help').swModalbox();
            });

            // Include the shared initialization of the StripePaymentCard and StripePaymentSepa libraries
            {include file='frontend/stripe_payment/checkout/stripe_payment_card/header.js'}
            {include file='frontend/stripe_payment/checkout/stripe_payment_sepa/header.js'}
        });
    </script>
{/block}
