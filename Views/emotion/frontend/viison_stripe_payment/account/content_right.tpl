{extends file='parent:frontend/account/content_right.tpl'}

{block name='frontend_account_content_right_payment' append}
	{* Add Stripe credit card management *}
	<li>
		<a href="{url controller='ViisonStripePaymentAccount' action='manageCreditCards'}">
			{s namespace='frontend/plugins/viison_stripe/account' name='credit_cards/title'}{/s}
		</a>
	</li>
{/block}
