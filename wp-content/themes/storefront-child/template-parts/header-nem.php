<?php
/** Keep the existing Storefront header and use the shared editable NEM menu. */
defined('ABSPATH') || exit;
$parent_header = get_template_directory() . '/template-parts/header-nem.php';
if (!function_exists('npe_nav_legacy_header_markup')) {
    require $parent_header;
    return;
}
ob_start();
require $parent_header;
$parent_markup = ob_get_clean();
echo npe_nav_legacy_header_markup($parent_markup); // Escaped by the parent template and navigation renderer.
