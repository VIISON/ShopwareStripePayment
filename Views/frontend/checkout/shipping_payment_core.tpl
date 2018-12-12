{extends file="parent:frontend/checkout/shipping_payment_core.tpl"}

{block name="frontend_checkout_shipping_payment_core_payment_fields"}
    {include file="frontend/checkout/stripe_payment_error.tpl"}
    
    {$smarty.block.parent}
{/block}
