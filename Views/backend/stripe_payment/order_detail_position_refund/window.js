// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

//{namespace name=backend/plugins/stripe_payment/order_detail_position_refund}

/**
 * A simple window, displaying a list of the selected psitions as well as their total
 * value and a field for adding a comment to the refund transaction.
 */
Ext.define('Shopware.apps.StripePayment.Order.view.detail.position.refund.Window', {

    extend: 'Ext.window.Window',

    alias: 'widget.stripe-payment-refund-window',
    title: '{s name=order/view/detail/position/refund/window/title}{/s}',
    modal: true,
    height: 440,
    width: 800,
    layout: 'fit',

    /**
     * Creates the view and store of this window.
     */
    initComponent: function() {
        // Create a new loadmask
        this.loadMask = new Ext.LoadMask(this, {
            msg: '{s name=order/view/detail/position/refund/window/load_mask}{/s}',
        });
        this.loadMask.hide();

        this.addEvents(
            /**
             * Event will be fired, if an edited row is saved.
             *
             * @event performRefund
             * @param window This window.
             * @param record The record whose quantity has changed.
             */
            'positionQuantityChanged',
            /**
             * Event will be fired when the user clicks the 'refund' button.
             *
             * @event performRefund
             * @param window This window.
             */
            'performRefund'
        );

        this.items = {
            layout: 'border',
            border: false,
            items: [
                this.createGrid(),
                this.createForm()
            ],
            dockedItems: [
                this.createToolbar()
            ]
        };

        this.callParent(arguments);
    },

    /**
     * @return A new refund position grid panel.
     */
    createGrid: function() {
        return Ext.create('Ext.grid.Panel', {
            region: 'center',
            layout: 'fit',
            store: this.store,
            border: false,
            viewConfig: {
                markDirty: false
            },
            columns: [{
                xtype: 'gridcolumn',
                header: '{s name=order/view/detail/position/refund/window/grid/column/article_numer}{/s}',
                dataIndex: 'articleNumber',
                flex: 2
            }, {
                xtype: 'gridcolumn',
                header: '{s name=order/view/detail/position/refund/window/grid/column/article_name}{/s}',
                dataIndex: 'articleName',
                flex: 4
            }, {
                xtype: 'gridcolumn',
                header: '{s name=order/view/detail/position/refund/window/grid/column/quantity}{/s}',
                dataIndex: 'quantity',
                flex: 1,
                editor: {
                    xtype: 'numberfield',
                    allowBlank: false,
                    allowDecimals: false,
                    minValue: 0,
                    padding: '0 5 0 0'
                }
            }, {
                xtype: 'gridcolumn',
                header: '{s name=order/view/detail/position/refund/window/grid/column/price}{/s}',
                dataIndex: 'price',
                align: 'right',
                flex: 1,
                renderer: function(value, metaData, record) {
                    return Ext.util.Format.number(value, '0,000.00/i');
                }
            }, {
                xtype: 'gridcolumn',
                header: '{s name=order/view/detail/position/refund/window/grid/column/total}{/s}',
                dataIndex: 'total',
                align: 'right',
                flex: 1,
                renderer: function(value, metaData, record) {
                    return Ext.util.Format.number(value, '0,000.00/i');
                }
            }],
            plugins: [
                Ext.create('Ext.grid.plugin.RowEditing', {
                    clicksToEdit: 2,
                    autoCancel: true,
                    listeners: {
                        scope: this,
                        edit: function(editor, event) {
                            this.fireEvent('positionQuantityChanged', this, event.record);
                        }
                    }
                })
            ]
        });
    },

    /**
     * Creates and returns a new form panel consisting of a label for the total amount and
     * a text field for the comment.
     *
     * @return The created form panel.
     */
    createForm: function() {
        this.form = Ext.create('Ext.form.Panel', {
            region: 'south',
            layout: 'vbox',
            padding: 10,
            border: false,
            items: [
                {
                    xtype: 'displayfield',
                    fieldLabel: '{s name=order/view/detail/position/refund/window/form/total_amount}{/s}',
                    name: 'total',
                    value: Ext.util.Format.currency(this.total, '', 2, true)
                }, {
                    xtype: 'textfield',
                    fieldLabel: '{s name=order/view/detail/position/refund/window/form/comment}{/s}',
                    name: 'comment',
                    width: '100%'
                }
            ]
        });

        return this.form;
    },

    /**
     * @return A toolbar containing a 'cancel' and a 'save' button.
     */
    createToolbar: function() {
        return Ext.create('Ext.toolbar.Toolbar', {
            dock: 'bottom',
            ui: 'shopware-ui',
            padding: 10,
            items: [
                {
                    xtype: 'component',
                    flex: 1
                }, {
                    xtype: 'button',
                    text: '{s name=order/view/detail/position/refund/window/cancel_button}{/s}',
                    cls: 'secondary',
                    scope: this,
                    handler: function() {
                        this.close();
                    }
                }, {
                    xtype: 'button',
                    text: '{s name=order/view/detail/position/refund/window/confirm_button}{/s}',
                    cls: 'primary',
                    scope: this,
                    handler: function() {
                        this.fireEvent('performRefund', this);
                    }
                }
            ]
        });
    }

});
