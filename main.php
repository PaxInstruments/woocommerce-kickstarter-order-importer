<?php
/*
 * Plugin Name: WooCommmerce Kickstarter Importer
 * Description: A wordpress plugin to Import kickstarter survey results and create orders in WooCommerce.
 * Version: 0.0.1
 * Author: Paxinstruments
 * Author URI: https://github.com/paxinstruments
 * Plugin URI: https://github.com/PaxInstruments/woocommerce-kickstarter-order-importer
 * GitHub Plugin URI: https://github.com/PaxInstruments/woocommerce-kickstarter-order-importer
 * License: GPL2
*/


if( !defined('ABSPATH') ){
    exit;
}


/**
 * Check if WooCommerce is already activated.
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    class Woocommerce_kickstarter_order_importer {

        /**
         * @var string
         */
        public $version = '0.0.1';

        /**
         * Constructor
         */
        function __construct() {

            register_activation_hook( __FILE__, array( __CLASS__, 'install' ) );
            $this->define_constants();
            $this->includes();
            add_action( 'init', array($this, 'init') );
        }
        
        /**
         * Fires at 'init' hook
         */
        function init() {

            $this->load_plugin_textdomain();
            $this->set_variables();
            $this->instantiate();
        }
        
        /**
         * Load locale
         */
        function load_plugin_textdomain() {

            load_plugin_textdomain( 'woocommerce-kickstarter-order-importer', false, plugin_basename( dirname( __FILE__ ) ) . "/languages" );
        }

        /**
         * Sets the variables
         */
        function __set( $name, $value ) {
            
        }

        /**
         * Define all constants
         */
        function define_constants() {

            define( 'WK_URL', plugins_url('', __FILE__) );
            define( 'WK_CSS', WK_URL. "/css/" ); 
            define( 'WK_JS',  WK_URL. "/js/" );
            //define( 'WK_IMG',  OE_URL. "/img/" );
        }
        
        /**
         * Set necessary variables.
         */
        function set_variables() {

        }

        /**
         * Include helper classes
         */
        function includes() {
            // Includes PHP files located in 'lib' folder
            foreach( glob ( dirname(__FILE__). "/lib/*.php" ) as $lib_filename ) {
                require_once( $lib_filename );
            }
        }

        /**
         * Runs when plugin is activated.
         */
        function install() {

        }

        /**
         * Instantiate necessary classes.
         */
        function instantiate() {
            $this->admin_menu = new kickstarter_admin();
        }

    }

    $wkoi = new Woocommerce_kickstarter_order_importer();

}