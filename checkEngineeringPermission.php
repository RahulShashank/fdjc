<?php
require_once("common/validateUser.php");
$approvedRoles = [$roles["engineer"]];
$auth->checkPermission($hash, $approvedRoles);
?>
