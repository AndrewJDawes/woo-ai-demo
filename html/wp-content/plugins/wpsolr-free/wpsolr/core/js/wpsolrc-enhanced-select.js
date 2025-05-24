/*global wpsolrc_enhanced_select_params */
jQuery(function ($) {

    function wpsolrc_getEnhancedSelectFormatString() {
        var formatString = {
            formatMatches: function (matches) {
                if (1 === matches) {
                    return wpsolrc_enhanced_select_params.i18n_matches_1;
                }

                return wpsolrc_enhanced_select_params.i18n_matches_n.replace('%qty%', matches);
            },
            formatNoMatches: function () {
                return wpsolrc_enhanced_select_params.i18n_no_matches;
            },
            formatAjaxError: function () {
                return wpsolrc_enhanced_select_params.i18n_ajax_error;
            },
            formatInputTooShort: function (input, min) {
                var number = min - input.length;

                if (1 === number) {
                    return wpsolrc_enhanced_select_params.i18n_input_too_short_1;
                }

                return wpsolrc_enhanced_select_params.i18n_input_too_short_n.replace('%qty%', number);
            },
            formatInputTooLong: function (input, max) {
                var number = input.length - max;

                if (1 === number) {
                    return wpsolrc_enhanced_select_params.i18n_input_too_long_1;
                }

                return wpsolrc_enhanced_select_params.i18n_input_too_long_n.replace('%qty%', number);
            },
            formatSelectionTooBig: function (limit) {
                if (1 === limit) {
                    return wpsolrc_enhanced_select_params.i18n_selection_too_long_1;
                }

                return wpsolrc_enhanced_select_params.i18n_selection_too_long_n.replace('%qty%', limit);
            },
            formatLoadMore: function () {
                return wpsolrc_enhanced_select_params.i18n_load_more;
            },
            formatSearching: function () {
                return wpsolrc_enhanced_select_params.i18n_searching;
            }
        };

        return formatString;
    }

    $(document)
        .on('wpsolrc-enhanced-select-init', function () {

            //console.log('0');

            // Regular select boxes
            $(':input.wpsolrc-enhanced-select, :input.chosen_select').filter(':not(.enhanced)').each(function () {
                console.log('1');
                var select2_args = $.extend({
                    minimumResultsForSearch: 10,
                    allowClear: $(this).data('allow_clear') ? true : false,
                    placeholder: $(this).data('placeholder')
                }, wpsolrc_getEnhancedSelectFormatString());

                $(this).select2(select2_args).addClass('enhanced');
            });

            $(':input.wpsolrc-enhanced-select-nostd, :input.chosen_select_nostd').filter(':not(.enhanced)').each(function () {
                console.log('2');
                var select2_args = $.extend({
                    minimumResultsForSearch: 10,
                    allowClear: true,
                    placeholder: $(this).data('placeholder')
                }, wpsolrc_getEnhancedSelectFormatString());

                $(this).select2(select2_args).addClass('enhanced');
            });

            // Ajax product search box
            $(':input.wpsolrc-multiselect-search').filter(':not(.enhanced)').each(function () {

                //console.log('3 ');

                var current = $(this);
                var action = current.data('action');
                var exclude = current.data('exclude');
                var include = current.data('include');
                var limit = current.data('limit');
                var params = current.data('params');
                // Add selectors values to params

                var select2_args = {
                    allowClear: $(this).data('allow_clear') ? true : false,
                    placeholder: $(this).data('placeholder'),
                    allowClear: true,
                    minimumInputLength: $(this).data('minimum_input_length') ? $(this).data('minimum_input_length') : '0',
                    escapeMarkup: function (m) {
                        return m;
                    },
                    ajax: {
                        url: wpsolrc_enhanced_select_params.ajax_url,
                        dataType: 'json',
                        quietMillis: 250,
                        data: function (term) {
                            return {
                                term: term,
                                action: action,
                                security: wpsolrc_enhanced_select_params.security,
                                exclude: current.prop('value'),
                                limit: limit,
                                params: params,
                                params_extras: (function () {
                                    var params_extras = {};
                                    var params_selectors = current.data('params_selectors') || [];
                                    $.each(params_selectors, function (name, selector) {
                                        params_extras[name] = $(selector).val();
                                    });
                                    return params_extras;
                                })()
                            };
                        },
                        results: function (data) {
                            var terms = [];
                            if (data) {
                                $.each(data, function (postion, value) {
                                    terms.push({id: value['id'], text: value['label']});
                                });
                            }
                            return {
                                results: terms
                            };
                        },
                        cache: true
                    }
                };

                if ($(this).data('multiple') === true) {
                    select2_args.multiple = true;
                    select2_args.initSelection = function (element, callback) {
                        var data = $.parseJSON(element.attr('data-selected'));
                        var selected = [];

                        return $.ajax({
                            url: wpsolrc_enhanced_select_params.ajax_url,
                            dataType: 'json',
                            quietMillis: 250,
                            data: {
                                term: '',
                                action: action,
                                security: wpsolrc_enhanced_select_params.security,
                                include: current.prop('value'),
                                limit: limit,
                                params: params
                            }
                            ,
                            success: function (data) {

                                $.each(data, function (position, value) {
                                    terms.push({id: value['id'], text: value['label']});
                                });

                                //current.trigger('wpsolrc_update_options');
                                return callback(selected);
                            }
                        });


                    };
                    select2_args.formatSelection = function (data) {
                        return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
                    };
                } else {
                    select2_args.multiple = false;
                    select2_args.initSelection = function (element, callback) {
                        var data = {
                            id: element.val(),
                            text: element.attr('data-selected')
                        };
                        return callback(data);
                    };
                }

                select2_args = $.extend(select2_args, wpsolrc_getEnhancedSelectFormatString());

                $(this).select2(select2_args).addClass('enhanced');
            });

            // Dynamically remove/add form options when the select value is modified.
            $(':input.wpsolrc-multiselect-search').filter('.enhanced').on('change', function (e) {

                //console.log('val : ' + $(this).val());

                var current = $(this);

                // Prevent recursivity when adding elements below
                current.off('change');

                var id = current.prop('id');
                var value = current.prop('value');
                var option_name = current.data('option-name');
                var option_class = current.data('option-class');

                var multiple = current.prop('multiple');

                // CLear all the options
                current.next('.wpsolrc-multiselect-search-values').html('');
                // Add all the options
                $(value.split(',')).each(function (i, val) {
                    if (val[0] !== undefined) {

                        if (multiple) {
                            var element_hidden = '<input type="hidden" class="$option_class" name="$option_name[$value]" value="$value"/>';
                        } else {
                            var element_hidden = '<input type="hidden" id="$option_class" name="$option_name" value="$value"/>';
                        }
                        current.after(element_hidden.replace('$option_class', option_class).replace('$option_name', option_name).replace('$value', val).replace('$value', val));

                        // Propagate change outside
                        $(document).trigger('wpsolr_select2_after_change', [current, value]);
                    }
                });
                //
            });

        })

        // WooCommerce Backbone Modal
        .on('wc_backbone_modal_before_remove', function () {
            $(':input.wpsolrc-enhanced-select, :input.wpsolrc-product-search, :input.wpsolrc-customer-search').select2('close');
        })

        .trigger('wpsolrc-enhanced-select-init');

});
