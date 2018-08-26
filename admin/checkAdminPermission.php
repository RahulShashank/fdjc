<?php
require_once("../common/validateUser.php");
$approvedRoles = [$roles["admin"]];
$auth->checkPermission($hash, $approvedRoles);
?>
