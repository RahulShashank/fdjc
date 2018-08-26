<?php
require_once("../common/validateUser.php");
$approvedRoles = array($roles["engineer"],$roles["customer"]);
$auth->checkPermission($hash, $approvedRoles);
?>
