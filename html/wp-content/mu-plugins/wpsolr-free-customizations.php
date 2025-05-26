<?php

/**
 * Plugin Name: WPSOLR Free Customizations
 */

use wpsolr\core\classes\WPSOLR_Events;
use wpsolr\core\classes\WpSolrSchema;


add_action('plugins_loaded', function () {
    error_log('WPSOLR Free Customizations plugin loaded.');
    // Check if the WPSOLR plugin is active
    // if (! defined('WPSOLR_PLUGIN_VERSION') || ! class_exists('wpsolr\core\classes\WPSOLR_Events') || ! class_exists('wpsolr\core\classes\WpSolrSchema') || ! defined('WPSOLR_Events::WPSOLR_FILTER_FIELDS') || ! defined('WpSolrSchema::_FIELD_NAME_CATEGORIES_STR') || ! defined('WpSolrSchema::_FIELD_NAME_POST_HREF_STR') || ! defined('WpSolrSchema::_FIELD_NAME_SNIPPET_S')) {
    //     return; // WPSOLR is not active, exit
    // }
    add_filter(WPSOLR_Events::WPSOLR_FILTER_FIELDS, function ($fields) {
        error_log('WPSOLR Free Customizations plugin: removing fields from schema.');
        // print the fields before removal
        error_log('WPSOLR Free Customizations plugin: fields before removal: ' . print_r($fields, true));
        $fields_to_remove = [
            '*' . WpSolrSchema::_FIELD_NAME_CATEGORIES_STR,
            '*' . WpSolrSchema::_FIELD_NAME_POST_HREF_STR,
            WpSolrSchema::_FIELD_NAME_SNIPPET_S,
        ];
        // print the fields to remove
        error_log('WPSOLR Free Customizations plugin: fields to remove: ' . print_r($fields_to_remove, true));
        // Remove the fields from the schema
        $fields = array_filter($fields, function ($field) use ($fields_to_remove) {
            // Check if the field starts with any of the fields to remove
            foreach ($fields_to_remove as $field_to_remove) {
                if (strpos($field, $field_to_remove) === 0) {
                    return false; // Remove this field
                }
            }
            return true; // Keep this field
        });
        // print the fields
        error_log('WPSOLR Free Customizations plugin: fields after removal: ' . print_r($fields, true));
        return $fields;
    }, 10, 1);
});
