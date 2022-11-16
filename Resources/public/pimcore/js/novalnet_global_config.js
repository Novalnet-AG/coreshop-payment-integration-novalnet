/**
 * Novalnet payment bundle
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 * that is bundled with this package in the file LICENSE.txt
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment bundle for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

pimcore.registerNS('coreshop.provider.gateways.novalnet_global_config');
coreshop.provider.gateways.novalnet_global_config = Class.create(coreshop.provider.gateways.abstract, {

    /**
     * Configurations layout page
     *
     * @param mixed config
     */
    getLayout: function (config) {
        let self = this,
            storeBaseUrl = location.protocol + '//' + location.hostname;

        return [
            {
                xtype: 'label',
                anchor: '100%',
                html: t('novalnet.global.documentation')
            },
            {
                xtype: 'label',
                anchor: '100%',
                html: t('novalnet.global.config.title')
            },
            {
                xtype: 'textfield',
                fieldLabel: t('novalnet.signature.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.signature',
                length: 255,
                value: config.signature ? config.signature : "",
                id: 'novalnet_signature',
                allowBlank: false,
                afterSubTpl: t('novalnet.signature.map'),
                enableKeyEvents: true,
                listeners: {
                    keyup: function(component) {
                        self.handleKeyupProcess(component);
                    }
                }
            },
            {
                xtype: 'textfield',
                fieldLabel: t('novalnet.accesskey.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.payment_access_key',
                length: 255,
                value: config.payment_access_key ? config.payment_access_key : "",
                id: 'novalnet_accesskey',
                allowBlank: false,
                afterSubTpl: t('novalnet.paymentaccesskey.map'),
                enableKeyEvents: true,
                listeners: {
                    keyup: function(component) {
                        self.handleKeyupProcess(component);
                    }
                }
            },
            {
                xtype: 'button',
                text: t('novalnet.activatebtn.label'),
                maxWidth: 110,
                height: 30,
                margin: '0 0 0 500',
                id: 'novalnet_vendor_configbtn',
                listeners: {
                    click: function() {
                       self.sendVendorAutoConfigCall(true);
                    }
                }
            },
            {
                xtype: 'label',
                anchor: '100%',
                html: '<br><p></p>'
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.tariff.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.tariff',
                value: config.tariff ? config.tariff : '',
                store: self.getEmptyTariffIds(),
                triggerAction: 'all',
                valueField: 'tariffId',
                displayField: 'tariffName',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true,
                id: 'novalnet_tariff',
                allowBlank: false,
                listeners: {
                    afterrender: function (component) {
                        Ext.Function.defer(function () {
                            self.sendVendorAutoConfigCall();
                        }, 500);

                        self.createTooltip(component, t('novalnet.tariff.tooltip'));
                    }
                }
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.restorecart.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.restore_cart',
                value: config.restore_cart ? config.restore_cart : '',
                store: self.getBooleanStore(),
                triggerAction: 'all',
                valueField: 'value',
                displayField: 'label',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.global.onhold.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.onhold_status',
                value: config.onhold_status ? config.onhold_status : 'authorized',
                store: self.getStatusStore(),
                triggerAction: 'all',
                valueField: 'value',
                displayField: 'label',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true,
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.global.onhold.tooltip'));
                    }
                }
            },
            {
                xtype: 'hiddenfield',
                name: 'gatewayConfig.config.client_key',
                value: config.client_key ? config.client_key : "",
                id: 'novalnet_client_key'
            },
            {
                xtype: 'hiddenfield',
                name: '',
                value: config.tariff ? config.tariff : "",
                id: 'novalnet_saved_tariff'
            },
            {
                xtype: 'label',
                anchor: '100%',
                html: t('novalnet.global.webhook.document')
            },
            {
                xtype: 'textfield',
                fieldLabel: t('novalnet.webhook.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.webhook_url',
                length: 255,
                value: config.webhook_url ? config.webhook_url : storeBaseUrl + '/shop/novalnet/callback',
                id: 'novalnet_webhook_url',
                afterSubTpl: t('novalnet.webhook.map'),
                enableKeyEvents: true,
                listeners: {
                    keyup: function(component) {
                        if (component.getValue() == '') {
                            Ext.getCmp('novalnet_webhook_btn').setDisabled(true);
                        } else {
                            Ext.getCmp('novalnet_webhook_btn').setDisabled(false);
                        }
                    }
                }
            },
            {
                xtype: 'button',
                text: t('novalnet.webhookbtn.label'),
                maxWidth: 110,
                height: 30,
                margin: '0 0 0 500',
                style: 'width:50px !important; height:50px;',
                id: 'novalnet_webhook_btn',
                listeners: {
                    click: function() {
                       self.sendWebhookConfigureCall();
                    }
                }
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.webhook.manualtest.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.webhook_testmode',
                value: config.webhook_testmode ? config.webhook_testmode : '',
                store: self.getBooleanStore(),
                triggerAction: 'all',
                valueField: 'value',
                displayField: 'label',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true,
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.webhook.testmode.tooltip'));
                    }
                }
            },
            {
                xtype: 'textfield',
                fieldLabel: t('novalnet.merchantmail.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.merchant_mailto',
                length: 255,
                value: config.merchant_mailto ? config.merchant_mailto : "",
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.merchantmail.tooltip'));
                    }
                }
            },
        ];
    },

    /**
     * Vendor auto configuration call
     *
     * @param bool stateChanged
     */
    sendVendorAutoConfigCall: function (stateChanged = false) {
        let merchantSignature = Ext.getCmp('novalnet_signature').getValue(),
            merchantPaymentAccessKey = Ext.getCmp('novalnet_accesskey').getValue();

        if (!merchantSignature || !merchantPaymentAccessKey) {
            Ext.Msg.alert('Error', t('novalnet.global.activate.validate.error'));
            return;
        }

        Ext.Ajax.request({
            url: location.hostname + '/shop/novalnet/globalconfig',
            method: 'POST',
            params: {
                signature: merchantSignature,
                paymentAccessKey: merchantPaymentAccessKey,
                apiProcess: 'vendorAutoConfig'
            },
            success: function(result, request) {
                let response = Ext.util.JSON.decode(result.responseText);
                if (response.result.status == 'SUCCESS') {
                    let tariffs = response.merchant.tariff,
                        tariffField = Ext.getCmp('novalnet_tariff'),
                        savedTariffId = Ext.getCmp('novalnet_saved_tariff').getValue(),
                        newStoreData = [];

                    Ext.getCmp('novalnet_client_key').setValue(response.merchant.client_key);

                    Ext.Object.each(tariffs, function (id, value) {
                        Ext.Array.push(newStoreData, [[id, value.name]]);
                    });

                    tariffField.setStore(
                        new Ext.data.ArrayStore({
                            fields: ['tariffId', 'tariffName'],
                            data: newStoreData
                        })
                    );

                    if (savedTariffId && tariffs.hasOwnProperty(savedTariffId)) {
                        tariffField.setValue(savedTariffId);
                    }

                    if (stateChanged) {
                        Ext.Msg.alert('Success', t('novalnet.vendorconfig.success.msg'), Ext.emptyFn);
                    }
                }
                else {
                    Ext.Msg.alert('Error', response.result.status_text, Ext.emptyFn);
                }
            },
            failure: function(result, request) {
                let errResponse = Ext.util.JSON.decode(result.responseText);
                Ext.Msg.alert('Error', errResponse, Ext.emptyFn);
            }
        });
    },

    /**
     * Webhook URL configuration call
     */
    sendWebhookConfigureCall: function () {
        let merchantSignature = Ext.getCmp('novalnet_signature').getValue(),
            merchantPaymentAccessKey = Ext.getCmp('novalnet_accesskey').getValue(),
            merchantWebhookUrl = Ext.getCmp('novalnet_webhook_url').getValue();

        if (!merchantSignature || !merchantPaymentAccessKey || !merchantWebhookUrl) {
            return;
        }

        Ext.Ajax.request({
            url: location.hostname + '/shop/novalnet/globalconfig',
            method: 'POST',
            params: {
                signature: merchantSignature,
                paymentAccessKey: merchantPaymentAccessKey,
                webhookUrl: merchantWebhookUrl,
                apiProcess: 'webhookConfigure'
            },
            success: function(result, request) {
                let response = Ext.util.JSON.decode(result.responseText);
                if (response.result.status == 'SUCCESS') {
                    Ext.Msg.alert('Success', t('novalnet.webhook.configured.msg'), Ext.emptyFn);
                }
                else {
                    Ext.Msg.alert('Error', response.result.status_text, Ext.emptyFn);
                }
            },
            failure: function(result, request) {
                let errResponse = Ext.util.JSON.decode(result.responseText);
                Ext.Msg.alert('Error', errResponse, Ext.emptyFn);
            }
        });
    },

    /**
     * signature / payment access key input keyup handler
     *
     * @param mixed component
     */
    handleKeyupProcess: function (component) {
        let self = this;

        Ext.getCmp('novalnet_client_key').setValue('');
        Ext.getCmp('novalnet_tariff').setStore(self.getEmptyTariffIds());

        if (!component.getValue()) {
            Ext.getCmp('novalnet_vendor_configbtn').setDisabled(true);
        } else {
            Ext.getCmp('novalnet_vendor_configbtn').setDisabled(false);
        }
    },

    /**
     * To create tooltip
     *
     * @param mixed component
     * @param string message
     */
    createTooltip: function (component, message) {
        new Ext.create('Ext.tip.ToolTip', {
            target: component,
            html: message
        });
    },

    /**
     * To get empty tariff ids store
     */
    getEmptyTariffIds: function () {
        return new Ext.data.ArrayStore({
            fields: ['tariffId', 'tariffName'],
            data: [
                [null, '-- Please Select --']
            ]
        });
    },

    /**
     * To get yes/no combobox store
     */
    getBooleanStore: function () {
        return new Ext.data.ArrayStore({
            fields: ['value', 'label'],
            data: [
                [1, 'Yes'],
                [0, 'No']
            ]
        });
    },

    /**
     * To get order status
     */
    getStatusStore: function () {
        return new Ext.data.ArrayStore({
            fields: ['value', 'label'],
            data: [
                ['captured', 'Captured'],
                ['authorized', 'Authorized'],
                ['payedout', 'Payedout'],
                ['refunded', 'Refunded'],
                ['unknown', 'Unknown'],
                ['failed', 'Failed'],
                ['suspended', 'Suspended'],
                ['expired', 'Expired'],
                ['pending', 'Pending'],
                ['canceled', 'Canceled'],
                ['new', 'New']
            ]
        });
    },

    /**
     * To create tooltip
     *
     * @param mixed component
     * @param string message
     */
    createTooltip: function (component, message) {
        new Ext.create('Ext.tip.ToolTip', {
            target: component,
            html: message
        });
    }
});
