<?php
// Start the session
session_start();

require_once "../database/connecti_database.php";
require_once "../common/functions.php";

require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];


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

/*
$query = "SELECT a.hostName, hwPartNumber, serialNumber, model, totalPowerOnTime, revision, swConf
		FROM $dbName.BIT_lru a, 
			(
				SELECT hostName, GROUP_CONCAT(CONCAT_WS(' / ',description,partNumber) SEPARATOR '\n') AS swConf 
				FROM $dbName.BIT_confSw 
				GROUP BY hostName
			) AS b 
		WHERE a.hostName = b.hostName
		AND a.lastUpdate = (
			SELECT MAX(b.lastUpdate) AS max
			FROM $dbName.BIT_lru b
			WHERE a.hostName = b.hostName
		)
		ORDER BY CASE 
			         WHEN a.hostName LIKE 'DSU%' THEN 1
			         WHEN a.hostName LIKE 'ADBG%' THEN 2
			         WHEN a.hostName LIKE 'LAIC%' THEN 3
	                 ELSE 4
                 END,
                 LENGTH(a.hostName)";
*/

$query = "SELECT a.hostName, hwPartNumber, serialNumber, model, totalPowerOnTime, revision, swConf
		FROM $dbName.BIT_lru a, 
			(
				SELECT hostName, GROUP_CONCAT(swConfInt SEPARATOR '
') AS swConf 
				FROM (
					SELECT hostName, lastUpdate, CONCAT_WS(' / ',description,partNumber) as swConfInt
					FROM $dbName.BIT_confSw z					
					WHERE z.lastUpdate IN (
						SELECT MAX(y.lastUpdate) AS max
						FROM $dbName.BIT_confSw y
						WHERE z.hostName = y.hostName
						AND z.description = y.description
						AND z.partNumber = y.partNumber
						AND z.hostName<>''
					)
					AND z.hostName<>''
					GROUP BY description, hostName 
				) as t 
				GROUP BY hostName
			) AS b 
		WHERE a.hostName = b.hostName
		AND b.hostName <> ''
		AND a.lastUpdate = (
			SELECT MAX(b.lastUpdate) AS max
			FROM $dbName.BIT_lru b
			WHERE a.hostName = b.hostName
			AND a.hostName <>''
		)
		ORDER BY CASE 
			         WHEN a.hostName LIKE 'DSU%' THEN 1
			         WHEN a.hostName LIKE 'ADBG%' THEN 2
			         WHEN a.hostName LIKE 'LAIC%' THEN 3
	                 ELSE 4
                 END,
                 LENGTH(a.hostName)";
				 
//echo $query."\n";
$result = mysqli_query($dbConnection,$query);

while ($row = mysqli_fetch_array($result)) {
	$model = $row['model'];
	//$modsBinaryValue = getBinaryMod($model);
	//$row['model'] = getDecimalMod($modsBinaryValue);
	$row['model'] = getModval($model);
	$lrus[] = $row;
}


# JSON-encode the response
echo $json_response = json_encode($lrus);

?>
