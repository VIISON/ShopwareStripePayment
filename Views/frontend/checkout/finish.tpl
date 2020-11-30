{extends file="parent:frontend/checkout/finish.tpl"}

{block name="frontend_checkout_finish_items"}
    {if $stripePaymentSepatMandateUrl}
        <div class="finish--teaser panel stripe-payment-sepa-mandate">
            <div class="alert is--info is--rounded">
                <div class="alert--icon">
                    <i class="icon--element icon--info"></i>
                </div>
                <div class="alert--content">
                    <div class="info">
                        <strong>{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="checkout/finish/mandate_info/title"}{/s}</strong>
                        <p>{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="checkout/finish/mandate_info/text"}{/s}</p>
                    </div>
                    <a class="btn is--link" href="{$stripePaymentSepatMandateUrl|escape:"htmlall"}" target="_blank">{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="checkout/finish/mandate_info/open_mandate_button/title"}{/s}</a>
                </div>
            </div>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}
