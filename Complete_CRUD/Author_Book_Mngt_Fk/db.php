<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("localhost", "root", "", "library_crud");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>