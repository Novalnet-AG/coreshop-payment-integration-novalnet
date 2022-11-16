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

pimcore.registerNS('coreshop.provider.gateways.novalnet_post_finance_card');
coreshop.provider.gateways.novalnet_post_finance_card = Class.create(coreshop.provider.gateways.abstract, {
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
            }
        ];
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
    }
});
