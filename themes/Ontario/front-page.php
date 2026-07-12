<?php
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$theme_uri = get_template_directory_uri();
$brand_name = ontario_site_brand_name();
$phone_number = ontario_site_field('phone_number');
$working_hours = ontario_site_field('working_hours');
$address = ontario_site_field('address');
$display_mode = ontario_effective_display_mode();

get_header();

if ($display_mode === 'simple') {
    include locate_template('template-parts/front-page/simple.php');
} else {
    include locate_template('template-parts/front-page/full.php');
}

include locate_template('template-parts/front-page/modals.php');

get_footer();
