<?php

$conn = new mysqli("localhost", "root", "", "contact_manager");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>