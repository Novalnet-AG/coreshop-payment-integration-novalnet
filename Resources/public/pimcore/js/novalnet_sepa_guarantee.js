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

pimcore.registerNS('coreshop.provider.gateways.novalnet_sepa_guarantee');
coreshop.provider.gateways.novalnet_sepa_guarantee = Class.create(coreshop.provider.gateways.abstract, {
    getLayout: function (config) {
        var self = this;

        return [
            {
                xtype: 'label',
                anchor: '100%',
                html: self.getGuaranteeNotice()
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.testmode.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.test_mode',
                value: config.test_mode ? config.test_mode : '',
                store: self.getBooleanStore(),
                triggerAction: 'all',
                valueField: 'value',
                displayField: 'label',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true,
                allowBlank: false,
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.testmode.tooltip'));
                    }
                }
            },
            {
                xtype: 'textfield',
                fieldLabel: t('novalnet.invoice.duedays.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.due_days',
                length: 255,
                value: config.due_days ? config.due_days : "",
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.sepa.duedays.tooltip'));
                    }
                }
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.paymentaction.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.payment_action',
                value: config.payment_action ? config.payment_action : '',
                store: self.getPaymentActionsStore(),
                triggerAction: 'all',
                valueField: 'value',
                displayField: 'label',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true,
                allowBlank: false,
                listeners: {
                    change: function(component) {
                        var minAmountField = Ext.getCmp('novalnet_sepaguarantee_min_authorize_amount');
                        if (component.getValue() == '1') {
                            minAmountField.show();
                        } else {
                            minAmountField.hide();
                        }
                    },
                    afterrender: function(component) {
                        self.createTooltip(component, t('novalnet.payment.action.tooltip'));

                        var minAmountField = Ext.getCmp('novalnet_sepaguarantee_min_authorize_amount');
                        if (component.getValue() == '1') {
                            minAmountField.show();
                        } else {
                            minAmountField.hide();
                        }
                    }
                }
            },
            {
                xtype: 'textfield',
                fieldLabel: t('novalnet.minimum.authorize.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.min_authorize_amount',
                length: 255,
                value: config.min_authorize_amount ? config.min_authorize_amount : "",
                id: 'novalnet_sepaguarantee_min_authorize_amount',
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.min.authorize.tooltip'));
                    }
                }
            },
            {
                xtype: 'textfield',
                fieldLabel: t('novalnet.guarantee.minorder.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.min_order_amount',
                length: 255,
                value: config.min_order_amount ? config.min_order_amount : '9.99',
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.guarantee.minorder.tooltip'));
                    }
                }
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.completed.status.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.completed_status',
                value: config.completed_status ? config.completed_status : 'captured',
                store: self.getStatusStore(),
                triggerAction: 'all',
                valueField: 'value',
                displayField: 'label',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true,
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.complete.status.tooltip'));
                    }
                }
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.allowb2b.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.allow_b2b',
                value: config.allow_b2b ? config.allow_b2b : '',
                store: self.getBooleanStore(),
                triggerAction: 'all',
                valueField: 'value',
                displayField: 'label',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true,
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.allowb2b.tooltip'));
                    }
                }
            }
        ];
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
     * To get yes/no combobox store
     */
    getBooleanStore: function () {
        return new Ext.data.ArrayStore({
            fields: ['value', 'label'],
            data: [
                ['1', 'Yes'],
                ['0', 'No']
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
    },

    /**
     * To create guarantee payment notice
     */
    getGuaranteeNotice: function () {
        var guaranteeNotice = '<br><h4>'+ t('novalnet.guarantee.requirement.title') +'</h4>';
            guaranteeNotice += '<p>'+ t('novalnet.guarantee.countries.text') +'</p>';
            guaranteeNotice += '<p>'+ t('novalnet.guarantee.currency.text') +'</p>';
            guaranteeNotice += '<p>'+ t('novalnet.guarantee.minamount.text') +'</p>';
            guaranteeNotice += '<p>'+ t('novalnet.guarantee.age.text') +'</p>';
            guaranteeNotice += '<p>'+ t('novalnet.guarantee.billing.text') +'</p>';
            guaranteeNotice += '<p>'+ t('novalnet.guarantee.b2b.text') +'</p>';

        return guaranteeNotice;
    },

    /**
     * To get payment actions
     */
    getPaymentActionsStore: function () {
        return new Ext.data.ArrayStore({
            fields: ['value', 'label'],
            data: [
                ['1', t('novalnet.authorize.label')],
                ['0', t('novalnet.capture.label')]
            ]
        });
    }
});
