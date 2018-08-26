<?php

require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');

if(isset($_GET['db'])){
	$db = $_GET['db'];
	$resultDBs = mysqli_query($dbConnection, "DROP DATABASE $db", $dbhandle);

	if( !$resultDBs) {
		$msg = "<b>$db</b> data cannot be deleted.";
	} else {
		$msg = "SQL Dump <b>$db</b> deleted successfully.";
	}
} else {
	$msg = 'error - db parameter is not set';
}

echo $msg;

?>
