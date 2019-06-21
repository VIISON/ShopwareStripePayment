// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

//{namespace name=backend/plugins/stripe_payment/order_detail_position_refund}

//{block name="backend/order/view/detail/position"}
    //{$smarty.block.parent}

Ext.define('Shopware.apps.StripePayment.Order.view.detail.Position', {

    override: 'Shopware.apps.Order.view.detail.Position',

    /**
     * Add a new event, which is fired by this view.
     */
    initComponent: function() {
        this.addEvents(
            /**
             * Event will be fired when the user clicks the 'refund positions' button.
             *
             * @event openRefundWindow
             * @param [Shopware.apps.StripePayment.Order.view.detail.Position] grid
             */
            'openRefundWindow'
        );

        this.callParent(arguments);
    },

    /**
     * Adds a 'refund' button to the toolbar.
     *
     * @return The toolbar created by the parent method, including the refund button.
     */
    createGridToolbar: function() {
        var toolbar = this.callParent(arguments);

        // Check if the order was payed with Stripe
        var paymentMethod = this.record.getPayment().first();
        if (paymentMethod && (paymentMethod.raw.action === 'StripePayment' || paymentMethod.raw.action === 'StripePaymentIntent')) {
            // Add the refund button
            this.stripeRefundPositionButton = Ext.create('Ext.button.Button', {
                iconCls: 'sprite-money--minus',
                text: '{s name=order/view/detail/position/refund_button}{/s}',
                disabled: true,
                scope: this,
                handler: function() {
                    this.fireEvent('openRefundWindow', this);
                }
            });
            toolbar.add(this.stripeRefundPositionButton);
        }

        return toolbar;
    },

    /**
     * Adds a new listener to the selection model of the grid, which enables/disables
     * the refund button based on the number of selected positions.
     *
     * @return The position grid created by the parent method.
     */
    createPositionGrid: function() {
        var positionGrid = this.callParent(arguments);

        // Listen on changes in the selection model
        positionGrid.selModel.addListener('selectionchange', function (model, records) {
            if (this.stripeRefundPositionButton !== undefined) {
                // Enable/disable the refund button based on the selection
                this.stripeRefundPositionButton.setDisabled(records.length === 0);
            }
        }, this);

        return positionGrid;
    }

});
//{/block}

//{block name="backend/order/controller/detail"}
    //{$smarty.block.parent}

    // Include the refund model and window
    //{include file="backend/stripe_payment/order_detail_position_refund/item.js"}
    //{include file="backend/stripe_payment/order_detail_position_refund/window.js"}

Ext.define('Shopware.apps.StripePayment.Order.controller.Detail', {

    override: 'Shopware.apps.Order.controller.Detail',

    /**
     * Add new events, which are controlled by this controller.
     */
    init: function() {
        this.control({
            'order-detail-window order-position-panel': {
                openRefundWindow: this.onOpenRefundWindow
            },
            'stripe-payment-refund-window': {
                positionQuantityChanged: this.onPositionQuantityChanged,
                performRefund: this.onPerformRefund
            }
        });

        this.callParent(arguments);
    },

    /**
     * Collects the selected positions from the given panel and copies some of its
     * grid columns. Finally a new refund window is created and displayed.
     *
     * @param positionPanel The panel providing the positions.
     */
    onOpenRefundWindow: function(positionPanel) {
        // Collect all positions, which are currently selected and sum up their total values
        var data = [];
        var total = 0.0;
        Ext.each(positionPanel.orderPositionGrid.selModel.getSelection(), function(record) {
            if (record.get('quantity') <= 0) {
                return;
            }
            data.push({
                id: record.get('id'),
                articleNumber: record.get('articleNumber'),
                articleName: record.get('articleName'),
                quantity: record.get('quantity'),
                price: record.get('price'),
                total: record.get('total')
            });
            total += record.get('total');
        }, this);

        // Create a new store with the collected positions
        var store = Ext.create('Ext.data.Store', {
            model: 'Shopware.apps.StripePayment.Order.model.detail.position.refund.Item',
            data: data
        });

        // Create and open a new window with the store, columns and total amount
        var refundWindow = Ext.create('Shopware.apps.StripePayment.Order.view.detail.position.refund.Window', {
            orderRecord: positionPanel.record,
            store: store,
            total: total
        });
        refundWindow.show();
    },

    /**
     * Updates the total amount of the changed position as well as the
     * total refund amount.
     *
     * @param refundWindow The window firing the event.
     * @param record The changed record.
     */
    onPositionQuantityChanged: function(refundWindow, record) {
        // Update item total
        var newItemTotal = record.get('quantity') * record.get('price');
        record.set('total', newItemTotal);

        // Update overall total
        var newOverallTotal = 0;
        refundWindow.store.each(function(item) {
            newOverallTotal += item.get('total');
        });
        refundWindow.total = newOverallTotal;
        refundWindow.form.getForm().setValues({
            total: Ext.util.Format.currency(newOverallTotal, ' &euro;', 2, true)
        });
    },

    /**
     * Gets all the refund data from the given window and sends it to the
     * backend controller to perform the refund with Stripe. On success, the refund
     * window is closed and the internal comment in the communication tab is updated
     * to reflect the changes maded in the backend.
     *
     * @param refundWindow The window, which triggered the refund event.
     */
    onPerformRefund: function(refundWindow) {
        var values = refundWindow.form.getForm().getValues();

        // Gather some information about the refunded positions
        var positions = [];
        refundWindow.store.each(function(positionRecord) {
            positions.push(positionRecord.getData());
        });

        // Create the refund
        refundWindow.loadMask.show();
        Ext.Ajax.request({
            url: '{url controller="StripePayment" action="refund"}',
            jsonData: {
                orderId: refundWindow.orderRecord.get('id'),
                amount: refundWindow.total,
                comment: values['comment'],
                positions: positions
            },
            callback: function(options, success, response) {
                // Hide the loading mask
                refundWindow.loadMask.hide();
                // Try to decode the response
                var responseObject = Ext.JSON.decode(response.responseText, true);
                if (success && responseObject !== null && responseObject.success === true) {
                    // Update the internal comment
                    var communicationPanel = Ext.ComponentQuery.query('order-detail-window order-communication-panel')[0];
                    communicationPanel.internalTextArea.setValue(responseObject.internalComment);
                    // Show a growl notification and close the window
                    Shopware.Notification.createGrowlMessage('{s name=order/controller/detail/success_notification/title}{/s}', '{s name=order/controller/detail/success_notification/message}{/s}', 'stripe-payment-refund');
                    refundWindow.close()
                } else {
                    // Show an alert
                    var message = '{s name=order/controller/detail/error_alert/message}{/s} ';
                    message += (responseObject.message !== undefined) ? responseObject.message : '{s name=order/controller/detail/error_alert/message/unknown}{/s}';
                    Ext.MessageBox.alert('{s name=order/controller/detail/error_alert/title}{/s}', message);
                }
            }
        });
    }

});
//{/block}
