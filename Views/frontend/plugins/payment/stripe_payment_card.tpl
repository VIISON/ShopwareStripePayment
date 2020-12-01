{if $Controller != "account" && $payment_mean.class == "StripePaymentCard"}
    {* The main container for filling in the credit card information *}
    <div class="stripe-payment-card-form payment--form-group">
        {* A box for displaying general errors *}
        <div class="stripe-payment-error-box alert is--error is--rounded" style="display: none;">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content error-content"></div>
        </div>

        {* The main form field table *}
        <div class="panel--table">
            {if $stripePayment.allowSavingCreditCard or $stripePayment.availableCards|count > 0}
                {* Credit card selection *}
                <div class="panel--tr saved-cards">
                    <label>
                        <span class="panel--td">{s namespace=frontend/plugins/payment/stripe_payment/card name=form/card_selection}{/s}</span>
                        <div class="select-field">
                            <select class="stripe-saved-cards panel--td">
                                <option value="new" selected>
                                    {s namespace=frontend/plugins/payment/stripe_payment/card name=form/card_selection/new_card}{/s}
                                </option>
                                {foreach from=$stripePayment.availableCards item=card}
                                    <option value="{$card.id|escape:"htmlall"}">
                                        {$card.name|escape:"htmlall"} | {$card.brand|escape:"htmlall"} | &bull;&bull;&bull;&bull;{$card.last4|escape:"htmlall"} | {$card.exp_month|string_format:"%02d"|escape:"htmlall"}/{$card.exp_year|escape:"htmlall"}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </label>
                </div>
            {/if}
            {* Card holder *}
            <div class="panel--tr stripe-card-field">
                <label>
                    <span class="panel--td">{s namespace=frontend/plugins/payment/stripe_payment/card name=form/card/holder}{/s} *</span>
                    <input type="text" size="20" class="stripe-card-holder panel--td" value="{$sUserData.billingaddress.firstname|escape:"htmlall"} {$sUserData.billingaddress.lastname|escape:"htmlall"}">
                </label>
            </div>
            {* Card number *}
            <div class="panel--tr stripe-card-field">
                <label>
                    <span class="panel--td">{s namespace=frontend/plugins/payment/stripe_payment/card name=form/card/number}{/s} *</span>
                    <div class="stripe-element-card-number panel--td"><!-- Stripe element is inserted here --></div>
                </label>
            </div>
            {* Expiry date *}
            <div class="panel--tr stripe-card-field">
                <label>
                    <span class="panel--td">{s namespace=frontend/plugins/payment/stripe_payment/card name=form/card/expiry}{/s} *</span>
                    <div class="stripe-element-card-expiry panel--td"><!-- Stripe element is inserted here --></div>
                </label>
            </div>
            {* CVC *}
            <div class="panel--tr stripe-card-field">
                <label>
                    <span class="panel--td">{s namespace=frontend/plugins/payment/stripe_payment/card name=form/card/cvc}{/s} *</span>
                    <div class="stripe-element-card-cvc panel--td"><!-- Stripe element is inserted here --></div>
                </label>
                <div class="stripe-card-cvc--help help panel--td"
                    data-modalbox="true"
                    data-content="{url controller=StripePaymentCard action=cvcInfo forceSecure}"
                    data-mode="ajax"
                    data-height="430"
                    data-width="650">
                </div>
            </div>
            {if $customerAccountMode == 0 and $stripePayment.allowSavingCreditCard}
                {* Save data *}
                <div class="panel--tr stripe-card-field">
                    <label>
                        <span class="outer-checkbox">
                            <div class="checkbox">
                                <input class="stripe-save-card" type="checkbox">
                                <span class="checkbox--state"></span>
                            </div>
                        </span>
                        <span class="checkox-label">{s namespace=frontend/plugins/payment/stripe_payment/card name=form/save_card}{/s}</span>
                    </label>
                </div>
            {/if}
        </div>

        {* A box for displaying validation errors *}
        <div class="stripe-payment-validation-error-box alert is--error is--rounded" style="display: none;">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content error-content"></div>
        </div>

        {* Info *}
        <div class="description">
            {s namespace=frontend/plugins/payment/stripe_payment/card name=form/description}{/s}
        </div>
    </div>
{/if}
