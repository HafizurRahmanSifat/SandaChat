<?php
session_start();
require_once "config.php";

$user = new USER();

if(isset($_SESSION['user'])) {
    session_unset();
    session_destroy();
    header("location: login.php");
    exit;
}


?>