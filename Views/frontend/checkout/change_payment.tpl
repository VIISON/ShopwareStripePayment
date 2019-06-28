{extends file="parent:frontend/checkout/change_payment.tpl"}

{block name="frontend_checkout_payment_fieldset_description"}
    {if $Controller != "account" && ($payment_mean.action == "StripePayment" || $payment_mean.action == "StripePaymentIntent") && $stripePayment.showPaymentProviderLogos}
        {* Inject the payment logos before the additional description *}
        <div class="panel--table stripe-payment-provider-logos">
            <div class="panel--tr">
            {if $payment_mean.class == "StripePaymentApplePay"}
                <div class="panel--td stripe-payment-provider-logo apple-pay"></div>
            {elseif $payment_mean.class == "StripePaymentBancontact"}
                <div class="panel--td stripe-payment-provider-logo bancontact"></div>
            {elseif $payment_mean.class == "StripePaymentCard"}
                <div class="panel--td stripe-payment-provider-logo visa"></div>
                <div class="panel--td stripe-payment-provider-logo mastercard"></div>
                <div class="panel--td stripe-payment-provider-logo american-express"></div>
            {elseif $payment_mean.class == "StripePaymentGiropay"}
                <div class="panel--td stripe-payment-provider-logo giropay"></div>
            {elseif $payment_mean.class == "StripePaymentIdeal"}
                <div class="panel--td stripe-payment-provider-logo ideal"></div>
            {elseif $payment_mean.class == "StripePaymentSepa"}
                <div class="panel--td stripe-payment-provider-logo sepa"></div>
            {elseif $payment_mean.class == "StripePaymentSofort"}
                <div class="panel--td stripe-payment-provider-logo sofort"></div>
            {/if}
            </div>
        </div>

        {* Default content *}
        <div class="method--description is--last" style="margin-top: 5px;">
            {include file="string:{$payment_mean.additionaldescription}"}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
