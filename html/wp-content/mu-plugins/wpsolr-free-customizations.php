<?php

/**
 * Plugin Name: WPSOLR Free Customizations
 * Description: Customizations for WPSOLR Free plugin
 * Version: 1.0.0
 * Author: Your Name
 */

// Only execute the plugin's functionality if WPSOLR is active and properly loaded
add_action('plugins_loaded', function () {
    // Check if WPSOLR plugin is active by checking for key classes
    if (
        !class_exists('wpsolr\core\classes\WPSOLR_Events') ||
        !class_exists('wpsolr\core\classes\WpSolrSchema')
    ) {
        // WPSOLR is not active, log message and exit
        error_log('WPSOLR Free Customizations: WPSOLR plugin is not active or classes not found.');
        return;
    }

    // Check if the filter constant exists
    if (!defined('wpsolr\core\classes\WPSOLR_Events::WPSOLR_FILTER_FIELDS')) {
        error_log('WPSOLR Free Customizations: WPSOLR_FILTER_FIELDS constant not defined, skipping field removal.');
        return;
    }

    $filter_hook = wpsolr\core\classes\WPSOLR_Events::WPSOLR_FILTER_FIELDS;

    // Add our filter with error handling
    add_filter($filter_hook, function ($fields) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WPSOLR Free Customizations: Starting to process fields for removal.');
        }

        // Check if $fields is an array before processing
        if (!is_array($fields)) {
            error_log('WPSOLR Free Customizations: $fields is not an array, cannot process');
            return $fields;
        }

        // Initialize empty array of fields to remove
        $fields_to_remove = [];

        // Only add fields to remove list if their constants are defined
        if (defined('wpsolr\core\classes\WpSolrSchema::_FIELD_NAME_CATEGORIES_STR')) {
            $fields_to_remove[] = '*' . wpsolr\core\classes\WpSolrSchema::_FIELD_NAME_CATEGORIES_STR;
        }

        if (defined('wpsolr\core\classes\WpSolrSchema::_FIELD_NAME_POST_HREF_STR')) {
            $fields_to_remove[] = '*' . wpsolr\core\classes\WpSolrSchema::_FIELD_NAME_POST_HREF_STR;
        }

        if (defined('wpsolr\core\classes\WpSolrSchema::_FIELD_NAME_SNIPPET_S')) {
            $fields_to_remove[] = wpsolr\core\classes\WpSolrSchema::_FIELD_NAME_SNIPPET_S;
        }

        // If no fields to remove, return original fields
        if (empty($fields_to_remove)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WPSOLR Free Customizations: No fields to remove, returning original fields.');
            }
            return $fields;
        }

        // Log debugging information
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WPSOLR Free Customizations: Fields before removal: ' . print_r($fields, true));
            error_log('WPSOLR Free Customizations: Fields to remove: ' . print_r($fields_to_remove, true));
        }

        // Remove the fields from the schema using safe array filtering
        $filtered_fields = array_filter($fields, function ($field) use ($fields_to_remove) {
            // Make sure $field is a string before checking
            if (!is_string($field)) {
                return true; // Keep non-string fields
            }

            // Check if the field starts with any of the fields to remove
            foreach ($fields_to_remove as $field_to_remove) {
                if (strpos($field, $field_to_remove) === 0) {
                    return false; // Remove this field
                }
            }
            return true; // Keep this field
        });

        // Log the results if debugging is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WPSOLR Free Customizations: Fields after removal: ' . print_r($filtered_fields, true));
        }

        return $filtered_fields;
    }, 10, 1);
});
