<?php
/**
 * Plugin Name: Midtrans Standalone Payment Gateway with Products
 * Plugin URI: https://gmindo.com
 * Description: Midtrans payment gateway with product management and shopping cart
 * Version: 2.0.0
 * Author: sheinaMei
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MIDTRANS_STANDALONE_VERSION', '2.0.0');
define('MIDTRANS_STANDALONE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MIDTRANS_STANDALONE_PLUGIN_PATH', plugin_dir_path(__FILE__));

class MidtransStandalonePayment {
    
    private $settings;
    
    public function __construct() {
        $this->init();
    }
    
    private function init() {
        // Load settings
        $this->settings = get_option('midtrans_standalone_settings', array());
        
        // Register hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'register_post_types'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Payment handling
        add_action('wp_ajax_create_midtrans_payment', array($this, 'create_payment'));
        add_action('wp_ajax_nopriv_create_midtrans_payment', array($this, 'create_payment'));
        add_action('wp_ajax_check_payment_status', array($this, 'check_payment_status'));
        add_action('wp_ajax_nopriv_check_payment_status', array($this, 'check_payment_status'));
        
        // Webhook handler
        add_action('wp_ajax_midtrans_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_midtrans_webhook', array($this, 'handle_webhook'));
        
        // Product management
        add_action('init', array($this, 'register_product_post_type'));
        add_action('add_meta_boxes', array($this, 'add_product_meta_boxes'));
        add_action('save_post_midtrans_product', array($this, 'save_product_meta'));
        
        // Cart functionality
        add_action('wp_ajax_add_to_cart', array($this, 'add_to_cart'));
        add_action('wp_ajax_nopriv_add_to_cart', array($this, 'add_to_cart'));
        add_action('wp_ajax_remove_from_cart', array($this, 'remove_from_cart'));
        add_action('wp_ajax_nopriv_remove_from_cart', array($this, 'remove_from_cart'));
        add_action('wp_ajax_update_cart', array($this, 'update_cart'));
        add_action('wp_ajax_nopriv_update_cart', array($this, 'update_cart'));
        add_action('wp_ajax_get_cart', array($this, 'get_cart'));
        add_action('wp_ajax_nopriv_get_cart', array($this, 'get_cart'));
        add_action('wp_ajax_create_cart_payment', array($this, 'create_cart_payment'));
        add_action('wp_ajax_nopriv_create_cart_payment', array($this, 'create_cart_payment'));
        add_action('wp_ajax_create_direct_midtrans_payment', array($this, 'create_direct_payment'));
        add_action('wp_ajax_nopriv_create_direct_midtrans_payment', array($this, 'create_direct_payment'));
        
        // Shortcodes
        add_shortcode('midtrans_payment_form', array($this, 'payment_form_shortcode'));
        add_shortcode('midtrans_payment_history', array($this, 'payment_history_shortcode'));
        add_shortcode('midtrans_pay_button', array($this, 'pay_button_shortcode'));
        add_shortcode('product_list', array($this, 'product_list_shortcode'));
        add_shortcode('product_detail', array($this, 'product_detail_shortcode'));
        add_shortcode('shopping_cart', array($this, 'shopping_cart_shortcode'));
        
        // Initialize cart
        add_action('init', array($this, 'init_cart'));
        
        // Floating cart
        add_action('wp_footer', array($this, 'floating_cart'));
    }
    
    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'midtrans_transactions';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id varchar(100) NOT NULL,
            transaction_id varchar(100),
            customer_name varchar(255),
            customer_email varchar(255),
            customer_phone varchar(50),
            amount decimal(10,2) NOT NULL,
            payment_method varchar(100),
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            payment_data text,
            PRIMARY KEY (id),
            UNIQUE KEY order_id (order_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function register_post_types() {
        // We'll register product post type in separate function
    }
    
    public function register_product_post_type() {
        $labels = array(
            'name' => 'Products',
            'singular_name' => 'Product',
            'menu_name' => 'Products',
            'name_admin_bar' => 'Product',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Product',
            'new_item' => 'New Product',
            'edit_item' => 'Edit Product',
            'view_item' => 'View Product',
            'all_items' => 'All Products',
            'search_items' => 'Search Products',
            'parent_item_colon' => 'Parent Products:',
            'not_found' => 'No products found.',
            'not_found_in_trash' => 'No products found in Trash.'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'midtrans-standalone',
            'query_var' => true,
            'rewrite' => array('slug' => 'product'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-cart'
        );

        register_post_type('midtrans_product', $args);
    }
    
    public function add_product_meta_boxes() {
        add_meta_box(
            'product_price_meta',
            'Product Information',
            array($this, 'render_product_meta_box'),
            'midtrans_product',
            'normal',
            'high'
        );
    }
    
    public function render_product_meta_box($post) {
        $price = get_post_meta($post->ID, '_product_price', true);
        $stock = get_post_meta($post->ID, '_product_stock', true);
        $sku = get_post_meta($post->ID, '_product_sku', true);
        
        wp_nonce_field('product_meta_nonce', 'product_meta_nonce');
        ?>
        <div class="product-meta-fields">
            <div class="meta-field">
                <label for="product_price">Price:</label>
                <input type="number" id="product_price" name="product_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" required>
                <p class="description">Enter the product price in IDR</p>
            </div>
            <div class="meta-field">
                <label for="product_stock">Stock:</label>
                <input type="number" id="product_stock" name="product_stock" value="<?php echo esc_attr($stock); ?>" min="0">
                <p class="description">Leave empty for unlimited stock</p>
            </div>
            <div class="meta-field">
                <label for="product_sku">SKU:</label>
                <input type="text" id="product_sku" name="product_sku" value="<?php echo esc_attr($sku); ?>">
                <p class="description">Product SKU (Stock Keeping Unit)</p>
            </div>
        </div>
        <style>
            .product-meta-fields .meta-field {
                margin-bottom: 20px;
            }
            .product-meta-fields label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .product-meta-fields input {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .product-meta-fields .description {
                font-size: 12px;
                color: #666;
                margin: 5px 0 0 0;
            }
        </style>
        <?php
    }
    
    public function save_product_meta($post_id) {
        if (!isset($_POST['product_meta_nonce']) || !wp_verify_nonce($_POST['product_meta_nonce'], 'product_meta_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array('product_price', 'product_stock', 'product_sku');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Midtrans Settings',
            'Midtrans Store',
            'manage_options',
            'midtrans-standalone',
            array($this, 'admin_page'),
            'dashicons-store',
            56
        );
        
        add_submenu_page(
            'midtrans-standalone',
            'Settings',
            'Settings',
            'manage_options',
            'midtrans-standalone',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'midtrans-standalone',
            'Transactions',
            'Transactions',
            'manage_options',
            'midtrans-transactions',
            array($this, 'transactions_page')
        );
        
        // Products submenu is automatically added by register_post_type
    }
    
    public function register_settings() {
        register_setting('midtrans_standalone_settings', 'midtrans_standalone_settings');
        
        add_settings_section(
            'midtrans_general_section',
            'General Settings',
            array($this, 'general_section_callback'),
            'midtrans_standalone_settings'
        );
        
        add_settings_field(
            'environment',
            'Environment',
            array($this, 'environment_field_callback'),
            'midtrans_standalone_settings',
            'midtrans_general_section'
        );
        
        add_settings_field(
            'server_key',
            'Server Key',
            array($this, 'server_key_field_callback'),
            'midtrans_standalone_settings',
            'midtrans_general_section'
        );
        
        add_settings_field(
            'client_key',
            'Client Key',
            array($this, 'client_key_field_callback'),
            'midtrans_standalone_settings',
            'midtrans_general_section'
        );
    }
    
    public function general_section_callback() {
        echo '<p>Configure your Midtrans payment gateway settings.</p>';
    }
    
    public function environment_field_callback() {
        $environment = isset($this->settings['environment']) ? $this->settings['environment'] : 'sandbox';
        ?>
        <select name="midtrans_standalone_settings[environment]">
            <option value="sandbox" <?php selected($environment, 'sandbox'); ?>>Sandbox</option>
            <option value="production" <?php selected($environment, 'production'); ?>>Production</option>
        </select>
        <p class="description">Use Sandbox for testing, Production for real transactions</p>
        <?php
    }
    
    public function server_key_field_callback() {
        $server_key = isset($this->settings['server_key']) ? $this->settings['server_key'] : '';
        echo '<input type="password" name="midtrans_standalone_settings[server_key]" value="' . esc_attr($server_key) . '" class="regular-text" />';
        echo '<p class="description">Get this from your Midtrans dashboard</p>';
    }
    
    public function client_key_field_callback() {
        $client_key = isset($this->settings['client_key']) ? $this->settings['client_key'] : '';
        echo '<input type="text" name="midtrans_standalone_settings[client_key]" value="' . esc_attr($client_key) . '" class="regular-text" />';
        echo '<p class="description">Get this from your Midtrans dashboard</p>';
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Midtrans Standalone Payment Gateway</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('midtrans_standalone_settings');
                do_settings_sections('midtrans_standalone_settings');
                submit_button();
                ?>
            </form>
            
            <div class="shortcode-guide">
                <h2>Shortcode Usage</h2>
                <p>Use the following shortcodes to display payment forms and products:</p>
                <div class="shortcode-examples">
                    <div class="shortcode-item">
                        <h3>Payment Forms</h3>
                        <ul>
                            <li><code>[midtrans_payment_form]</code> - Display payment form</li>
                            <li><code>[midtrans_payment_history]</code> - Display payment history</li>
                            <li><code>[midtrans_pay_button amount="100000"]</code> - Direct payment button</li>
                        </ul>
                    </div>
                    <div class="shortcode-item">
                        <h3>Product Display</h3>
                        <ul>
                            <li><code>[product_list]</code> - Display product grid</li>
                            <li><code>[product_list limit="8" columns="4"]</code> - Custom product grid</li>
                            <li><code>[product_detail id="123"]</code> - Display single product</li>
                            <li><code>[shopping_cart]</code> - Display shopping cart</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <h2>Webhook URL</h2>
            <p>Set this URL in your Midtrans dashboard as the payment notification URL:</p>
            <code><?php echo admin_url('admin-ajax.php?action=midtrans_webhook'); ?></code>
        </div>
        
        <style>
            .shortcode-guide {
                margin-top: 30px;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 8px;
            }
            .shortcode-examples {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-top: 15px;
            }
            .shortcode-item {
                background: white;
                padding: 15px;
                border-radius: 6px;
                border: 1px solid #ddd;
            }
            .shortcode-item h3 {
                margin-top: 0;
                color: #2c3338;
            }
            .shortcode-item ul {
                margin: 0;
            }
            .shortcode-item code {
                background: #f1f1f1;
                padding: 2px 4px;
                border-radius: 3px;
            }
        </style>
        <?php
    }
    
    public function transactions_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'midtrans_transactions';
        $transactions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Midtrans Transactions</h1>
            <div class="transaction-stats">
                <?php
                $total_success = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'");
                $total_pending = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
                $total_failed = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'");
                $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $table_name WHERE status = 'success'");
                ?>
                <div class="stat-cards">
                    <div class="stat-card success">
                        <h3>Successful</h3>
                        <span class="stat-number"><?php echo $total_success; ?></span>
                    </div>
                    <div class="stat-card pending">
                        <h3>Pending</h3>
                        <span class="stat-number"><?php echo $total_pending; ?></span>
                    </div>
                    <div class="stat-card failed">
                        <h3>Failed</h3>
                        <span class="stat-number"><?php echo $total_failed; ?></span>
                    </div>
                    <div class="stat-card revenue">
                        <h3>Revenue</h3>
                        <span class="stat-number">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions): ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo esc_html($transaction->order_id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($transaction->customer_name); ?></strong><br>
                                    <?php echo esc_html($transaction->customer_email); ?><br>
                                    <?php echo esc_html($transaction->customer_phone); ?>
                                </td>
                                <td>Rp <?php echo number_format($transaction->amount, 0, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($transaction->status); ?>">
                                        <?php echo esc_html(ucfirst($transaction->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($transaction->created_at)); ?></td>
                                <td>
                                    <button class="button button-small view-details" data-transaction='<?php echo json_encode($transaction); ?>'>View Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <style>
            .transaction-stats {
                margin: 20px 0;
            }
            .stat-cards {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                margin-bottom: 20px;
            }
            .stat-card {
                padding: 20px;
                border-radius: 8px;
                color: white;
                text-align: center;
            }
            .stat-card.success { background: #46b450; }
            .stat-card.pending { background: #ffb900; }
            .stat-card.failed { background: #dc3232; }
            .stat-card.revenue { background: #0073aa; }
            .stat-card h3 {
                margin: 0 0 10px 0;
                font-size: 14px;
                opacity: 0.9;
            }
            .stat-number {
                font-size: 24px;
                font-weight: bold;
            }
            .status-badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .status-success { background: #46b450; color: white; }
            .status-pending { background: #ffb900; color: white; }
            .status-failed { background: #dc3232; color: white; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.view-details').on('click', function() {
                var transaction = $(this).data('transaction');
                var content = '<h3>Transaction Details</h3>' +
                    '<p><strong>Order ID:</strong> ' + transaction.order_id + '</p>' +
                    '<p><strong>Customer:</strong> ' + transaction.customer_name + ' (' + transaction.customer_email + ')</p>' +
                    '<p><strong>Amount:</strong> Rp ' + Number(transaction.amount).toLocaleString() + '</p>' +
                    '<p><strong>Status:</strong> ' + transaction.status + '</p>' +
                    '<p><strong>Date:</strong> ' + transaction.created_at + '</p>';
                
                alert(content);
            });
        });
        </script>
        <?php
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        // Midtrans Snap.js
        $environment = isset($this->settings['environment']) ? $this->settings['environment'] : 'sandbox';
        $snap_url = $environment === 'production' 
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
        
        wp_enqueue_script('midtrans-snap', $snap_url, array(), null, true);
        wp_enqueue_script('midtrans-standalone', MIDTRANS_STANDALONE_PLUGIN_URL . 'assets/midtrans-standalone.js', array('jquery', 'midtrans-snap'), MIDTRANS_STANDALONE_VERSION, true);
        
        wp_localize_script('midtrans-standalone', 'midtrans_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('midtrans_nonce'),
            'home_url' => home_url('/')
        ));
        
        wp_enqueue_style('midtrans-standalone', MIDTRANS_STANDALONE_PLUGIN_URL . 'assets/midtrans-standalone.css', array(), MIDTRANS_STANDALONE_VERSION);
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'midtrans-standalone') !== false || $hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_style('midtrans-admin', MIDTRANS_STANDALONE_PLUGIN_URL . 'assets/midtrans-admin.css', array(), MIDTRANS_STANDALONE_VERSION);
        }
    }
    
    // Cart functionality
    public function init_cart() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
        
        if (!isset($_SESSION['midtrans_cart'])) {
            $_SESSION['midtrans_cart'] = array();
        }
    }
    
    public function add_to_cart() {
        if (!wp_verify_nonce($_POST['nonce'], 'midtrans_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($product_id <= 0 || $quantity <= 0) {
            wp_send_json_error('Invalid product or quantity');
        }
        
        $product = get_post($product_id);
        if (!$product || $product->post_type != 'midtrans_product') {
            wp_send_json_error('Product not found');
        }
        
        $price = get_post_meta($product_id, '_product_price', true);
        $stock = get_post_meta($product_id, '_product_stock', true);
        
        if ($stock != '' && $quantity > $stock) {
            wp_send_json_error('Insufficient stock. Only ' . $stock . ' items available.');
        }
        
        $cart_item = array(
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => floatval($price),
            'name' => $product->post_title,
            'image' => get_the_post_thumbnail_url($product_id, 'thumbnail'),
            'permalink' => get_permalink($product_id)
        );
        
        if (isset($_SESSION['midtrans_cart'][$product_id])) {
            $_SESSION['midtrans_cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['midtrans_cart'][$product_id] = $cart_item;
        }
        
        wp_send_json_success(array(
            'message' => 'Product added to cart successfully',
            'cart_count' => $this->get_cart_count(),
            'cart_total' => $this->get_cart_total(),
            'cart_items' => $_SESSION['midtrans_cart']
        ));
    }
    
    public function remove_from_cart() {
        if (!wp_verify_nonce($_POST['nonce'], 'midtrans_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $product_id = intval($_POST['product_id']);
        
        if (isset($_SESSION['midtrans_cart'][$product_id])) {
            unset($_SESSION['midtrans_cart'][$product_id]);
            wp_send_json_success(array(
                'message' => 'Product removed from cart',
                'cart_count' => $this->get_cart_count(),
                'cart_total' => $this->get_cart_total()
            ));
        } else {
            wp_send_json_error('Product not found in cart');
        }
    }
    
    public function update_cart() {
        if (!wp_verify_nonce($_POST['nonce'], 'midtrans_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity <= 0) {
            unset($_SESSION['midtrans_cart'][$product_id]);
        } else {
            // Check stock
            $stock = get_post_meta($product_id, '_product_stock', true);
            if ($stock != '' && $quantity > $stock) {
                wp_send_json_error('Insufficient stock. Only ' . $stock . ' items available.');
            }
            $_SESSION['midtrans_cart'][$product_id]['quantity'] = $quantity;
        }
        
        wp_send_json_success(array(
            'cart_count' => $this->get_cart_count(),
            'cart_total' => $this->get_cart_total(),
            'item_total' => isset($_SESSION['midtrans_cart'][$product_id]) ? 
                $_SESSION['midtrans_cart'][$product_id]['quantity'] * $_SESSION['midtrans_cart'][$product_id]['price'] : 0
        ));
    }
    
    public function get_cart() {
        $cart = isset($_SESSION['midtrans_cart']) ? $_SESSION['midtrans_cart'] : array();
        wp_send_json_success(array(
            'cart' => $cart,
            'cart_count' => $this->get_cart_count(),
            'cart_total' => $this->get_cart_total()
        ));
    }
    
    public function create_cart_payment() {
        if (!wp_verify_nonce($_POST['nonce'], 'midtrans_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $customer_phone = sanitize_text_field($_POST['customer_phone']);
        
        if (empty($customer_name) || empty($customer_email) || empty($customer_phone)) {
            wp_send_json_error('Please fill all customer information');
        }
        
        $cart_total = $this->get_cart_total();
        if ($cart_total <= 0) {
            wp_send_json_error('Cart is empty');
        }
        
        // Generate unique order ID
        $order_id = 'MT-CART-' . date('YmdHis') . '-' . wp_rand(1000, 9999);
        
        // Save transaction to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'midtrans_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'amount' => $cart_total,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%f', '%s', '%s')
        );
        
        // Prepare Midtrans transaction data
        $transaction_data = array(
            'transaction_details' => array(
                'order_id' => $order_id,
                'gross_amount' => $cart_total
            ),
            'customer_details' => array(
                'first_name' => $customer_name,
                'email' => $customer_email,
                'phone' => $customer_phone
            ),
            'item_details' => $this->get_cart_items_for_midtrans()
        );
        
        // Get Snap token from Midtrans
        $snap_token = $this->get_snap_token($transaction_data);
        
        if ($snap_token) {
            // Clear cart after successful payment creation
            $_SESSION['midtrans_cart'] = array();
            
            wp_send_json_success(array(
                'snap_token' => $snap_token,
                'order_id' => $order_id
            ));
        } else {
            wp_send_json_error('Failed to create payment transaction. Please check your Midtrans configuration.');
        }
    }
    
    public function create_direct_payment() {
        if (!wp_verify_nonce($_POST['nonce'], 'midtrans_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Get data dari button
        $amount = floatval($_POST['amount']);
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $customer_phone = sanitize_text_field($_POST['customer_phone']);
        
        // Jika data customer kosong, coba ambil dari user logged in
        if (empty($customer_name) && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $customer_name = $current_user->display_name;
            $customer_email = $current_user->user_email;
        }
        
        // Validasi required fields
        if ($amount <= 0) {
            wp_send_json_error('Amount must be greater than 0');
        }
        
        if (empty($customer_email)) {
            wp_send_json_error('Email is required');
        }
        
        // Generate unique order ID
        $order_id = 'MT-DIRECT-' . date('YmdHis') . '-' . wp_rand(1000, 9999);
        
        // Save transaction to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'midtrans_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'customer_name' => $customer_name ?: 'Guest',
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone ?: '',
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%f', '%s', '%s')
        );
        
        // Prepare Midtrans transaction data
        $transaction_data = array(
            'transaction_details' => array(
                'order_id' => $order_id,
                'gross_amount' => $amount
            ),
            'customer_details' => array(
                'first_name' => $customer_name ?: 'Customer',
                'email' => $customer_email,
                'phone' => $customer_phone ?: ''
            )
        );
        
        // Get Snap token from Midtrans
        $snap_token = $this->get_snap_token($transaction_data);
        
        if ($snap_token) {
            wp_send_json_success(array(
                'snap_token' => $snap_token,
                'order_id' => $order_id
            ));
        } else {
            wp_send_json_error('Failed to create payment transaction. Please check your Midtrans configuration.');
        }
    }
    
    private function get_cart_items_for_midtrans() {
        $items = array();
        
        if (isset($_SESSION['midtrans_cart'])) {
            foreach ($_SESSION['midtrans_cart'] as $product_id => $item) {
                $items[] = array(
                    'id' => $product_id,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'name' => $item['name']
                );
            }
        }
        
        return $items;
    }
    
    private function get_cart_count() {
        $count = 0;
        if (isset($_SESSION['midtrans_cart'])) {
            foreach ($_SESSION['midtrans_cart'] as $item) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }
    
    private function get_cart_total() {
        $total = 0;
        if (isset($_SESSION['midtrans_cart'])) {
            foreach ($_SESSION['midtrans_cart'] as $item) {
                $total += $item['quantity'] * $item['price'];
            }
        }
        return $total;
    }
    
    // Shortcode implementations
    public function payment_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '0',
            'button_text' => 'Pay Now'
        ), $atts);
        
        ob_start();
        ?>
        <div class="midtrans-payment-form">
            <form id="midtrans-payment-form">
                <?php wp_nonce_field('midtrans_payment_nonce', 'midtrans_nonce'); ?>
                
                <div class="form-group">
                    <label for="customer_name">Full Name *</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_email">Email *</label>
                    <input type="email" id="customer_email" name="customer_email" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_phone">Phone Number *</label>
                    <input type="tel" id="customer_phone" name="customer_phone" required>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount (IDR) *</label>
                    <input type="number" id="amount" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" id="pay-button" class="pay-button">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
                
                <div id="payment-status" class="payment-status"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function payment_history_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="midtrans-payment-history"><p>Please log in to view your payment history.</p></div>';
        }
        
        global $wpdb;
        $current_user = wp_get_current_user();
        $table_name = $wpdb->prefix . 'midtrans_transactions';
        
        $transactions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE customer_email = %s ORDER BY created_at DESC",
            $current_user->user_email
        ));
        
        ob_start();
        ?>
        <div class="midtrans-payment-history">
            <h3>Your Payment History</h3>
            <?php if ($transactions): ?>
                <div class="transactions-list">
                    <?php foreach ($transactions as $transaction): ?>
                        <div class="transaction-item">
                            <div class="transaction-header">
                                <span class="order-id"><?php echo esc_html($transaction->order_id); ?></span>
                                <span class="status status-<?php echo esc_attr($transaction->status); ?>">
                                    <?php echo esc_html(ucfirst($transaction->status)); ?>
                                </span>
                            </div>
                            <div class="transaction-details">
                                <div class="amount">Rp <?php echo number_format($transaction->amount, 0, ',', '.'); ?></div>
                                <div class="date"><?php echo date('M j, Y H:i', strtotime($transaction->created_at)); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-transactions">No payment history found.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function pay_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '0',
            'button_text' => 'Bayar Sekarang',
            'customer_name' => '',
            'customer_email' => '',
            'customer_phone' => '',
            'class' => 'midtrans-pay-button',
            'description' => ''
        ), $atts);
        
        // Generate unique ID untuk button
        $button_id = 'midtrans-btn-' . uniqid();
        
        ob_start();
        ?>
        <div class="midtrans-pay-button-wrapper">
            <?php if (!empty($atts['description'])): ?>
                <p class="payment-description"><?php echo esc_html($atts['description']); ?></p>
            <?php endif; ?>
            
            <button 
                id="<?php echo esc_attr($button_id); ?>" 
                class="<?php echo esc_attr($atts['class']); ?>" 
                data-amount="<?php echo esc_attr($atts['amount']); ?>"
                data-customer-name="<?php echo esc_attr($atts['customer_name']); ?>"
                data-customer-email="<?php echo esc_attr($atts['customer_email']); ?>"
                data-customer-phone="<?php echo esc_attr($atts['customer_phone']); ?>"
            >
                <?php echo esc_html($atts['button_text']); ?>
            </button>
            
            <div id="<?php echo esc_attr($button_id); ?>-status" class="payment-status"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function product_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => '12',
            'columns' => '3',
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts);

        $args = array(
            'post_type' => 'midtrans_product',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => $atts['orderby'],
            'order' => $atts['order']
        );

        // Add category filter if specified
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }

        $products = new WP_Query($args);

        ob_start();
        ?>
        <div class="midtrans-product-list">
            <?php if ($products->have_posts()): ?>
                <div class="products-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                    <?php while ($products->have_posts()): $products->the_post(); ?>
                        <?php
                        $product_id = get_the_ID();
                        $price = get_post_meta($product_id, '_product_price', true);
                        $stock = get_post_meta($product_id, '_product_stock', true);
                        $sku = get_post_meta($product_id, '_product_sku', true);
                        
                        // Default values if meta is empty
                        $price = !empty($price) ? $price : 0;
                        $stock = !empty($stock) ? $stock : '';
                        ?>
                        <div class="product-card" data-product-id="<?php echo $product_id; ?>">
                            <div class="product-image">
                                <?php if (has_post_thumbnail()): ?>
                                    <img src="<?php echo get_the_post_thumbnail_url($product_id, 'medium'); ?>" 
                                         alt="<?php the_title_attribute(); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="no-image">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                        <span>No Image</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($stock !== '' && $stock <= 5 && $stock > 0): ?>
                                    <div class="product-badge low-stock">Low Stock</div>
                                <?php elseif ($stock === '0'): ?>
                                    <div class="product-badge out-of-stock">Out of Stock</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-title"><?php the_title(); ?></h3>
                                
                                <div class="product-price">Rp <?php echo number_format($price, 0, ',', '.'); ?></div>
                                
                                <?php if ($stock !== ''): ?>
                                    <div class="product-stock <?php echo $stock == '0' ? 'out-of-stock' : 'in-stock'; ?>">
                                        <?php echo $stock == '0' ? 'Out of Stock' : 'Stock: ' . $stock; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                </div>
                                
                                <div class="product-actions">
                                    <div class="quantity-selector">
                                        <button class="quantity-btn minus" type="button">−</button>
                                        <input type="number" class="quantity-input" 
                                               value="1" min="1" 
                                               <?php if ($stock !== '' && $stock > 0) echo 'max="' . $stock . '"'; ?>
                                               <?php if ($stock === '0') echo 'disabled'; ?>>
                                        <button class="quantity-btn plus" type="button">+</button>
                                    </div>
                                    
                                    <button class="add-to-cart-btn" 
                                            data-product-id="<?php echo $product_id; ?>"
                                            <?php if ($stock === '0') echo 'disabled'; ?>>
                                        <?php if ($stock === '0'): ?>
                                            Out of Stock
                                        <?php else: ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                                <line x1="3" y1="6" x2="21" y2="6"></line>
                                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                                            </svg>
                                            Add to Cart
                                        <?php endif; ?>
                                    </button>
                                    
                                    <a href="<?php the_permalink(); ?>" class="view-details-btn">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($products->max_num_pages > 1): ?>
                    <div class="products-pagination">
                        <?php
                        echo paginate_links(array(
                            'total' => $products->max_num_pages,
                            'current' => max(1, get_query_var('paged')),
                            'prev_text' => '&laquo; Previous',
                            'next_text' => 'Next &raquo;'
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-products-found">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <h3>No Products Found</h3>
                    <p>Sorry, no products are available at the moment.</p>
                    <?php if (current_user_can('manage_options')): ?>
                        <div class="admin-notice">
                            <strong>Admin Notice:</strong> 
                            <a href="<?php echo admin_url('post-new.php?post_type=midtrans_product'); ?>" target="_blank">
                                Add your first product
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function product_detail_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '0'
        ), $atts);
        
        $product_id = intval($atts['id']);
        
        if ($product_id <= 0) {
            global $post;
            if ($post && $post->post_type == 'midtrans_product') {
                $product_id = $post->ID;
            } else {
                return '<div class="midtrans-product-detail"><p>Product not found.</p></div>';
            }
        }
        
        $product = get_post($product_id);
        
        if (!$product || $product->post_type != 'midtrans_product') {
            return '<div class="midtrans-product-detail"><p>Product not found.</p></div>';
        }
        
        $price = get_post_meta($product_id, '_product_price', true);
        $stock = get_post_meta($product_id, '_product_stock', true);
        $sku = get_post_meta($product_id, '_product_sku', true);
        
        ob_start();
        ?>
        <div class="midtrans-product-detail">
            <div class="product-detail-container">
                <div class="product-images">
                    <?php if (has_post_thumbnail($product_id)): ?>
                        <div class="main-image">
                            <img src="<?php echo get_the_post_thumbnail_url($product_id, 'large'); ?>" alt="<?php echo esc_attr($product->post_title); ?>">
                        </div>
                    <?php else: ?>
                        <div class="no-image large">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <span>No Image Available</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h1 class="product-title"><?php echo $product->post_title; ?></h1>
                    
                    <?php if ($sku): ?>
                        <div class="product-sku">SKU: <?php echo esc_html($sku); ?></div>
                    <?php endif; ?>
                    
                    <div class="product-price">Rp <?php echo number_format($price, 0, ',', '.'); ?></div>
                    
                    <?php if ($stock !== ''): ?>
                        <div class="product-stock <?php echo $stock == '0' ? 'out-of-stock' : 'in-stock'; ?>">
                            <?php echo $stock == '0' ? 'Out of Stock' : 'Stock: ' . $stock; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-description">
                        <?php echo apply_filters('the_content', $product->post_content); ?>
                    </div>
                    
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-controls">
                                <button class="quantity-btn minus" type="button">−</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" <?php if ($stock !== '' && $stock > 0) echo 'max="' . $stock . '"'; ?>>
                                <button class="quantity-btn plus" type="button">+</button>
                            </div>
                        </div>
                        <button class="add-to-cart-btn large" data-product-id="<?php echo $product_id; ?>" <?php if ($stock === '0') echo 'disabled'; ?>>
                            <?php if ($stock === '0'): ?>
                                Out of Stock
                            <?php else: ?>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                    <line x1="3" y1="6" x2="21" y2="6"></line>
                                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                                </svg>
                                Add to Cart
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function shopping_cart_shortcode($atts) {
        ob_start();
        ?>
        <div class="midtrans-shopping-cart">
            <h2>Shopping Cart</h2>
            <div id="cart-contents">
                <!-- Cart contents will be loaded via AJAX -->
                <div class="cart-loading">Loading cart...</div>
            </div>
            
            <?php if (is_user_logged_in()): ?>
                <?php
                $current_user = wp_get_current_user();
                $customer_name = $current_user->display_name;
                $customer_email = $current_user->user_email;
                ?>
                <div class="customer-info" style="display: none;">
                    <h3>Customer Information</h3>
                    <form id="cart-checkout-form">
                        <?php wp_nonce_field('midtrans_payment_nonce', 'midtrans_nonce'); ?>
                        <div class="form-group">
                            <label for="cart_customer_name">Full Name *</label>
                            <input type="text" id="cart_customer_name" name="customer_name" value="<?php echo esc_attr($customer_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="cart_customer_email">Email *</label>
                            <input type="email" id="cart_customer_email" name="customer_email" value="<?php echo esc_attr($customer_email); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="cart_customer_phone">Phone Number *</label>
                            <input type="tel" id="cart_customer_phone" name="customer_phone" required>
                        </div>
                        <button type="submit" class="checkout-button">Proceed to Checkout</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="customer-info" style="display: none;">
                    <h3>Customer Information</h3>
                    <form id="cart-checkout-form">
                        <?php wp_nonce_field('midtrans_payment_nonce', 'midtrans_nonce'); ?>
                        <div class="form-group">
                            <label for="cart_customer_name">Full Name *</label>
                            <input type="text" id="cart_customer_name" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label for="cart_customer_email">Email *</label>
                            <input type="email" id="cart_customer_email" name="customer_email" required>
                        </div>
                        <div class="form-group">
                            <label for="cart_customer_phone">Phone Number *</label>
                            <input type="tel" id="cart_customer_phone" name="customer_phone" required>
                        </div>
                        <button type="submit" class="checkout-button">Proceed to Checkout</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div id="cart-payment-status" class="payment-status"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function floating_cart() {
        ?>
        <div id="floating-cart" class="floating-cart">
            <div class="cart-icon">
                <span class="cart-count">0</span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
            </div>
            <div class="cart-panel">
                <div class="cart-header">
                    <h3>Shopping Cart</h3>
                    <button class="close-cart">&times;</button>
                </div>
                <div class="cart-body">
                    <div class="cart-items">
                        <!-- Cart items will be loaded via AJAX -->
                    </div>
                    <div class="cart-total">
                        Total: Rp <span class="total-amount">0</span>
                    </div>
                </div>
                <div class="cart-footer">
                    <button class="view-cart-btn">View Full Cart</button>
                    <button class="checkout-btn" disabled>Checkout</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Payment processing methods
    public function create_payment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'midtrans_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        // Validate input
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_email = sanitize_email($_POST['customer_email']);
        $customer_phone = sanitize_text_field($_POST['customer_phone']);
        $amount = floatval($_POST['amount']);
        
        if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || $amount <= 0) {
            wp_send_json_error('Please fill all required fields with valid values');
        }
        
        // Generate unique order ID
        $order_id = 'MT-' . date('YmdHis') . '-' . wp_rand(1000, 9999);
        
        // Save transaction to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'midtrans_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%f', '%s', '%s')
        );
        
        // Prepare Midtrans transaction data
        $transaction_data = array(
            'transaction_details' => array(
                'order_id' => $order_id,
                'gross_amount' => $amount
            ),
            'customer_details' => array(
                'first_name' => $customer_name,
                'email' => $customer_email,
                'phone' => $customer_phone
            )
        );
        
        // Get Snap token from Midtrans
        $snap_token = $this->get_snap_token($transaction_data);
        
        if ($snap_token) {
            wp_send_json_success(array(
                'snap_token' => $snap_token,
                'order_id' => $order_id
            ));
        } else {
            wp_send_json_error('Failed to create payment transaction. Please check your Midtrans configuration.');
        }
    }
    
    private function get_snap_token($transaction_data) {
        $environment = isset($this->settings['environment']) ? $this->settings['environment'] : 'sandbox';
        $server_key = isset($this->settings['server_key']) ? $this->settings['server_key'] : '';
        
        if (empty($server_key)) {
            error_log('Midtrans Error: Server key is empty');
            return false;
        }
        
        $url = $environment === 'production' 
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($server_key . ':')
            ),
            'body' => json_encode($transaction_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Midtrans API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['token'])) {
            return $data['token'];
        } else {
            error_log('Midtrans Error: No token in response. Response: ' . $body);
            return false;
        }
    }
    
    public function check_payment_status() {
        $order_id = sanitize_text_field($_POST['order_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'midtrans_transactions';
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM $table_name WHERE order_id = %s",
            $order_id
        ));
        
        if ($transaction) {
            wp_send_json_success(array('status' => $transaction->status));
        } else {
            wp_send_json_error('Transaction not found');
        }
    }
    
    public function handle_webhook() {
        // Get the raw POST data
        $raw_post = file_get_contents('php://input');
        $response = json_decode($raw_post, true);
        
        if (!$response || !isset($response['order_id'])) {
            status_header(400);
            exit('Invalid webhook data');
        }
        
        $order_id = $response['order_id'];
        $transaction_status = $response['transaction_status'];
        $fraud_status = isset($response['fraud_status']) ? $response['fraud_status'] : '';
        
        // Update transaction status in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'midtrans_transactions';
        
        $status = 'pending';
        
        if ($transaction_status == 'capture') {
            $status = ($fraud_status == 'accept') ? 'success' : 'failed';
        } elseif ($transaction_status == 'settlement') {
            $status = 'success';
        } elseif (in_array($transaction_status, array('deny', 'expire', 'cancel'))) {
            $status = 'failed';
        }
        
        $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'transaction_id' => isset($response['transaction_id']) ? $response['transaction_id'] : '',
                'payment_method' => isset($response['payment_type']) ? $response['payment_type'] : '',
                'payment_data' => $raw_post,
                'updated_at' => current_time('mysql')
            ),
            array('order_id' => $order_id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%s')
        );
        
        // You can add additional actions here, like sending emails, etc.
        
        status_header(200);
        echo 'OK';
        exit;
    }
}

// Initialize the plugin
new MidtransStandalonePayment();
?>