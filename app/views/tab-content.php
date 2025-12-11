<style>
.mfss-message-container .mfss-notice{
    position: relative;
    padding: .75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: .25rem;
}

.mfss-message-container .mfss-alert{
    background-color: #fff3cd;
    color: #856404;
    border-color: #ffeeba;
}

.mfss-message-container .mfss-success{
    background-color:#d4edda;
    color: #155724;
    border-color:#c3e6cb;
}

.mfss-message-container .mfss-error{
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}
</style>

<div class="mfss-form-section mp_wrapper mp_wrapper_home">

<?php
    if(!MFSS_Pause_Controller::has_active_subscription()){
        echo '<p>' . __("You don't have an active subscription", MFSS_SLUG) . '</p>';
        exit;
    }
?>
<?php if(!MF_Mepr_Suspend_Schedule::is_current_user_paused()): ?>
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <input type="hidden" name="mfss_nonce" value="<?php echo $mfss_nonce; ?>">
        <div class="form-group">
            <label for="mfss-start-date"><?php echo __('Pause Starting Date', MFSS_SLUG); ?></label>
            <input type="date" name="mfss-start-date" id="mfss-start-date">
        </div>
        <br />
        <div class="form-group">
            <label for="mfss-end-date"><?php echo __('Pause Ending Date', MFSS_SLUG); ?></label>
            <input type="date" name="mfss-end-date" id="mfss-end-date">
            <?php
                if(get_option('mfss-once-a-month') == 'true'):
                    $limit_of_days = get_option('mfss-pause-limit'); ?>
                    <p style="color:red;"><?php echo __("The pause cannot be longer than $limit_of_days days.", MFSS_SLUG); ?></p>
                <?php endif; ?>
        </div>
        <div class="form-group">
            <input type="submit">
        </div>
    </form>
</div>
<?php else: ?>
<div class="mfss-reactivate-section">
    <div class="mfss-message-container">
        <div class="mfss-notice mfss-success">
            <p><?php echo __('The pause has been scheduled.', MFSS_SLUG); ?></p>
        </div>
    </div>
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
        <input type="hidden" name="action" value="mfss_end_pause_now">
        <input type="hidden" name="mfss_end_pause_now_nonce" value="<?php echo wp_create_nonce('mfss_end_pause_now'); ?>">
        <input type="submit" value="End Pause">
    </form>
</div>
<?php endif; ?>