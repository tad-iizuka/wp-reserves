<?php
/*
  Plugin Name: WordPress Reservation System
  Plugin URI: http://ragob.com/wpreserves
  Description: WordPress equipment/material reservation system
  Version: 2.3.11
  Author: Moises Heberle
  Author URI: http://codecanyon.net/user/moiseh
 */

/* TODO:
 * Reject reserve with Reason (new icon to reject , with JS popup to user inform the reject reason -- send email!)
 * Make user reserves widget, complementing the My Reserves page
 * Tags to Admin confirm reserves directly by email, eg.: [authorize_link], [reject_link] -- put it on tags helper
 * Log event when user cancel their reserve
 */

// constants
define('WPR_VERSION', '2.3.11');
define('WPR_DEMO_MSG', __("You can't execute this operation in demo mode"));
define('WPR_STATUS_WAITING', 'waiting');
define('WPR_STATUS_CANCELLED', 'cancelled');
define('WPR_STATUS_AUTHORIZED', 'authorized');
define('WPR_STATUS_REJECTED', 'rejected');
define('WPR_STATUS_FINISHED', 'finished');

/* START OF HOOKS REGISTERS */

// general startup/bootstrap hooks
add_action('init', 'wpr_init');
add_action('plugins_loaded', 'wpr_update_check');
register_activation_hook(__FILE__, 'wpr_install_cmds');
register_deactivation_hook(__FILE__, 'wpr_deactivation');

// admin menus
add_action('admin_menu', 'wpr_menu_register'); // admin main menu
add_action('admin_menu', 'wpr_config_menu', 20); // settings submenu

// javascript, stylesheet
add_action('wp_enqueue_scripts', 'wpr_scripts', 50); // load scripts after all others

// flash messages
add_filter('the_content', 'wpr_flash_display');
add_action('admin_notices', 'wpr_flash_display_admin');

// log task
add_action('wpr_log_task', 'wpr_clear_log_task');

// post type: Item
add_action('save_post', 'wpr_item_meta_save', 1, 2); // save the custom fields
add_filter('manage_wpr_item_posts_columns', 'wpr_item_cols_head'); // admin columns
add_action('manage_wpr_item_posts_custom_column', 'wpr_item_col_values', 10, 2); // admin columns

// post type: Reserve
add_filter('manage_wpr_reserve_posts_columns', 'wpr_reserve_cols_head'); // admin columns
add_action('manage_wpr_reserve_posts_custom_column', 'wpr_reserve_col_values', 10, 2); // admin columns

// Reserves page (user)
add_shortcode('reserves_page', 'wpr_reserves_page'); // shortcode [reserves_page] for display on user reserves
//add_action('widgets_init', 'wpr_register_widgets'); // user widget

/* END OF HOOKS REGISTERS */

function wpr_init() {
//wpr_set_default_roles();
    
    wpr_status_init();
    wpr_register_types();
    
    // necessary for flash messages
    if ( !session_id() )
    {
        session_start();
    }

    // prevent to demo user not edit their profile
    if (wpr_is_demo() && defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE) {
        // redirect to reserves menu
        wp_redirect(site_url('wp-admin/edit.php?post_type=wpr_reserve'));
    }

    // action "Add reserve" (reserves page)
    if ( isset($_GET['add-reserve']) ) {
        wpr_reserve_add($_GET['add-reserve']);
    }
    
    // change reserve status (on admin screen)
    if ( isset($_GET['reserve-chstatus']) && isset($_GET['status']) ) {
        wpr_reserve_changestatus($_GET['reserve-chstatus'], $_GET['status']);
    }
    
    // user reserve cancellation
    if ( isset($_GET['cancel-reserve']) ) {
        wpr_reserve_cancel($_GET['cancel-reserve']);
    }
}

function wpr_is_demo() {
    if ( !defined('WPR_IS_DEMO') ) {
        return false;
    }
    
    $user = wp_get_current_user();

    return ( !$user || ( $user->user_login != 'admin' ) );
}

function wpr_alert_demo() {
    return "<script>alert('" . str_replace("'", '', WPR_DEMO_MSG) . "');</script>";
}

function wpr_install_cmds() {
    wpr_set_default_roles();
    wpr_set_default_options();
    wpr_page_create();
    wpr_create_log_table();

    // wp task for clear logs
    wp_schedule_event(time(), 'daily', 'wpr_log_task');
}

function wpr_update_check() {
    if ( get_site_option( 'wpr_version' ) != WPR_VERSION )
    {
        wpr_install_cmds();
        update_site_option('wpr_version', WPR_VERSION);
    }
}

function wpr_set_default_roles() {
    // default roles
    $role = get_role('administrator');
    if ( $role ) {
        $role->add_cap('manage_reserves');
    }
    
    // reserves manager role
    $role = get_role('reserves_manager');
    if ( !$role ) {
        $role = add_role('reserves_manager', __( 'Reserves manager'));
    }

    $role->add_cap('manage_reserves');
    $role->add_cap('read');
    $role->add_cap('edit_posts'); // necessary to Add new reserve/item permission
    $role->add_cap('manage_categories');
    
    $role->remove_cap('create_posts');
    $role->remove_cap('publish_posts');
    $role->remove_cap('read_private_posts');
    $role->remove_cap('edit_posts');
    $role->remove_cap('edit_others_posts');
    $role->remove_cap('edit_published_posts');
    $role->remove_cap('edit_private_posts');
    $role->remove_cap('edit_others_posts');
    $role->remove_cap('delete_posts');
    $role->remove_cap('delete_private_posts');
    $role->remove_cap('delete_published_posts');
    $role->remove_cap('delete_others_posts');
    $role->remove_cap('import');
    $role->remove_cap('export');
    $role->remove_cap('list_users');
    $role->remove_cap('upload_files');
    
//    var_dump($role->capabilities);
}

function wpr_menu_register() {
    // main menu
    add_menu_page( __('WP Reserves'), __('WP Reserves'), 'manage_reserves', 'wpr_menu', 'wpr_render_dashboard', 'dashicons-album', 1001 );
    
    // remove some menus in demonstration mode
    if (wpr_is_demo()) {
        remove_menu_page( 'edit-tags.php?taxonomy=category' );
        remove_menu_page( 'edit-tags.php?taxonomy=post_tag' );
        remove_menu_page( 'profile.php' );
    }
}

function wpr_render_dashboard() {
    echo 'main menu';
}

function wpr_scripts() {
    wp_enqueue_style('wpr-style', plugin_dir_url(__FILE__) . '/assets/wpreserves.css');
}

function wpr_deactivation() {
    remove_role('reserves_manager');

    // clear log task
    wp_clear_scheduled_hook('wpr_log_task');
}

function wpr_create_log_table() {
    global $wpdb;
    
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpr_log` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `event` text NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
    ";
            
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_table_query );
}

function wpr_clear_log_task() {
    global $wpdb;

    // delete logs older than N days
    $days = (int) get_option('wpr_log_days');

    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wpr_log WHERE datediff(now(), date) > {$days}"));
}

function wpr_log($event) {
    global $wpdb;

    $wpdb->insert("{$wpdb->prefix}wpr_log", array('event' => $event));
}

function wpr_reports() {
    // fazer relatorios em abas, semelhante ao woocommerce
    // fazer queries diretas ou com WP_Query
}

// deprecated: not work
function wpr_slug_exists($post_name) {
    global $wpdb;

    return ($wpdb->get_row("SELECT post_name FROM wp_posts WHERE post_name = '" . $post_name . "'", 'ARRAY_A'));
}

function wpr_mail_send($to, $option_name, $item_id) {
    // send mail
    $option = get_option($option_name);

    if ( $option['enabled'] ) {
        $post = get_post($item_id);

        // replace tags
        $tags = array(
            '[item]' => $post->post_title,
            '[manage_link]' => site_url('wp-admin/edit.php?post_type=wpr_reserve'),
        );
        $title = strtr($option['title'], $tags);
        $message = strtr($option['message'], $tags);

        wp_mail($to, $title, $message);

        // log email
        if ( get_option('wpr_log_mail') == 'yes' ) {
            wpr_log(sprintf(__('E-mail was sent to: `%s`, with title `%s`'), $to, $title));
        }
    }
}

function wpr_flash_display_admin() {
    if (isset($_SESSION['flash_msg'])){
        $msg_html = wpr_render('flash-admin.php', array('msg_type' => $_SESSION['flash_msg']['type'], 'msg_text' => $_SESSION['flash_msg']['text']));
    }

    if (isset($msg_html)){
        unset($_SESSION['flash_msg']);
        echo $msg_html;
    }
}

function wpr_flash_display($content) {
    if (isset($_SESSION['flash_msg'])){
        $msg_html = wpr_render('flash-site.php', array('msg_type' => $_SESSION['flash_msg']['type'], 'msg_text' => $_SESSION['flash_msg']['text']));
    }

    if (isset($msg_html)){
        unset($_SESSION['flash_msg']);
        $content = $msg_html . $content;
    }

    return $content;
}

function wpr_flash($type, $msg){
    $_SESSION['flash_msg']['type'] = $type;
    $_SESSION['flash_msg']['text'] = $msg;

    return $_SESSION['flash_msg'];
}

function wpr_render($template, $vars = array()) {
    $file = __DIR__ . '/views/' . $template;
    extract($vars);

    ob_start();
    include $file;
    return ob_get_clean();
}

function wpr_status_list() {
    return array(
        WPR_STATUS_WAITING => __('Waiting approval'),
        WPR_STATUS_AUTHORIZED => __('Authorized'),
        WPR_STATUS_REJECTED => __('Rejected'),
        WPR_STATUS_FINISHED => __('Finished'),
        WPR_STATUS_CANCELLED => __('Cancelled'),
    );
}

function wpr_status_get($status) {
    $options = wpr_status_list();
    return $options[$status];
}

function wpr_status_init() {
    // register statuses (used for Reserves admin post type)
    foreach ( wpr_status_list() as $status => $label ) {
        $label_count = $label . ' <span class="count">(%s)</span>';

        register_post_status($status, array(
            'label'                     => $label,
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop($label_count, $label_count),
        ));
    }
}

function wpr_default_caps() {
    $perm = wpr_is_demo() ? false : 'manage_reserves';
    
    return array(
        'create_posts' => $perm,
        'publish_posts' => $perm,
        'edit_posts' => 'manage_reserves',
//        'edit_others_posts' => 'manage_reserves',
//        'edit_published_posts' => 'manage_reserves',
//        'edit_private_posts' => 'manage_reserves',
        'delete_posts' => $perm,
        'delete_others_posts' => $perm,
        'delete_private_posts' => $perm,
        'delete_published_posts' => $perm,
//        'read_private_posts' => 'manage_reserves',
        'edit_post' => $perm,
        'delete_post' => $perm,
        'read_post' => 'manage_reserves',
        'read' => 'manage_reserves',
    );
}

function wpr_register_types() {
    // create Reserves post type
    // get_post_type_labels($post_type_object);
    register_post_type('wpr_reserve', array(
        'labels' => array(
          'name' => __( 'Reserves' ),
          'singular_name' => __( 'Reserve' ),
          'add_new' => __('Add new reserve'),
          'add_new_item' => __('Add new reserve'),
          'edit_item' => __('Edit reserve'),
          'new_item' => false,
          'view_item' => __('View reserve'),
        ),
        'show_ui' => true,
        'public' => true,
        'has_archive' => true,
        'show_in_menu' => 'wpr_menu',
        'supports' => array( 'title', 'editor', 'thumbnail' ),
//        'capability_type' => 'manage_reserves',
        'capabilities' => wpr_default_caps(),
        'exclude_from_search' => true,
    ));

//var_dump($GLOBALS['wp_post_types']['wpr_reserve']);
    
    // create Item post type
    // get_post_type_labels($post_type_object);
    register_post_type('wpr_item', array(
        'labels' => array(
          'name' => __( 'Items' ),
          'singular_name' => __( 'Item' ),
          'add_new' => __('Add new item'),
          'add_new_item' => __('Add new item'),
          'edit_item' => __('Edit item'),
          'new_item' => __('Add new item'),
          'view_item' => __('View item'),
        ),
        'public' => true,
//        'show_ui' => true,
        'has_archive' => true,
        'show_in_menu' => 'wpr_menu',
        'supports' => array( 'title', 'editor', 'thumbnail', 'wpr_categories' ),
        'taxonomies' => array('wpr_categories'),
//        'capability_type' => 'manage_reserves',
        'capabilities' => wpr_default_caps(),
        'rewrite' => array('slug' => 'res-item'),
        'register_meta_box_cb' => 'wpr_item_meta_register',
//            'menu_icon'   => 'dashicons-album',
    ));
    
//    var_dump(get_post_type_object('wpr_item'));
    
    register_taxonomy('wpr_categories', 'wpr_item', array(
        // Hierarchical taxonomy (like categories)
        'hierarchical' => false,
        'show_ui' => true,
        'public' => true,
        'show_admin_column' => true,
        // This array of options controls the labels displayed in the WordPress Admin UI
        'labels' => array(
            'name' => __( 'Item category'),
            'singular_name' => __( 'Item category'),
            'all_items' => __( 'All categories' ),
            'edit_item' => __( 'Edit category' ),
            'update_item' => __( 'Update category' ),
            'add_new_item' => __( 'Add new category' ),
            'new_item_name' => __( 'New category' ),
            'menu_name' => __( 'Item categories' ),
        ),
    ));
}

function wpr_item_meta_register() {
    // meta box "Item detail"
    add_meta_box('wpr_item_details', 'Item detail', 'wpr_item_meta_render', null, 'side', 'default');
}

function wpr_item_meta_render() {
    global $post;

    // Get the location data if its already been entered
    $quantity = get_post_meta($post->ID, '_quantity', true) ? get_post_meta($post->ID, '_quantity', true) : 1;
    $type = get_post_meta($post->ID, '_type', true);
    
    echo wpr_render('meta-item.php', compact('quantity', 'type'));
}

function wpr_item_types($labelEmpty = null) {
    $types = array();

    return $types;
}

function wpr_item_meta_save($post_id, $post) {
    if ( ! wp_verify_nonce( $_POST['nonce_item'], plugin_basename('wpreserves') ) ) {
        return $post_id;
    }
    
    // Is the user allowed to edit the post or page
    if ( !current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }

    // Don't store custom data twice
    if ( $post->post_type == 'revision' ) {
        return;
    }

    update_post_meta($post_id, '_quantity', $_POST['_quantity']);
    update_post_meta($post_id, '_type', $_POST['_type']);
}

function wpr_item_cols_head($defaults) {
    $defaults['_reserves'] = __('Allotted reserves');

    return $defaults;
}

function wpr_item_col_values($column_name, $post_id) {
    switch ($column_name) {
        case '_reserves':
            $qty = get_post_meta($post_id, '_quantity', true);
            $using = ( $qty - wpr_free_slots($post_id) );
            $color = ( $using >= $qty ) ? 'red' :  ( $using <= 0 ? '' : 'green' );
            echo "<span style=\"color: {$color}; font-weight: bold;\">{$using} / {$qty}</span>";
            break;
    }
}

function wpr_reserve_cols_head($defaults) {
    $defaults['_user'] = __('Requester');
    $defaults['_status'] = __('Status');
    $defaults['_actions'] = __('Actions');

    return $defaults;
}

function wpr_reserve_col_values($column_name, $post_id) {
    $post = get_post($post_id);
    
    switch ($column_name) {
        case '_actions':
            $status = get_post_status($post_id);
            echo wpr_render('admin-reserve-action.php', compact('status', 'post_id'));
            break;
        
        case '_status':
            $statuses = wpr_status_list();
            echo $statuses[ $post->post_status ] ? $statuses[ $post->post_status ] : $post->post_status;
            break;
        
        case '_user':
            $user = get_userdata($post->post_author);
            echo sprintf('<a href="%s">%s</a>', get_edit_user_link($post->post_author), $user->display_name);
            break;
    }
}

function wpr_reserve_changestatus($post_id, $status) {
    if ( ! current_user_can('manage_reserves') ) {
        wpr_flash('warning', __('Permission denied.'));
    }
    else if (wpr_is_demo()) {
        wpr_flash('warning', WPR_DEMO_MSG);
    }
    else {
        $post = get_post($post_id);
        $user = get_userdata($post->post_author);

        switch ($status) {
            case 'authorize':
                $sys_status = WPR_STATUS_AUTHORIZED;
                wp_update_post(array('ID' => $post_id, 'post_status' => $sys_status));
                wpr_mail_send($user->user_email, 'wpr_message_authorized', $post_id);
                wpr_flash('success', __('Reserve authorized.'));
                break;

            case 'reject':
                $sys_status = WPR_STATUS_REJECTED;
                wp_update_post(array('ID' => $post_id, 'post_status' => $sys_status));
                wpr_mail_send($user->user_email, 'wpr_message_rejected', $post_id);
                wpr_flash('success', __('Reserve rejected.'));
                break;

            case 'finish':
                $sys_status = WPR_STATUS_FINISHED;
                wp_update_post(array('ID' => $post_id, 'post_status' => $sys_status));
                wpr_flash('success', __('Reserve finished.'));
                break;
        }

        // log event
        if ( get_option('wpr_log_reserves') == 'yes' ) {
            $statuses = wpr_status_list();
            wpr_log(sprintf(__('Reserve `%s` of user `%s` changed to: `%s`'), $post->post_title, $user->display_name, $statuses[$sys_status]));
        }
    }

    // redirect back
    wp_safe_redirect(remove_query_arg(array('reserve-chstatus', 'status')) );
    exit;
}

function wpr_checkbox($name) {
    ?>
    <input type="checkbox" name="<?= $name ?>" id="<?= $name ?>"
        <?php if (get_option($name) == 'yes'): ?>checked<?php endif ?>
        >
    <?php
}

function wpr_set_default_options($is_reset = false) {
    // default configurations
    $defaults = array(
        'wpr_log_mail' => 'yes',
        'wpr_log_reserves' => 'yes',
        'wpr_log_days' => 60,

        'wpr_show_reserves_filters' => 'yes',
        'wpr_show_my_reserves' => 'yes',
        'wpr_allow_user_cancel' => 'yes',
        
        'wpr_per_page' => '20',
        'wpr_admin_mail' => 'admin@example.com',

        'wpr_message_rejected' => array(
            'enabled' => true,
            'title' => 'Reserve rejected: [item]',
            'message' => 'Your reserve of item `[item]` has been rejected by administrator',
         ),

        'wpr_message_authorized' => array(
            'enabled' => true,
            'title' => 'Reserve authorized: [item]',
            'message' => 'Your reserve of item `[item]` has been authorized by administrator. Now you are allowed to get the item',
         ),

        'wpr_message_waiting' => array(
            'enabled' => true,
            'title' => 'Reserve added: [item]',
            'message' => 'Your reserve of item `[item]` is added and waiting to be authorized by administrator',
         ),
        
        'wpr_message_admin_new' => array(
            'enabled' => true,
            'title' => 'Reserve request alert: [item]',
            'message' => 'New reserve request: [item]. Click on the following link to manage the reserves: [manage_link]',
         ),
    );

    // set default settings
    foreach ( $defaults as $key => $val ) {
        if (get_option($key) === false || $is_reset) {
            update_option($key, $val);
        }
    }
}

function wpr_config_menu() {
    if ( !wpr_is_demo() ) {
        add_submenu_page( 'wpr_menu', __('Item categories'), __('Item categories'), 'manage_reserves', 'edit-tags.php?taxonomy=wpr_categories');
    }
    
    add_submenu_page( 'wpr_menu', __('Settings'),  __('Settings') , 'manage_reserves', 'wpr-config', 'wpr_config_render');

    add_submenu_page( 'wpr_menu', __('System logs'),  __('System logs') , 'manage_reserves', 'wpr-syslog', 'wpr_syslog_render');
}

function wpr_config_render() {
    global $wpdb;
    // based on class-wc-admin-settings.php

    $tabs = array(
        'general' => __('General'),
        'messages' => __('E-mail messages'),
        'page' => __('Reserves page'),
    );

    $tab = isset( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : current(array_keys($tabs));

    // Save settings if data has been posted
    if ( ! empty( $_POST ) ) {
        if ( wpr_is_demo() ) {
            echo wpr_alert_demo();
        }
        else if ( $_POST['save'] == __( 'Save changes' ) ) {
            wpr_config_save($tab);
        } else if ( $_POST['save'] == __( 'Reset all settings' ) ) {
            wpr_set_default_options(true);
        }
    }
    
    echo wpr_render('config.php', compact('tabs', 'tab'));
}

function wpr_config_save($tab) {
    switch ( $tab ) {
        case 'general':
            update_option('wpr_admin_mail', $_POST['wpr_admin_mail']);
            update_option('wpr_log_days', $_POST['wpr_log_days'] ? $_POST['wpr_log_days'] : 10);
            wpr_save_bool('wpr_log_mail');
            wpr_save_bool('wpr_log_reserves');
            break;
        
        case 'messages':
            // loop messages
            foreach ( $_POST['messages'] as $key => $msg ) {
                $msg['enabled'] = isset($msg['enabled']) ? 'yes' : 'no';
                update_option($key, $msg);
            }
            break;
            
        case 'page':
            update_option('wpr_per_page', $_POST['wpr_per_page']);
            wpr_save_bool('wpr_show_reserves_filters');
            wpr_save_bool('wpr_show_my_reserves');
            wpr_save_bool('wpr_allow_user_cancel');
            break;
    }
}

function wpr_save_bool($name) {
    update_option($name, isset($_POST[$name]) ? 'yes' : 'no');
}

function wpr_messages_box($name, $label, $help = null) {
    $opt = get_option($name);

    if ( !$opt ) {
        $opt['title'] = '';
        $opt['message'] = '';
        $opt['enabled'] = 'yes';
    }

    echo wpr_render('config-messages-box.php', compact('opt', 'name', 'label', 'help'));
}

function wpr_syslog_render() {
    global $wpdb;

    // erase ALL logs    
    if ( isset($_POST) && isset($_POST['btn_clear']) ) {
        if ( wpr_is_demo() ) {
            echo wpr_alert_demo();
        } else {
            // delete all records
            $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}wpr_log`");
        }
    }
    
    // load required class
    if ( ! class_exists( 'WP_List_Table' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }

    // create the admin table for view logs
    class WPR_Log_Table extends WP_List_Table
    {
        function no_items() {
            return _e( 'No logs found.' );
        }

        function column_default( $item, $column_name ) {
            switch( $column_name ) { 
                default:
                    return $item[$column_name];
            }
        }

        function get_sortable_columns() {
            $sortable_columns = array(
                //'date' => array('date',false),
                //'event'  => array('event',false),
            );
            return $sortable_columns;
        }

        function get_columns(){
            $columns = array(
                'date' => __( 'Date' ),
                'event' => __( 'Event' ),
            );

            return $columns;
        }

        function prepare_items() {
            global $wpdb;

            $columns  = $this->get_columns();
            $hidden   = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array( $columns, $hidden, $sortable );

            $per_page = 50;
            $current_page = $this->get_pagenum();
            $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpr_log" );
            $offset = ( $current_page - 1 ) * $per_page;

            $this->items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpr_log ORDER BY id DESC LIMIT {$offset}, {$per_page}", 'ARRAY_A' );

            $this->set_pagination_args( array('total_items' => $total_items, 'per_page' => $per_page) );
        }
    }
    
    // instantiate the table and prepare data
    $table = new WPR_Log_Table();
    $table->prepare_items();

    echo wpr_render('admin-table-log.php', compact('table'));
}

function wpr_page_create() {
    // create reserves page if not exists already
    if (!get_option('wpr_page_id')){
        $post_id = wp_insert_post(array(
            'post_type' => 'page',
            'post_title' => __('Reserve items'),
            'post_content' => '[reserves_page]',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_slug' => 'reserves',
            'comment_status' => 'closed',
        ));
        
        update_option('wpr_page_id', $post_id);
    }
}

function wpr_reserves_page() {
    // user reserves page
    if ( isset($_GET['my-reserves'])) {
        return wpr_user_reserves();
    }
    
    $categories = get_terms('wpr_categories');
    $cat_filter = isset($_GET['_cat']) ? $_GET['_cat'] : null;
    $tax_query = array();

    // add the category filter
    if ( $cat_filter ) {
        $tax_query[] = array('taxonomy' => 'wpr_categories', 'field' => 'name', 'terms' => $cat_filter); // eg.: equipment
    }

    $paged = isset($_GET['paged']) ? filter_input(INPUT_GET, 'paged', FILTER_VALIDATE_INT): 1;
    
    // query
    $query = new WP_Query(array(
        'posts_per_page' => get_option('wpr_per_page'), 
        'paged' => $paged, 
        'post_type' => 'wpr_item',
        'tax_query' => $tax_query,
    ));

    // query pager
    $paginator = paginate_links(array(
        'base' => str_replace( 99999999, '%#%', esc_url( get_pagenum_link( 99999999 ) ) ),
        'format' => '?paged=%#%',
        'current' => $paged,
        'total' => $query->max_num_pages
    ));

    $show_filters = get_option('wpr_show_reserves_filters') == 'yes';
    $show_my_reserves = get_option('wpr_show_my_reserves') == 'yes' && is_user_logged_in();
    
    return wpr_render('page-reserves-list.php', compact('paginator', 'query', 'show_filters', 'show_my_reserves', 'categories', 'cat_filter'));
}

function wpr_user_reserves() {
    // query
    $query = new WP_Query(array(
        'posts_per_page' => 200,
        'post_type' => 'wpr_reserve',
        'author' => get_current_user_id(),
        // order by date DESC
    ));
    
    $allow_cancel = get_option('wpr_allow_user_cancel') == 'yes';
    
    return wpr_render('page-reserves-user.php', compact('query', 'allow_cancel'));
}

function wpr_reserve_add($post_id_item) {
    // check logged user
    if ( !is_user_logged_in() ) {
        wp_safe_redirect(wp_login_url($_SERVER['REQUEST_URI']));
        exit;
    }
    // check if user already has this reserve in waiting status
    else if ( wpr_is_reserve_waiting($post_id_item) ) {
        wpr_flash('warning', __('This item is already on your reserves waiting list'));
    }
    else if ( wpr_free_slots($post_id_item) <= 0 ) {
        wpr_flash('warning', __('This item has no available reserve'));
    }
    else if ( wpr_is_demo() ) {
        wpr_flash('warning', WPR_DEMO_MSG);
    }
    else {
        $user = wp_get_current_user();
        $item = get_post($post_id_item);

        // inser user reserve
        $new_post_id = wp_insert_post(array(
            'post_type' => 'wpr_reserve',
            'post_title' => $item->post_title,
            'post_content' => '',
            'post_status' => WPR_STATUS_WAITING,
            'post_author'=> $user->ID,
        ));
        update_post_meta($new_post_id, 'reserve_item', $post_id_item); // ID of item

        // log reserve activity
        if ( get_option('wpr_log_reserves') == 'yes' ) {
            wpr_log(sprintf(__('Reserve request added: `%s` by `%s`'), $item->post_title, $user->display_name));
        }

        // email messages
        wpr_mail_send($user->user_email, 'wpr_message_waiting', $post_id_item); // user mail
        wpr_mail_send(get_option('wpr_admin_mail'), 'wpr_message_admin_new', $post_id_item); // reserves manager mail
        
        // ok message
        wpr_flash('success', __('Reserve added. The item is now awaiting administrator approval.'));
    }

    // redirect back
    wp_safe_redirect(remove_query_arg('add-reserve'));
    exit;
}

function wpr_is_reserve_waiting($post_id_item, $user_id = null) {
    if ( !$user_id ) {
        $user_id = get_current_user_id();
    }
    
    // check if user already reserved this item
    $query = new WP_Query(array(
        'author' => $user_id,
        'post_type' => 'wpr_reserve',
        'post_status' => WPR_STATUS_WAITING,
        'meta_key'  => 'reserve_item',
        'meta_value' => $post_id_item,
    ));
    
    return $query->have_posts();
}

function wpr_reserve_link($reserve_post_id) {
    return get_permalink(get_post_meta($reserve_post_id, 'reserve_item', true));
}

function wpr_reserve_cancel($post_id) {
    $post = get_post($post_id);
    
    // check if is allowed
    if ( ( !$post ) || ( $post->post_author != get_current_user_id() ) || ( $post->post_status != WPR_STATUS_WAITING ) ) {
        wpr_flash('warning', __('Permission denied.'));
    }
    else if (wpr_is_demo()) {
        wpr_flash('warning', WPR_DEMO_MSG);
    }
    else {
        wp_update_post(array('ID' => $post_id, 'post_status' => WPR_STATUS_CANCELLED));
        wpr_flash('success', __('Reserve cancelled.'));

        // wpr_log() 
    }

    // redirect back
    wp_safe_redirect(remove_query_arg('cancel-reserve') );
    exit;
}

function wpr_free_slots($post_id_item) {
    $query = new WP_Query(array(
        'post_type' => 'wpr_reserve',
        'post_status' => array(WPR_STATUS_AUTHORIZED, WPR_STATUS_WAITING),
        'meta_key'  => 'reserve_item',
        'meta_value' => $post_id_item,
    ));

    $quantity = get_post_meta($post_id_item, '_quantity', true);
    
    return $quantity - $query->post_count;
}

function wpr_register_widgets() {
    register_widget( 'WPR_Widget_User' );
}

/**
 * Based on WC_Widget
 */
class WPR_Widget_User extends WP_Widget {
    function __construct() {
        $this->settings = array(
            'title'  => array(
                'type'  => 'text',
                'std'   => __( 'Title' ),
                'label' => __( 'Title' )
            ),
        );

        $widget_ops = array(
            'description' => __('Display user reservation info'),
        );
        
        parent::__construct(false, __( 'WPReserves User Widget'), $widget_ops);
    }

    function widget( $args, $instance ) {
        // Widget output
        echo 'bla: ' . $instance['title'];
    }

    function update( $new_instance, $old_instance ) {
        // Save widget options
    }

    function form( $instance ) {
        // Output admin widget options form
    }
}
