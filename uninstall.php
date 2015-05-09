<?php

/**
 * Plugin Notes Uninstall Functions
 *
 * Code used when the plugin is removed (not just deactivated but actively deleted through the WordPress Admin).
 *
 * @package Plugin Notes
 * @subpackage Uninstall
 *
 * @author Juliette Reinders Folmer
 */


if( !defined( 'ABSPATH' ) && !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();
 
delete_option( 'plugin_notes' );