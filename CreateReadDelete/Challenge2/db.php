<?php

$conn = new mysqli("localhost", "root", "", "inventory_manager" );

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);

}
?>