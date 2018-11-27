/**
 * Created by chung on 2/4/17.
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
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magenest_SagePay/js/model/payment/sage-message',
        'Magento_Ui/js/modal/modal',
        'mage/translate'
    ],
    function (Component, $, ko, additionalValidators, redirectOnSuccessAction, fullScreenLoader, messageContainer, modal, $t) {
        'use strict';

        var Card = function (cardData) {
            return {
                id: ko.observable(cardData.id),
                last4: ko.observable(cardData.last4)
            }
        };

        if (window.magenest.sage.isSandbox === '1') {
            require(['https://pi-test.sagepay.com/api/v1/js/sagepay-dropin.js']);
        } else {
            require(['https://pi-live.sagepay.com/api/v1/js/sagepay-dropin.js']);
        }

        var sageCheckoutCalled = false;
        var popUp = null;

        return Component.extend({
            defaults: {
                template: 'Magenest_SagePay/payment/sagepay-dropin',
                cardIdentifier: "",
                merchantSessionKey: "",
                messageContainer: messageContainer,
                savedCards: window.magenest.sage.cards,
                newSavedCards: ko.observableArray(),
                selectedCard: ko.observable(0),
                isFormVisible: ko.observable(0),
                isModalOpen: ko.observable(false),
                redirectAfterPlaceOrder: false,
                hasSelected: ko.observable(0)
            },

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

            },


            getCode: function () {
                return 'magenest_sagepay_dropin';
            },

            getPopUp: function () {
                var self = this;
                if (!popUp) {
                    var options = {
                        buttons: false,
                        type: 'popup',
                        responsive: true,
                        innerScroll: true,
                        title: 'Sagepay',
                        opened: function () {
                            self.isModalOpen(true);
                            // self.isFormVisible(true);
                            if (!sageCheckoutCalled) {
                                self.sageCheckout();
                            }
                        },
                        closed: function () {
                            self.isModalOpen(false);
                            $("button#sagepay-continue-button").removeClass('disabled');
                        }
                    };

                    popUp = modal(options, $('#sagepay-dropin-modal'));

                }
                return popUp;
            },

            sageCheckout: function () {
                var self = this;
                sageCheckoutCalled = true;
                this.setMerchantSessionKey();
                if(typeof self.merchantSessionKey == 'undefined'){
                    console.log("Merchant Key error");
                    self.messageContainer.addErrorMessage({
                        message: "Merchant Key error"
                    });
                    fullScreenLoader.stopLoader(true);
                    $(".loading-mask").hide();
                    self.isPlaceOrderActionAllowed(true);
                    self.getPopUp().closeModal();
                    return;
                }
                return sagepayCheckout({
                    merchantSessionKey: self.merchantSessionKey,
                    containerSelector: '#sp-container',
                    onTokenise: function (tokenisationResult) {
                        console.log(tokenisationResult);
                        if (tokenisationResult.success) {
                            self.cardIdentifier = tokenisationResult.cardIdentifier;
                            self.getPopUp().closeModal();
                            self.placeOrder();
                        } else {
                            self.getPopUp().closeModal();
                            self.messageContainer.addErrorMessage({
                                message: "Authentication failed. Please reload your browser"
                            });
                        }
                    }
                }).form({formSelector: '#sagepay-checkout-form'}); //
            },

            validate: function () {
                return true;
                // var $form = $('#' + this.getCode() + '-form');
                // return $form.validation() && $form.validation('isValid');
            },

            context: function () {
                return this;
            },

            canSave: function () {
                return window.magenest.sage.canSave;
            },

            hasCard: function () {
                return window.magenest.sage.hasCard;
            },

            isShowLegend: function () {
                return true;
            },

            isActive: function () {
                return true;
            },

            setMerchantSessionKey: function () {
                var self = this;
                fullScreenLoader.startLoader();
                $.ajax({
                    type: "GET",
                    data:{
                        form_key:window.checkoutConfig.formKey
                    },
                    url: window.magenest.sage.sageMerchantSessionKey,
                    success: function (response) {
                        self.merchantSessionKey = response.merchantSessionKey;
                    },
                    async: false
                });
                $(".loading-mask").hide();
            },

            openPlaceModal: function () {
                var self = this;
                this.isPlaceOrderActionAllowed(false);
                if (self.selectedCard() === 0) {
                    self.getPopUp().openModal();
                } else if (this.canSave() && this.hasCard() && (this.selectedCard() !== 0)) {
                    this.setMerchantSessionKey();
                    sagepayOwnForm(
                        {
                            merchantSessionKey: self.merchantSessionKey
                        }
                    ).activateReusableCardIdentifier(
                        {
                            reusableCardIdentifier: this.selectedCard(),
                            securityCode: this.creditCardVerificationNumber(),
                            onActivated: function(status) {
                                console.log(status);
                                if(status.success){
                                    self.placeOrder();
                                }else{
                                    self.messageContainer.addErrorMessage({
                                        message: "Authentication failed. Please reload your browser"
                                    });
                                    fullScreenLoader.stopLoader(true);
                                    $(".loading-mask").hide();
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            }
                        });

                    fullScreenLoader.stopLoader(true);
                    self.isPlaceOrderActionAllowed(true);
                }
            },

            getData: function () {
                var self = this;
                var isSave = $('#sage-dropin-savecard').is(":checked");
                var isGiftAid = $('#sage-dropin-gift_aid').is(":checked");
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'card_identifier': this.cardIdentifier,
                        'merchant_sessionKey': this.merchantSessionKey,
                        'cc_number': this.creditCardNumber(),
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
            }

        });

    }
);