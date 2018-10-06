{extends file='parent:frontend/account/content_right.tpl'}

{block name='frontend_account_content_right_payment'}
    {$smarty.block.parent}

    {* Add Stripe credit card management *}
    <li>
        <a href="{url controller='StripePaymentAccount' action='manageCreditCards'}">
            {s namespace='frontend/plugins/stripe_payment/account' name='credit_cards/title'}{/s}
        </a>
    </li>
{/block}
