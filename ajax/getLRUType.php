<?php
require_once "../database/connecti_database.php";
//require_once('../engineering/checkEngineeringPermission.php');
$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );
$lruName = trim($_REQUEST['name']);

$query = "SELECT name FROM `lru_types` WHERE type='".$lruName."' ORDER BY name ASC";
	$stmt = $dbConnection->prepare($query) ;
	$stmt->execute();
	$stmt->bind_result($name);
	// $result = mysqli_query($dbConnection, $query);

	$aircrafts = array();
	while ($stmt->fetch()) {
		$lruNameDetails[] = array(
			'name' 		=> $name,
		);
	}

# JSON-encode the response
echo $json_response = json_encode($lruNameDetails);

//echo $json_response;
?>