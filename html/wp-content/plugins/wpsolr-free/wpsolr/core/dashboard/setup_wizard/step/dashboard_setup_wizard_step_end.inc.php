<?php

use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>

<div class="wpsolr_wizard_main">
    <h2>Success!</h2>

    <div class="wpsolr_wizard_description">
        <br>
        You can now test your front-end search. You can also refine your search configuration even further as you wish.<br><br>

        <b>You come from:</b>
        <ul>
            <li>Sign up for a free account on <?php WPSOLR_Escape::echo_esc_html( $hosting_name ); ?></li>
            <li>Creation of a new free index on <?php WPSOLR_Escape::echo_esc_html( $hosting_name ); ?></li>
            <li>Configuration files uploaded to your new index</li>
            <li>Indexed your post types in your new index</li>
            <li>Facets widget configured and displayed in your sidebars</li>
            <li>Sort widget configured and displayed in your sidebars</li>
            <li>Replacement of WordPress search by WPSolr search on all archives (search, categories, tags, authors, ...)
            </li>
            <li>Ajax results configured and displayed on your search bars</li>
        </ul>
    </div>

    <input type="submit"
           class="button-primary wpsolr_setup_wizard_btn" value="Close the wizard"
           onclick="location.href='?page=solr_settings&path=setup_wizard&step=step_skip'"
    />

</div>