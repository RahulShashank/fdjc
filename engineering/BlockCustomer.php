<?php
require_once("../common/validateUser.php");
$approvedRoles = array($roles["admin"],$roles["engineer"]);
$auth->checkPermission($hash, $approvedRoles);
?>