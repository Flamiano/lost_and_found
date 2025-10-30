<?php

$host = 'localhost';
$db_name = 'lost_and_found';
$username = 'root';
$password = '';

try {
    // create a new PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);

    // set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // default fetch mode
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // display the error
    echo "Database Connection Failed: " . $e->getMessage();
    exit;
}
    