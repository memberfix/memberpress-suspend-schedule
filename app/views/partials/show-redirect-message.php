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

<div class="mfss-message-container">
    <div class="mfss-notice <?php echo $type; ?>">
        <p><?php echo $message; ?></p>
    </div>
</div>

<script>
    document.cookie = "mfss_redirect_type=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    document.cookie = "mfss_redirect_message=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
</script>