<?php

/**
 * Plugin Name:       MemberPress Pause Subscription
 * Plugin URI:        https://memberfix.rocks
 * Description:       It gives your users the posiblity of pausing their membership once a month and then to get their access back
 * Version:           1.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Sorin Marta @ MemberFix
 * Author URI:        https://sorinmarta.com
 */

define('MFSS_SLUG', 'mf-memberpress-suspend-schedule');
define('MFSS_APP', WP_PLUGIN_DIR . '/' . MFSS_SLUG . '/app');
define('MFSS_VIEWS', MFSS_APP . '/views');

require MFSS_APP . '/lib/mfss-controller.php';
require MFSS_APP . '/lib/mfss-database.php';
require MFSS_APP . '/controllers/mfss-pause-controller.php';
require MFSS_APP . '/controllers/mfss-settings-controller.php';

class MF_Mepr_Suspend_Schedule{
    use MFSS_Controller, MFSS_Database;

     public function __construct(){
        // Activate
        add_action('activate_mf-memberpress-suspend-schedule/mf-memberpress-suspend-schedule.php', array($this, 'activate'));

        // Account Handlers
        add_action('mepr_account_nav', array($this, 'add_new_mepr_tab'));
        add_action('mepr_account_nav_content', array($this, 'mepr_tab_content'));
        
        // Admin Page
        add_action('admin_menu', array($this, 'menu'));
     }

     public function activate(){
        if(!is_plugin_active('memberpress/memberpress.php')){
            wp_die('MemberPress is not active. MemberPress is required for this add-on to work.');
        }

        if(!get_option('mfss-pause-limit')){
            add_option('mfss-pause-limit');
        }

        if(!get_option('mfss-once-a-month')){
            add_option('mfss-once-a-month', 'false');
        }

        if(!get_option('mfss-pause-email-subject')){
            add_option('mfss-pause-email-subject', 'false');
        }

        if(!get_option('mfss-pause-email-content')){
            add_option('mfss-pause-email-content', 'false');
        }

        if(!get_option('mfss-resume-email-subject')){
            add_option('mfss-resume-email-subject', 'false');
        }

        if(!get_option('mfss-resume-email-content')){
            add_option('mfss-resume-email-content', 'false');
        }

        $fields = "
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        pause_start varchar(255) NOT NULL,
        pause_end varchar(255) NOT NULL,
        PRIMARY KEY  (id)
        ";

        $this->create_table(self::generate_table_name(MFSS_Pause_Controller::$table), $fields);
     }

     public function menu(){
         add_menu_page('Pause Scheduler', 'Pause Scheduler', 'manage_options', 'subscription-pause' , array($this, 'admin_page'));
         add_submenu_page('subscription-pause', 'Settings', 'Settings', 'manage_options', 'mepr-pause-scheduler', array($this, 'admin_subpage'));
         add_submenu_page('subscription-pause', 'Set Pause', 'Set Pause', 'manage_options', 'mepr-manual-pause-scheduler', array($this, 'manual_pause_page'));
         add_submenu_page('subscription-pause', 'Previous Pauses Log', 'Previous Pauses Log', 'manage_options', 'mepr-previous-pauses', array($this, 'historical_page'));
     }

     public function admin_page(){
         require MFSS_VIEWS . '/admin/paused-list.php';
     }

     public function admin_subpage(){
         $action = 'mfss_settings_form';
         $nonce = wp_create_nonce($action);

         require MFSS_VIEWS . '/admin/settings-form.php';
     }

     public function manual_pause_page(){
         $action = 'mfss_manual_pause_form';
         $nonce = wp_create_nonce($action);

         require MFSS_VIEWS . '/admin/manual-pause-form.php';
     }

     public function historical_page(){
         require MFSS_VIEWS . '/admin/historical-list.php';
     }

     public function add_new_mepr_tab($user){
        ?>
        <span class="mepr-nav-item subscription-pause <?php MeprAccountHelper::active_nav('premium-support'); ?>">
            <a href="/account/?action=sub-pause"><?php echo __('Subscription Pause', MFSS_SLUG) ?></a>
        </span>
        <?php
     }

     public function mepr_tab_content($action){
        if($action === 'sub-pause'){
            $mfss_nonce = wp_create_nonce('mfss_subscription_pause');
            $action = 'mfss_subscription_pause';

            require MFSS_VIEWS . '/tab-content.php';
        }
     }

     static function is_current_user_paused(){
        if(is_user_logged_in()){
            $user = get_current_user_id();
            $today = new DateTime();
            $today = $today->format('d-m-Y');
            $date = new DateTime(get_user_meta($user, 'mfss-pause-start', true));
            $date = $date->format('d-m-Y');

            if(get_user_meta($user, 'mfss-pause-start', true) &&  $date >= $today){
                    return true;
            }

            return false;
        }
     }
}

new MF_Mepr_Suspend_Schedule();