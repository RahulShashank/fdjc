<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$seatId = $_REQUEST['seatId'];

if($aircraftId != '') {
	$query = "SELECT databaseName FROM aircrafts WHERE id=$aircraftId";
	$result = mysqli_query($dbConnection,$query);
	if($result && mysqli_num_rows($result) > 0 ) {
		$row = mysqli_fetch_array($result);
		$dbName = $row['databaseName'];
	} else {
		exit;
	}
} else {
	$selected = mysqli_select_db($dbConnection, $sqlDump)
			or die("Could not select ".$sqlDump);
}

$query = "SELECT DISTINCT hostName 
			FROM $dbName.BIT_lru 
			WHERE (hostName LIKE 'SVDU$seatId' OR hostName LIKE 'TPMU$seatId' OR hostName LIKE '%TPCU$seatId')";
$result = mysqli_query($dbConnection,$query);
if($result && mysqli_num_rows($result) > 0 ) {
	$i=0;
	while($row = mysqli_fetch_array($result)) {
		if($i >0) {
			$lrusString .= '<br>';
		}
		$hostName = $row['hostName'];
		// $lrusString .= "<b>$hostName</b>";
		$lrusString .= "<a href=\"hostnameAnalysis.php?aircraftId=$aircraftId&hostname=$hostName\">$hostName</a>";
		$i++;
	}
}

echo $lrusString;
?>
