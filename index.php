<?php
/**
* @User Token Api
*/
/*
Plugin Name: فانکشن و api
Plugin URI: https://google.com
Description: فانکشن ها و rest api مورد نیاز سایت
Version: 1.0
Author: crbank
Author URI: https://google.com
License: GPLv2 or later
Text Domain: cr-rest-api
*/

// constants
require_once('const.php');
// functions
require_once('functions.php');
// api
require_once('rest-api/rest-api.php');

/*
add_action('wp_head', function() {
   fsww_set_balance(1, 200000); 
});
*/


/*
// attach css file
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'custom', plugins_url( 'assets/css/custom.css' , __FILE__ ) );
} );

// attach js file
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_script( 'custom', plugins_url( 'assets/js/custom.js' , __FILE__ ) );
	wp_enqueue_script( 'axios', 'https://unpkg.com/axios@1.1.2/dist/axios.min.js' );
} );

// Loading
add_action('wp_footer', function() { ?>
    <!-- Loading -->
    <div id="x-loading" class="x-loading">
        <img src="<?= plugin_dir_url( __FILE__ ) . 'assets/img/loading-ball.gif' ?>" width="100" />
    </div>
    <!-- Alert -->
    <div id="x-alert" class="x-alert"></div>
<? });
*/