<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli("localhost", "root", "", "createread");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>