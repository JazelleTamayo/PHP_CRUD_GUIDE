<?php

/*
 * mysqli_report(MYSQLI_REPORT_OFF);
 * 
 * PURPOSE: Turns OFF automatic exception throwing for MySQL errors.
 *          Allows traditional error checking with $conn->errno.
 * 
 * WHY: PHP 8.1+ throws exceptions for MySQL errors by default.
 *      Our code expects execute() to return false on errors.
 *      Without this: duplicate email causes fatal exception.
 *      With this: execute() returns false, our errno check works.
 * 
 * PUT IN: db.php (once) - applies to all files that include it.
 */
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli("localhost", "root", "", "simplecrud");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);

}


?>
