<?php

use wpsolr\core\classes\admin\ui\ajax\WPSOLR_Admin_UI_Ajax;
use wpsolr\core\classes\hosting_api\WPSOLR_Hosting_Api_Abstract_Root;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

wp_enqueue_media();

$hosting_api  = WPSOLR_Hosting_Api_Abstract_Root::get_hosting_api_by_id( $index_hosting_api_id );
$server_files = $hosting_api->get_server_files( $index );
if ( $is_clone_index || empty( $server_files ) ) {
	// No server files: quit
	return;
}

$buton_deploy_name = 'Redeploy the file to your index server';

?>
    <script>
        jQuery(document).ready(function ($) {

            // Fill file content textarea with file content in popup
            $(document).on('click', '.wpsolr-edit-file', function (event) {

                // Set button label
                const current_button_el = $(this);
                const current_button_label = current_button_el.val();
                current_button_el.val('Please wait, loading the file content ...');
                current_button_el.nextAll('.wpsolr_err').text('');

                const file_name = current_button_el.data('wpsolr-file-name');
                const button_deploy = $('.wpsolr-deploy-file');
                button_deploy.data('wpsolr-file-name', file_name);
                button_deploy.val('<?php WPSOLR_Escape::echo_esc_attr( $buton_deploy_name ); ?>');
                button_deploy.parent().find('.wpsolr_err').text('');
                button_deploy.parent().find('.solr_success').text('');
                button_deploy.parent().find('.img-succ').css('display', 'none');
                button_deploy.parent().find('.img-err').css('display', 'none');
                button_deploy.parent().find('.img-load').css('display', 'none');

                return $.ajax({
                    url: wpsolrc_enhanced_select_params.ajax_url,
                    dataType: 'json',
                    quietMillis: 250,
                    method: 'POST',
                    data: {
                        action: '<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax::AJAX_INDEX_SERVER_CONFIGURATION_FILE_GET ); ?>',
                        security:
                        wpsolrc_enhanced_select_params.security,
                        index_uuid: '<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>',
                        file_name: file_name
                    },
                    success: function (data) {

                        var file_name = data[0]['id'];
                        var file_content = data[0]['label'];

                        if ('error' === file_name) {

                            // Show error
                            current_button_el.nextAll('.wpsolr_err').text(file_content);

                        } else {

                            $('.wpsolr_file_popup_content').val(file_content);

                            // Open popup
                            tb_show("File content", "#TB_inline?width=1200&amp;height=800&amp;inlineId=wpsolr_file_popup");
                        }

                        // Set button label back
                        current_button_el.val(current_button_label);

                    }

                    ,
                    error: function (data) {
                    }
                });
            });

            // Send file content textarea in popup to search engine server
            $(document).on('click', '.wpsolr-deploy-file', function (event) {

                // Set button label
                const current_button_el = $(this);
                const current_button_label = current_button_el.val();

                current_button_el.val('Please wait, deploying the file content ...');
                current_button_el.prop('disabled', true);
                current_button_el.parent().find('.img-succ').css('display', 'none');
                current_button_el.parent().find('.img-err').css('display', 'none');
                current_button_el.parent().find('.img-load').css('display', 'inline-block');

                current_button_el.nextAll('.wpsolr_err').text('');
                current_button_el.nextAll('.solr_success').text('');
                const file_name = current_button_el.data('wpsolr-file-name');
                const file_content = $('.wpsolr_file_popup_content').val();

                return $.ajax({
                    url: wpsolrc_enhanced_select_params.ajax_url,
                    dataType: 'json',
                    quietMillis: 250,
                    method: 'POST',
                    data: {
                        action: '<?php WPSOLR_Escape::echo_esc_attr( WPSOLR_Admin_UI_Ajax::AJAX_INDEX_SERVER_CONFIGURATION_FILE_DEPLOY ); ?>',
                        security:
                        wpsolrc_enhanced_select_params.security,
                        index_uuid: '<?php WPSOLR_Escape::echo_esc_attr( $index_indice ); ?>',
                        file_name: file_name,
                        file_content: file_content
                    },
                    success: function (data) {

                        var file_name = data[0]['id'];
                        var file_content = data[0]['label'];

                        if ('error' === file_name) {

                            // Show error
                            current_button_el.nextAll('.wpsolr_err').text(file_content);

                            current_button_el.parent().find('.img-err').css('display', 'block');

                        } else {

                            current_button_el.nextAll('.solr_success').text(file_content);

                            current_button_el.parent().find('.img-succ').css('display', 'block');
                        }

                        // Set button label back
                        current_button_el.val(current_button_label);
                        current_button_el.prop('disabled', false);

                        current_button_el.parent().find('.img-load').css('display', 'none');
                    },
                    error: function (data) {
                    }
                });
            });

        });
    </script>

    <div id="wpsolr_file_popup" class="wdm-vertical-tabs-content" style="display:none">
        <div>
            <input type="button"
                   class="button-primary wpsolr-deploy-file"
                   value="<?php WPSOLR_Escape::echo_esc_attr( $buton_deploy_name ); ?>"
                   style="float:right;margin:10px 0 10px 0"/>
            <span>
                <div class='img-load' style='float:right;'></div>
                <img
                        src='<?php WPSOLR_Escape::echo_esc_url( WPSOLR_DEFINE_PLUGIN_DIR_URL . '/images/success.png' ); ?>'
                        style='float:right;height:18px;width:18px;margin-top: 10px;display: none'
                        class='img-succ'/>
                <img
                        src='<?php WPSOLR_Escape::echo_esc_url( WPSOLR_DEFINE_PLUGIN_DIR_URL . '/images/warning.png' ); ?>'
                        style='float:right;height:18px;width:18px;margin-top: 10px;display: none'
                        class='img-err'/>
					</span>
            <span class="wpsolr_err" style="float:left;margin-left:10px"></span>
            <span class="solr_success" style="float:left;margin-left:10px"></span>
            <textarea class="wpsolr_file_popup_content" rows="80" style="width:100%"></textarea>
        </div>
    </div>

<?php
foreach (
	$server_files as $file_id => $file_def
) {
	?>
    <div class="wdm_row">
        <div class='col_left'>
			<?php WPSOLR_Escape::echo_esc_html( $file_def['label'] ); ?>
        </div>
        <div class='col_right'>

            <input type="button"
                   class="button-secondary wpsolr-edit-file"
                   value="Download"
                   data-wpsolr-file-name="<?php WPSOLR_Escape::echo_esc_attr( $file_id ); ?>"
                   style="float:left"/>
            <div class="wpsolr_err" style="float:left;margin-left:10px"></div>

        </div>
        <div class="clear"></div>
    </div>
<?php }



