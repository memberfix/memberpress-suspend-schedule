<style>
    .mfss-form-group{
        margin: 10px 0;
    }

    label{
        display: block;
    }
</style>

<?php MF_Mepr_Suspend_Schedule::show_redirect_message() ?>

<h1>Set Manual Pause</h1>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
    <input type="hidden" name="action" value="<?php echo $action; ?>">
    <input type="hidden" name="mfss_manual_pause_nonce" value="<?php echo $nonce; ?>">
    <div class="mfss-form-group">
        <label for="mfss-user-email" style="display:block;"><?php echo __('User Email', MFSS_SLUG); ?></label>
        <input type="email" id="mfss-user-email" name="mfss-user-email" required>
    </div>
    <div class="form-group">
        <label for="mfss-start-date"><?php echo __('Pause Starting Date', MFSS_SLUG); ?></label>
        <input type="date" name="mfss-start-date" id="mfss-start-date" required>
    </div>
    <br />
    <div class="form-group">
        <label for="mfss-end-date"><?php echo __('Pause Ending Date', MFSS_SLUG); ?></label>
        <input type="date" name="mfss-end-date" id="mfss-end-date" required>
    </div>
    <div class="mfss-form-group">
        <input type="submit" class="button button-primary" style="margin-top:10px;">
    </div>
</form>