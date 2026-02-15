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
                #wapf-field-group-variables .wapf-list--empty {
                    display: block !important;
                }
            </style>
            <script type="text/javascript">
            // WAPF Admin Fix: Manual Override & Sync
            jQuery(window).on('load', function() {
                var $ = jQuery;

                // 1. Force UI Visibility
                $('.wapf-list--empty').hide(); 
                $('.wapf-performance').hide();

                // 2. Auto-sync on form submit
                $('form#post').on('submit', function() {
                    syncWapfFields(true); // Silent sync
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
                $(document).on('click', '.wapf-field-list__footer .button-primary', function(e) {
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

                // 7. Manual Data Sync Function (Hybrid: Preserve choices, update edits)
                function syncWapfFields(silent) {
                    var $container = $('[data-raw-fields]');
                    if ($container.length === 0) return;
                    
                    var rawFields = $container.attr('data-raw-fields');
                    var fields = JSON.parse(rawFields);
                    
                    // Update each field with current DOM edits
                    $('.wapf-field').each(function(index) {
                        var $el = $(this);
                        var fieldId = $el.attr('data-field-id');
                        var field = fields.find(f => f.id === fieldId);
                        if (!field) return;
                        
                        // Update with current DOM values
                        var label = $el.find('[data-setting="label"] input').val();
                        if (label !== undefined) field.label = label;
                        
                        var desc = $el.find('[data-setting="description"] textarea').val();
                        if (desc !== undefined) field.description = desc;
                        
                        field.required = $el.find('[data-setting="required"] input').is(':checked');
                        
                        // Scrape choices/options if they exist in DOM
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
                                    pricing_amount: parseFloat($opt.find('input[type="number"]').val()) || 0
                                });
                            });
                            if (choices.length > 0) field.choices = choices;
                        }
                    });
                    var jsonString = JSON.stringify(fields);
                    $('input[name="wapf-fields"]').val(jsonString);
                    $container.attr('data-raw-fields', jsonString);
                }
            });
            
            </script>
            <?php
        }
    });

}, 99 );
