<?php
session_start();
require_once('../engineering/checkEngineeringPermission.php');

$option = $_REQUEST['option'];
$value = $_REQUEST['value'];

$_SESSION[$option] = $value;

echo $_SESSION['showCritical'];
?>
