<?php use wpsolr\core\classes\utilities\WPSOLR_Escape;

?>
<p>
    We will create a <a
            href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( 'https://opensolr.com/pricing' ) ); ?>"
            target="_new">free account</a>
    </a> on your behalf for <?php WPSOLR_Escape::echo_esc_html( $admin_email ); ?> on Opensolr.com. Your data will be
    stored in a Solr index hosted in Germany.
</p>
<p>
    Opensolr may forward your email to WPSolr for follow-up questions.
</p>
<p>
    If you would like to learn more about how to change your index region,
    or how to upgrade your account to more disk space,
    visit
    <a
            href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( 'https://opensolr.com/contact' ) ); ?>"
            target="_new">contact us</a>
    or
    <a href="<?php WPSOLR_Escape::echo_esc_url( $license_manager->add_campaign_to_url( 'https://opensolr.com/pricing' ) ); ?>"
       target="_new">pricing</a>
    </a>
    pages.
</p>