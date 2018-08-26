<?php
require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');

$query = "SELECT a.id, a.lruType, a.hwPartNumber, a.expectedRevision, a.expectedModel from hardware_revs_mods a";
$result = mysqli_query($dbConnection, $query);

$hwRevsMods = array();
if($result){
	while ($row = mysqli_fetch_array($result)) {
		array_push($hwRevsMods, array('id'=>$row['id'], 'lruType'=>$row['lruType'], 'hwPartNumber'=>$row['hwPartNumber'], 'expectedRevision'=>$row['expectedRevision'], 'expectedModel'=>$row['expectedModel']));
	}
}

# JSON-encode the response
$json_response = json_encode($hwRevsMods);

echo $json_response;
?>
