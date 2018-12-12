// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

var stripePublicKey = '{$stripePayment.publicKey}';
var stripePaymentApplePaySnippets = {
    error: {
        connectionNotSecure: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/apple_pay name=error/connection_not_secure}{/stripe_snippet}',
        invalidConfig: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/apple_pay name=error/invalid_config}{/stripe_snippet}',
        notAvailable: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/apple_pay name=error/not_available}{/stripe_snippet}',
        paymentCancelled: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/apple_pay name=error/payment_cancelled}{/stripe_snippet}',
        title: '{stripe_snippet namespace=frontend/plugins/payment/stripe_payment/apple_pay name=error/title}{/stripe_snippet}',
    }
};
var stripePaymentApplePayConfig = {
    countryCode: '{$sUserData.additional.country.countryiso}',
    currencyCode: '{$stripePayment.currency}',
    statementDescriptor: '{$stripePayment.applePayStatementDescriptor}',
    amount: '{$sAmount}',
};

// Initialize StripePaymentApplePay once the DOM is ready
$(document).ready(function() {
    StripePaymentApplePay.snippets = stripePaymentApplePaySnippets;
    StripePaymentApplePay.init(stripePublicKey, stripePaymentApplePayConfig);
});
