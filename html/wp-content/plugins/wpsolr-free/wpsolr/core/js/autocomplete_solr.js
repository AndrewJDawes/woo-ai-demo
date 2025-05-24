var wpsolr_ajax_timer;

function removeQueryParamFromHTML(html_string, param_to_remove) {
    if (html_string.search(param_to_remove) >= 0) {
        return html_string.replaceAll('?' + param_to_remove, '')
            .replaceAll('&#038;' + param_to_remove, '').replaceAll('&amp;' + param_to_remove, '').replaceAll('&' + param_to_remove, '');
    }

    return html_string;
}

/**
 * Change the value of a url parameter, without reloading the page.
 */
function generateUrlParameters(url, current_parameters, is_remove_unused_parameters) {

    // jsurl library to manipulate parameters (https://github.com/Mikhus/jsurl)
    var url1 = new Url(url);
    var force_clear_url = false;


    /**
     * Toolset Views removes the indice of each wpsolr_fq[indice]=xxx:yyy in url. Reposition them.
     */
    var params_fq_without_index = url1.query['wpsolr_fq[]'];
    //console.log('url:' + params_fq_without_index);
    if (undefined !== params_fq_without_index) {
        for (var i = 0; i < params_fq_without_index.length; i++) {
            // Reposition the indice
            url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ + '[' + i + ']'] = params_fq_without_index[i];
        }

        // Remove now.
        delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ + '[]'];
    }


    //console.log(JSON.stringify(current_parameters));

    /**
     * Set parameters not from wpsolr
     */
    jQuery.each(current_parameters, function (key, value) {
        //console.log(key + ' : ' +  value);
        if (key.substring(0, 'wpsolr_'.length) !== 'wpsolr_') {
            url1.query[key] = value;
        }
    });

    /**
     * Extract parameter query
     */
    var query = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_S];
    if (undefined !== query) {
        url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_S] = query || '';
        force_clear_url = true;
    }

    /**
     * Extract parameter query
     */
    var query = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_Q] || '';
    if (query !== '') {
        url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_Q] = query;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_Q];
    }

    /**
     *    Extract parameter fq (query field)
     *    We follow Wordpress convention for url parameters with multiple occurence: xxx[0..n]=
     *    (php is xxx[]=)
     */
    // First, remove all fq parameters
    for (var index = 0; ; index++) {
        if (undefined === url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ + '[' + index + ']']) {
            break;
        } else {
            delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ + '[' + index + ']'];
        }
    }
    if (!force_clear_url) {
        // 2nd, add parameters
        var query_fields_with_duplicates = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ] || [];
        // Remove potential duplicates
        query_fields = query_fields_with_duplicates.filter(function (item, pos, self) {
            return self.indexOf(item) == pos;
        });
        for (var index in query_fields) {
            url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ + '[' + index + ']'] = query_fields[index];
        }
    }

    /**
     * Extract parameter sort
     */
    var sort = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT] || '';
    if ((!force_clear_url) && (sort !== '')) {
        url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT] = sort;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT];
    }

    /**
     * Extract parameter page number
     */
    var paged = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE] || '';
    if (paged !== '') {
        url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE] = paged;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE];
    }


    // Remove old search parameter
    delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SEARCH];

    return '?' + url1.query.toString();
}

/**
 * History back/forward buttons (history controlled with pushState())
 */
// Update url with the current selection, if required, and authorized by admin option
window.addEventListener("popstate", function (e) {
    if (wp_localize_script_autocomplete.data.is_show_url_parameters) {
        call_ajax_search_timer(window.location.search, false, true);
    }
});

/**
 * Push url to history and mark it as a WPSOLR push
 * @param url
 */
function wpsolr_push_state(url) {
    if (wp_localize_script_autocomplete.data.is_show_url_parameters && (undefined !== history.pushState)) {
        var state = {url: url, is_wpsolr: true};
        history.pushState(state, '', state.url);
    }
}

/**
 * Get the facets state (checked)
 * @returns {Array}
 */
function get_ui_facets_state(element) {

    if (element) {
        current_facets = element.closest('.res_facets');
    } else {
        current_facets = jQuery(document);
    }

    // Add all selected facets to the state
    state = [];
    visited = [];
    current_facets.find('.select_opt.checked').each(function () {
        // Retrieve current selection
        let current_selected = jQuery(this);
        var facet_id = current_selected.attr('id');

        var facet_data = current_selected.data('wpsolr-facet-data');

        if ((facet_id !== 'wpsolr_remove_facets') &&
            (undefined !== facet_data) &&
            (!facet_data.is_permalink) &&
            (0 === current_selected.next('ul').find('.select_opt.checked').length)) {
            // Do not add the remove facets facet to url parameters
            // Do not add the url parameter of a permalink (to prevent /red?wpsolr_fq[0]=color:red)
            // Do not add parents of flat hierachies, only leaf elements

            var value = '';

            switch (facet_data.type) {

                default:

                    value = facet_data.id + ':' + facet_data.item_value;

                    break;

            }

            //console.log(facet_data.is_permalink + ' ' + value);
            state.push(value);
        }

    });


    current_facets.find('.select_opt.unchecked').each(function () {

        // Retrieve current selection. Remove the selected value.
        opts = jQuery(this).data('wpsolr-facet-data').id + ':';

        state.push(opts);

        //console.log('remove unchecked: ' + jQuery(this).attr('id').split(':')[0]);
    });


    return state;
}

/**
 * Return current stored values
 * @returns {{query: *, fq: *, sort: *, start: *}}
 */
function get_ui_selection() {

    var result = {};

    let css_sort = '.select_field';
    if (wp_localize_script_autocomplete.data.css_ajax_container_page_sort) {
        css_sort = wp_localize_script_autocomplete.data.css_ajax_container_page_sort
    }

    result[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_Q] = jQuery('#search_que').val() || '';
    result[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ] = get_ui_facets_state();
    result[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT] = jQuery(css_sort).val() || '';
    result[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE] = jQuery('#paginate').val() || '';

    return result;
}

function wpsolr_ajax_loading(container, action) {
    var loader_options = {
        //color: "rgba(0, 0, 0, 0.4)",
        //image: "img/custom_loading.gif",
        //maxSize: "80px",
        //minSize: "20px",
        //resizeInterval: 0,
        //size: "50%"
    };
    container.LoadingOverlay(action, loader_options);
}

function call_ajax_search_timer(selection_parameters, is_change_url, is_scroll_top_after) {

    // Mark the beginning of loading. Removed when facets are refreshed.
    jQuery('.res_facets').append('<!-- wpsolr loading -->');

    // Ajax, show loader
    if (wp_localize_script_autocomplete.data.is_ajax) {
        var current_overlay = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_overlay).first();
        wpsolr_ajax_loading(current_overlay, 'show');
    }

    if ('' !== wp_localize_script_autocomplete.data.ajax_delay_ms) {
        // Delay
        if (undefined !== wpsolr_ajax_timer) {
            window.clearTimeout(wpsolr_ajax_timer);
        }

        wpsolr_ajax_timer = window.setTimeout(call_ajax_search, wp_localize_script_autocomplete.data.ajax_delay_ms, selection_parameters, is_change_url, is_scroll_top_after);
    } else {
        // No delay
        call_ajax_search(selection_parameters, is_change_url, is_scroll_top_after);
    }
}

function call_ajax_search(selection_parameters, is_change_url, is_scroll_top_after) {

    var url_parameters = selection_parameters;
    if ((selection_parameters instanceof Object) && (undefined === selection_parameters['url'])) {
        // Merge default parameters with active parameters
        var parameters = get_ui_selection();
        jQuery.extend(parameters, selection_parameters);

        // Update url with the current selection
        url_parameters = generateUrlParameters(window.location.href, parameters, true);
    }

    // Remove the pagination from the url, to start from page 1
    // xxx/2/ => xxx/
    if (!(selection_parameters instanceof Object) || (undefined === selection_parameters['url'])) {
        var url_base = window.location.href.split("?")[0];

        if (selection_parameters instanceof Object) {
            // Any selection should reset the pagination
            var url = url_base.replace(/\/page\/\d+/, '');
        } else {
            var url = url_base;
        }

    } else {
        var url = selection_parameters['url'];
        url_parameters = '';
    }

    // Not an ajax, redirect to url
    if (!wp_localize_script_autocomplete.data.is_ajax) {
        // Redirect to url
        window.location.href = url + url_parameters;
        return;
    }

    // Update url with the current selection, if required, and authorized by admin option
    if (is_change_url) {
        // Option to show parameters in url no selected: do nothing
        wpsolr_push_state(url + url_parameters);
    }

    var current_overlay = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_overlay).first();

    // Pass parameters to Ajax
    jQuery.ajax({
        url: url + url_parameters,
        //type: "post",
        data: {wpsolr_is_ajax: 1},
        dataType: 'html',
        success: function (response) {

            var response_results = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_results).first().html();

            if (undefined === response_results) {
                // Show the page with the empty message
                window.location.href = url + url_parameters;

            } else {

                // Remove loader
                wpsolr_ajax_loading(current_overlay, 'hide');

                // Filter HTML response
                response = removeQueryParamFromHTML(response, 'wpsolr_is_ajax=1');

                var response_page_title = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_page_title).first().html();
                var response_page_sort = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_page_sort).first().html();
                var response_pagination = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_pagination).first().html();
                var response_count = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_results_count).first().html();

                // Refresh metas information like title, description
                jQuery(document).find('title').html(jQuery(response).filter('title').html());
                jQuery('meta').each(function () {
                    var attribute = jQuery(this).attr('name') ? 'name' : (jQuery(this).attr('property') ? 'property' : '');
                    if ('' !== attribute) {
                        ///console.log(attribute + ': ' + 'meta[' + attribute + '="' + jQuery(this).attr(attribute) + '"]');
                        jQuery(this).attr('content', jQuery(response).filter('meta[' + attribute + '="' + jQuery(this).attr(attribute) + '"]').attr('content'));
                    }
                });


                // Display facets
                jQuery('.res_facets').html(jQuery(response).find('.res_facets').first().html());

                // Display page title
                jQuery(wp_localize_script_autocomplete.data.css_ajax_container_page_title).html(response_page_title);

                // Display page sort list
                jQuery(wp_localize_script_autocomplete.data.css_ajax_container_page_sort).html(response_page_sort);

                // Display results
                jQuery(wp_localize_script_autocomplete.data.css_ajax_container_results).first().html(undefined === response_results ? '' : response_results);

                // Display number of results
                jQuery(wp_localize_script_autocomplete.data.css_ajax_container_results_count).html(response_count);

                let has_pagination = false;
                const current_pagination = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_pagination);
                if (undefined !== response_pagination) {
                    if (current_pagination.length === 0) {
                        //response_pagination.insertAfter(current_results);
                    }

                    current_pagination.html(response_pagination).show();
                    has_pagination = true;
                } else {
                    current_pagination.hide();
                }

                if (is_scroll_top_after) {
                    // Come back to top
                    jQuery('html,body').animate({scrollTop: 0}, "fast");
                }

            }

        },
        error: function () {

            // Remove loader
            wpsolr_ajax_loading(current_overlay, 'hide');

            // Notify that Ajax has failed
            jQuery(document).trigger('wpsolr_on_ajax_error');

        },
        always: function () {
            // Not called.
        }
    });
}

/**
 * JQuery UI events
 */

jQuery(document).ready(function ($) {


    var suggestions = wp_localize_script_autocomplete.data.wpsolr_autocomplete_selector;

    $.each(suggestions, function (index, suggestion) {
        var suggestion_selector = suggestion['jquery_selector'];
        $(suggestion_selector).off(); // Deactivate other events of theme
        $(suggestion_selector).prop('autocomplete', 'off'); // Prevent browser autocomplete
    });

    /**
     * Search form is focused
     */
    if (wp_localize_script_autocomplete.data.wpsolr_autocomplete_is_active) {

        var suggestion_is_search_admin = wp_localize_script_autocomplete.data.wpsolr_is_search_admin;
        var lang = wp_localize_script_autocomplete.data.lang;

        $.each(suggestions, function (index, suggestion) {

            var suggestion_selector = suggestion['jquery_selector'];
            var suggestion_uuid = suggestion['suggestion_uuid'];
            var suggestion_class = suggestion['suggestion_class'];
            var suggestion_url_parameters = suggestion['url_parameters'];
            var view_uuid = suggestion['view_uuid'];
            var jquery_autocomplete_options = suggestion['jquery_autocomplete_options'];
            var custom_options = $.isEmptyObject(jquery_autocomplete_options) ? {} : JSON.parse(jquery_autocomplete_options);

            $(document).on('focus', suggestion_selector, function (event) {

                event.preventDefault();

                var wp_ajax_action = wp_localize_script_autocomplete.data.wpsolr_autocomplete_action;
                var wp_ajax_nonce = $(wp_localize_script_autocomplete.data.wpsolr_autocomplete_nonce_selector).val();

                var me = $(this);

                $(this).devbridgeAutocomplete(
                    $.extend(true, {}, {
                            minChars: 1,
                            triggerSelectOnValidInput: false,
                            serviceUrl: wp_localize_script_autocomplete.data.ajax_url,
                            type: 'POST',
                            paramName: 'word',
                            params: {
                                action: wp_ajax_action,
                                suggestion_uuid: suggestion_uuid,
                                security: wp_ajax_nonce,
                                url_parameters: suggestion_url_parameters,
                                is_search_admin: suggestion_is_search_admin,
                                lang: lang,
                                view_uuid: view_uuid
                            },
                            preserveInput: true,
                            onHide: function (element, container) {
                                jQuery(document).trigger('wpsolr_on_ajax_suggestions_hide', {
                                    me: me,
                                    suggestion: suggestion
                                });
                            },
                            onSearchStart: function () {
                                me.addClass('wpsolr_loading_sugg');
                            },
                            onSearchComplete: function () {
                                me.removeClass('wpsolr_loading_sugg');
                                jQuery(document).trigger('wpsolr_on_ajax_suggestions_success', {
                                    me: this,
                                    suggestion: suggestion
                                });
                            },
                            formatResult: function (suggestion, currentValue) {
                                return suggestion.value;
                            },
                            transformResult: function (response, originalQuery) {
                                return {"suggestions": [JSON.parse(response)[0].html]};
                            }
                        },
                        // Override default parameters with custom parameters
                        custom_options
                    )
                );

            });
        });
    }


    if ((wp_localize_script_autocomplete.data.is_ajax) && (0 === $(document).find('.search-frm').length)) {
        /**
         *
         * Search form is triggered on ajax
         */
        $('form').on('submit', function (event) {

            var me = $(this);

            var current_results = $(wp_localize_script_autocomplete.data.css_ajax_container_results).first();

            if (current_results.length && $(this).find(wp_localize_script_autocomplete.data.wpsolr_autocomplete_selector).length) {
                // The submitted form is on a search page
                event.preventDefault();

                var keywords = me.find(wp_localize_script_autocomplete.data.wpsolr_autocomplete_selector).first().attr("value");

                // Ajax call on the current selection
                var parameter = {};
                if ('' !== wp_localize_script_autocomplete.data.redirect_search_home) {

                    var redirect_search_home = wp_localize_script_autocomplete.data.redirect_search_home;
                    if (!keywords && !wp_localize_script_autocomplete.data.redirect_search_home.endsWith('/')) {
                        redirect_search_home = redirect_search_home.substring(0, 1 + redirect_search_home.indexOf('/'));
                    }

                    parameter['url'] = '/' + redirect_search_home + encodeURIComponent(keywords);

                    // Use Ajax if current page is the redirect search home page
                    wp_localize_script_autocomplete.data.is_ajax = window.location.pathname.startsWith('/' + redirect_search_home.split('/')[0]);

                } else {
                    parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_S] = keywords;
                }

                call_ajax_search_timer(parameter, true, true);
            }
        });

        /**
         *
         * Search sort is triggered on ajax
         */
        // Unbind them's sort event, before replacing it.
        $(document).off('change', wp_localize_script_autocomplete.data.css_ajax_container_page_sort + ' select');
        $(wp_localize_script_autocomplete.data.css_ajax_container_page_sort).closest('form').on('submit', function () {
            return false;
        });
        $(document).on('change', wp_localize_script_autocomplete.data.css_ajax_container_page_sort + ' select', function (event) {

            var me = $(this);

            // The submitted form is on a search page
            event.preventDefault();

            // Ajax call on the current selection
            var parameter = {};
            parameter[me.prop("name")] = me.prop("value");
            call_ajax_search_timer(parameter, true, true);
        });

        /**
         *
         * Search navigation is triggered on ajax
         */
        $(document).on('click', wp_localize_script_autocomplete.data.css_ajax_container_pagination_page, function (event) {

            event.preventDefault();

            var me = $(this);

            // Ajax call on the current selection
            var parameter = {};
            parameter['url'] = me.attr("href");
            call_ajax_search_timer(parameter, true, true);
        });

    }

    /**
     * Select/unselect a facet
     */
    window.wpsolr_facet_change = function ($items, event) {

        if (event) {
            event.preventDefault();
        }

        // Reset pagination
        $('#paginate').val('');

        var state = [];
        var $this;

        $items.each(function (index) {

            $this = $(this);
            var facet_data = $this.data('wpsolr-facet-data');

            if ($this.attr('id') === 'wpsolr_remove_facets') {

                // Unselect all facets
                $('.select_opt').removeClass('checked');
                $this.addClass('checked');

            } else {

                // Select/Unselect the element
                is_already_selected = $this.hasClass('checked') && ('facet_type_min_max' !== facet_data.type);
                var facet_name = facet_data.id;

                if (is_already_selected) {
                    // Unselect current selection

                    $this.removeClass('checked');
                    $this.addClass('unchecked');
                    $this.next("ul").children().find("[id^=" + facet_name + "]").removeClass('checked');


                    if ($this.hasClass('wpsolr_facet_option')) {

                        if ($this.parent().prop("multiple")) {
                            // Unselelect children too (next with sublevel)

                            var current_level = $this.data('wpsolr-facet-data').level;
                            $this.nextAll().each(function () {

                                //alert(current_level + ' : ' + $(this).data('wpsolr-facet-data').level);
                                if (current_level < $(this).data('wpsolr-facet-data').level) {

                                    //alert($(this).attr('id') + ' : ' + $(this).attr('class'));
                                    $(this).removeClass('checked');

                                } else {

                                    return false; // Stop asap to prevent adding another sublevel
                                }
                            });
                        }
                    }

                } else {

                    // Unselect other radioboxes
                    $this.closest("ul.wpsolr_facet_radiobox").children().find("[id^=" + facet_name + "]").removeClass('checked');

                    if ($this.hasClass('wpsolr_facet_option')) {

                        if (!$this.parent().prop("multiple")) {
                            // Unselect other options first

                            $this.parent().children().removeClass('checked');

                        } else {
                            // Select parents too (previous with sublevel)

                            var current_selected_level = $this.data('wpsolr-facet-data').level;
                            $this.prevAll().each(function () {

                                if (current_selected_level > $(this).data('wpsolr-facet-data').level) {

                                    $(this).addClass('checked');

                                    // Recursive on parents
                                    current_selected_level = $(this).data('wpsolr-facet-data').level;
                                }
                            });
                        }

                        $this.addClass('checked');

                    } else {
                        // Select current selection (ul/li)
                        $this.parents("li").children(".select_opt").addClass('checked');
                    }

                }

                // Get facets state
                state = get_ui_facets_state($this);
            }
        })

        //alert(JSON.stringify(state));

        // Ajax call on the current selection
        var parameter = {};
        var permalink;
        if (undefined !== $this) {
            permalink = $this.find('a.wpsolr_permalink').first().attr('href') || $this.data('wpsolr-facet-data').permalink_href;
        }

        if (undefined === permalink) {
            parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ] = state;
        } else {
            if ((null !== event) && (undefined !== event)) {
                event.preventDefault(); // Prevent permalink redirection
            }
            parameter['url'] = permalink;
        }

        //parameter['element'] = $items;

        call_ajax_search_timer(parameter, true, false);

    }

    /**
     * A simple facet is selected/unselected
     */
    $(document).on('click', 'div.select_opt', function (event) {
        if ('facet_type_field' === $(this).data('wpsolr-facet-data').type) {

            wpsolr_facet_change($(this), event);
        }
    });

    function wpsolr_select_value(current) {
        var selected_values = current.val();

        //console.log(selected_values);
        //console.log(current.prop('multiple'));

        if (current.prop('multiple')) {
            // It is a multi-select. Delete first to replace values.

            current.children().removeClass('checked');
        }

        wpsolr_facet_change(current.find('option:selected'), event);
    }

    /**
     * A non-multiselect select facet is selected/unselected
     */
    $(document).on('change', '.wpsolr_facet_select select', function (event) {

        wpsolr_select_value($(this));
    });

    /**
     * A non-multiselect select facet is clicked
     */
    $(document).on('clickx', '.wpsolr_facet_select .wpsolr-select-multiple option', function (event) {
        //console.log('test');

        wpsolr_select_value($(this).parent('select'));
    });

    /**
     * Sort is selected
     */
    $(document).on('change', '.select_field', function () {

        // Reset pagination
        $('#paginate').val('');

        // Retrieve current selection
        sort_value = $(this).val();

        // Ajax call on the current selection
        var parameter = {};
        parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT] = sort_value;
        call_ajax_search_timer(parameter, true, true);
    });


    /**
     * Pagination is selected
     */
    $(document).on('click', '.paginate', function () {

        // Retrieve current selection
        page_number = $(this).attr('id');

        // Store the current selection
        $('#paginate').val(page_number);

        // Ajax call on the current selection
        var parameter = {};
        parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE] = page_number;
        call_ajax_search_timer(parameter, true, false);

    });


    /**
     * Add geolocation user agreement to selectors
     */
    $(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_SEARCH_BOX_JQUERY_SELECTOR).each(function (index) {

        $(this).closest('form').append(wp_localize_script_autocomplete.data.WPSOLR_FILTER_ADD_GEO_USER_AGREEMENT_CHECKBOX_TO_AJAX_SEARCH_FORM);
    });

    /**
     * Manage geolocation
     */
    $('form').on('submit', function (event) {

        //event.preventDefault();

        var me = $(this);

        if ($(this).parent().find(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_SEARCH_BOX_JQUERY_SELECTOR).length) {
            // The submitted form contains an element linked to the geolocation by a jQuery selector

            var nb_user_agreement_checkboxes = $(this).find(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR).length;
            var user_agreement_first_checkbox_value = $(this).find(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR).filter(':checked').first().val();

            /**
             * We want to force the checkbox value to 'n' when unchecked (normally, it's value disappears from the form).
             * Else, no way to have a 3-state url value: absent/checked/unchecked. The url absent state can be then translated to checked or unchecked.
             */
            var current_checkbox = $(this).find(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR).first();
            if (!current_checkbox.prop('checked')) {
                me.append($("<input />").attr("type", "hidden").attr("name", current_checkbox.prop("name")).val(wp_localize_script_autocomplete.data.PARAMETER_VALUE_NO));
            } else {
                current_checkbox.val(wp_localize_script_autocomplete.data.PARAMETER_VALUE_YES);
            }

            //console.log('wpsolr geolocation selectors: ' + wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_SEARCH_BOX_JQUERY_SELECTOR);
            //console.log('wpsolr geolocation user agreement selectors: ' + wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR);
            //console.log('wpsolr nb of geolocation user agreement checkboxes: ' + nb_user_agreement_checkboxes);
            //console.log('wpsolr first geolocation user agreement checkbox value: ' + user_agreement_first_checkbox_value);

            if ((0 === nb_user_agreement_checkboxes) || (undefined !== user_agreement_first_checkbox_value)) {
                // The form does not contain a field requiring to not use geolocation (a checkbox unchecked)

                if (navigator.geolocation) {

                    // Stop the submit happening while the geo code executes asynchronously
                    event.preventDefault();

                    // Add a css class to the submit buttons while collecting the location
                    me.addClass("wpsolr_geo_loading");
                    // Remove the class automatically, to prevent it staying forever if the visitor denies geolocation.
                    var wpsolr_geo_loading_timeout = window.setTimeout(
                        function () {
                            me.removeClass("wpsolr_geo_loading");
                        }
                        ,
                        10000
                    );

                    navigator.geolocation.getCurrentPosition(
                        function (position) {

                            // Stop wpsolr_geo_loading_timeout
                            window.clearTimeout(wpsolr_geo_loading_timeout);
                            // Add a css class to the submit buttons while collecting the location
                            me.addClass("wpsolr_geo_loading");

                            // Add coordinates to the form
                            me.append($("<input />").attr("type", "hidden").attr("name", wp_localize_script_autocomplete.data.SEARCH_PARAMETER_LATITUDE).val(position.coords.latitude));
                            me.append($("<input />").attr("type", "hidden").attr("name", wp_localize_script_autocomplete.data.SEARCH_PARAMETER_LONGITUDE).val(position.coords.longitude));

                            // Finally, submit
                            me.unbind('submit').submit();

                        },
                        function (error) {

                            console.log('wpsolr: geolocation error: ' + error.code);

                            // Stop wpsolr_geo_loading_timeout
                            window.clearTimeout(wpsolr_geo_loading_timeout);
                            // Add a css class to the submit buttons while collecting the location
                            me.addClass("wpsolr_geo_loading");

                            // Finally, submit
                            me.unbind('submit').submit();
                        }
                    );

                } else {

                    console.log('wpsolr: geolocation not supported by browser.');
                }
            }

        }

    });

    /**
     * Toolset Views triggered event. Refresh the facets.
     */
    $(document).on('js_event_wpv_parametric_search_form_updated', function (event, data) {

        // Get Toolset View form search box value (not in data, unfortunatly)
        var keywords = $('[name="' + data.view_changed_form[0].name + '"]').find('[name="wpv_post_search"]').val();

        var context = $(data.view_changed_form.context);
        var is_require_facets_refresh =
            context.hasClass('wpv-submit-trigger') ||
            context.hasClass('wpv-reset-trigger') ||
            context.hasClass('js-wpv-filter-trigger-delayed form-control');

        /*
        console.log('search box:' + keywords);
        console.log('url: ' + keywords_in_url);
        console.log(context);
        console.log('submit? : ' + context.hasClass('wpv-submit-trigger'));
        console.log('Reset? : ' + context.hasClass('wpv-reset-trigger'));
        */

        if (is_require_facets_refresh) {
            // Ajax call on the current selection

            var parameter = {};
            parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_S] = keywords;
            parameter['wpv_post_search'] = keywords;

            // Mark the beginning of loading. Removed when facets are refreshed.
            $('.res_facets').append('<!-- wpsolr loading -->');

            call_ajax_search(parameter, true, false);
        }

    });

});
