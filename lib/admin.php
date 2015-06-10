<?php


class kickstarter_admin {
    public function __construct()
    {
         //add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
         //add_action( 'woocommerce_settings_tabs_kickstarter_import', array($this, 'kickstarter_import_tab') );
        add_action( 'admin_menu', array( $this, 'kickstarter_menu' ), 20 );
    }

    public function add_settings_tab($settings_tabs)
    {
        $settings_tabs['kickstarter_import'] = __( 'Kickstarter Importer', 'woocommerce-kickstarter-order-importer' );
            return $settings_tabs;
    }

    public function kickstarter_menu()
    {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            add_submenu_page( 'woocommerce', __( 'Kickstarter Import', 'woocommerce-kickstarter-order-importer' ),  __( 'Kickstarter Import', 'woocommerce-kickstarter-order-importer' ) , 'manage_woocommerce', 'wc-kick-import', array( $this, 'kickstarter_import_page' ) );
        }
    }

    public function kickstarter_import_page()
    {
        print "hello";
    }
}