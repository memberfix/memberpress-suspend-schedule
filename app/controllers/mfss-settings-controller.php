<?php

class MFSS_Settings_Controller{
    use MFSS_Controller, MFSS_Database;

    public function __construct(){
        // Settings Form Handler
        add_action('admin_post_mfss_settings_form', array($this, 'settings_form_callback'));
    }

    public function settings_form_callback(){
        if(isset($_POST['mfss-settings-nonce']) && wp_verify_nonce($_POST['mfss-settings-nonce'], 'mfss_settings_form')){
           // Maximum days Validation
            if(!isset($_POST['mfss-maximum-days']) || empty($_POST['mfss-maximum-days'])){
               $this->redirect('/wp-admin/tools.php?page=mepr-pause-scheduler', 'error', __('The maximum days field cannot be empty', MFSS_SLUG));
               exit;
            }

            // Once a month Validation
            if(!isset($_POST['mfss-once-a-month'])){
               $this->redirect('/wp-admin/tools.php?page=mepr-pause-scheduler', 'error', __('The once a month field cannot be empty', MFSS_SLUG));
               exit;
            }

            // Pause Start Subject Validation
            if(!isset($_POST['mfss-pause-email-subject'])){
                $this->redirect('/wp-admin/tools.php?page=mepr-pause-scheduler', 'error', __('The pause start subject field cannot be empty', MFSS_SLUG));
                exit;
             }

             // Pause Start Content Validation
            if(!isset($_POST['mfss-pause-email-content'])){
                $this->redirect('/wp-admin/tools.php?page=mepr-pause-scheduler', 'error', __('The pause start content field cannot be empty', MFSS_SLUG));
                exit;
             }

             // Pause Resume Subject Validation
            if(!isset($_POST['mfss-resume-email-subject'])){
                $this->redirect('/wp-admin/tools.php?page=mepr-pause-scheduler', 'error', __('The subscription resume subject field cannot be empty', MFSS_SLUG));
                exit;
             }

             // Pause Resume Content Validation
            if(!isset($_POST['mfss-resume-email-content'])){
                $this->redirect('/wp-admin/tools.php?page=mepr-pause-scheduler', 'error', __('The subscription resume content field cannot be empty', MFSS_SLUG));
                exit;
             }

            // Save the limit days
            update_option('mfss-pause-limit', $_POST['mfss-maximum-days']);

            // Save the once a month
            update_option('mfss-once-a-month', $_POST['mfss-once-a-month']);

            // Save the pause start subject
            update_option('mfss-pause-email-subject', $_POST['mfss-pause-email-subject']);

            // Save the pause start content
            update_option('mfss-pause-email-content', $_POST['mfss-pause-email-content']);

            // Save the subscription resume subject
            update_option('mfss-resume-email-subject', $_POST['mfss-resume-email-subject']);

            // Save the subscription resume content
            update_option('mfss-resume-email-content', $_POST['mfss-resume-email-content']);

            // Redirect
            $this->redirect('/wp-admin/admin.php?page=mepr-pause-scheduler', 'success', __('Settings saved!', MFSS_SLUG));
        }
    }

    static function get_the_users(){
        return $users = get_users(array('meta_key' => 'mfss-pause-start'));
    }

    static function get_the_paused_users(){
        $users = self::get_the_users();
        $returnable = array();

        foreach($users as $user){
            $start = get_user_meta($user->ID, 'mfss-pause-start', true);
            $start = strtotime($start);

            $end = get_user_meta($user->ID, 'mfss-pause-end', true);
            $end = strtotime($end);
            
            $today = new DateTime();
            $today = time();

            if($end > $today){
                array_push($returnable, $user->ID);
            }
        }

        return $returnable;
    }

    static function get_previous_paused_users(){
        global $wpdb;

        return $wpdb->get_results('SELECT * FROM ' . self::generate_table_name(MFSS_Pause_Controller::$table));
    }

    static function clean_historical_data(){
        global $wpdb;

        $now = new DateTime();
        $yearago = $now->modify('-1 year')->format('Y-m-d');

        $table_name = self::generate_table_name(MFSS_Pause_Controller::$table);

        return $wpdb->query("DELETE FROM $table_name WHERE pause_start < '$yearago'");
    }
}

new MFSS_Settings_Controller();