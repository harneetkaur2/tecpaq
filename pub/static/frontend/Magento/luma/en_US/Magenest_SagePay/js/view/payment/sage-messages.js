/**
 * Created by joel on 27/02/2017.
 */
define([
    'Magento_Ui/js/view/messages',
    '../../model/payment/sage-messages'
], function (Component, messageContainer) {
    'use strict';

    return Component.extend({
        initialize: function (config) {
            return this._super(config, messageContainer);
        }
    });
});
