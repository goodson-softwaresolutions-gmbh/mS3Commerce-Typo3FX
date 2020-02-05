/***************************************************************
 * Part of mS3 Commerce Fx
 * Copyright (C) 2019 Goodson GmbH <http://www.goodson.at>
 *  All rights reserved
 *
 * Dieses Computerprogramm ist urheberrechtlich sowie durch internationale
 * Abkommen geschützt. Die unerlaubte Reproduktion oder Weitergabe dieses
 * Programms oder von Teilen dieses Programms kann eine zivil- oder
 * strafrechtliche Ahndung nach sich ziehen und wird gemäß der geltenden
 * Rechtsprechung mit größtmöglicher Härte verfolgt.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

Ms3CAjaxSearchControlFactory = {
    controlFactory: {},
    registerControlType: function(type, ctr) {
        Ms3CAjaxSearchControlFactory.controlFactory[type] = ctr;
    },
    hasControlType: function(type) {
        return type in Ms3CAjaxSearchControlFactory.controlFactory;
    },
    getControlBuilder: function(type) {
        if (Ms3CAjaxSearchControlFactory.hasControlType(type)) {
            let ctr = Ms3CAjaxSearchControlFactory.controlFactory[type];
            return ctr;
        }
        return null;
    }
};
Ms3CAjaxSearchControlFactory.registerControlType('checkbox', Ms3CAjaxSearchCheckbox);

function Ms3CAjaxSearchController(formName) {
    this.formName = formName;
    this.form = jQuery('#' + formName);
    this.controls = [];
    this.data = {};
}
(function() {
    Ms3CAjaxSearchController.prototype.init = function(data) {
        let controls = this.form.find('.mS3Control');
        let me = this;
        this.data = data;
        jQuery(controls).each(function (idx, elem) {
            let ctrl = jQuery(elem);
            let type = ctrl.data('controltype');
            let attr = ctrl.data('attribute');

            if (Ms3CAjaxSearchControlFactory.hasControlType(type)) {
                let ctr = Ms3CAjaxSearchControlFactory.getControlBuilder(type);
                me.controls[attr] = new ctr(me, attr, elem);
            }
        });

        this.form.submit(function() {
            me.applyFilter();
            return false;
        });
    };

    Ms3CAjaxSearchController.prototype.initializeFilters = function (filterValues) {
        for (let key in filterValues) {
            if (key in this.controls) {
                this.controls[key].initialValues = filterValues[key];
            }
        }
        this.reset();
    };

    Ms3CAjaxSearchController.prototype.reset = function () {
        for (let key in this.controls) {
            this.controls[key].reset();
        }
    };

    Ms3CAjaxSearchController.prototype.getFilterAttributes = function() {
        let filterAttributes = [];
        for (let key in this.controls) { filterAttributes.push(this.controls[key].attribute); }
        return filterAttributes;
    };

    Ms3CAjaxSearchController.prototype.applyFilter = function() {
        let filters = this.getSelectedFilters();
        this.filterProducts(filters);
    };

    Ms3CAjaxSearchController.prototype.getSelectedFilters = function() {
        let filters = {};
        for (let key in this.controls) {
            let f = this.controls[key].getSelectedValues();
            if (f != null && f.length > 0) filters[key] = f;
        }
        return filters;
    };

    Ms3CAjaxSearchController.prototype.filterSelectionChanged = function(ctrl) {
        this.form.submit();
    };

    Ms3CAjaxSearchController.prototype.displayFitlerResult = function(result) {
        jQuery('#'+this.data['resultElement']).html(result);
    };

    Ms3CAjaxSearchController.prototype.updateAvailableFilters = function(filters, selectedValues) {
        for (key in this.controls) {
            let ctrl = this.controls[key];
            if (key in filters) {
                let selValues = (key in selectedValues) ? selectedValues[key] : [];
                ctrl.setFilterValues(filters[key], selValues);
                ctrl.show();
            } else {
                // No values for this filter
                ctrl.reset();
                ctrl.hide();
            }
        }
    };

    Ms3CAjaxSearchController.prototype.filterProducts = function(filters) {
        let me = this;
        let filterAttributes = this.getFilterAttributes();

        let data = {
            selectedFilters: filters,
            filterAttributes: filterAttributes
        };
        jQuery.ajax(me.form.attr('action'), {
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            beforeSend: function(xhr) { me.beforeSendFilter(xhr); },
            complete: function(xhr, textStatus) { me.afterSendFilter(xhr, textStatus); },
            success: function(data, textStatus, xhr) { me.receiveFilterResult(filters, data, textStatus, xhr); },
            error: function(xhr, textStatus, err) { me.errorFilterResult(xhr, textStatus, err); },
            data: JSON.stringify(data)
        });
    };

    Ms3CAjaxSearchController.prototype.beforeSendFilter = function(xhr) {};
    Ms3CAjaxSearchController.prototype.afterSendFilter = function(xhr, textStatus) {};
    Ms3CAjaxSearchController.prototype.errorFilterResult = function(xhr, textStatus, err) {};
    Ms3CAjaxSearchController.prototype.receiveFilterResult = function(selectedFilters, data, textStatus, xhr) {
        this.updateAvailableFilters(data.filter, selectedFilters);
        this.displayFitlerResult(data.result);
    };
})();


function Ms3CAjaxSearchCheckbox(controller, attribute, element) {
    this.controller = controller;
    this.attribute = attribute;
    this.element = jQuery(element);
    this.initialValues = [];
}
(function() {
    Ms3CAjaxSearchCheckbox.prototype.reset = function () {
        this.setFilterValues(this.initialValues, []);
    };

    Ms3CAjaxSearchCheckbox.prototype.hide = function() {
        this.element.css('display', 'none');
    };

    Ms3CAjaxSearchCheckbox.prototype.show = function() {
        this.element.css('display', 'block');
    };

    Ms3CAjaxSearchCheckbox.prototype.getFilterValueContainer = function() {
        return this.element.children('.filterValues').first();
    };

    Ms3CAjaxSearchCheckbox.prototype.setFilterValues = function(values, selectedValues) {
        let val = '';
        for (let k in values) {
            let isSelected = selectedValues.indexOf(values[k].ContentPlain) >= 0;
            val += this.buildElement(values[k].ContentPlain, values[k].ContentHtml, isSelected);
        }
        this.getFilterValueContainer(this.element).html(val);
        this.activateElements();
    };

    Ms3CAjaxSearchCheckbox.prototype.buildElement = function(value, display, isSelected) {
        let selStr = isSelected ? ' checked="checked"' : '';
        return '<input type="checkbox" value="' + value + '"'+selStr+'>' + display + '</intput><br/>';
    };

    Ms3CAjaxSearchCheckbox.prototype.activateElements = function() {
        let me = this;
        this.element.find('input[type=checkbox]').change(function() {
            me.selectionChanged(this);
        });
    };

    Ms3CAjaxSearchCheckbox.prototype.selectionChanged = function(elem) {
        this.controller.filterSelectionChanged(this);
    };

    Ms3CAjaxSearchCheckbox.prototype.getSelectedValues = function() {
        let sel = [];
        this.element.find('input[type=checkbox]:checked').each(function(){sel.push(this.value);});
        return sel;
    };
})();
