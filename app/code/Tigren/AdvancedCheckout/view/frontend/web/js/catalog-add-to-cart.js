/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    "jquery",
    "mage/translate",
    "underscore",
    "Magento_Catalog/js/product/view/product-ids-resolver",
    "Magento_Catalog/js/product/view/product-info-resolver",
    "Magento_Customer/js/customer-data",
    "mage/url",
    "jquery-ui-modules/widget",
], function ($, $t, _, idsResolver, productInfoResolver, customerData, url) {
    "use strict";

    $.widget("mage.catalogAddToCart", {
        options: {
            processStart: null,
            processStop: null,
            bindSubmit: true,
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: ".stock.available",
            addToCartButtonSelector: ".action.tocart",
            addToCartButtonDisabledClass: "disabled",
            addToCartButtonTextWhileAdding: "",
            addToCartButtonTextAdded: "",
            addToCartButtonTextDefault: "",
            productInfoResolver: productInfoResolver,
        },

        /** @inheritdoc */
        _create: function () {
            if (this.options.bindSubmit) {
                this._bindSubmit();
            }
            $(this.options.addToCartButtonSelector).prop("disabled", false);
        },

        /**
         * @private
         */
        _bindSubmit: function () {
            var self = this;

            if (this.element.data("catalog-addtocart-initialized")) {
                return;
            }

            this.element.data("catalog-addtocart-initialized", 1);
            this.element.on("submit", function (e) {
                e.preventDefault();
                self.submitForm($(this));
            });
        },

        /**
         * @private
         */
        _redirect: function (url) {
            var urlParts, locationParts, forceReload;

            urlParts = url.split("#");
            locationParts = window.location.href.split("#");
            forceReload = urlParts[0] === locationParts[0];

            window.location.assign(url);

            if (forceReload) {
                window.location.reload();
            }
        },

        /**
         * @return {Boolean}
         */
        isLoaderEnabled: function () {
            return this.options.processStart && this.options.processStop;
        },

        /**
         * Handler for the form 'submit' event
         *
         * @param {jQuery} form
         */
        submitForm: function (form) {
            this.ajaxSubmit(form);
        },

        /**
         * @param {jQuery} form
         */
        ajaxSubmit: function (form) {
            var self = this,
                productIds = idsResolver(form),
                productInfo = self.options.productInfoResolver(form),
                formData;
            $(self.options.minicartSelector).trigger("contentLoading");
            self.disableAddToCartButton(form);
            formData = new FormData(form[0]);

            $.ajax({
                url: form.prop("action"),
                data: formData,
                type: "post",
                dataType: "json",
                cache: false,
                contentType: false,
                processData: false,

                /** @inheritdoc */
                beforeSend: function () {
                    if (self.isLoaderEnabled()) {
                        $("body").trigger(self.options.processStart);
                    }
                },

                /** @inheritdoc */
                success: function (res) {
                    var eventData, parameters;

                    $(document).trigger("ajax:addToCart", {
                        sku: form.data().productSku,
                        productIds: productIds,
                        productInfo: productInfo,
                        form: form,
                        response: res,
                    });

                    if (self.isLoaderEnabled()) {
                        $("body").trigger(self.options.processStop);
                    }

                    if (res.backUrl) {
                        eventData = {
                            form: form,
                            redirectParameters: [],
                        };
                        // trigger global event, so other modules will be able add parameters to redirect url
                        $("body").trigger(
                            "catalogCategoryAddToCartRedirect",
                            eventData
                        );

                        if (
                            eventData.redirectParameters.length > 0 &&
                            window.location.href.split(/[?#]/)[0] ===
                                res.backUrl
                        ) {
                            parameters = res.backUrl.split("#");
                            parameters.push(
                                eventData.redirectParameters.join("&")
                            );
                            res.backUrl = parameters.join("#");
                        }

                        self._redirect(res.backUrl);

                        return;
                    }

                    if (res.messages) {
                        $(self.options.messagesSelector).html(res.messages);
                    }

                    if (res.minicart) {
                        $(self.options.minicartSelector).replaceWith(
                            res.minicart
                        );
                        $(self.options.minicartSelector).trigger(
                            "contentUpdated"
                        );
                    }

                    if (res.product && res.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass("available")
                            .addClass("unavailable")
                            .find("span")
                            .html(res.product.statusText);
                    }
                    self.enableAddToCartButton(form);
                    //popup modal code
                    var baseUrl =
                        window.location.protocol + "//" + window.location.host;
                    var url =
                        baseUrl +
                        "/rest/V1/custom/custom-api/check?productId=" +
                        productIds +
                        "&attributeCode=allow_multi_order";
                    // var customer = customerData.get("customer");
                    // console.log(customerData.get("id"));
                    //
                    // if (customer().id) {
                    //     console.log("Customer ID: " + customer().id);
                    // } else {
                    //     console.log("Customer is not logged in");
                    // }
                    $.ajax({
                        url: url,
                        type: "GET",
                        dataType: "json",
                        success: function (data) {
                            data = JSON.parse(data);
                            console.log(data);
                            if (data["success"] == true) {
                                var popup = $(
                                    '<div class="add-to-cart-modal-popup"/>'
                                )
                                    .html(
                                        $(".page-title span").text() +
                                            "<span> has been added to cart.</span>"
                                    )
                                    .modal({
                                        modalClass: "add-to-cart-popup",
                                        title: $.mage.__("Popup Title"),
                                        buttons: [
                                            {
                                                text: "Clear cart",
                                                click: function () {
                                                    this.closeModal();
                                                    $.ajax({
                                                        url:
                                                            baseUrl +
                                                            "/rest/V1/carts/clear",
                                                        type: "POST",
                                                        dataType: "json",
                                                        success: function (
                                                            data
                                                        ) {
                                                            var sections = [
                                                                "cart",
                                                            ];
                                                            customerData.invalidate(
                                                                sections
                                                            );
                                                            customerData.reload(
                                                                sections,
                                                                true
                                                            );
                                                        },
                                                        error: function (
                                                            xhr,
                                                            status,
                                                            errorThrown
                                                        ) {
                                                            console.error(
                                                                status +
                                                                    ": " +
                                                                    errorThrown
                                                            );
                                                        },
                                                    });
                                                },
                                            },
                                            {
                                                text: "Proceed to Checkout",
                                                click: function () {
                                                    window.location =
                                                        window.checkout.checkoutUrl;
                                                },
                                            },
                                        ],
                                    });
                                popup.modal("openModal");
                            }
                        },
                        error: function (xhr, status, errorThrown) {
                            console.error(status + ": " + errorThrown);
                        },
                    });
                },

                /** @inheritdoc */
                error: function (res) {
                    $(document).trigger("ajax:addToCart:error", {
                        sku: form.data().productSku,
                        productIds: productIds,
                        productInfo: productInfo,
                        form: form,
                        response: res,
                    });
                },

                /** @inheritdoc */
                complete: function (res) {
                    if (res.state() === "rejected") {
                        location.reload();
                    }
                },
            });
        },

        /**
         * @param {String} form
         */
        disableAddToCartButton: function (form) {
            var addToCartButtonTextWhileAdding =
                    this.options.addToCartButtonTextWhileAdding ||
                    $t("Adding..."),
                addToCartButton = $(form).find(
                    this.options.addToCartButtonSelector
                );

            addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
            addToCartButton.find("span").text(addToCartButtonTextWhileAdding);
            addToCartButton.prop("title", addToCartButtonTextWhileAdding);
        },

        /**
         * @param {String} form
         */
        enableAddToCartButton: function (form) {
            var addToCartButtonTextAdded =
                    this.options.addToCartButtonTextAdded || $t("Added"),
                self = this,
                addToCartButton = $(form).find(
                    this.options.addToCartButtonSelector
                );

            addToCartButton.find("span").text(addToCartButtonTextAdded);
            addToCartButton.prop("title", addToCartButtonTextAdded);

            setTimeout(function () {
                var addToCartButtonTextDefault =
                    self.options.addToCartButtonTextDefault ||
                    $t("Add to Cart");

                addToCartButton.removeClass(
                    self.options.addToCartButtonDisabledClass
                );
                addToCartButton.find("span").text(addToCartButtonTextDefault);
                addToCartButton.prop("title", addToCartButtonTextDefault);
            }, 1000);
        },
    });

    return $.mage.catalogAddToCart;
});
