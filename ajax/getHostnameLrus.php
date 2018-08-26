<?php
// Start the session
session_start();

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$hostname = $_REQUEST['hostname'];


if($aircraftId != '') {
	$query = "SELECT a.databaseName FROM aircrafts a WHERE a.id = $aircraftId" ;
	$result = mysqli_query($dbConnection,$query);

	$lrus = array();
	if($result && mysqli_num_rows($result) > 0 ) {
		$row = mysqli_fetch_array($result);
		$dbName = $row['databaseName'];
	}
} else if($sqlDump != '') {
	$dbName = $sqlDump;
} else {
	echo "no acid nor sqlDump";
	exit;
}

$query = "SELECT  hwPartNumber, serialNumber, model, revision, DATE_FORMAT(lastUpdate,'%Y-%m-%d') AS lastUpdate
		FROM $dbName.BIT_lru
		WHERE hostName = '$hostname'
		AND hwPartNumber != ''
		ORDER BY lastUpdate DESC";
$result = mysqli_query($dbConnection,$query);

while ($row = mysqli_fetch_array($result)) {
	$model = $row['model'];
	$modsBinaryValue = getBinaryMod($model);
	$row['model'] = getDecimalMod($modsBinaryValue);
	$lrus[] = $row;
}


# JSON-encode the response
echo $json_response = json_encode($lrus);

?>
