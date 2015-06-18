<?php



/**
* 
*/
class process_kick_data
{
    public $stats = array();
    function __construct()
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

    public function load_data($filename, $map_data)
    {
        $errors = array();
        $rc = 1;

        $csv_data = $this->csv_to_array($filename);
        $woo_data = array();


        $products = $map_data['product_choices'];
        unset($map_data['product_choices']);
        $map_data = array_flip($map_data);
        
        foreach ($products as $key => $value) {
            // extract product from value and make keys, because chosen is not doing it
            // $value ~= '#1234: cool blue item';
            $newkey = explode(':', substr($value, 1));
            $products[$newkey[0]] = $value;
            unset($products[$key]);
        }

        // map kick columns to woocommerce field names
        foreach ($csv_data as $row) {
            foreach ($row as $key => $value) {
                if(isset($map_data[$key])){
                    $woo_data[$map_data[$key]] = $value;
                    //unset($row[$key]);
                } 
                
            }

            //breakup name
            $username_parts = preg_split('/\s+/', $woo_data['username'], 2);
            $shipping_name_parts = preg_split('/\s+/', $woo_data['shipping_name'], 2);
            $email = trim($woo_data['email']);
            $username = implode('.', preg_split('/\s+/', $woo_data['username']));
            $woo_data["first_name"] = $username_parts[0];
            $woo_data["last_name"]  = (isset($username_parts[1]))? $username_parts[1] : '';
            $woo_data["shipping_first_name"] = $shipping_name_parts[0];
            $woo_data["shipping_last_name"]  = (isset($shipping_name_parts[1]))? $shipping_name_parts[1] : '';;
            //print 'woo_data:<br>';print_r($woo_data);return;
            //print 'row:<br>';print_r($row);return;


            $user_id = $this->create_user($email, $username, $woo_data, $row);
            if(is_wp_error($user_id)){
                $errors[] = array($rc, $user_id);
                continue;
            }

            $order = $this->create_order($user_id);
            if(is_wp_error($order)){
                $errors[] = array($rc, $order);
                continue;
            }

            $this->add_shipping_address($order, $user_id);
            
            

            #print "including products<br>";
            $incp = $this->include_products( $order, $products, $user_id );
            if(is_wp_error($incp)){
                $errors[] = array($rc, $incp);
                continue;
            }

            $rc += 1;
        }
        
        return array($this->stats, $errors);
    }

    public function include_products($order, $products, $customer_id)
    {

        #print "enter include_products ".print_r($products,true)."<br>";
        $order->post->post_status = 'wc-hold';
        wp_update_post($order->post);

        $existing_products_ids = array();
        $existing_products = $order->get_items();
        foreach ($existing_products as $product) {
            $existing_products_ids[] = $product['product_id'];
        }
        
        foreach ($products as $key => $value) {
            if(in_array($key, $existing_products_ids)) continue;
            $this->stat('Products Added');
            $result =  $this->add_order_item($order->id, $key, $customer_id);
            if(is_wp_error($result)) return $result;
            #print "added item $value<br>";
        }

        $order->post->post_status = 'wc-processing';
        wp_update_post($order->post);

        return true;
    }

    public function create_order($user_id){
        global $wpdb;
        
        # get existing kickstarter order
        $query = new WP_Query( 'post_type=shop_order');
        $order_id = $wpdb->get_var( $wpdb->prepare( "
            SELECT id FROM `{$wpdb->prefix}posts` WHERE post_type = 'shop_order' and `id` in (
            SELECT a.post_id from {$wpdb->prefix}postmeta as a INNER JOIN {$wpdb->prefix}postmeta as b on a.post_id = b.post_id  WHERE
            a.meta_key = '_created_via' and
            a.meta_value = 'kickstart_importer' and
            b.meta_key = '_customer_user' and
            b.meta_value = '%d'
            )" , $user_id) );
        if($order_id){
            // has kick order already
            // if order is in processing state, set to hold
            // if item is not on order, add item(s)
            // set order back to processing
            #print "found order $order_id<br>";
            $order = wc_get_order($order_id);
            return $order;
        } else {

            ## create order for user
            $order_args = array(
                    'status'        => 'processing',
                    'customer_id'   => $user_id,
                    'created_via'   => 'kickstart_importer'
                );
            $order = wc_create_order($order_args);
            if(is_wp_error($order)) return $order;
            else $this->stat('Orders Created');
            return $order;
        }
        return new WP_Error('order_error', 'Unable to create order');
    }

    private function create_user($email, $username, $data, $other_meta)
    {
        $user = get_user_by('email', $email);
        if(!$user){
            //print "could not find user, create new user<br>";
            $user = wc_create_new_customer($email, $username, wp_generate_password());
            if($user) $this->stat('Users Created');
            else return $user; 
        } else return $user->ID;

        
        $shipping = array ( 
            "shipping_first_name",
            "shipping_last_name",
            //"shipping_company",
            "shipping_address_1",
            "shipping_address_2",
            "shipping_city",
            "shipping_state",
            "shipping_postcode",
            "shipping_country");

        $exceptions = array('shipping_address_2');
        foreach ($shipping as $key) {
            if(!isset($data[$key])) return new WP_Error('order_error', 'Missing shipping data.');
            if( empty( trim( $data[$key] ) ) and ! in_array($key, $exceptions) ) return new WP_Error('order_error', "Missing required shipping data $key.");
            update_user_meta( $user, $key, $data[$key] );
        }


        update_user_meta( $user, '_kick_meta', json_encode($other_meta) );
        
        return $user;
    }

    private function add_shipping_address($order, $user_id){
        $shipping = array ( 
            "first_name",
            "last_name",
            //"company",
            "address_1",
            "address_2",
            "city",
            "state",
            "postcode",
            "country");
        $kvs = array();
        foreach ($shipping as $key) {
            $value = get_user_meta($user_id, 'shipping_'.$key, true);
            $kvs[$key] = $value;
        }
        $order->set_address($kvs, 'shipping');
    }

    public function add_order_item($order_id, $item_to_add, $customer_id, $quantity=1){

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            return new WP_Error('order_error', 'You do not have permissions to do this');
        }

        // Find the item
        if ( ! is_numeric( $item_to_add ) ) {
            return new WP_Error('order_error', 'item_id invalid');
        }

        $post = get_post( $item_to_add );

        if ( ! $post || ( 'product' !== $post->post_type && 'product_variation' !== $post->post_type ) ) {
            return new WP_Error('order_error', 'cant find order.');
        }

        $_product    = wc_get_product( $post->ID );
        $order       = wc_get_order( $order_id );
        $order_taxes = $order->get_taxes();
        $class       = 'new_row';

        // Set values
        $item = array();

        $item['product_id']        = $_product->id;
        $item['variation_id']      = isset( $_product->variation_id ) ? $_product->variation_id : '';
        $item['variation_data']    = $item['variation_id'] ? $_product->get_variation_attributes() : '';
        $item['name']              = $_product->get_title();
        $item['tax_class']         = $_product->get_tax_class();
        $item['qty']               = $quantity;
        $item['line_subtotal']     = wc_format_decimal( $_product->get_price_excluding_tax() );
        $item['line_subtotal_tax'] = '';
        $item['line_total']        = wc_format_decimal( $_product->get_price_excluding_tax() );
        $item['line_tax']          = '';

        // Add line item
        $item_id = wc_add_order_item( $order_id, array(
            'order_item_name'       => $item['name'],
            'order_item_type'       => 'line_item'
        ) );

        // Add line item meta
        if ( $item_id ) {
            wc_add_order_item_meta( $item_id, '_qty', $item['qty'] );
            wc_add_order_item_meta( $item_id, '_tax_class', $item['tax_class'] );
            wc_add_order_item_meta( $item_id, '_product_id', $item['product_id'] );
            wc_add_order_item_meta( $item_id, '_variation_id', $item['variation_id'] );
            wc_add_order_item_meta( $item_id, '_line_subtotal', $item['line_subtotal'] );
            wc_add_order_item_meta( $item_id, '_line_subtotal_tax', $item['line_subtotal_tax'] );
            wc_add_order_item_meta( $item_id, '_line_total', $item['line_total'] );
            wc_add_order_item_meta( $item_id, '_line_tax', $item['line_tax'] );

            // Since 2.2
            wc_add_order_item_meta( $item_id, '_line_tax_data', array( 'total' => array(), 'subtotal' => array() ) );

            // Store variation data in meta
            if ( $item['variation_data'] && is_array( $item['variation_data'] ) ) {
                foreach ( $item['variation_data'] as $key => $value ) {
                    wc_add_order_item_meta( $item_id, str_replace( 'attribute_', '', $key ), $value );
                }
            }
        }

        
        //wc_add_order_item_meta( $item_id, '_customer_user', $customer_id );

        return true;
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

    public function stat($key)
    {
        if(!isset($this->stats[$key])) $this->stats[$key] = 0;
        $this->stats[$key] ++;
    }
}