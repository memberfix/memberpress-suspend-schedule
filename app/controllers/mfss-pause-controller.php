<?php

class MFSS_Pause_Controller{
    use MFSS_Controller, MFSS_Database;

    static $table = 'historical_pauses';

    private $pause_start = 'mfss-pause-start';
    private $pause_end = 'mfss-pause-end';
    private $last_paused = 'mfss-last-paused';
    private $expiry_date = 'mfss-expiry-date';
    private $previous_sub = 'mfss-previous-sub';
    private $last_restored = 'mfss-last-restored';
    private $previous_prod = 'mfss-previous-prod';

    public function __construct(){
        // Add Pause Form handlers
        add_action('admin_post_mfss_subscription_pause', array($this, 'add_pause_form_handler'));
        add_action('admin_post_nopriv_mfss_subscription_pause', array($this, 'add_pause_form_handler'));

        // Manual Pause Pause Form handlers
        add_action('admin_post_mfss_manual_pause_form', array($this, 'manual_pause'));
        add_action('admin_post_nopriv_mfss_manual_pause_form', array($this, 'manual_pause'));

        // End Pause Now handler
        add_action('admin_post_mfss_end_pause_now', array($this, 'end_pause_now'));
        add_action('admin_post_nopriv_mfss_end_pause_now', array($this, 'end_pause_now'));

        // Pause handler
        add_action('init', array($this, 'pause_operator'));
        add_action('mepr-event-transaction-expired', array($this, 'is_subscription_renewable'));
    }

    public function add_pause_form_handler(){
        if( isset( $_POST['mfss_nonce'] ) && wp_verify_nonce( $_POST['mfss_nonce'], 'mfss_subscription_pause') ) {
            $user_id = get_current_user_id();

            $this->schedule_pause($user_id, '/account?action=sub-pause');
        }
    }

    public function pause_operator(){
        if(is_user_logged_in()){
            $user = get_current_user_id();

            // If the pause is ended, resume the transaction and schedule the subscription resume
            if($this->is_date_today_or_past(get_user_meta($user, $this->pause_end, true)) && $this->is_pause_ended()){
                if(!$this->is_restored($user)){
                    $this->resume_transaction($user);
                }
            }

            // If today is the pause start day, pause the subscription and remove the access
            if($this->is_date_today(get_user_meta($user, $this->pause_start, true)) && !$this->is_user_paused($user)){
                $this->pause_subscription($user);
            }
        }
     }

    public function is_subscription_renewable($event){
        $txn = $event->get_data();
        $user = $txn->user_id;

        if($this->is_user_paused($user)){
            if($this->is_pause_ended($user)){
                $this->resume_subscription($user);
            }
        }
    }

    public function end_pause_now(){
        if( isset( $_POST['mfss_end_pause_now_nonce'] ) && wp_verify_nonce( $_POST['mfss_end_pause_now_nonce'], 'mfss_end_pause_now') ) {
            $user = get_current_user_id();
            $today = new DateTime();
            $today = $today->format('d-m-Y');

            $this->maybe_add_user_meta($user, $this->pause_end, $today);

            $this->resume_transaction($user);

            $this->redirect('/account?action=sub-pause', 'success', __('The pause has been ended', MFSS_SLUG));
        }
    }

    public function manual_pause(){
        if( isset( $_POST['mfss_manual_pause_nonce'] ) && wp_verify_nonce( $_POST['mfss_manual_pause_nonce'], 'mfss_manual_pause_form') ) {
            $user = get_user_by('email', $_POST['mfss-user-email']);
            $redirect = '/wp-admin/admin.php?page=mepr-manual-pause-scheduler';

            if(!self::has_active_subscription()){
                $this->redirect($redirect, 'error', 'The user does not have any active subscriptions');
            }

            if($user instanceof WP_User){
                $user_id = $user->ID;

                $this->schedule_pause($user_id, $redirect, false);
            }else{
                $this->redirect($redirect, 'error', 'The user could not be found');
            }
        }
    }

    private function log_expired_pause($user_id, $pause_start, $pause_end){
        global $wpdb;

        $wpdb->insert(self::generate_table_name(self::$table), ['user_id' => $user_id, 'pause_start' => $pause_start, 'pause_end' => $pause_end]);
    }

    private function schedule_pause($user_id, $redirect, $pause = true){
        // Validate
        if(!$this->validate_form_data($_POST, $user_id, $pause)){
            $this->redirect($redirect, 'error', $this->error);
            exit;
        }

        // Add user meta
        $this->maybe_add_user_meta($user_id, $this->pause_start, $_POST['mfss-start-date']);
        $this->maybe_add_user_meta($user_id, $this->pause_end, $_POST['mfss-end-date']);

        // If the date is today, pause the subscription
        if($this->is_date_today($_POST['mfss-start-date'])){
            $this->pause_subscription($user_id);
        }

        $this->redirect($redirect, 'success', 'The pause has been scheduled');
    }

    private function is_user_paused($user = null){
        if($user != null){
            if(get_user_meta($user, $this->pause_start, true)){
                if(get_user_meta($user, $this->pause_start, true) <= date('d-m-Y')){
                    return true;
                }else{
                    return false;
                }
            }

            return false;
        }

        if(is_user_logged_in()){
            $user = get_current_user_id();
            $today = new DateTime();
            $today = $today->format('d-m-Y');
            $date = new DateTime(get_user_meta($user, $this->pause_start, true));
            $date = $date->format('d-m-Y');

            if(get_user_meta($user, $this->pause_start) &&  $date >= $today){
                    return true;
            }
        }

        return false;
    }

    private function is_pause_ended($user = null){
        if($user != null){
            if(get_user_meta($user, $this->pause_end, true) && get_user_meta($user, $this->pause_end, true) <= date('d-m-Y')){
                    return true;
            }

            return false;
        }

        if(is_user_logged_in()){
            $user = get_current_user_id();
            $today = new DateTime();
            $today = $today->format('d-m-Y');
            $date = new DateTime(get_user_meta($user, $this->pause_end, true));
            $date = $date->format('d-m-Y');

            if(get_user_meta($user, $this->pause_end) && $date <= $today){
                return true;
            }
        }

        return false;
    }

    private function maybe_add_user_meta($user, $key, $value){
        if(!get_user_meta($user, $key)){
            add_user_meta($user, $key, $value);
        }else{
            update_user_meta($user, $key, $value);
        }
    }

    private function validate_form_data($post, $user, $pause = true){
        // Check if the starting date is empty
        if(!isset($_POST['mfss-start-date']) || empty($_POST['mfss-start-date'])){
            $this->error = 'Starting date cannot be empty';
            return false;
        }

        // Check if the ending date is empty
        if(!isset($_POST['mfss-end-date']) || empty($_POST['mfss-end-date'])){
            $this->error = 'Ending date cannot be empty';
            return false;
        }

        // Today
        $today = new DateTime();
        $today_timestamp = $today->getTimestamp();

        // 14 days
        $limit_days = strtotime(get_option('mfss-pause-limit') . ' days', 0);

        // Start date
        $start_date = new DateTime($_POST['mfss-start-date']);
        $start_timestamp = $start_date->getTimestamp();

        // End date
        $end_date = new DateTime($_POST['mfss-end-date']);
        $end_timestamp = $end_date->getTimestamp();

        // Check if the end date is not after the start date
        if($end_date < $start_date){
            $this->error = 'Please select an end date that is later than the start date';
            return false;
        }

        // Time diference
        $difference = $end_timestamp - $start_timestamp;

        // Check if the end date isn't more than 14 days over the start date
        if($pause == true){
            if($limit_days >= 1){
                if($difference > $limit_days){
                    $this->error = 'The pause cannot be longer than 14 days';
                    return false;
                }
            }
        }

        // Check if the user has paused in the last 30 days
        if(get_option('mfss-once-a-month') == 'true'){
            if(get_user_meta( $user, $this->last_paused, true)){
                $last_paused = get_user_meta( $user, $this->last_paused, true);
                $last_paused = new DateTime($last_paused);
                $thirty_interval = new DateInterval('P30D');
                $thirty_difference = $last_paused->add($thirty_interval);
                
                if($thirty_difference > $today){
                    $this->error = 'You have already paused your subscription in the current month';
                    return false;
                }
            }
        }

        // Check if the start date is older than today
        if ($today > $start_date){
            if($today->format('d-m-Y') == $start_date->format('d-m-Y')){
                return true;
            }

            $this->error = 'Please select a start date that is either today or in the future';
            return false;
        }

        return true;
    }

    private function is_date_today($date){
        $today = strtotime(date("d-m-Y"));
        $date = new DateTime($date);
        $date = $date->getTimestamp();
        $datediff = $today - $date;
        $difference = floor($datediff/(60*60*24));

        if($difference==0) {
            return true;
        }

        return false;
    }

    private function is_date_today_or_past($date){
        $today = strtotime(date("d-m-Y"));
        $date = new DateTime($date);
        $date = $date->getTimestamp();
        $datediff = $today - $date;
        $difference = floor($datediff/(60*60*24));

        if($difference==0 || $date < $today) {
            return true;
        }

        return false;
    }

    private function pause_subscription($user){
        $active_txn = self::get_active_txns($user);
        $latest_txn = end($active_txn);

        if($latest_txn && $latest_txn->subscription()){
            $subscription = $latest_txn->subscription();
            
            // Add user meta
            $this->maybe_add_user_meta($user, $this->expiry_date, $latest_txn->expires_at);
            $this->maybe_add_user_meta($user, $this->previous_sub, $subscription->id);
            $this->maybe_add_user_meta($user, $this->previous_prod, $latest_txn->product_id);

            // Suspend the subscription in the payment method and expire the current transaction
            $subscription->status = 'suspended';
            $subscription->suspend();
            $subscription->store();
            $latest_txn->expire();

            // Send the notification
            $user_obj = new WP_User($user);
            wp_mail($user_obj->user_email, get_option('mfss-pause-email-subject'), get_option('mfss-pause-email-content'));

            // If the account was restored before, change the restored status to not restored
            if($this->is_restored($user)){
                $this->maybe_add_user_meta($user, $this->last_restored, 0);
            }
        }
    }

    private function is_restored($user){
        if(get_user_meta($user, $this->last_restored, true) && intval(get_user_meta($user, $this->last_restored, true)) == 1){
            return true;
        }

        return false;
    }

    private function resume_transaction($user){
        $difference = $this->calculate_difference_days($user);
        $new_expiry = new DateTime();
        $new_expiry = $new_expiry->add(new DateInterval('P'.$difference.'D'));
        $pause = get_user_meta($user, $this->pause_start, true);
        $sub = get_user_meta($user, $this->previous_sub, true);

        // Create a new transaction that will include the days that were left when the subscription was paused
        $new_txn = new MeprTransaction();
        $new_txn->user_id = $user;
        $new_txn->trans_num = 'mf-txn-pause-restored-' . $pause;
        $new_txn->status = 'complete';
        $new_txn->subscription_id = $sub;
        $new_txn->product_id = get_user_meta($user, $this->previous_prod, true);
        $new_txn->expires_at = $new_expiry->format('Y-m-d H:i:s');
        $new_txn->store();

        $sub = new MeprSubscription($sub);
        $sub->status = 'active';
        $sub->store();

        // Log the pause
        $this->log_expired_pause($user, get_user_meta($user, $this->pause_start, true), get_user_meta($user, $this->pause_end, true));

        // Send the resume notification
        $user_obj = new WP_User($user);
        wp_mail($user_obj->user_email, get_option('mfss-resume-email-subject'), get_option('mfss-resume-email-content'));

        // Add user meta
        $this->maybe_add_user_meta($user, $this->last_paused, get_user_meta($user, $this->pause_start, true));
        $this->maybe_add_user_meta($user, $this->last_restored, 1);
        $this->maybe_add_user_meta($user, $this->pause_start, '1970-01-01');
        $this->maybe_add_user_meta($user, $this->pause_end, '1970-01-01');
    }

    private function resume_subscription($user){
        $sub = new MeprSubscription(get_user_meta($user, $this->previous_sub, true));
        $sub->resume();
    }

    private function calculate_difference_days($user){
        $pause = get_user_meta($user, $this->pause_start, true);
        $expire = get_user_meta($user, $this->expiry_date, true);
        $pause_day = new DateTime($pause);
        $expire_day = new DateTime($expire);

        $diff = $pause_day->diff($expire_day);
        return intval($diff->format('%R%a'));
    }

    static function static_active(){
        if(is_user_logged_in()){
            $user = get_current_user_id();
            $today = new DateTime();
            $today = $today->format('d-m-Y');
            $date = new DateTime(get_user_meta($user, 'mfss-pause-start', true));
            $date = $date->format('d-m-Y');

            if(get_user_meta($user, 'mfss-pause-start') &&  $date >= $today){
                    return true;
            }

            return false;
        }

        return false;
    }

    static function get_active_txns($user){
        $member = new MeprUser($user);
        $txns = $member->recent_transactions();
        $active_txns = array();

        foreach($txns as $txn){
            if($txn->is_active()){
                array_push($active_txns, $txn);
            }
        }

        return $active_txns;
    }

    static function has_active_subscription(){
        $user = get_current_user_id();
        $active_txns = self::get_active_txns($user);
        $txn = end($active_txns);

        if(!$txn || !$txn->subscription()){
            if(self::static_active()){
                return true;
            }

            return false;
        }

        return true;
    }
}

new MFSS_Pause_Controller();