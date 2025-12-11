<?php

/**
 * The trait that helps with database functionalities
 */

trait MFSS_Database{
    // Does the table exist?
    public function table_exists($table_name){
        global $wpdb;

        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

        if ($wpdb->get_var( $query ) == $table_name) {
            return true;
        }

        return false;
    }

    // Create a new table
    public function create_table($table_name, $fields){
        if(!$this->table_exists($table_name)){
            global $wpdb;

            $charset = $wpdb->get_charset_collate();

            $formatted_table_name = $table_name;

            $sql = "CREATE TABLE $formatted_table_name ( $fields ) $charset";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $res = dbDelta($sql);
        }
    }

    // Format the table name
    static function generate_table_name($name){
        global $wpdb;
        return $wpdb->prefix . 'mfss_' . $name;
    }
}