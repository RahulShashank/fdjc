<?php
include("../database/connecti_database.php");
require_once('../engineering/checkEngineeringPermission.php');

$tailsign = $_POST['tailsign'];
$msg = "error";

if($tailsign) {
	$query = "SELECT id FROM aircrafts WHERE tailsign = '$tailsign'";
	$result = mysqli_query($dbConnection, $query);

	if($result) {
		if (mysqli_num_rows ($result)) {
			$row = mysqli_fetch_array($result);
			$msg = $row[id];
		}
		else {
			$msg = "aircraft not found";
		}
	} else {
		$msg = "error with query $query - ".mysqli_error($dbConnection);
	}
}

echo $msg;

?>
