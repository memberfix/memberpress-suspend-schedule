<?php

trait MFSS_Controller{
    protected $error;
    
    protected function redirect($url, $type = null, $message = null){
        if ($type != null && $message != null){
            setcookie('mfss_redirect_type', $type, time() + 3600, '/');
            setcookie('mfss_redirect_message', $message, time() + 3600, '/');

            return wp_redirect($url);
        }

        return wp_redirect($url);
    }

    static function generate_message_class($type){
        switch ($type){
            case 'success':
                return 'mfss-success';
                break;
            case 'alert':
                return 'mfss-alert';
                break;
            case 'error':
                return 'mfss-error';
                break;
        }
    }

    static function show_redirect_message(){
        if(isset($_COOKIE['mfss_redirect_type']) && isset($_COOKIE['mfss_redirect_message'])){
            $type = self::generate_message_class($_COOKIE['mfss_redirect_type']);
            $message = $_COOKIE['mfss_redirect_message'];

            require MFSS_VIEWS . '/partials/show-redirect-message.php';
        }
    }
}