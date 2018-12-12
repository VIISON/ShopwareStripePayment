// Copyright (c) Pickware GmbH. All rights reserved.
// This file is part of software that is released under a proprietary license.
// You must not copy, modify, distribute, make publicly available, or execute
// its contents or parts thereof without express permission by the copyright
// holder, unless otherwise permitted by law.

//{namespace name=backend/plugins/stripe_payment/order_detail_position_refund}

/**
 * A model, which represents a simple version of an order item, which are used
 * to refund some of the items of an order.
 */
Ext.define('Shopware.apps.StripePayment.Order.model.detail.position.refund.Item', {

    extend : 'Ext.data.Model',

    fields : [
        { name: 'id', type: 'int' },
        { name: 'articleNumber', type: 'string' },
        { name: 'articleName', type: 'string' },
        { name: 'quantity', type: 'int' },
        { name: 'price', type: 'decimal' },
        { name: 'total', type: 'decimal' }
    ]

});
