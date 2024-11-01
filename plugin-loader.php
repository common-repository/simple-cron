<?php
/*
Plugin Name: Simple Cron
Plugin URI: http://MyWebsiteAdvisor.com/tools/wordpress-plugins/simple-cron/
Description: Manage and Monitor WordPress Cron Scheduling System
Version: 1.1
Author: MyWebsiteAdvisor
Author URI: http://MyWebsiteAdvisor.com
*/

register_activation_hook(__FILE__, 'simple_cron_activate');

// display error message to users

function simple_cron_activate() {

	if ($_GET['action'] == 'error_scrape') {                                                                                                   
		die("Sorry, Simple Cron Plugin requires PHP 5.3 or higher. Please deactivate Simple Cron Plugin.");                                 
	}
	
	if ( version_compare( phpversion(), '5.3', '<' ) ) {
		trigger_error('', E_USER_ERROR);
	}
	
}

// require Plugin if PHP 5 installed
if ( version_compare( phpversion(), '5.3', '>=') ) {
	define('SC_LOADER', __FILE__);

	require_once(dirname(__FILE__) . '/cron-schedule.class.php');
	require_once(dirname(__FILE__) . '/cron-list.class.php');
	require_once(dirname(__FILE__) . '/custom-cron-list-table.class.php');
	
	require_once(dirname(__FILE__) . '/simple-cron.php');
	require_once(dirname(__FILE__) . '/plugin-admin.php');
	
	if( class_exists( 'Simple_Cron_Admin' ) ){
	
		$simple_cron = new Simple_Cron_Admin();
		
	}
}
?>