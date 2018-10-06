{block name="frontend_index_header_javascript"}
    {$smarty.block.parent}

    {if $sUserData.additional.payment.class == "StripePaymentApplePay"}
        {* Include and set up the Stripe SDK *}
        <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
        <script type="text/javascript" src="{link file="frontend/stripe_payment/_resources/javascript/stripe_payment_apple_pay.js"}"></script>
        <script type="text/javascript">
            {* Include the shared initialization of the StripePaymentApplePay library *}
            {include file='frontend/stripe_payment/checkout/stripe_payment_apple_pay/header.js'}
        </script>
    {/if}
{/block}

{block name="frontend_index_header_css_screen"}
    {$smarty.block.parent}

    <style type="text/css">
        {* Include shared CSS for payment provider logo SVGs *}
        {include file="frontend/stripe_payment/_resources/styles/stripe_payment_provider_logos.css"}
    </style>
{/block}

{block name="frontend_index_content_top"}
    {$smarty.block.parent}

    {if $stripePayment.error}
        <div class="grid_20 first">
            <div class="error">
                <div class="center">{s namespace=frontend/plugins/payment/stripe_payment/base name=payment_error/title}{/s}</div>
                <br />
                <div class="normal" style="text-align: left; font-weight: normal;">{$stripePayment.error}</div>
            </div>
        </div>
    {/if}

    {if $sUserData.additional.payment.class == "StripePaymentApplePay"}
        {* Add a hidden error message component *}
        <div id="stripe-payment-apple-pay-error-box" class="grid_20 first" style="display: none;">
            <div class="error">
                <div class="error-content"></div>
            </div>
        </div>
   {/if}
{/block}
