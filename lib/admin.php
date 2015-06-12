<?php


class kickstarter_admin {
    public function __construct()
    {
         //add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
         //add_action( 'woocommerce_settings_tabs_kickstarter_import', array($this, 'kickstarter_import_tab') );

        add_action( 'admin_menu', array( $this, 'kickstarter_menu' ), 20 );
        add_action( 'admin_enqueue_scripts', array($this, 'scripts') );
        //kick_survey_upload_action
        add_action('wp_ajax_kick_survey_upload_action', array($this, 'kick_survey_upload_action'));//page1
        add_action('wp_ajax_kickstarter_define_page', array($this, 'kickstarter_define_page')); //page2
        add_action('wp_ajax_nopriv_kick_survey_upload_action', array($this, 'kick_survey_upload_action'));
    }

    public function scripts()
    {  
        $screen = get_current_screen();
        //
        if( $screen->id =='woocommerce_page_wc-kick-import'){
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
        global $wpdb;
        if(isset($_GET['kickstep']) and $_GET['kickstep']=='2'){

        } else {
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
            ## add customer test
            // get_user_by('email', bobsmith); false if user is not found
            // $newcustomer = wc_create_new_customer('bob.smith1223@mailinator.com', 'bobsmith', wp_generate_password());
            // if(is_wp_error($newcustomer)){
            //     $error_string = $newcustomer->get_error_message();
            //     echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
            // } else {
            //     print "created new customer:<pre>";
            //     print_r( $newcustomer );
            // }

            ## 11, add shipping address test
            // $shipping = array ( "shipping_first_name",
            //     "shipping_last_name",
            //     "shipping_company",
            //     "shipping_address_1",
            //     "shipping_address_2",
            //     "shipping_city",
            //     "shipping_state",
            //     "shipping_postcode",
            //     "shipping_country");


            //     //update_user_meta( $user_id, $meta_key, $meta_value, $prev_value );
            // foreach ($shipping as $key) {
            //     if($key=='shipping_country') update_user_meta( 11, $key, 'US' );
            //     update_user_meta( 11, $key, $key );
            // }

            //add_user_meta( '11', '_imported_from_kickstarter', true);

            ## create order for user
           //  $order_args = array(
           //          'status'        => 'processing',
           //          'customer_id'   => 11,
           //          'created_via'   => 'kickstart_importer'
           //      );
           // $order = wc_create_order($order_args);
           // print '<pre>'; print_r($order);
           //1488
            

            $query = new WP_Query( 'post_type=shop_order');
            $order_id = $wpdb->get_var( $wpdb->prepare( "
                SELECT id FROM `{$wpdb->prefix}posts` WHERE post_type = 'shop_order' and `id` in (
                SELECT a.post_id from {$wpdb->prefix}postmeta as a INNER JOIN {$wpdb->prefix}postmeta as b on a.post_id = b.post_id  WHERE
                a.meta_key = '_created_via' and
                a.meta_value = 'kickstart_importer' and
                b.meta_key = '_customer_user' and
                b.meta_value = '%d'
                )" , '11') );
            if($order_id){
                // has kick order already
                // if order is in processing state, set to hold
                // if item is not on order, add item(s)
                // set order back to processing
                print "have order $order_id";
            } else {
                print "go ahead and create order<br>";

            }
            $nsfw = new process_kick_data();

            //$meta_query = WC()->query->get_meta_query();
            $args = array(
                        'post_type'      => 'product',
                        'no_found_rows'  => 1,
                        'post_status'    => 'publish'
                    );

            // collect success and errors into result set and display
            // provide an export of remaining data/


?>

<div class="side-by-side clearfix">
<select data-placeholder="Choose a Product..." class="chosen-select" multiple style="width:350px;" tabindex="4">
<option value=""></option>

<?php
            $products = new WP_Query( $args );
            if ( $products->have_posts() ) : ?>
                    <?php while ( $products->have_posts() ) : $products->the_post(); ?>
                        <?php  print "<option name='{$products->post->ID}'>#{$products->post->ID}: {$products->post->post_title}</option>";  ?>
                    <?php endwhile; // end of the loop. ?>
            <?php endif;
            wp_reset_postdata();
?>
 </select>
</div>
<?php

        }
    }

    public function kickstarter_define_page() //page 2
    {
        $incoming_fields = $this->csv_header($_POST['file']);
        $options = '';
        foreach ($incoming_fields as $field) {
            $options .= "<option>$field</option>";
        }

        print "
        <h1>Step 2</h1>
        <form id='kick_file_define' method='POST'>
        ".wp_nonce_field('kick_file_define_nonce', 'kick_file_define_nonce')."
        <p>Match each field with its wordpress equivalent</p>
        <h2>User fields</h2>
        <label>User name:<select name='username'>$select</select></label>
        <label>Email:<select  name='email'>$select</select></label>
        <h2>WooCommerce shipping</h2>
        <label>Email:<select  name='email'>$select</select></label>
        </form>
        ";

        //$this->html_show_array($this->csv_to_array($_POST['file']));

        die();
    }

    public function process_survey_data() // final
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

    public function csv_header($csvfile) {
        if (!file_exists($csvfile)) {
            return array();
        }
        $f = fopen($csvfile, 'r');

        $line = fgetcsv($f);
        fclose($f);
        return $line;
    }

    public function csv_to_array($csvfile) {
        if (!file_exists($csvfile)) {
            return array();
        }
        $f = fopen($csvfile, 'r');

        $csv_data = array();

        $first_row_is_header = true;
        $header_keys = array();

        while ($line = fgetcsv($f)) {
            #array_push($csv_data, $line);
            if ($first_row_is_header) {
                $header_keys = $line;
                $first_row_is_header = false;
                continue;
            }
            $row = array();
            #print = '<>';
            #print_r($header_keys);
            #print_r($line);
            #exit;
            for ($i = 0; $i < count($line); $i++) {
                if (!isset($header_keys[$i]) or !isset($line[$i])) {
                    continue;
                }

                $row[$header_keys[$i]] = $line[$i];
            }
            array_push($csv_data, $row);

        }
        fclose($f);
        #print_r($csv_data);
        return $csv_data;
    }
    function html_show_array($table) {
        echo "<table border='1'>";
        
        echo "<tr>";
        foreach (array_keys($table[0]) as $key) {
            echo "<td>" . $key . "</td>";
        }
        echo "</tr>";
        
        foreach ($table as $rows => $row) {
            echo "<tr>";
            foreach ($row as $col => $cell) {
                echo "<td>" . $cell . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}