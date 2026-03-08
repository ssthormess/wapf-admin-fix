<?php
/**
 * Plugin Name: WAPF Admin Fix
 * Plugin URI: https://github.com/ssthormess/wapf-admin-fix
 * Description: Restores administrative interface functionality for Advanced Product Fields for WooCommerce. Enables local development and testing by bypassing license validation while maintaining full admin capabilities.
 * Version: 1.0.0
 * Author: ssthormess
 * Author URI: https://github.com/ssthormess
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wapf-admin-fix
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested with WAPF: 3.1.5
 * 
 * This plugin is intended for use with Advanced Product Fields for WooCommerce,
 * which is licensed under the GNU General Public License (GPL).
 * Under the terms of the GPL, users have the right to modify and extend the software.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action( 'init', function() {

    // Define our bypassed license data with a realistic key
    $license_data = [
        'key'              => 'a1b2c3d4e5f67890a1b2c3d4e5f67890', 
        'status'           => 'valid',
        'expires'          => '2099-01-01',
        'limit'            => 999,
        'activations_left' => 999,
        'email'            => 'admin@example.com'
    ];

    $json_license = json_encode($license_data);
    $base64_license = base64_encode($json_license);

    // Filter to return our bypassed license when the plugin requests it
    add_filter('pre_option_advanced-product-fields-for-woocommerce-pro_license', function($value) use ($base64_license) {
        return $base64_license;
    }, 999, 1);

    // Also filter the option directly just in case
    add_filter('option_advanced-product-fields-for-woocommerce-pro_license', function($value) use ($base64_license) {
        return $base64_license;
    }, 999, 1);

    // Bypass update checks
    add_filter('pre_option_wapf_last_update_check', function() {
        return time();
    }, 999);

    add_action('admin_footer', function() {
        $is_wapf = false;
        
        // 1. Check Screen
        $screen = get_current_screen();
        if ($screen && in_array($screen->post_type, ['wapf_product', 'product'])) {
            $is_wapf = true;
        }

        // 2. Check Global Post
        if (!$is_wapf && isset($GLOBALS['post']) && in_array($GLOBALS['post']->post_type, ['wapf_product', 'product'])) {
            $is_wapf = true;
        }

        // 3. Check URL Param 'post'
        if (!$is_wapf && isset($_GET['post'])) {
            $post_id = intval($_GET['post']);
            if ($post_id > 0) {
                $type = get_post_type($post_id);
                if (in_array($type, ['wapf_product', 'product'])) {
                    $is_wapf = true;
                }
            }
        }
        
        // 4. Check URL Param 'post_type' (New Post)
        if (!$is_wapf && isset($_GET['post_type']) && in_array($_GET['post_type'], ['wapf_product', 'product'])) {
             $is_wapf = true;
        }

        if ($is_wapf) {
            ?>
            <style>
                .wapf-block-pro {
                    zoom: 0.000000000000000000000001 !important;
                }
                #wapf-field-group-variables .wapf-list--empty,
                #wapf-field-group-conditions .wapf-list--empty {
                    display: block !important;
                }
                #wapf-field-group-conditions .wapf-list--empty:has(~ div:not(.wapf-list--empty)) {
                    display: none !important;
                }
            </style>
            <script type="text/javascript">
            // WAPF Admin Fix: Manual Override & Sync
            jQuery(window).on('load', function() {
                var $ = jQuery;

                // 1. Force UI Visibility
                $('.wapf-list--empty').hide(); 
                $('.wapf-performance').hide();
                
                // Show conditions empty state if no rules
                var $condList = $('.apf-conditions-wrapper');
                if ($condList.length > 0 && $condList.children().length === 0) {
                    $('#wapf-field-group-conditions .wapf-list--empty').show();
                }
                
                // Show fields empty state if no fields
                var $rawFields = $('[data-raw-fields]');
                if ($rawFields.length > 0 && ($rawFields.attr('data-raw-fields') === '[]' || $rawFields.attr('data-raw-fields') === '' || !$rawFields.attr('data-raw-fields'))) {
                     $('.wapf-field-list__body > .wapf-list--empty').show();
                     $('.wapf-field-list__footer').hide();
                } else {
                     $('.wapf-field-list__footer').show();
                }

                // 2. Auto-sync on form submit
                $('form#post').on('submit', function() {
                    syncWapfFields(true); // Silent sync
                    syncWapfConditions(true);
                });

                // 3. Inject Input & Inject Requirements
                // We replace the input to stop the broken JS from overwriting our values
                var $originalInput = $('input[name="wapf-fields"]');
                if ($originalInput.length > 0 && $originalInput.attr('rv-value')) {
                    var $freshInput = $('<input type="hidden" name="wapf-fields">');
                    $freshInput.val($originalInput.val());
                    $originalInput.replaceWith($freshInput);
                } else if ($originalInput.length === 0) {
                     $('<input type="hidden" name="wapf-fields">').appendTo('form#post');
                }

                // Inject wapf-conditions
                var $originalCondInput = $('input[name="wapf-conditions"]');
                if ($originalCondInput.length > 0 && ($originalCondInput.attr('rv-value') || $originalCondInput.attr('data-rv-value'))) {
                    var $freshCondInput = $('<input type="hidden" name="wapf-conditions">');
                    $freshCondInput.val($originalCondInput.val());
                    $originalCondInput.replaceWith($freshCondInput);
                }

                // Inject ID and Type which are required by the backend
                if ($('input[name="wapf-fieldgroup-id"]').length === 0) {
                    var postID = $('#post_ID').val();
                    $('<input type="hidden" name="wapf-fieldgroup-id">').val(postID).appendTo('form#post');
                }
                
                if ($('input[name="wapf-fieldgroup-type"]').length === 0) {
                     $('<input type="hidden" name="wapf-fieldgroup-type">').val('wapf_product').appendTo('form#post');
                }

                // Helper function for generating field IDs (matches WAPF.Api.Helpers.uniqueId())
                function generateFieldId() {
                    function toHex(num, len) {
                        var hex = parseInt(num, 10).toString(16);
                        if (len < hex.length) return hex.slice(hex.length - len);
                        if (len > hex.length) return Array(len - hex.length + 1).join('0') + hex;
                        return hex;
                    }
                    var counter = Math.floor(Math.random() * 123456789);
                    counter++;
                    var id = '';
                    // Get timestamp in seconds (not milliseconds)
                    var timestamp = Math.floor((new Date()).getTime() / 1000);
                    id += toHex(timestamp, 8);
                    id += toHex(counter, 5);
                    return id;
                }

                // 4. Manual "Add a Field" button handler (workaround for TinyBind detachment)
                $(document).on('click', '.wapf-field-list__footer .button-primary, .wapf-field-list__body .wapf-list--empty .button-primary', function(e) {
                    e.preventDefault();
                    
                    var $fieldList = $('[data-raw-fields]');
                    if ($fieldList.length === 0) return;
                    
                    var rawFields = $fieldList.attr('data-raw-fields');
                    var fields = JSON.parse(rawFields);
                    
                    // Create new field with defaults
                    var newField = {
                        id: generateFieldId(),
                        label: 'New field',
                        type: 'text',
                        required: false,
                        description: '',
                        conditionals: [],
                        pricing: {
                            enabled: false,
                            type: 'fixed',
                            amount: 0
                        },
                        qty_based: false,
                        level: 0,
                        group: 'field'
                    };
                    
                    fields.push(newField);
                    
                    $fieldList.attr('data-raw-fields', JSON.stringify(fields));
                    $('input[name="wapf-fields"]').val(JSON.stringify(fields));
                    $('#publish').trigger('click');
                });

                // 4.1 Manual "Import Fields" button handler
                $(document).on('click', '.btn-wapf-import', function(e) {
                    var importData = $('.wapf-import-ta').val();
                    var mode = $('.wapf-import-mode').val();
                    if (!importData) return;
                    
                    try {
                        var parsed = JSON.parse(importData);
                        if (!Array.isArray(parsed)) return;
                        
                        var $fieldList = $('[data-raw-fields]');
                        if ($fieldList.length === 0) return;
                        
                        var rawFields = $fieldList.attr('data-raw-fields');
                        var fields = JSON.parse(rawFields || '[]');
                        
                        if (mode === 'replace') {
                            fields = parsed;
                        } else {
                            fields = fields.concat(parsed);
                        }
                        
                        $fieldList.attr('data-raw-fields', JSON.stringify(fields));
                        $('input[name="wapf-fields"]').val(JSON.stringify(fields));
                        
                        // Give TinyBind a moment to process original JS if any, then save
                        setTimeout(function() { $('#publish').trigger('click'); }, 500); 
                    } catch(err) {
                        console.error('Failed to parse and update imported raw fields', err);
                    }
                });

                // 5. Manual "Duplicate Field" button handler
                $(document).on('click', '.wapf-action-dupe', function(e) {
                    e.preventDefault();
                    
                    var $field = $(this).closest('.wapf-field');
                    var fieldId = $field.data('field-id');
                    var $fieldList = $('[data-raw-fields]');
                    
                    if ($fieldList.length === 0) return;
                    
                    var rawFields = $fieldList.attr('data-raw-fields');
                    var fields = JSON.parse(rawFields);
                    
                    // Find the field to duplicate
                    var fieldToDupe = null;
                    var fieldIndex = -1;
                    for (var i = 0; i < fields.length; i++) {
                        if (fields[i].id === fieldId) {
                            fieldToDupe = fields[i];
                            fieldIndex = i;
                            break;
                        }
                    }
                    
                    if (!fieldToDupe) return;
                    
                    // Clone the field and generate new ID
                    var duplicatedField = JSON.parse(JSON.stringify(fieldToDupe));
                    duplicatedField.id = generateFieldId();
                    duplicatedField.label = fieldToDupe.label + ' (Copy)';
                    
                    // Insert after the original
                    fields.splice(fieldIndex + 1, 0, duplicatedField);
                    
                    $fieldList.attr('data-raw-fields', JSON.stringify(fields));
                    $('input[name="wapf-fields"]').val(JSON.stringify(fields));
                    $('#publish').trigger('click');
                });

                // 6. Manual "Delete Field" button handler
                $(document).on('click', '.wapf-field-actions a[style*="color: #a00"]', function(e) {
                    e.preventDefault();
                    
                    var $field = $(this).closest('.wapf-field');
                    var fieldId = $field.data('field-id');
                    
                    if (!fieldId) {
                        console.error('No field ID found!');
                        alert('Error: Cannot find field ID. The field cannot be deleted.');
                        return;
                    }
                    
                    if (!confirm('Are you sure you want to delete this field?')) return;
                    
                    var $fieldList = $('[data-raw-fields]');
                    
                    if ($fieldList.length === 0) {
                        console.error('No field list found!');
                        return;
                    }
                    
                    var rawFields = $fieldList.attr('data-raw-fields');
                    var fields = JSON.parse(rawFields);
                    
                    // Remove the field
                    var found = false;
                    for (var i = 0; i < fields.length; i++) {
                        if (fields[i].id === fieldId) {
                            fields.splice(i, 1);
                            found = true;
                            break;
                        }
                    }
                    
                    if (!found) {
                        console.error('Field not found in fields array!');
                        alert('Error: Field not found. Cannot delete.');
                        return;
                    }
                    
                    $fieldList.attr('data-raw-fields', JSON.stringify(fields));
                    $('input[name="wapf-fields"]').val(JSON.stringify(fields));
                    $('#publish').trigger('click');
                });

                // 7. Manual "Conditions" handlers
                // Add your first rule
                $(document).on('click', '#wapf-field-group-conditions .wapf-list--empty .button-primary', function(e) {
                    e.preventDefault();
                    var $condList = $('[data-raw-conditions]');
                    var conds = JSON.parse($condList.attr('data-raw-conditions') || '[]');
                    
                    // Add first group with one rule
                    conds.push({
                        rules: [{
                            subject: 'product_tag',
                            condition: 'p_tags',
                            value: []
                        }]
                    });
                    
                    saveConditions($condList, conds);
                });

                // "And" rule handler (add rule to group)
                $(document).on('click', '#wapf-field-group-conditions .apf-condition-group-wrapper .apf-button', function(e) {
                    if ($(this).text().trim().toLowerCase() !== 'and') return;
                    e.preventDefault();
                    
                    var $groupWrapper = $(this).closest('.apf-condition-group-wrapper');
                    var match = $groupWrapper.attr('class').match(/wapf-rulegroup-(\d+)/);
                    if (!match) return;
                    var groupIdx = parseInt(match[1]);
                    
                    var $condList = $('[data-raw-conditions]');
                    var conds = JSON.parse($condList.attr('data-raw-conditions') || '[]');
                    
                    if (conds[groupIdx]) {
                        conds[groupIdx].rules.push({
                            subject: 'product_tag',
                            condition: 'p_tags',
                            value: []
                        });
                        saveConditions($condList, conds);
                    }
                });

                // "Or" group handler (add new group)
                $(document).on('click', '#wapf-field-group-conditions .apf-conditions-footer .apf-button', function(e) {
                    if ($(this).text().trim().toLowerCase() !== 'or') return;
                    e.preventDefault();
                    
                    var $condList = $('[data-raw-conditions]');
                    var conds = JSON.parse($condList.attr('data-raw-conditions') || '[]');
                    
                    conds.push({
                        rules: [{
                            subject: 'product_tag',
                            condition: 'p_tags',
                            value: []
                        }]
                    });
                    
                    saveConditions($condList, conds);
                });

                // Delete rule handler
                $(document).on('click', '#wapf-field-group-conditions .apf-condition-group-wrapper .apf-button-transparent', function(e) {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to delete this rule?')) return;
                    
                    var $row = $(this).closest('tr');
                    var $groupWrapper = $(this).closest('.apf-condition-group-wrapper');
                    
                    var groupMatch = $groupWrapper.attr('class').match(/wapf-rulegroup-(\d+)/);
                    var ruleMatch = $row.attr('class').match(/wapf-rulegroup-rule-(\d+)/);
                    if (!groupMatch || !ruleMatch) return;
                    
                    var groupIdx = parseInt(groupMatch[1]);
                    var ruleIdx = parseInt(ruleMatch[1]);
                    
                    var $condList = $('[data-raw-conditions]');
                    var conds = JSON.parse($condList.attr('data-raw-conditions') || '[]');
                    
                    if (conds[groupIdx] && conds[groupIdx].rules[ruleIdx]) {
                        conds[groupIdx].rules.splice(ruleIdx, 1);
                        
                        // If group is empty, remove group
                        if (conds[groupIdx].rules.length === 0) {
                            conds.splice(groupIdx, 1);
                        }
                        
                        saveConditions($condList, conds);
                    }
                });

                function saveConditions($el, conds) {
                    var json = JSON.stringify(conds);
                    $el.attr('data-raw-conditions', json);
                    $('input[name="wapf-conditions"]').val(json);
                    $('#publish').trigger('click');
                }

                // 8. Manual Data Sync Functions
                function syncWapfFields(silent) {
                    var $container = $('[data-raw-fields]');
                    if ($container.length === 0) return;
                    
                    var rawFields = $container.attr('data-raw-fields');
                    var fields = JSON.parse(rawFields);
                    
                    // Settings that are handled explicitly elsewhere in this function
                    // and should NOT be overwritten by the generic scraper below.
                    var skipSettings = [
                        'label', 'description', 'required',
                        'hide_cart', 'hide_checkout', 'hide_order',
                        'attributes', 'clone', 'conditionals', 'pricing',
                        'options' // choices use their own loop
                    ];

                    // Update each field with current DOM edits
                    $('.wapf-field').each(function(index) {
                        var $el = $(this);
                        var fieldId = $el.attr('data-field-id');
                        var field = fields.find(f => f.id === fieldId);
                        if (!field) return;
                        
                        // --- Core always-present settings ---
                        var label = $el.find('[data-setting="label"] input').val();
                        if (label !== undefined) field.label = label;
                        
                        var desc = $el.find('[data-setting="description"] textarea').val();
                        if (desc !== undefined) field.description = desc;
                        
                        field.required = $el.find('[data-setting="required"] input').is(':checked');

                        // --- Generic scraper: capture ALL [data-setting] values ---
                        // This covers: placeholder, default, pattern, minlength, maxlength,
                        // minimum, maximum, number_type, display, message, label_true,
                        // label_false, label_pos, grid_layout, item_width, etc.
                        $el.find('[data-setting]').each(function() {
                            var $setting = $(this);
                            var settingKey = $setting.attr('data-setting');

                            // Skip settings handled explicitly below
                            if (skipSettings.indexOf(settingKey) !== -1) return;

                            // Read the value from the visible/active input inside this setting wrapper
                            var $input = $setting.find('input[type="text"]:visible, input[type="number"]:visible, input[type="email"]:visible, input[type="url"]:visible, textarea:visible, select:visible').first();

                            if ($input.length === 0) return; // nothing visible to read

                            var val = $input.val();
                            if (val === undefined) return;

                            // Empty string → remove the key so WAPF uses its default
                            if (val === '') {
                                delete field[settingKey];
                            } else {
                                field[settingKey] = val;
                            }
                        });
                        
                        // --- Choices/options (handled separately because they're complex) ---
                        var $options = $el.find('.wapf-option');
                        if ($options.length > 0) {
                            var choices = [];
                            $options.each(function() {
                                var $opt = $(this);
                                choices.push({
                                    slug: $opt.attr('data-option-slug'),
                                    label: $opt.find('.choice-label').val(),
                                    selected: $opt.find('.wapf-option__selected input').is(':checked'),
                                    disabled: $opt.find('.wapf-option__disabled input').is(':checked'),
                                    options: [],
                                    pricing_type: $opt.find('.wapf-pricing-list').val() || 'none',
                                    pricing_amount: ($opt.find('.wapf-pricing-list').val() === 'fx') 
                                        ? $opt.find('.wapf-input-prepend-append input[type="text"]').val() || '' 
                                        : (parseFloat($opt.find('input[type="number"]').val()) || 0)
                                });
                            });
                            if (choices.length > 0) field.choices = choices;
                        }

                        // --- Appearance: hide on cart/checkout/order ---
                        var $hideCart = $el.find('[data-setting="hide_cart"] input[type="checkbox"]');
                        if ($hideCart.length > 0) field.hide_cart = $hideCart.is(':checked');
                        
                        var $hideCheckout = $el.find('[data-setting="hide_checkout"] input[type="checkbox"]');
                        if ($hideCheckout.length > 0) field.hide_checkout = $hideCheckout.is(':checked');
                        
                        var $hideOrder = $el.find('[data-setting="hide_order"] input[type="checkbox"]');
                        if ($hideOrder.length > 0) field.hide_order = $hideOrder.is(':checked');

                        // --- Appearance: wrapper attributes (width + CSS class) ---
                        var $attrWidth = $el.find('[data-setting="attributes"] .wapf-input-prepend-append input[type="number"]');
                        var $attrClass = $el.find('[data-setting="attributes"] .wapf-input-with-prepend input[type="text"]');
                        if ($attrWidth.length > 0 || $attrClass.length > 0) {
                            var w = $attrWidth.val();
                            var c = $attrClass.val();
                            if (w === '') {
                                delete field.width;
                            } else if (w !== undefined) {
                                field.width = w;
                            }
                            if (c !== undefined) field.class = c;
                        }

                        // --- Pricing ---
                        var $pricingWrapper = $el.find('[data-setting="pricing"]');
                        if ($pricingWrapper.length > 0) {
                            var $pricingEnable = $pricingWrapper.find('.wapf-toggle input[type="checkbox"]').first();
                            if ($pricingEnable.length > 0) {
                                if (!field.pricing) field.pricing = { enabled: false, type: 'fixed', amount: 0 };
                                field.pricing.enabled = $pricingEnable.is(':checked');
                                var pricingType = $pricingWrapper.find('select:visible').val();
                                if (pricingType) field.pricing.type = pricingType;
                                // Amount: formula field (text) or numeric field
                                var $pricingAmountFx = $pricingWrapper.find('input[type="text"]:visible').first();
                                var $pricingAmountNr = $pricingWrapper.find('input[type="number"]:visible').first();
                                if ($pricingAmountFx.length > 0) {
                                    field.pricing.amount = $pricingAmountFx.val();
                                } else if ($pricingAmountNr.length > 0) {
                                    field.pricing.amount = parseFloat($pricingAmountNr.val()) || 0;
                                }
                            }
                        }

                        // --- Advanced: Clone/Repeater ---
                        var $cloneInputs = $el.find('[data-setting="clone"]');
                        if ($cloneInputs.length > 0) {
                            var $cloneEnable = $cloneInputs.find('.wapf-toggle input[type="checkbox"]');
                            if ($cloneEnable.length > 0) {
                                if (!field.clone) field.clone = {};
                                field.clone.enabled = $cloneEnable.is(':checked');
                                field.clone.type = $cloneInputs.find('select').val();
                                var $texts = $cloneInputs.find('input[type="text"]');
                                if ($texts.length > 0) field.clone.label = $texts.eq(0).val();
                                if ($texts.length > 1) field.clone.add = $texts.eq(1).val();
                                if ($texts.length > 2) field.clone.del = $texts.eq(2).val();
                                var $maxInput = $cloneInputs.find('input[type="number"]');
                                if ($maxInput.length > 0) field.clone.max = $maxInput.eq(0).val();
                            }
                        }

                        // --- Advanced: Field conditionals ---
                        var $fieldConds = $el.find('.wapf-field__conditionals');
                        if ($fieldConds.length > 0) {
                            var conditionals = [];
                            var $fieldCondsGroups = $fieldConds.find('.apf-condition-group-wrapper > div');
                            $fieldCondsGroups.each(function() {
                                var $group = $(this);
                                var rules = [];
                                $group.find('tr.conditional__rule').each(function() {
                                    var $rule = $(this);
                                    var fieldVal = $rule.find('td:eq(0) select').val();
                                    var condVal = $rule.find('td:eq(1) select').val();
                                    var valueVal = $rule.find('td:eq(2) input:visible, td:eq(2) select:visible').val();
                                    
                                    if (fieldVal && condVal) {
                                        rules.push({
                                            field: fieldVal,
                                            condition: condVal,
                                            value: valueVal
                                        });
                                    }
                                });
                                if (rules.length > 0) {
                                    conditionals.push({ rules: rules });
                                }
                            });
                            field.conditionals = conditionals;
                        }
                    });
                    var jsonString = JSON.stringify(fields);
                    $('input[name="wapf-fields"]').val(jsonString);
                    $container.attr('data-raw-fields', jsonString);
                }

                function syncWapfConditions(silent) {
                    var $container = $('[data-raw-conditions]');
                    if ($container.length === 0) return;
                    
                    var conds = JSON.parse($container.attr('data-raw-conditions') || '[]');
                    
                    $('.apf-condition-group-wrapper').each(function(groupIdx) {
                        var $group = $(this);
                        if (!conds[groupIdx]) return;
                        
                        $group.find('tr').each(function(ruleIdx) {
                            var $rule = $(this);
                            var rule = conds[groupIdx].rules[ruleIdx];
                            if (!rule) return;
                            
                            // Update rule from DOM
                            rule.subject = $rule.find('td:eq(0) select').val();
                            rule.condition = $rule.find('td:eq(1) select:visible').val() || $rule.find('td:eq(2) select:visible').val();
                            
                            // Values are harder due to Select2
                            var $select2 = $rule.find('.wapf-select2');
                            if ($select2.length > 0) {
                                var val = [];
                                $select2.find('option:selected').each(function() {
                                    val.push({ id: $(this).val(), text: $(this).text() });
                                });
                                rule.value = val;
                            } else {
                                rule.value = $rule.find('td:eq(3) input, td:eq(3) select').val();
                            }
                        });
                    });
                    
                    var json = JSON.stringify(conds);
                    $('input[name="wapf-conditions"]').val(json);
                    $container.attr('data-raw-conditions', json);
                }
            });
            
            </script>
            <?php
        }
    });

}, 99 );
