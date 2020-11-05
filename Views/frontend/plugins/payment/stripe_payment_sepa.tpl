{if $Controller != "account" && $payment_mean.class == "StripePaymentSepa"}
    {* The main container for filling in the bank account information *}
    <div class="stripe-payment-sepa-form payment--form-group">
        {* A box for displaying general errors *}
        <div class="stripe-payment-error-box alert is--error is--rounded" style="display: none;">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content error-content"></div>
        </div>

        {* The main form field table *}
        <div class="panel--table">
            {* IBAN *}
            <div class="panel--tr stripe-sepa-field">
                <label>
                    <span class="panel--td">{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="form/iban"}{/s} *</span>
                    <div class="stripe-element-sepa-iban panel--td"><!-- Stripe element is inserted here --></div>
                </label>
            </div>
            {* Account owner *}
            <div class="panel--tr stripe-sepa-field">
                <label>
                    <span class="panel--td">{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="form/account_owner"}{/s} *</span>
                    <input type="text" class="stripe-sepa-account-owner panel--td is--required" name="account_owner" required="required" value="{$sUserData.billingaddress.firstname} {$sUserData.billingaddress.lastname}">
                </label>
            </div>
            {* Street *}
            <div class="panel--tr stripe-sepa-field">
                <label>
                    <span class="panel--td">{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="form/street"}{/s} *</span>
                    <input type="text" class="stripe-sepa-street panel--td is--required" name="street" required="required" value="{$sUserData.billingaddress.street}{if $sUserData.billingaddress.streetnumber} {$sUserData.billingaddress.streetnumber}{/if}">
                </label>
            </div>
            {* ZIP code *}
            <div class="panel--tr stripe-sepa-field">
                <label>
                    <span class="panel--td">{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="form/zip_code"}{/s} *</span>
                    <input type="text" class="stripe-sepa-zip-code panel--td is--required" name="zip_code" required="required" value="{$sUserData.billingaddress.zipcode}">
                </label>
            </div>
            {* City *}
            <div class="panel--tr stripe-sepa-field">
                <label>
                    <span class="panel--td">{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="form/city"}{/s} *</span>
                    <input type="text" class="stripe-sepa-city panel--td is--required" name="city" required="required" value="{$sUserData.billingaddress.city}">
                </label>
            </div>
            {* Country *}
            <div class="panel--tr stripe-sepa-field country-selection">
                <label>
                    <span class="panel--td">{s namespace="frontend/plugins/payment/stripe_payment/sepa" name="form/country"}{/s} *</span>
                    <div class="select-field">
                        <select class="stripe-sepa-country panel--td is--required" name="country" required="required">
                            {foreach $stripePayment.sepaCountryList as $country}
                                <option value="{$country.countryiso}"{if $country.id eq $sUserData.billingaddress.countryId} selected="selected"{/if}>
                                    {$country.countryname}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </label>
            </div>
        </div>

        {* A box for displaying validation errors *}
        <div class="stripe-payment-validation-error-box alert is--error is--rounded" style="display: none;">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content error-content"></div>
        </div>

        {* A box for displaying the SEPA mandate authorization text *}
        <div class="alert is--info is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--info"></i>
            </div>
            <div class="alert--content">
                {capture name=stripePaymentSepaMandateInfo}
                    {s namespace="frontend/plugins/payment/stripe_payment/sepa" name="form/mandate_info"}{/s}
                {/capture}
                {$smarty.capture.stripePaymentSepaMandateInfo|replace:"[creditor]":$stripePayment.sepaCreditor}
            </div>
        </div>

        {* Info *}
        <div class="description">
            {s namespace="frontend/plugins/payment/stripe_payment/sepa" name="form/description"}{/s}
        </div>
    </div>
{/if}
