<?php

$conn = new mysqli("localhost", "root", "", "simplecrud");
mysqli_report(MYSQLI_REPORT_OFF);

if ($conn->connect_error) {
    die("Connection Failed: " .$conn->connect_error);
}
?>
