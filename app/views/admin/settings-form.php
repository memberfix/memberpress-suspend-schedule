<style>
    .mfss-form-group{
        margin: 10px 0;
    }
</style>

<?php MF_Mepr_Suspend_Schedule::show_redirect_message() ?>

<h1>Settings</h1>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
    <input type="hidden" name="action" value="<?php echo $action; ?>">
    <input type="hidden" name="mfss-settings-nonce" value="<?php echo $nonce; ?>">
    <div class="mfss-form-group">
        <label for="mfss-maximum-days" style="display:block;"><?php echo __('Maximum Days of Pause', MFSS_SLUG); ?></label>
        <input type="number" id="mfss-maximum-days" name="mfss-maximum-days" <?php if(!empty(get_option('mfss-pause-limit'))){echo 'value="'. get_option('mfss-pause-limit') . '"';} ?>>
    </div>
    <p><?php echo __('Should the pausing functionality be limited to once in 30 days?', MFSS_SLUG); ?></p>
    <div class="mfss-form-group">
        <label for="mfss-once-a-month"><?php echo __('Yes', MFSS_SLUG); ?></label>
        <input type="radio" id="mfss-once-a-month" name="mfss-once-a-month" value="true" <?php if(get_option('mfss-once-a-month') == 'true'){echo 'checked';} ?>>
    </div>
    <div class="mfss-form-group">
        <label for="mfss-once-a-month"><?php echo __('No', MFSS_SLUG); ?></label>
        <input type="radio" id="mfss-once-a-month" name="mfss-once-a-month" value="false" <?php if(get_option('mfss-once-a-month') == 'false'){echo 'checked';} ?>>
    </div>
    <h2><?php echo __('Pause Starting Email', MFSS_SLUG); ?></h2>
    <div class="mfss-form-group">
        <label for="mfss-pause-email-subject"><?php echo __('Email Subject', MFSS_SLUG); ?></label><br />
        <input type="text" id="mfss-pause-email-subject" name="mfss-pause-email-subject" <?php if(get_option('mfss-pause-email-subject')){echo 'value="' . get_option('mfss-pause-email-subject') . '"';} ?>>
    </div>
    <div class="mfss-form-group">
        <label for="mfss-pause-email-content"><?php echo __('Email Content', MFSS_SLUG); ?></label><br />
        <textarea id="mfss-pause-email-content" name="mfss-pause-email-content" cols="70" rows="10"><?php if(get_option('mfss-pause-email-content')){echo get_option('mfss-pause-email-content');} ?></textarea>
    </div>
    <h2><?php echo __('Subscription Resume Email', MFSS_SLUG); ?></h2>
    <div class="mfss-form-group">
        <label for="mfss-resume-email-subject"><?php echo __('Email Subject', MFSS_SLUG); ?></label><br />
        <input type="text" id="mfss-resume-email-subject" name="mfss-resume-email-subject" <?php if(get_option('mfss-resume-email-subject')){echo 'value="' . get_option('mfss-resume-email-subject') . '"';} ?>>
    </div>
    <div class="mfss-form-group">
        <label for="mfss-resume-email-content"><?php echo __('Email Content', MFSS_SLUG); ?></label><br />
        <textarea id="mfss-resume-email-content" name="mfss-resume-email-content" cols="70" rows="10"><?php if(get_option('mfss-resume-email-content')){echo get_option('mfss-resume-email-content');} ?></textarea>
    </div>
    <div class="mfss-form-group">
        <input type="submit" class="button button-primary" style="margin-top:10px;">
    </div>
</form>