<?php
/**
 * Plugin Name: Arya NSK Calculator
 * Description: Calculateur de chiffre adulte et enfant avec notification administrative.
 * Version: 1.0.1
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: Arya Slowlife
 * Text Domain: arya-nsk-calculator
 *
 * @package Arya_NSK_Calculator
 */

defined( 'ABSPATH' ) || exit;

define( 'ARYA_NSK_VERSION', '1.0.1' );
define( 'ARYA_NSK_PLUGIN_FILE', __FILE__ );
define( 'ARYA_NSK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARYA_NSK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ARYA_NSK_PLUGIN_DIR . 'includes/class-arya-nsk-calculator.php';
require_once ARYA_NSK_PLUGIN_DIR . 'includes/class-arya-nsk-plugin.php';

register_activation_hook( __FILE__, array( 'Arya_NSK_Plugin', 'activate' ) );

Arya_NSK_Plugin::init();
