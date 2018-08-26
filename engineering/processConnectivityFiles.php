<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 0);
date_default_timezone_set("Pacific");

require_once "../database/connecti_database.php";
require_once("../common/validateUser.php");
include ("BlockCustomer.php");

$aircraftId = $_REQUEST['aircraftId'];
$airlineId = $_REQUEST['airlineId'];
$sqlDump = $_REQUEST['sqlDump'];

$query = "SELECT tailsign, platform, isp FROM aircrafts a WHERE a.id = $aircraftId";
$result = mysqli_query($dbConnection, $query);
if($result && mysqli_num_rows($result) > 0) {
	$row = mysqli_fetch_array($result);	
	$tailSign = $row['tailsign'];
	$platform = $row['platform'];
	$isp = $row['isp'];	
	$platformParser = $isp . "_" . $platform;	
} else {
	 echo "<br>error: ".mysqli_error($dbConnection);
	 exit;
}	
$_SESSION['aircraftId'] = $aircraftId;
$_SESSION['airlineId'] = $airlineId;

if($isp=="KaNoVAR"){
	include ("uploadKaConnectivityFiles.php");
}else{	
	include ("uploadConnectivityFiles.php");
}

?>
