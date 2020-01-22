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

Ms3CAjaxSearchController = function(formName)
{
    this.formName = formName;
    this.controls = {};
    this.init = function() {
        let form = jQuery('#'+this.formName);
        let controls = form.find('.mS3Control');
        let me = this;
        jQuery(controls).each(function(idx, elem) {
            // Assume checkbox for now
            let ctrl = jQuery(elem);
            let type = ctrl.data('controltype');
            let attr = ctrl.data('attribute');
            switch (type) {
                case 'checkbox':
                    me.controls[attr] = new Ms3CAjaxSearchCheckbox(ctrl);
                    break;
            }
        })
    };

    this.initializeFilters = function(filterValues) {
        for (let key in filterValues) {
            if (key in this.controls) {
                this.controls[key].initialValues = filterValues[key];
                this.controls[key].reset();
            }
        }
    }
};

Ms3CAjaxSearchCheckbox = function(element)
{
    this.element = element;
    this.initialValues = [];
    this.reset = function() {
        let val= '';
        for (let k in this.initialValues) {
            val += '<input type="checkbox" value="' + this.initialValues[k].ContentPlain + '">' + this.initialValues[k].ContentHtml + '</intput><br/>';
        }
        jQuery(jQuery(this.element).children('.filterValues')[0]).html(val);
    }
};
