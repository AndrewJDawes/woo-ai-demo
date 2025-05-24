<?php

use wpsolr\core\classes\extensions\licenses\OptionLicenses;

?>

<?php
$subtabs1 = OptionLicenses::get_plugins_tabs();

// Diplay the subtabs
include( 'dashboard_extensions.inc.php' );

