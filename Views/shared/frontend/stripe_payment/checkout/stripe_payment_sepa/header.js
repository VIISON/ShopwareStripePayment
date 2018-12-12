// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

var stripePublicKey = '{$stripePayment.publicKey}';
var stripePaymentSepaSnippets = {
    error: {
        incomplete_iban: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/incomplete_iban}{/stripe_snippet}',
        invalid_account_owner: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_account_owner}{/stripe_snippet}',
        invalid_city: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_city}{/stripe_snippet}',
        invalid_country: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_country}{/stripe_snippet}',
        invalid_iban: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_iban}{/stripe_snippet}',
        invalid_iban_country_code: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_iban_country_code}{/stripe_snippet}',
        invalid_street: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_street}{/stripe_snippet}',
        invalid_zip_code: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/invalid_zip_code}{/stripe_snippet}',
        title: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/sepa name=error/title}{/stripe_snippet}',
    }
};
var stripePaymentSepaConfig = {
    currency: '{$stripePayment.currency}',
    locale: '{$stripePayment.locale}',
};
if ('{$stripePayment.rawSepaSource}') {
    stripePaymentSepaConfig.sepaSource = JSON.parse('{$stripePayment.rawSepaSource}');
}

// Initialize StripePaymentSepa once the DOM is ready
$(document).ready(function() {
    StripePaymentSepa.snippets = stripePaymentSepaSnippets;
    StripePaymentSepa.init(stripePublicKey, stripePaymentSepaConfig);
});
