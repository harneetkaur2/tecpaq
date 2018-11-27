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
        var Card = function (cardData) {
            return {
                id: ko.observable(cardData.id),
                last4: ko.observable(cardData.last4)
            }
        };

        var baseUrl = window.baseUrl;
        var tempKey = '';
        var payUrl = "";

        $(".loading-mask").hide();
        $('#checkout-loader').hide();

        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/payment/sagepay',
                cardIdentifier: "",
                redirectAfterPlaceOrder: false
            },
            savedCards: window.magenest.sage.cards,
            messageContainer: messageContainer,
            expiryDate: ko.observable(),
            merchantSessionKey: ko.observable(tempKey),
            newSavedCards: ko.observableArray(),
            isFormVisible: ko.observable(1),
            selectedCard: ko.observable(0),
            hasSelected: ko.observable(0),
            /**
             * Initialize view.
             *
             * @return {exports}
             */
            initialize: function () {
                this._super();
                var self = this;

                this.newSavedCards(
                    $.map(this.savedCards, function (savedCard) {
                        var itemObj = new Card(savedCard);
                        itemObj.parentObj = self;
                        return itemObj;
                    })
                );

                this.selectedCard.subscribe(function (value) {
                    var isOtherCard = (value === 0);
                    self.isFormVisible(isOtherCard);
                    self.hasSelected(!isOtherCard);
                });

                if (window.magenest.sage.isSandbox === '1') {
                    requirejs(['magenest_sagepay_pi_test'], function () {
                        window.magenest.sage.isJsLoaded = true;
                        console.log("sagepay loaded: test mode");
                    });
                } else {
                    requirejs(['magenest_sagepay_pi_live'], function () {
                        window.magenest.sage.isJsLoaded = true;
                        console.log("sagepay loaded: live mode");
                    });
                }
            },

            hasCard: function () {
                return window.magenest.sage.hasCard;
            },

            canSave: function () {
                return window.magenest.sage.canSave;
            },

            getCode: function () {
                return 'magenest_sagepay';
            },

            isActive: function () {
                return true;
            },

            validate: function () {
                return true;
                // var $form = $('#' + this.getCode() + '-form');
                // return $form.validation() && $form.validation('isValid');
            },

            context: function () {
                return this;
            },

            isShowLegend: function () {
                return true;
            },

            getLogoUrl: function () {
                return window.magenest.sage.imageUrl;
            },

            setMerchantSessionKey: function () {
                var self = this;
                $.ajax({
                    type: "GET",
                    data:{
                        form_key:window.checkoutConfig.formKey
                    },
                    url: window.magenest.sage.sageMerchantSessionKey,
                    success: function (response) {
                        console.log(response);
                        self.merchantSessionKey(response.merchantSessionKey);
                        tempKey = response.merchantSessionKey;
                    },
                    async: false
                });
            },

            updateExpiry: function () {
                var month = $('#magenest_sagepay_expiration').val();
                var year = $('#magenest_sagepay_expiration_yr').val();
                var zero = '0';

                if (month && month < 10) {
                    month = zero.concat(month);
                }

                if (year) {
                    year = year.slice(2);
                    this.expiryDate(month.concat(year));
                }
            },

            createOrder: function () {
                var self = this;
                if (self.validate() && additionalValidators.validate()) {
                    fullScreenLoader.startLoader();

                    $.ajax({
                            type: "GET",
                            data: {
                                form_key: window.checkoutConfig.formKey
                            },
                            url: window.magenest.sage.sageMerchantSessionKey,
                            success: function (response) {
                                console.log(response);
                                self.merchantSessionKey(response.merchantSessionKey);
                                tempKey = response.merchantSessionKey;
                            },
                            showLoader: true
                        }
                    ).done(function (msg) {
                        if (typeof self.merchantSessionKey() == 'undefined') {
                            console.log("Merchant Key error");
                            self.messageContainer.addErrorMessage({
                                message: "Merchant Key error"
                            });
                            fullScreenLoader.stopLoader(true);
                            $(".loading-mask").hide();
                            self.isPlaceOrderActionAllowed(true);
                            return;
                        }
                        var cardDetail = {
                            cardholderName: $('#magenest_sagepay_cc_holder').val(),
                            cardNumber: $('#magenest_sagepay_cc_number').val(),
                            expiryDate: self.expiryDate(),
                            securityCode: $('#magenest_sagepay_cc_cid').val()
                        };

                        var $form = $('#magenest_sagepay-form');

                        self.isPlaceOrderActionAllowed(false);
                        if (self.canSave() && self.hasCard() && (self.selectedCard() !== 0)) {
                            sagepayOwnForm(
                                {
                                    merchantSessionKey: self.merchantSessionKey()
                                }
                            ).activateReusableCardIdentifier(
                                {
                                    reusableCardIdentifier: self.selectedCard(),
                                    securityCode: self.creditCardVerificationNumber(),
                                    onActivated: function (status) {
                                        console.log(status);
                                        if (status.success) {
                                            self.placeOrder();
                                        } else {
                                            self.messageContainer.addErrorMessage({
                                                message: "Authentication failed."
                                            });
                                            fullScreenLoader.stopLoader(true);
                                            $(".loading-mask").hide();
                                            self.isPlaceOrderActionAllowed(true);
                                        }
                                    }
                                });

                            fullScreenLoader.stopLoader(true);
                            self.isPlaceOrderActionAllowed(true);
                        } else {
                            sagepayOwnForm({merchantSessionKey: self.merchantSessionKey()})
                                .tokeniseCardDetails({
                                    cardDetails: cardDetail,
                                    onTokenised: function (result, response) {
                                        console.log(result);
                                        if (result.success) {
                                            self.cardIdentifier = result['cardIdentifier'];
                                            self.placeOrder();
                                        } else {
                                            self.messageContainer.addErrorMessage({
                                                message: "Authentication failed"
                                            });
                                            fullScreenLoader.stopLoader(true);
                                            self.isPlaceOrderActionAllowed(true);
                                            $(".loading-mask").hide();
                                        }
                                    }
                                });

                        }

                    });
                }
            },

            getData: function () {
                var self = this;
                var isSave = $('#sage-savecard').is(":checked");
                var isGiftAid = $('#sagepay-gift_aid').is(":checked");
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'card_identifier': this.cardIdentifier,
                        'merchant_sessionKey': self.merchantSessionKey(),
                        'save': isSave ? '1' : '0',
                        'selected_card': this.selectedCard(),
                        'gift_aid': isGiftAid ? '1' : '0'
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

            getInstructions: function () {
                return window.magenest.sage.sageinstructions;
            }
        });


    }
);