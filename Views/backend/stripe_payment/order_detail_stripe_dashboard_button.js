// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

/**
 * Overrides the backend order detail overview to provide an extra button
 * for opening Stripe payments in the Stripe dashboard.
 */
//{block name="backend/order/view/detail/overview"}
    //{$smarty.block.parent}

Ext.define('Shopware.apps.StripePayment.Order.view.detail.Overview.StripeDashboardButton', {

    override: 'Shopware.apps.Order.view.detail.Overview',

    /**
     * Adds the order's transaction ID to the render data of the payment container.
     *
     * @return The newly created payment container.
     */
    createPaymentContainer: function() {
        var container = this.callParent(arguments);
        if (container) {
            // Append the transaction ID to the render data
            var template = container.items.first();
            template.renderData['stripeChargeId'] = this.record.get('transactionId');
            template.renderData['stripeButtonTitle'] = '{s namespace="backend/stripe_payment/order_detail_stripe_dashboard_button" name="open_dashboard"}{/s}';
        }

        return container;
    },

    /**
     * Replaces the default template for Stripe payments, to add a button that
     * directly links to the resepctive charge in the Stripe dashboard.
     *
     * @return The created template.
     */
    createPaymentTemplate: function() {
        // Check for stripe payment
        if (this.record.getPayment().first() && this.record.getPayment().first().raw.action === 'StripePayment') {
            // Use the custom template
            return new Ext.XTemplate(
                '{literal}<tpl for=".">',
                    '<div class="customer-info-pnl">',
                        '<div class="base-info">',
                            '<p>',
                                '<span>{description}</span>',
                            '</p>',
                            '<p class="stripe-payment" style="margin-top: 20px;">',
                                '<a href="https://dashboard.stripe.com/payments/{stripeChargeId}" target="_blank" class="stripe-button">',
                                    '{stripeButtonTitle}',
                                '</a>',
                            '</p>',
                        '</div>',
                    '</div>',
                '</tpl>{/literal}'
            );
        }

        // Use the default template
        return this.callParent(arguments);
    }

});
//{/block}
