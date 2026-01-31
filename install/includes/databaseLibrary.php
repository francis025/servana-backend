<?php

use IonAuth\Libraries\IonAuth;

class Database
{
    function create_database($data)
    {
        $port = isset($data['port']) && !empty($data['port']) ? $data['port'] : 3306;
        ini_set('default_socket_timeout', 120);
        ini_set('mysql.connect_timeout', 120);
        
        // Connect directly to the database to verify it exists
        $mysqli = @new mysqli($data['hostname'], $data['username'], $data['password'], $data['database'], $port);
        
        if ($mysqli->connect_error) {
            return false;
        }
        
        // Database exists and connection successful
        $mysqli->close();
        return true;
    }


    function create_tables($data)
    {
        $port = isset($data['port']) && !empty($data['port']) ? $data['port'] : 3306;
        ini_set('default_socket_timeout', 120);
        ini_set('mysql.connect_timeout', 120);
        $link = @mysqli_connect($data['hostname'], $data['username'], $data['password'], $data['database'], $port);
        if (mysqli_connect_errno())
            return false;
     
        $filename = 'assets/sqlcommand.sql';
        
        $tempLine = '';
        // Read in the full file
        $lines = file($filename);
        // Loop through each line
        foreach ($lines as $line) {
            // Skip it if it's a comment
            if (substr($line, 0, 2) == '--' || $line == '')
                continue;

            // Add this line to the current segment
            $tempLine .= $line;
            // If its semicolon at the end, so that is the end of one query
            if (substr(trim($line), -1, 1) == ';') {
                // Perform the query
                mysqli_query($link, $tempLine) or print("Error in " . $tempLine . ":" . mysqli_error($link));
                // Reset temp variable to empty
                $tempLine = '';
            }
        }
        return true;
    }

    function create_admin($data)
    {
        // $ionAuth = new IonAuth;
        // $ionAuth->register($data['admin_email'],$data['admin_password'],$data['admin_email'],[],[1]);

        // return true;
        $port = isset($data['port']) ? $data['port'] : 3306;
        ini_set('default_socket_timeout', 60);
        $mysqli = new mysqli($data['hostname'], $data['username'], $data['password'], $data['database'], $port);
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 60);
        if (mysqli_connect_errno())
            return false;

        $password = $data['admin_password'];
        $admin_email = $data['email'];
        $admin_mobile = $data['phone'];
        $name = $data['admin_username'];


        $params = [
            'cost' => 12
        ];

        if (empty($password) || strpos($password, "\0") !== FALSE || strlen($password) > 32) {
            return FALSE;
        } else {
            $password = password_hash($password, PASSWORD_BCRYPT, $params);
        }
        $mysqli->query("UPDATE `users` SET `username` = '$name', `password` = '$password', `email` = '$admin_email', `phone` = '$admin_mobile' WHERE `users`.`id` = 1;");
        $mysqli->close();
        return true;
    }

    function create_base_url($data)
    {
        $mysqli = new mysqli($data['hostname'], $data['username'], $data['password'], $data['database']);
        if (mysqli_connect_errno())
            return false;
        $data_json = array(
            'app_url' => $data['app_url'],
            'company_title' => 'eDemand'
        );
        $data = json_encode($data_json);

        $mysqli->query("UPDATE settings SET `data`='$data' WHERE `type`='general'");

        $mysqli->close();
        return true;
    }
}