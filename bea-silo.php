<?php
/*
 Plugin Name: BEA - Silo
 Version: 1.1.1
 Version Boilerplate: 2.1.0
 Plugin URI: http://www.beapi.fr
 Description: Add silo feature
 Author: BE API Technical team
 Author URI: http://www.beapi.fr
 Domain Path: languages
 Text Domain: bea-silo
 Network: True
 ----

 Copyright 2017 BE API Technical team (human@beapi.fr)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PASECTIONICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * @todo : Think about transversal taxonomy on two silos (cpt) to hide empty terms
 */

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'BEA_SILO_VERSION', '1.1.1' );
define( 'BEA_SILO_MIN_PHP_VERSION', '5.4' );

// Plugin URL and PATH
define( 'BEA_SILO_URL', plugin_dir_url( __FILE__ ) );
define( 'BEA_SILO_DIR', plugin_dir_path( __FILE__ ) );

// Check PHP min version
if ( version_compare( PHP_VERSION, BEA_SILO_MIN_PHP_VERSION, '<' ) ) {
	require_once( BEA_SILO_DIR . 'compat.php' );

	// possibly display a notice, trigger error
	add_action( 'admin_init', array( 'BEA\Silo\Compatibility', 'admin_init' ) );

	// stop execution of this file
	return;
}

/**
 * Autoload all the things \o/
 */
require_once BEA_SILO_DIR . 'autoload.php';

add_action( 'plugins_loaded', 'init_bea_silo_plugin' );
/**
 * Init the plugin
 */
function init_bea_silo_plugin() {
	BEA\Silo\Main::get_instance();
	BEA\Silo\Plugin::get_instance();
	BEA\Silo\Blocks::get_instance();
	BEA\Silo\Rest\Main::get_instance();
}
