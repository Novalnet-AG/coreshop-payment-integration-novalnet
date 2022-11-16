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

pimcore.registerNS('coreshop.provider.gateways.novalnet_cc');
coreshop.provider.gateways.novalnet_cc = Class.create(coreshop.provider.gateways.abstract, {
    getLayout: function (config) {
        var self = this;

        return [
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.testmode.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.test_mode',
                value: config.test_mode ? config.test_mode : '1',
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
                xtype: 'combobox',
                fieldLabel: t('novalnet.cc.enforce3d.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.enforce_3d',
                value: config.enforce_3d ? config.enforce_3d : '1',
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
                        self.createTooltip(component, t('novalnet.enforce3d.tooltip'));
                    }
                }
            },
            {
                xtype: 'combobox',
                fieldLabel: t('novalnet.paymentaction.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.payment_action',
                value: config.payment_action ? config.payment_action : '0',
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
                        var minAmountField = Ext.getCmp('novalnet_cc_min_authorize_amount');
                        if (component.getValue() == '1') {
                            minAmountField.show();
                        } else {
                            minAmountField.hide();
                        }
                    },
                    afterrender: function(component) {
                        self.createTooltip(component, t('novalnet.payment.action.tooltip'));

                        var minAmountField = Ext.getCmp('novalnet_cc_min_authorize_amount');
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
                id: 'novalnet_cc_min_authorize_amount',
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.min.authorize.tooltip'));
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
                fieldLabel: t('novalnet.cc.inlineform.label'),
                labelStyle: 'font-weight: 600;',
                name: 'gatewayConfig.config.inline_form',
                value: config.inline_form ? config.inline_form : '1',
                store: self.getBooleanStore(),
                triggerAction: 'all',
                valueField: 'value',
                displayField: 'label',
                mode: 'local',
                forceSelection: true,
                selectOnFocus: true,
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.cc.inlineform.tooltip'));
                    }
                }
            },
            self.getCreditcardLogosMultiselect(config),
            {
                xtype: 'fieldset',
                title: t('novalnet.cc.formcss.label'),
                collapsible: true,
                collapsed: true,
                autoHeight: true,
                labelWidth: 250,
                anchor: '100%',
                flex: 1,
                defaultType: 'textfield',
                items: [
                    {
                        xtype: 'label',
                        anchor: '100%',
                        html: t('novalnet.cc.customcss.title')
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: t('novalnet.cc.form.label'),
                        labelStyle: 'font-weight: 600;',
                        name: 'gatewayConfig.config.form_label_style',
                        length: 255,
                        value: config.form_label_text ? config.form_label_text : "font-family: Raleway,Helvetica Neue,Verdana,Arial,sans-serif;font-size: 13px;font-weight: 600;color: #636363;line-height: 1.5;"
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: t('novalnet.cc.form.input'),
                        labelStyle: 'font-weight: 600;',
                        name: 'gatewayConfig.config.form_input_style',
                        length: 255,
                        value: config.form_input_style ? config.form_input_style : "color: #636363;font-family: Helvetica Neue,Verdana,Arial,sans-serif;font-size: 14px;"
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: t('novalnet.cc.form.css'),
                        labelStyle: 'font-weight: 600;',
                        name: 'gatewayConfig.config.form_css',
                        length: 255,
                        value: config.form_css ? config.form_css : ""
                    }
                ]
            }
        ];
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
     * create credit card logo multiselect
     *
     * @param config
     */
    getCreditcardLogosMultiselect: function (config) {
        var self = this;

        if (config.logo_types) {
            config.logo_types = config.logo_types.split(',');
        }

        return new Ext.ux.form.MultiSelect({
            fieldLabel: t('novalnet.cc.displaylogo.label'),
            labelStyle: 'font-weight: 600;',
            typeAhead: true,
            listWidth: 50,
            width: 100,
            store: self.getCcLogoStore(),
            valueField: 'value',
            displayField: 'label',
            forceSelection: true,
            multiselect: true,
            triggerAction: 'all',
            name: 'gatewayConfig.config.logo_types',
            height: 200,
            delimiter: false,
            value: config.logo_types ? config.logo_types : "",
                listeners: {
                    afterrender: function (component) {
                        self.createTooltip(component, t('novalnet.cc.cardlogos.tooltip'));
                    }
                }
        });
    },

    /**
     * To get logo store
     */
    getCcLogoStore: function () {
        return new Ext.data.ArrayStore({
            fields: ['value', 'label'],
            data: [
                ['novalnetvisa', 'Visa'],
                ['novalnetmastercard', 'MasterCard'],
                ['novalnetamex', 'American Express'],
                ['novalnetmaestro', 'Maestro'],
                ['novalnetcartasi', 'Cartasi'],
                ['novalnetunionpay', 'Union Pay'],
                ['novalnetdiscover', 'Discover'],
                ['novalnetdiners', 'Diners'],
                ['novalnetjcb', 'Jcb'],
                ['novalnetcartebleue', 'Carte Bleue']
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
