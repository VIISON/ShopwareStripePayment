{extends file="parent:frontend/account/sidebar.tpl"}

{block name="frontend_account_menu_link_payment"}
    {$smarty.block.parent}

    <li class="navigation--entry">
        <a href="{url controller='StripePaymentAccount' action='manageCreditCards'}" title="{s namespace='frontend/plugins/stripe_payment/account' name='credit_cards/title'}{/s}" class="navigation--link{if $sAction == 'manageCreditCards'} is--active{/if}">
            {s namespace='frontend/plugins/stripe_payment/account' name='credit_cards/title'}{/s}
        </a>
    </li>
{/block}
