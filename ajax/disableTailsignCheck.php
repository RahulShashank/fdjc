<?php
session_start();

$value = $_REQUEST['value'];
$_SESSION['disableTailsignCheck'] = $value;
?>