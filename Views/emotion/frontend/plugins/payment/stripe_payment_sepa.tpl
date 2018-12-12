{if $Controller != "account" && $payment_mean.class == "StripePaymentSepa"}
    {* Include the custom styles for the payment form *}
    <link href="{link file='frontend/stripe_payment/_resources/styles/stripe_payment_sepa.css'}" rel="stylesheet">
    {* Include and set up the Stripe SDK *}
    <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
    <script type="text/javascript" src="{link file="frontend/stripe_payment/_resources/javascript/stripe_payment_sepa.js"}"></script>
    <script type="text/javascript">
        // Include the shared initialization of the StripePaymentSepa library
        {include file='frontend/stripe_payment/checkout/stripe_payment_sepa/header.js'}
    </script>

    {* The main container for filling in the SEPA information *}
    <div class="stripe-payment-sepa-form">
        {* A box for displaying general errors *}
        <div class="stripe-payment-error-box" style="display: none;">
            <div class="error-content"></div>
        </div >

        {* IBAN *}
        <div class="form-group stripe-sepa-field">
            <label>
                <span class="control-label">{s namespace=frontend/plugins/payment/stripe_payment/sepa name=form/iban}{/s} *</span>
                <div class="stripe-element-sepa-iban form-input"><!-- Stripe element is inserted here --></div>
            </label>
        </div>
        {* Account owner *}
        <div class="form-group stripe-sepa-field">
            <label>
                <span class="control-label">{s namespace=frontend/plugins/payment/stripe_payment/sepa name=form/account_owner}{/s} *</span>
                <input type="text" class="stripe-sepa-account-owner form-input text" name="account_owner" required="required" value="{$sUserData.billingaddress.firstname} {$sUserData.billingaddress.lastname}">
            </label>
        </div>
        {* Street *}
        <div class="form-group stripe-sepa-field">
            <label>
                <span class="control-label">{s namespace=frontend/plugins/payment/stripe_payment/sepa name=form/street}{/s} *</span>
                <input type="text" class="stripe-sepa-street form-input text" name="street" required="required" value="{$sUserData.billingaddress.street}{if $sUserData.billingaddress.streetnumber} {$sUserData.billingaddress.streetnumber}{/if}">
            </label>
        </div>
        {* ZIP code *}
        <div class="form-group stripe-sepa-field">
            <label>
                <span class="control-label">{s namespace=frontend/plugins/payment/stripe_payment/sepa name=form/zip_code}{/s} *</span>
                <input type="text" class="stripe-sepa-zip-code form-input text" name="zip_code" required="required" value="{$sUserData.billingaddress.zipcode}">
            </label>
        </div>
        {* City *}
        <div class="form-group stripe-sepa-field">
            <label>
                <span class="control-label">{s namespace=frontend/plugins/payment/stripe_payment/sepa name=form/city}{/s} *</span>
                <input type="text" class="stripe-sepa-city form-input text" name="city" required="required" value="{$sUserData.billingaddress.city}">
            </label>
        </div>
        {* Country *}
        <div class="form-group stripe-sepa-field country-selection">
            <label>
                <span class="control-label">{s namespace=frontend/plugins/payment/stripe_payment/sepa name=form/country}{/s} *</span>
                <select class="stripe-sepa-country form-input adjust-margin" name="country" required="required">
                    {foreach $stripePayment.sepaCountryList as $country}
                        <option value="{$country.countryiso}"{if $country.id eq $sUserData.billingaddress.countryId} selected="selected"{/if}>
                            {$country.countryname}
                        </option>
                    {/foreach}
                </select>
            </label>
        </div>

        {* A box for displaying validation errors *}
        <div class="stripe-payment-validation-error-box" style="display: none;">
            <div class="error-content"></div>
        </div>

        {* A box for displaying the SEPA mandate authorization text *}
        <div class="stripe-payment-sepa-mandate-authorization">
            {capture name=stripePaymentSepaMandateInfo}
                {s namespace=frontend/plugins/payment/stripe_payment/sepa name=form/mandate_info}{/s}
            {/capture}
            {$smarty.capture.stripePaymentSepaMandateInfo|replace:"[creditor]":$stripePayment.sepaCreditor}
        </div>

        {* Info *}
        <div class="form-group adjust-margin">
            <p>{s namespace=frontend/plugins/payment/stripe_payment/sepa name=form/description}{/s}</p>
        </div>
    </div>
{/if}
