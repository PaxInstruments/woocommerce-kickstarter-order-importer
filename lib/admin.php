<?php


class kickstarter_admin {
    public function __construct()
    {
         //add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
         //add_action( 'woocommerce_settings_tabs_kickstarter_import', array($this, 'kickstarter_import_tab') );

        add_filter( 'query_vars',  array($this, 'add_query_vars_filter') );

        add_action( 'admin_menu', array( $this, 'kickstarter_menu' ), 20 );
        add_action( 'admin_enqueue_scripts', array($this, 'scripts') );
        
        //kick_survey_upload_action
        add_action('wp_ajax_kick_survey_upload_action', array($this, 'kick_survey_upload_action'));//page 1 action
        add_action('wp_ajax_kickstarter_define_page', array($this, 'kickstarter_define_page')); //page 2
        add_action('wp_ajax_kick_data_define_action', array($this, 'kick_data_define_action'));//page 2 action

        add_action('wp_ajax_kickstarter_process_survey_data', array($this, 'kickstarter_process_survey_data')); //final
        //add_action('wp_ajax_nopriv_kick_survey_upload_action', array($this, 'kick_survey_upload_action'));

        $this->process_kick = new process_kick_data();
    }

    public function add_query_vars_filter($vars)
    {
        $vars[] = "kickstep";
        return $vars;
    }

    public function scripts()
    {  
        $screen = get_current_screen();
        
        //
        if( $screen->id =='woocommerce_page_wc-kick-import'){
            //print "screen:".print_r($screen, true)."<br>";
            wp_enqueue_style('kick-css', WK_CSS.'style.css');
            wp_enqueue_script( 'kick-import-process', WK_JS. 'import_process.js', array('jquery', 'jquery-form'), false, true );
            wp_enqueue_script( 'kick-product-search', WK_JS. 'chosen.jquery.min.js', array(), false, true);
       }
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

    public function kickstarter_import_page() //page 1
    {
        #global $def_test_data;
        print "
        <div class='woo_kick_response'></div>
        <div class='woo_kick_stage'>
        <h1>Step 1</h1>
        <form id='kick_file_upload' enctype='multipart/form-data' method='POST'>
        <input type='hidden' name='MAX_FILE_SIZE' value='100000' /> 
        ".wp_nonce_field('kick_file_upload_nonce', 'kick_file_upload_nonce')."
        <p>Upload the cvs file received from the kickstarter survey.</p>
        <input type='hidden' id='action' name='action' value='kick_survey_upload_action' />
        <input type='file' name='kick_survey_file' id='kick_survey_file' /><br>
        <input type='submit' class='kick_survey_import button-primary' value='Upload Survey' /></form>
        </div>";

        print "<br><a class='next_action' href='#'>abcdefg</a><pre>";

            $map_data = array( 
                'action' => 'kick_data_define_action',
                'kick_file_define_nonce' => 'd0317fa20a',
                '_wp_http_referer' => '/wp-admin/admin-ajax.php',
                'username' => 'Backer Name',
                'email' => 'Email',
                'shipping_name' => 'Shipping Name',
                'shipping_address_1' => 'Shipping Address 1',
                'shipping_address_2' => 'Shipping Address 2',
                'shipping_city' => 'Shipping City',
                'shipping_state' => 'Shipping State',
                'shipping_postcode' => 'Shipping Postal Code',
                'shipping_country' => 'Shipping Country Code',
                'product_choices' => array
                    (
                        '#1147: Arduino Configuration Shield',
                        '#1003: Pax Instruments Graphic LCD 132x64 Arduino Shield'
                    )

            );

        //$res = $this->process_kick->load_data('/var/www/html/wp-content/uploads/survery_import.csv', $map_data);
        //$res = get_post_meta(1507);

        //print_r($res);
    }


    public function get_product_search($field_id)
    {
        $args = array(
                'post_type'      => 'product',
                'no_found_rows'  => 1,
                'post_status'    => 'publish'
            );

        ?>
        <div class="side-by-side clearfix">
        <select data-placeholder="Choose a Product..." class="chosen-select" multiple style="width:350px;" tabindex="4" name="product_choices[]" id="product_choices">
        <option value=""></option>

        <?php
        $products = new WP_Query( $args );
        if ( $products->have_posts() ) : ?>
                <?php while ( $products->have_posts() ) : $products->the_post(); ?>
                    <?php  print "<option id='{$products->post->ID} name='{$products->post->ID}'>#{$products->post->ID}: {$products->post->post_title}</option>";  ?>
                <?php endwhile; // end of the loop. ?>
        <?php endif;
        wp_reset_postdata();
        ?>
         </select>
        </div>
        <?php
    }
    public function kickstarter_define_page() //page 2
    {
        //
        $incoming_fields = $this->process_kick->csv_header($_POST['file']);
        $options = '<option value=\'\'>Choose a column from import ...</option>';
        foreach ($incoming_fields as $field) {
            $options .= "<option value='$field'>$field</option>";
        }

        $shipping_fields = array (
            "shipping_name" => 'Shipping Name', 
            //"shipping_company" => '',
            "shipping_address_1" => 'Shipping Address line 1',
            "shipping_address_2" => 'Shipping Address line 2',
            "shipping_city" => 'Shipping City',
            "shipping_state" => 'Shipping State',
            "shipping_postcode" => 'Shipping postcode',
            "shipping_country" => 'Shipping County Code');

        $shipping = '';

        foreach ($shipping_fields as $key => $value) {
             $shipping .= "<tr>
             <th scope='row'><label>$value:</label></th>
             <td><select class='chosen-select'  name='$key'>$options</select></td>
             </tr>";
        }

        $username = "<tr>
        <th scope='row'><label>Full name:</label></th>
        <td><select class='chosen-select'  name='username'>$options</select></td>
        </tr>";
        $email = "<tr>
        <th scope='row'><label>Email:</label></th>
        <td><select class='chosen-select'  name='email'>$options</select></td>
        </tr>";

        print "
        <h1>Step 2</h1>
        <form id='kick_file_define' method='POST'>
        <input type='hidden' id='action' name='action' value='kick_data_define_action' />
        ".wp_nonce_field('kick_file_define_nonce', 'kick_file_define_nonce')."
        <p>Match each field with its wordpress equivalent</p>
        <h2>User fields</h2>
        <table class='form-table'>
        $username
        $email
        </table>
        <h2>WooCommerce shipping</h2>
        <table class='form-table'>
        $shipping
        </table>
        
        <h2>Products to add on order</h2>
        ";
        $this->get_product_search('products');
        print "
        <input type='submit' class='kick_survey_import button-primary' value='Submit Data' />
        </form>
        ";

        //$this->html_show_array($this->csv_to_array($_POST['file']));

        die();
    }


    public function kickstarter_process_survey_data() // page 3
    {
        // go through survey data, and defined options
        // create users and order data

        // get reward, ie. item to add to order
        // for each order 
        //  get customer by email
        //      if customer not exist, create customer with shipping address, 
                    // id = WC_API_Customers::wc_create_new_customer (email, username, passs)
                    // WC_API_Customers::update_customer_data(id, shipping_address)
        //  if existing kickstarter order exists, get order
        //  else create order
        $process = $upload_dir = wp_upload_dir();
        $filename = $upload_dir['basedir'] . '/survery_import.csv';

        $procesed_data = $this->process_kick->load_data($filename, $_POST['data']);
        //$code = json_encode($_POST['data']);
        print "
        <h1>Step 3 - Import results</h1>
        <pre>".print_r($procesed_data, true)."</pre>";
        //print "<pre>".print_r($_POST['data'], true)."</pre>";
        die();
    }

    public function kick_data_define_action()
    {
        $form = $_POST;
        $errors = array();
        //$uniq = array_count_values(array_values($form));
        $uniq = array();
        foreach ($form as $key => $value) {
            if(is_array($value)) continue;
            if(!isset($uniq[$value])) $uniq[$value]=0;
            $uniq[$value]+=0;
        }
        $nonce_check = check_ajax_referer('kick_file_define_nonce', 'kick_file_define_nonce');
        if(! $nonce_check) $errors[] = "Bad nonce";
        foreach ( $form as $key => $value) {
            if (empty($value)) $errors[] = "missing info for $key";
            if( !is_array($value) and isset($uniq[$value]) and $uniq[$value]>1) $errors[$value] = "$value is set more then once";
        }

        $response = array();
        if(!empty($errors)){
            $response['error'] = true;
            $response['msg'] = implode('<br>', array_values($errors));
        } else {
            $response['action'] =  'kickstarter_process_survey_data';
            $response['data'] =  $form;
        }
        //$response['action'] =  'kickstarter_process_survey_data';
        echo json_encode($response);
        die();
    }

    public function kick_survey_upload_action()
    {
        check_ajax_referer('kick_file_upload_nonce', 'kick_file_upload_nonce');
         if(!(is_array($_POST) && is_array($_FILES) && defined('DOING_AJAX') && DOING_AJAX)){
            return;
        }
        if(!function_exists('wp_handle_upload')){
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        $upload_overrides = array('test_form' => false);

        $response = array();

        foreach($_FILES as $file){
            $upload_dir = wp_upload_dir();
            $filename = $upload_dir['basedir'] . '/survery_import.csv';
            $move_result = move_uploaded_file($file['tmp_name'], $filename);
            //$file_info = wp_handle_upload($file, $upload_overrides);
            if($move_result){
                $response['url'] = $_SERVER['REQUEST_URI'];
                //add_query_arg('file'='1');
                $response['file'] = $filename;
                $response['kickstep'] = '2';
                $response['action'] =  'kickstarter_define_page';
            }
            else {
                $response['msg'] = 'Unable to upload file.';
                $response['error'] = true;
            }
            
        }

        if(!isset($_FILES) or empty($_FILES)){
            $response['msg'] = 'Missing file.';
            $response['error'] = true;
        }

        echo json_encode($response);
        die();
    }

}