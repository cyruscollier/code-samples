<?php 

/*
Plugin Name: OR Custom Cart
Plugin URI:  http://www.collierwebdesign.com
Description: Shopping cart customizations for OR
Version:     1.0
Author:      Cyrus Collier
*/

define('OR_CUSTOM_CART_DIR',plugin_dir_path(__FILE__));
define('OR_CUSTOM_CART_URL',plugin_dir_url(__FILE__));

require_once(OR_CUSTOM_CART_DIR.'simple-product-options-admin.php');


class ORCustomCart {
    
    protected $debug = array();
    protected $product_id;
    protected $product_dp_ids;
    protected $variation_dp_options = array();
    protected $parameters = array();
    protected $cart_product_options;
    protected $custom_tags = array();
    
    const DEFAULT_LOW_STOCK_THRESHOLD = 10;
    const DEPENDENT_PRODUCT_CATEGORY_SLUG = 'dependent-product';

    /**
     * Adds top-level plugin hooks
     */
    function __construct() {
        add_action('plugins_loaded',array(&$this, 'init'),9999);
        register_activation_hook(__FILE__, array(&$this,'schedule_stock_alerts'));
        register_deactivation_hook(__FILE__, array(&$this,'unschedule_stock_alerts'));
    }
    
    /**
     * Add actions and filters
     */
    function init() {
        add_action('init',array(&$this,'ajax_requests'),9999);
        add_action('wpsc_before_cart',array(&$this,'edit_dependent_products'), 1, 3);
        add_action('wpsc_update_variation_product',array(&$this,'update_product_price'), 1, 2);
        add_action('wpsc_save_cart_item',array(&$this,'append_cart_item_name'),1,2);
        add_action('schedule_stock_alerts', array(&$this,'send_stock_alerts'));
        add_filter('wpsc_cart_item_name',array(&$this,'cart_item_name'));
        add_filter('esc_html',array(&$this,'cart_item_name_html'),1,2);
        add_filter('wpsc_cart_item_count', array(&$this,'cart_item_count'));
        add_filter('wpsc_cart_item_class', array(&$this,'cart_item_class'));
        add_filter('wpsc_product_stock', array(&$this,'product_stock'),1,2);
        add_filter('wp_mail', array(&$this, 'cart_email'));
        add_filter('wpsc_email_message',array(&$this, 'cart_email_message'),1,6);
        add_filter('wpsc_transaction_result_report',array(&$this, 'cart_message_clear'));
        add_filter('wpsc_transaction_result_message_html',array(&$this, 'cart_message_clear'));
        wp_enqueue_script('or-custom-cart-js',OR_CUSTOM_CART_URL.'or-custom-cart.js');
        $this->custom_tags = array(
            'product_list' => 'product_list_html'
        );
    }
    
    function debug() {
        echo '<pre>'.var_export($this->debug,true).'</pre>';
    }
    
    function clear_stock_claims() {
        global $wpdb;
        $wpdb->query( "DELETE FROM " . WPSC_TABLE_CLAIMED_STOCK . " WHERE 1=1" );
    }

    /**
     * Checks if product is dependently linked to another product (only needed if flag not present)
     * 
     * @param object $cart_item
     * @return bool
     */
    protected function is_dependent_product($cart_item) {
        global $wpdb, $wpsc_cart;
        if(is_object($cart_item)) {
            $product = get_post($cart_item->product_id);
        } else {
            $product = get_post($cart_item);
        }
        $dp_category = get_term_by('slug', self::DEPENDENT_PRODUCT_CATEGORY_SLUG, 'wpsc_product_category');
        //switch to parent product for variations
        $target_product_id = $product->post_parent ? $product->post_parent : $product->ID;
        $categories = get_the_terms($target_product_id,'wpsc_product_category');
        if(is_wp_error($categories) or empty($categories[0])) {
            return false;
        } else {
            return (
            $dp_category->term_id == $categories[0]->term_id ||
            $dp_category->term_id == $categories[0]->parent
            );
        }
    }
    
    /**
     * Get all dependent products of a single product
     * 
     * @param int $product_id
     * @return array
     */
    protected function get_dependent_products($product_id) {
        $dp_ids = array();
        $dp_skus = get_product_meta( $product_id, 'dependent-product-skus', true );
        $parent_product = get_post($product_id);
        if(!$dp_skus && $parent_product->post_parent) {
            // this is a variation, get parent product meta if not set explicitly on the variation
            $product_id = $parent_product->post_parent;
            $dp_skus = get_product_meta( $product_id, 'dependent-product-skus', true );
        }
        if($dp_skus) {
            $dp_skus = explode(',',$dp_skus);
            $dp_ids = array();
            foreach($dp_skus as $sku) {
                $sku = trim($sku);
                $dp_ids[] = get_product_by_sku($sku,true);            
            }
        }
        return $dp_ids;
    }
    
    /**
     * Get child variation products
     * 
     * @param int $product_id
     * @return array
     */
    protected function get_variation_products($product_id) {
        $posts = (array)get_posts( array(
                'post_parent' => $product_id,
                'post_type' => "wpsc-product",
                'post_status' => 'all',
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'suppress_filters' => true
        ) );
        //arrage in ID map
        $posts_array = array();
        foreach($posts as $post) $posts_array[$post->ID] = $post;
        return $posts_array;
    }
    
    /**
     * Gets variation product based on parent product and variation term
     * 
     * @param int $product_id
     * @param array $term_ids
     * @return object
     */
    protected function get_variation_product_from_term($product_id,$term_ids) {
        global $wpdb;
        $term_ids = (array) $term_ids;
        $term_ids = implode(',',$term_ids);
        $results = $wpdb->get_row(
                "SELECT * FROM wp_posts as p
                    INNER JOIN wp_term_relationships AS tr ON tr.object_id = p.ID
                    INNER JOIN wp_term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                    INNER JOIN wp_terms AS t ON tt.term_id = t.term_id
                        WHERE p.post_parent = $product_id AND t.term_id IN ($term_ids)"
        );
        return $results;
    }
    
    /**
     * Process ajax requests
     * action: init
     */
    function ajax_requests() {
        global $wpdb;
        if(isset($_GET['update_product_options']) && is_numeric($_GET['update_product_options'])) {
            //get variation if present
            $this->product_id = (int) $_GET['update_product_options'];
            $variation_term = isset($_GET['variation']) && is_numeric($_GET['variation']) ? (int) $_GET['variation'] : false;
            if($variation_term) {
                $variation_product = $this->get_variation_product_from_term($this->product_id, $variation_term);
                if($variation_product) $this->product_id = $variation_product->ID;
            }
            $this->display_product_options();
            exit();
        }
    }
    
    /**
     * Adds/updates/removes dependent products in cart
     * action: wpsc_before_cart
     * 
     * @param int $product_id
     * @param array $parameters
     * @param array $cart_messages
     */
    function edit_dependent_products($product_id, $parameters, $cart_messages ) {
        global $wpsc_cart;
        //adding a product
        if(isset($product_id)) {
            $this->product_id = $product_id;
            $dp_ids = $this->get_dependent_products($product_id);
            //check for remaining stock on all dps first
            $final_stock = $this->dependent_product_stock($dp_ids);
            $cart_dp_ids = array();
            foreach($dp_ids as $dp_id) {
                //copy working variables 
                $dp_parameters = $parameters;
                $cart_dp_id = $dp_id;
                //override with variation, if present
                $variation_dp_option_array = $this->get_posted_dependent_product_option($dp_id);
                if($variation_dp_option_array) {
                    $option_set = get_term($variation_dp_option_array['option_set'], 'wpec_product_option');
                    $option = $variation_dp_option_array['option'];
                    $cart_dp_id = $option->product_id;
                    $dp_parameters['custom_message'] = "$option_set->name: $option->name";
                } else {
                    $dp_parameters['custom_message'] = null;
                }
                if($final_stock - $parameters['quantity'] >= 0) {
                    $claimed_stock = $this->get_claimed_dependent_product_stock($cart_dp_id);
                    $state = $wpsc_cart->set_item( $cart_dp_id, $dp_parameters );
                    //product successfully added
                    if($state == true) {
                        $last_item = $wpsc_cart->cart_items[$wpsc_cart->cart_item_count - 1];
                        $last_item->is_dependent_product = true;
                        $this->set_claimed_dependent_product_stock($cart_dp_id,$claimed_stock+$dp_parameters['quantity']);
                    }
                }
            }
            $final_stock = $this->dependent_product_stock($dp_ids);
            //trigger ajax update of available product options
            echo '<script type="text/javascript">update_product_options('.$this->product_id.');</script>';
            
        }
        
        //update quantity
        if ( isset($_POST['key']) && is_numeric( $_POST['key'] ) && -1 < $_POST['key'] ) {
            $key = (int)$_POST['key'];
            if ( $_POST['quantity'] > 0 ) {
                // if the quantity is greater than 0, increment the key index and update the next items;
                $parameters['quantity'] = (int)$_POST['quantity'];    
                $key++;
                //gather all related dps
                $dp_ids = array();
                while ($wpsc_cart->cart_items[$key] && $this->is_dependent_product($wpsc_cart->cart_items[$key])) {
                    $dp_ids[$key] = $wpsc_cart->cart_items[$key]->product_id;
                    $key++;
                }
                $final_stock = $this->dependent_product_stock($dp_ids,$key);
                
                foreach($dp_ids as $key => $dp_id) {
                    $quantity = $parameters['quantity'] - $wpsc_cart->cart_items[$key]->quantity;
                    if($final_stock - $quantity >= 0) {
                        $post_quantity = $parameters['quantity'];
                        $claimed_stock = $this->get_claimed_dependent_product_stock($dp_id);
                        $new_stock = $claimed_stock + $quantity;
                        $state = $wpsc_cart->edit_item( $key, $parameters );
                        //product successfully edited
                        if($state == true) {
                            $this->set_claimed_dependent_product_stock($dp_id,$new_stock);
                        }
                        $key++;
                    }
                } 
                $final_stock = $this->dependent_product_stock($dp_ids);
            } else {
                //gather all related dps
                $dp_ids = array();
                $_key = $key;
                while ($wpsc_cart->cart_items[$_key] && $this->is_dependent_product($wpsc_cart->cart_items[$_key])) {
                    $dp_ids[$_key] = $wpsc_cart->cart_items[$_key]->product_id;
                    $_key++;
                }
                $final_stock = $this->dependent_product_stock($dp_ids);
                //remove the next items without incrementing the key index.
                while ($wpsc_cart->cart_items[$key] && $this->is_dependent_product($wpsc_cart->cart_items[$key])) {
                    $current_item = $wpsc_cart->cart_items[$key];
                    $claimed_stock = $this->get_claimed_dependent_product_stock($current_item->product_id);
                    $quantity = $current_item->quantity;
                    $new_stock = $claimed_stock-$quantity;
                    $wpsc_cart->remove_item( $key );
                    $this->set_claimed_dependent_product_stock($current_item->product_id,$new_stock);
                }
                $final_stock = $this->dependent_product_stock($dp_ids);
            }
        }
    }
    
    /**
     * Adds product options to cart product name
     * filter: wpsc_save_cart_item
     * 
     * @param string $name
     * @param $cart_item object|null
     * @return string
     */
    function cart_item_name($name, $cart_item = null) {
        global $wpsc_cart;
        //use current item in wpsc_cart if not supplied directly
        if(!$cart_item) $cart_item = $wpsc_cart->cart_item;
        $message = $cart_item->custom_message;
        //only add to dps
        if($this->is_dependent_product($cart_item) && $message) {
            $name = $name.'<br /><span class="product-option"><em>- '.$message.'</em></span>';
        }
        return $name;
    }
    
    /**
     * Add non-converted html to product name
     * filter: esc_html
     * 
     * @param string $safe_text
     * @param string $text
     * @return string
     */
    function cart_item_name_html($safe_text,$text) {
        global $wpsc_cart;
        if($wpsc_cart && $wpsc_cart->cart_item && $wpsc_cart->cart_item->custom_message) {
            return html_entity_decode($safe_text);
        }
        return $safe_text;
    }
    
    /**
     * Appends custom message to product name for transaction display and emails
     * action: wpsc_save_cart_item
     * 
     * @param int $cart_id
     * @param int $product_id
     */
    function append_cart_item_name($cart_id,$product_id) {
        global $wpdb,$wpsc_cart;
        $selected_cart_item = false;
        foreach($wpsc_cart->cart_items as $cart_item) {
            if($product_id == $cart_item->product_id) $selected_cart_item = $cart_item;
        }
        $name = $this->cart_item_name($selected_cart_item->product_name,$selected_cart_item);
        //saves appended name to database
        $wpdb->query($wpdb->prepare(
            "UPDATE `".WPSC_TABLE_CART_CONTENTS."` SET `name` = '%s'
            WHERE `id` = %d AND `prodid` = %d",
        $name,
        $cart_id,
        $product_id
        ));
    }
    
    /**
     * Subtracts dependent products from cart count
     * filter: wpsc_cart_item_count
     * 
     * @param int $count
     * @param int
     */
    function cart_item_count($count) {
        global $wpsc_cart;
        $count = 0;
        foreach((array)$wpsc_cart->cart_items as $cart_item) {
            $cart_item->is_dependent_product = $this->is_dependent_product($cart_item);
            if(!$cart_item->is_dependent_product) $count += $cart_item->quantity;
        }
        return $count;
    }
    
    /**
     * Adds "dependent-product" css class to item rows
     * filter: wpsc_cart_item_class
     * 
     * @param string $css_class
     * @return string
     */
    function cart_item_class($css_class) {
        global $wpsc_cart;
        if($this->is_dependent_product($wpsc_cart->cart_item)) $css_class .= ' dependent-product';
        return $css_class;
    }
    
    /**
     * Overrides update product price with dependent product availability
     * action: wpsc_update_variation_product
     * 
     * @param int $product_id
     * @param array $variations
     */
    function update_product_price($product_id,$variations) {
        global $wpdb, $wpsc_cart;
        $from = '';
        $change_price = true;
        $stock = null;
        $response = array(
            'product_id' => $product_id,
            'variation_found' => false,
        );
        $the_selected_product = $this->get_variation_product_from_term($product_id, $variations);
        $dp_ids = $this->get_dependent_products($the_selected_product->ID);

        if($the_selected_product && $dp_ids) {
            $this->product_id = $the_selected_product->ID;
            $dp_msg = array();
            $stock_available = true;
            foreach ( $dp_ids as $dp_id ) {
                $categories = get_the_terms($dp_id,'wpsc_product_category');
                foreach($categories as $cat) {
                    if(self::DEPENDENT_PRODUCT_CATEGORY_SLUG == $cat->slug) continue;
                    $cat_name = $cat->name;
                }
                $stock = $wpsc_cart->get_remaining_quantity($dp_id,null,0);
                //check if dp has variations, combine their stock totals instead
                $dp_variations = $this->get_variation_products($dp_id);
                foreach($dp_variations as $dp_variation) {
                    $stock += $wpsc_cart->get_remaining_quantity($dp_variation->ID,null,0);
                }
                $stock_display = $stock;
                $threshold = get_product_meta( $dp_id, 'low-stock-threshhold', true );
                if(!$threshold) $threshold = self::DEFAULT_LOW_STOCK_THRESHOLD;
                if($stock <= $threshold) {
                    if($stock != 1) $cat_name .= 's';
                    if($stock === 0) {
                        $stock_display = 'No';
                        $stock_available = false;
                        $stock_class = 'no-stock-warning';
                    } else {
                        $stock_class = 'low-stock-warning';
                    }
                    $dp_msg[] = "<span class=\"$stock_class\">$stock_display $cat_name left!</span>";
                } else {
                    $dp_msg[] = __( 'Product in stock', 'wpsc' );
                }
            }
        } else {
            $dp_msg[] = __( 'Product in stock', 'wpsc' );
        }
                
        $dp_msg = implode('<br />',$dp_msg);
        
        if ( $stock !== false ) {
            $response['variation_found'] = true;
            $response += array(
                    'variation_msg'   => $dp_msg,
                    'stock_available' => true,
            );
            if ( $change_price ) {
                $old_price = wpsc_calculate_price( $product_id, $variations, false );
                $you_save_amount = wpsc_you_save( array( 'product_id' => $product_id, 'type' => 'amount', 'variations' => $variations ) );
                $you_save_percentage = wpsc_you_save( array( 'product_id' => $product_id, 'variations' => $variations ) );
                $price = wpsc_calculate_price( $product_id, $variations, true );
                $response += array(
                    'old_price'         => wpsc_currency_display( $old_price, array( 'display_as_html' => false ) ),
                    'numeric_old_price' => (float) number_format( $old_price ),
                    'you_save'          => wpsc_currency_display( $you_save_amount, array( 'display_as_html' => false ) ) . "! (" . $you_save_percentage . "%)",
                    'price'             => $from . wpsc_currency_display( $price, array( 'display_as_html' => false ) ),
                    'numeric_price'     => (float) number_format( $price ),
                );
            }
        }
        echo json_encode( $response );
        exit();
    }
    
    /**
     * Overrides product stock with all dependent product stocks
     * filter: wpsc_product_stock
     * 
     * @param int $stock
     * @param int $product_id
     * @return int
     */
    function product_stock($stock, $product_id) {
        global $wpsc_cart;
        //prevent infinite recursion if already on dependent product or one of its variations, also not needed for checkout quantity update
        if(!$this->is_dependent_product($product_id)) {
            $this->set_posted_dependent_product_option($product_id);
            $dp_ids = $this->get_dependent_products($product_id);
            $stock = $this->dependent_product_stock($dp_ids);
        }
        return $stock;
    }
    
    /**
     * Checks availability of ALL dp stock inclusively
     * 
     * @param array $dp_ids
     * @param bool $key_base
     * @return int
     */
    protected function dependent_product_stock($dp_ids,$key_base = false) {
        global $wpsc_cart;
        $stock = true;
        // get quantity (if present) to compare to stock
        if((isset($_POST['quantity']) && $_POST['quantity'] > 0 && isset($_POST['key'])) && (!isset( $_POST['wpsc_quantity_update'] ))) {
            $key = $key_base ? $key_base : $_POST['key'];
            $post_quantity = $_POST['quantity'];
            $current_quantity = $wpsc_cart->cart_items[$key]->quantity;
            $quantity = $post_quantity- $current_quantity ;
        } elseif ( isset( $_POST['wpsc_quantity_update'] ) ) {
            $quantity = (int)$_POST['wpsc_quantity_update'];
        } else {
            $quantity = 0;
        }
        foreach($dp_ids as $index => $dp_id) {
            if($variation_dp_option_array = $this->get_posted_dependent_product_option($dp_id) ) {
                // a variation of a dp has been set, find its stock
                $product_id = $variation_dp_option_array['option']->product_id;
                $stock = $wpsc_cart->get_remaining_quantity($product_id,null);
            } else {
                // otherwise, combine stocks of all variations for total remainging stock
                $stock = $wpsc_cart->get_remaining_quantity($dp_id,null);
                $dp_variations = $this->get_variation_products($dp_id);
                foreach($dp_variations as $dp_variation) {
                    $stock += $wpsc_cart->get_remaining_quantity($dp_variation->ID,null,0);
                }
            } 
            if($stock - $quantity < 0) break;
        }
        
        //special case workaround for updating product back from 0 remaining stock (negative quantity addition)
        if($quantity < 0 && $stock == 0) return true;
        else return $stock;
    }
    
    /**
     * Sets combined claimed stock of dp cart items
     * 
     * @param int $dp_id
     * @param int $stock
     */
    protected function set_claimed_dependent_product_stock($dp_id,$stock) {
        global $wpdb, $wpsc_cart;
        $wpdb->query("UPDATE `".WPSC_TABLE_CLAIMED_STOCK."` SET `stock_claimed` = '$stock' WHERE `product_id` = '$dp_id' AND `cart_id` = '$wpsc_cart->unique_id'");
    }   
     
    /**
     * Gets claimed stock of dp cart items
     * 
     * @param int $dp_id
     * @return int
     */
    protected function get_claimed_dependent_product_stock($dp_id) {
        global $wpdb;
        $claimed_stock = $wpdb->get_var("SELECT SUM(`stock_claimed`) FROM `".WPSC_TABLE_CLAIMED_STOCK."` WHERE `product_id` IN('$dp_id') AND `variation_stock_id` IN('0')");
        return $claimed_stock;
    }
    
    /**
     * Gets product options from $_POST and link with a dp variation
     * 
     * @param int $product_id
     */
    protected function set_posted_dependent_product_option($product_id) {
        //don't do anything if there is no posted product and product options being added
        if(empty($_POST['product_id']) || empty($_POST['wpec-product-option'])) return;
        $this->product_id = $product_id;
        $dp_ids = $this->get_dependent_products($this->product_id);
        foreach($dp_ids as $dp_id) {
            //is there a variation, set from a submitted product option?
            $variation_options = $this->get_all_product_options($dp_id);
            $dp_variations = $this->get_variation_products($dp_id);
            //POSTed product option matches a product?
            $posted_options = $_POST['wpec-product-option'];
            $display_options = array();
            foreach($posted_options as $set => $option_id) {
                if(empty($option_id) || empty($variation_options[$option_id*1])) continue;
                $option = $variation_options[$option_id*1];
                if($dp_variations[$option->product_id]) {
                    //set variation product option for dp
                    $this->variation_dp_options[$dp_id] = array('option_set' => $set, 'option' => $option);
                }
            }
        }
    }
    
    /**
     * Returns stored dependent product variation(s) product option
     * 
     * @param int $dp_id
     * @return array|false
     */
    protected function get_posted_dependent_product_option($dp_id) {
        return isset($this->variation_dp_options[$dp_id]) ? $this->variation_dp_options[$dp_id] : false;
    }
    
    /**
     * Collects and combines all product options attached to a dp or its variations
     * 
     * @param int $dp_id
     * @param bool $check_stock
     * @return array
     */
    protected function get_all_product_options($dp_id, $check_stock = false) {
        global $wpsc_cart;
        //first combine main dp with variations to loop through all
        $product_ids = array($dp_id);
        $dp_variations = $this->get_variation_products($dp_id);
        foreach($dp_variations as $dp_variation) $product_ids[] = $dp_variation->ID;
        //loop through each product_id and combine all terms
        $options = array();
        foreach($product_ids as $product_id) {
            $var_options = wp_get_object_terms ( $product_id, 'wpec_product_option', array ( 'orderby' => 'term_group', 'order' => 'asc' ) );
            $options_map = array();
            //set optional stock flag
            $has_stock = $wpsc_cart->check_remaining_quantity($product_id);
            foreach($var_options as $option) {
                //skip if option already added
                if(!empty($options[$option->term_id])) continue;
                //map properties 
                $options_map[$option->term_id] = $option;
                $options_map[$option->term_id]->product_id = $product_id;
                if($check_stock) $options_map[$option->term_id]->disabled = !$has_stock;
            }
            $options = $options + $options_map;
        }
        return $options;
    }
    
    /**
     * Displays menu of product options from each dp variation
     */
    protected function display_product_options() {
        global $wpsc_cart;
        //retrieve the product options for the dp, combine options on variations 
        $product_id = $this->product_id ? $this->product_id : wpsc_the_product_id();
        $dp_ids = $this->get_dependent_products($product_id);
        if (!$dp_ids) return;
        $options_array = array();
        foreach ($dp_ids as $dp_id) {
            //get all the available options
            $options = $this->get_all_product_options($dp_id,true);
            if(empty($options)) continue;
            //first pass: add children placeholder array and create parent terms that don't exist
            foreach($options as $id => $option) {
                $option->children = array();
                if($option->parent && empty($options[$option->parent])) {
                    $options[$option->parent] = get_term($option->parent, 'wpec_product_option' );
                }
            }
            //second pass: add references to children
            $root = null;
            foreach($options as $id => $option) {
                $options[$option->parent]->children[$id] =& $options[$id];
                if(!$option->parent) $root = $id;
            }
            $options_array[$dp_id][$root] = $options[$root];
        }
        //loop through each dp
        foreach($options_array as $dp_id => $dp_set ) {
            //loop through each set of options within a dp
            foreach($dp_set as $set_id => $option_set) {
                $id = esc_attr($option_set->term_id);
                echo '<p><label for="wpec-product-option-'.$id.'" >'.esc_html($option_set->name).': </label></p>';
                echo '<p><select class="wpec-product-option-select" name="wpec-product-option['.$id.']" id="wpec-product-option-'.$id.'">';
                echo '<option value="">-- Select '.esc_html($option_set->name).' --</option>';
                //loop through each subset as optgroup
                foreach($option_set->children as $option_group ) {
                    $message = $option_group->disabled ? ' - SOLD OUT' : '';
                    echo '<optgroup label="'.esc_html($option_group->name).$message.'">';
                    //loop through options
                    foreach($option_group->children as $option_id => $option) {
                        $disabled = $option->disabled ? ' disabled="disabled"' : '';
                        echo '<option'.$disabled.' value="'.esc_attr($option->term_id).'">'.esc_html($option->name).'</option>';
                    }
                    echo '</optgroup>';
                }
                echo '</select></p>';
            }
        }
    }
    
    /**
     * Schedules send_stock_warning into wp-cron
     * activation_hook
     */
    function schedule_stock_alerts() {
        wp_schedule_event(time(), 'daily', 'schedule_stock_alerts');
    }
    
    /**
     * Unschedules schedule_stock_alerts
     * deactivation hook
     */
    function unschedule_stock_alerts() {
        wp_clear_scheduled_hook('schedule_stock_alerts');
    }
    
    /**
     * Send scheduled low stock warnings to admin
     * action: schedule_stock_alerts
     */
    function send_stock_alerts() {
        //try to load class from WP e-Commerce Dashboard
        include_once(WP_PLUGIN_DIR.'/wp-e-commerce-dashboard-premium/widgets/stock_alerts.php');
        if(!class_exists( 'ses_wpscd_stock_alerts_widget' )) return;
        $stock_alerts = new ses_wpscd_stock_alerts_widget();
        ob_start();
        $stock_alerts->render();
        $output = ob_get_clean();
        $subject = 'Daily Stock Alerts';
        $headers = 'MIME-Version: 1.0' . "\r\n" .
                'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
        //modify content for email body
        $output = preg_replace_callback(
            '/post\.php\?post=\d+&action=edit/',
            create_function(
                '$matches',
                'return admin_url($matches[0]);'
            ),
        $output);
        $output = str_replace('<table width="100%"','<table cellpadding="5" ',$output);
        $output = <<<HTML
<html>
    <head>
        <title>$subject</title>
        <style type="text/css">
.ses-wpscd-table { border: 1px solid #dddddd; border-collapse: collapse; background-color: #f9f9f9; }
.ses-wpscd-headerrow { border-bottom: 1px solid #dddddd; }
.ses-wpscd-row { border-bottom: 1px solid #f9f9f9; }
.ses-wpscd-cell { text-align: center; }
.ses-wpscd-left { text-align: left; }
.ses-wpscd-amber-stock { background: #cc9900; text-shadow: 1px 1px 1px rgb(255,255,255); }
.ses-wpscd-red-stock { background: #cc3333; color: #fff; text-shadow: 1px 1px 1px rgb(0,0,0); }
        </style>
    </head>
    <body>
        <h2>$subject</h2>
        <h3>The following products have low or no stock left:</h3>
        $output
    </body>
</html>
HTML;
        
        wp_mail(get_option( 'purch_log_email' ),'Daily Stock Alerts', $output, $headers);
    }
    
    /**
     * Remove custom tags from html page display and reports
     * filter: wpsc_transaction_result_report
     * 
     * @param string $message_html
     * return string
     */
    function cart_message_clear($message_html) {
        foreach($this->custom_tags as $base => $tag) {
            //restore original base tags
            if(is_string($base)) $message_html = str_replace("%$tag%", "%$base%", $message_html);
            //clear any remaining custom tags
            $message_html = str_replace("%$tag%", '', $message_html);
        }
        return $message_html;
    }
    
    /**
     * Adds custom tags to cart email message
     * filter: wpsc_email_message
     * 
     * @param string $message
     * @param int $report_id
     * @param array $product_list
     * @param float $total_tax
     * @param string $total_shipping_email
     * @param string $total_price_email
     * @return string
     */
    function cart_email_message($message, $report_id, $product_list, $total_tax, $total_shipping_email, $total_price_email) {
        $params = compact('message','report_id','product_list','total_tax','total_shipping_email','total_price_email');
        foreach($this->custom_tags as $base => $tag) {
            $output = method_exists($this, "tag__$tag") ? $this->{"tag__$tag"}($params) : '';
            $message = str_replace("%$tag%", $output, $message);
            //remove duplicate original tag if present
            if(is_string($base)) str_replace("%$base%", '', $message);
        }
        return $message;
    }
    
    function tag__product_list_html($params) {
        //convert product lines into table rows
        $rows = explode("\n\r",$params['product_list']);
        foreach($rows as $index => $row) {
            //delete and skip empty rows
            $row = trim($row);
            if(empty($row)) {
                   unset($rows[$index]);
                   continue;
               }
            if(preg_match('/(\d+)(.*?)(\$[\d\.]+)/',$row,$cells)) {
                //this is a product line
                $row = '<td class="quantity">'.$cells[1].'</td>'.
                               '<td class="product-name">'.trim($cells[2]).'</td>'.
                               '<td class="price">'.$cells[3].'</td>';
               } else {
                   //something else, just make a single cell in the last column
                $row = '<td colspan="3" class="price">'.$row.'</td>';
            }
            $rows[$index] = $row;
        }
           $product_list = '<table class="product-list"><tr>'.implode('</tr><tr>',$rows).'</tr></table>';
        return $product_list;
    }
    //filter: adds html content-type to cart emails
    function cart_email($params) {
        global $wpsc_cart, $message_html, $cart, $purchase_log;
        //confirm is this an email sent from transaction processing
        if(
            //correct globals exist
            $wpsc_cart && $message_html && $cart && $purchase_log && 
            //purchase log id and customer email exist
            isset($purchase_log['id']) && $email = wpsc_get_buyers_email($purchase_log['id']) &&
            //customer email matches recipient
            $email = $params['to']
        ) {
            //add correct headers
            $params['headers'] =  'From: noreply@xxxxxxx.com' . "\r\n" .
                'Reply-To: noreply@xxxxxxx.com' . "\r\n" .
                'MIME-Version: 1.0' . "\r\n" .
                'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
                        
            //convert plain text message via wpautop
            $params['message'] = apply_filters('the_content', $params['message']);
        }
        return $params;
    }
    
    //UNUSED: maps dependent product ids to their product category term ids
    function get_dependent_product_categories($product_id) {
        $categories = array();
        $dp_ids = $this->get_dependent_products($product_id);
        foreach($dp_id as $dp_id) {
            $categories = get_the_terms($dp_id,'wpsc_product_category');
            foreach($categories as $cat) {
                if(self::DEPENDENT_PRODUCT_CATEGORY_SLUG == $cat->slug) continue;
                $categories[$cat->term_id] = $dp_id;
            }
        }
        return $categories;
    }
    
    //UNUSED: update dependent product IDs on variations upon product save
    function update_variations_dependent_products($post_id, $post) {
        //don't do anything if we aren't editing a product  and its a variation
        if(!('wpsc_product' == get_post_type($post) && $post->post_parent)) return;
        $variation_terms = wp_get_object_terms($post_id, 'wpsc_variations');
        foreach($variation_terms as $term) {
            //get term description and retrieve term metadata
            $metadata = $this->get_variation_metadata($term->term_id);
            //only process correct metadata key
            if(self::DEPENDENT_PRODUCT_CATEGORY_SLUG == $metadata['key']) {
                $dp_term = get_term(self::DEPENDENT_PRODUCT_CATEGORY_SLUG, 'wpsc_product_category');
                $dp_product_categories = $this->get_dependent_product_categories($post_id);
                $dp_product_skus = array();
                foreach((array) $metadata['value'] as $value) {
                    //is value a category of dependent product and matches product's linked dps 
                    $category = get_term($value,'wpsc_product_category');
                    if(
                        $category && 
                        $category->parent == $dp_term->term_id &&
                        $dp_product_categories[$category->term_id]
                    ) {
                        $dp_product_id = $dp_product_categories[$category->term_id];
                        //collect SKU from matching dp product
                        $dp_product_skus[] = get_product_meta( $product_id, 'sku', true );
                    }
                }
                //store matches as new custom field on product metadata
                $dp_product_skus = implode(',',$dp_product_skus);
                update_product_meta($post_id, 'dependent-product-skus', $dp_product_skus);
            }                
        }
    }
    
    //UNUSED: retrieve variation "metadata" through regex of description
    function get_variation_metadata($term_id) {
        $term = get_term($term_id, 'wpsc-variation');
        $metadata = array('key' => null, 'value' => null);
        preg_match_all('/^Metadata: ?([\d\w_\-]+?) ?= ?([\d\w_\-,]+?)[\r\n]*/',$term->description,$matches);
        $metadata['key'] = isset($matches[1][0]) ? $matches[1][0] : false;
        $metadata['value'] = isset($matches[2][0]) ? $matches[2][0] : false;
        if(-1 !== strpos($metadata['value'],',')) $metadata['value'] = explode(',',$metadata['value']);
        return $metadata;
    }
    
    //UNUSED: remove metadata from front-end display
    function remove_variation_metadata($value, $term_id, $taxonomy, $context) {
        return preg_replace('/^Metadata: ?([\d\w_\-]+?) ?= ?([\d\w_\-,]+?)[\r\n]*/','',$value);
    }
}

$OR_CustomCart = new OR_CustomCart();

function or_custom_cart_debug() {
    global $OR_CustomCart;
    $OR_CustomCart->debug();
}

function get_product_by_sku($sku,$return_id=false) {
    global $wpdb;
    $post_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpsc_sku' AND meta_value = '$sku'");
    if($return_id) return (int) $post_id;
    return get_post($post_id);
}
