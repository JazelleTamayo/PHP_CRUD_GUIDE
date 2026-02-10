<?php

$conn = new mysqli("localhost", "root", "", "simplecrud");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);

}

?>