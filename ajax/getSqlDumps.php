<?php
// Start the session
session_start();

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$query = "SHOW databases LIKE 'bite%'";
$stmt = $dbConnection->prepare($query);
$stmt->execute();
$stmt->bind_result($dbName);
// $result = mysqli_query($dbConnection, $query);

$sqlDumps = array();

while ($stmt->fetch()) {
	$displayName = trimDBName($dbName);
	$sqlDumps[] = array('dbName' => $dbName, 'displayName' => $displayName);
}

$stmt->close();
// while ($row = mysqli_fetch_array($result)) {
// 		$dbName = $row[0];
// 		$displayName = trimDBName($row[0]);
// 		$sqlDumps[] = array(
// 			'dbName' => $dbName,
// 			'displayName' => $displayName
// 		);
// }

# JSON-encode the response
echo $json_response = json_encode($sqlDumps);

?>
