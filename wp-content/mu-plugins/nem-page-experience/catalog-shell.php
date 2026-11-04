<?php
defined('ABSPATH') || exit;
ob_start();
include $GLOBALS['npe_catalog_original_template'];
$html=ob_get_clean();
echo npe_catalog_transform($html,npe_visual_front_type());
