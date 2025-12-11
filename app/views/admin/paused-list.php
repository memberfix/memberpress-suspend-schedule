<style>
    table {
    font-family: Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
    }

    table td, table th {
    border: 1px solid #ddd;
    padding: 8px;
    }

    table tr:nth-child(even){background-color: #f2f2f2;}

    table tr:hover {background-color: #ddd;}

    table th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #0c71c3;
    color: white;
    }
</style>

<h1>Scheduled Pauses</h1>
<table>
    <thead>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Pause Start</th>
        <th>Pause End</th>
    </thead>
    <tbody>
        <?php
            $users = MFSS_Settings_Controller::get_the_paused_users();

            foreach($users as $user){
                $user = new WP_User($user);
                ?>
                    <tr>
                        <td><?php echo $user->ID; ?></td>
                        <td><a href="/wp-admin/user-edit.php?user_id=<?php echo $user->ID; ?>"><?php echo $user->user_login; ?></a></td>
                        <td><?php echo $user->user_email; ?></td>
                        <td><?php echo get_user_meta($user->ID, 'mfss-pause-start', true); ?></td>
                        <td><?php echo get_user_meta($user->ID, 'mfss-pause-end', true); ?></td>
                    </tr>
                <?php
            }
        ?>
    </tbody>
</table>