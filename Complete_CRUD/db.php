<?php
$conn = new mysqli("localhost", "root", "", "simplecrud");

if ($conn->connect_error) {
    die("Connection Failed: " .$conn->connect_error);
}
?>