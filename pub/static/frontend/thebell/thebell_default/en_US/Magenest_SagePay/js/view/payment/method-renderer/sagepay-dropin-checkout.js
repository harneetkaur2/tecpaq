/**
 * Created by Pham Quang Hau on 02/08/2016.
 */
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'ko',
        'Magento_Ui/js/model/messageList',
        'Magenest_SagePay/js/model/payment/sage-message',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $, ko, globalMessageList, messageContainer, fullScreenLoader, additionalValidators, redirectOnSuccessAction, quote, customer) {
        'use strict';

        var checkout;
        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/payment/sagepay-dropin-checkout',
                redirectAfterPlaceOrder: false
            },
            merchantSessionKey: "",
            lastMerchantSessionKey: "",
            cardIdentifier: "",
            isCheckoutDropinLoaded: false,

            /**
             * @return {Boolean}
             */
            selectPaymentMethod: function () {
                if(!this.isCheckoutDropinLoaded){
                    this.initSage();
                }
                return this._super();
            },

            /**
             * Initialize view.
             *
             * @return {exports}
             */
            initialize: function () {
                this._super();
                var self = this;
            },

            requireSageJs: function () {
                var self = this;
                if (window.magenest.sage.isSandbox === '1') {
                    requirejs(['magenest_sagepay_pi_test'], function () {
                        window.magenest.sage.isJsLoaded = true;
                        console.log("sagepay dropin loaded: test mode");
                        self.initSage();
                    });
                } else {
                    requirejs(['magenest_sagepay_pi_live'], function () {
                        window.magenest.sage.isJsLoaded = true;
                        console.log("sagepay dropin loaded: live mode");
                        self.initSage();
                    });
                }
            },

            initSage: function () {
                var self = this;
                $.ajax({
                        type: "GET",
                        data:{
                            form_key:window.checkoutConfig.formKey
                        },
                        url: window.magenest.sage.sageMerchantSessionKey,
                        success: function (response) {
                            console.log(response);
                            if(response.error){
                                self.messageContainer.addErrorMessage({
                                    message: "Merchant Key error"
                                });
                                self.isPlaceOrderActionAllowed(false);
                                return;
                            }
                            self.isPlaceOrderActionAllowed(true);
                            self.merchantSessionKey = response.merchantSessionKey;
                            self.lastMerchantSessionKey = response.merchantSessionKey;
                            if(typeof checkout !== 'undefined') {
                                checkout.destroy();
                            }
                            checkout = sagepayCheckout({
                                merchantSessionKey: self.merchantSessionKey,
                                containerSelector: '#sp-container',
                                onTokenise: function (tokenisationResult) {
                                    console.log(tokenisationResult);
                                    self.cardIdentifier = tokenisationResult.cardIdentifier;
                                    self.placeOrder();
                                }
                            });
                            checkout.form();
                            self.isCheckoutDropinLoaded = true;
                        },
                        showLoader: true
                    }
                ).done(function(msg) {
                });
            },

            placeOrderSage: function () {
                var self = this;
                if (this.validate() && additionalValidators.validate()) {
                    if (this.merchantSessionKey == "") {
                        $.ajax({
                                type: "GET",
                                data: {
                                    form_key: window.checkoutConfig.formKey
                                },
                                url: window.magenest.sage.sageMerchantSessionKey,
                                success: function (response) {
                                    console.log(response);
                                    self.merchantSessionKey = response.merchantSessionKey;
                                    self.lastMerchantSessionKey = response.merchantSessionKey;
                                },
                                showLoader: true
                            }
                        ).done(function () {
                            checkout.tokenise({
                                newMerchantSessionKey: self.merchantSessionKey
                            });
                        });
                    } else {
                        checkout.tokenise();
                        this.merchantSessionKey = "";
                    }
                }
            },

            getData: function () {
                this.isPlaceOrderActionAllowed(false);
                var self = this;
                var isGiftAid = $('#sage-dropin-gift_aid').is(":checked");
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'save': '0',
                        'selected_card': '0',
                        'gift_aid': isGiftAid ? '1' : '0',
                        'card_identifier': self.cardIdentifier,
                        'merchant_sessionKey': self.lastMerchantSessionKey
                    }
                }
            },

            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                $(".loading-mask").hide();
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },

            getCode: function () {
                return 'magenest_sagepay_dropin';
            },

            isActive: function () {
                return true;
            },

            afterPlaceOrder: function () {
                var self = this;
                $.ajax({
                    url: window.magenest.sage.threedSecureUrl,
                    data:{
                        form_key:window.checkoutConfig.formKey
                    },
                    dataType: "json",
                    type: 'POST',
                    success: function (response) {

                        if (response.success) {
                            //default pay -> success page
                            if(response.defaultPay){
                                redirectOnSuccessAction.execute();
                            }
                            if(response.threeDSercueActive){
                                var formData = response.formData;
                                var form = $('<form id="sage_3d_form" action="' + response.threeDSercueUrl + '" method="post">' +
                                    '</form>');
                                $('body').append(form);
                                for (var key in formData) {
                                    if (formData.hasOwnProperty(key)) {
                                        $('<input>').attr({
                                            type: 'hidden',
                                            name: key,
                                            value: formData[key]
                                        }).appendTo('#sage_3d_form');
                                    }
                                }
                                form.submit();
                            }

                        }
                        if (response.error){
                            self.messageContainer.addErrorMessage({
                                message: response.message
                            });
                            fullScreenLoader.stopLoader(true);
                            self.isPlaceOrderActionAllowed(true);
                        }
                    },
                    error: function (response) {
                        self.messageContainer.addErrorMessage({
                            message: "Error, please try again"
                        });
                        fullScreenLoader.stopLoader(true);
                        self.isPlaceOrderActionAllowed(true);
                    }
                })
            },
        });


    }
);