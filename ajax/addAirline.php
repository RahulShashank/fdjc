<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";
require_once('../admin/checkAdminPermission.php');

$airlineId = $_REQUEST ['airlineId'];
$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );

// $tailSign = $request->tailSign;
// $msn = $request->msn;
// $type = $request->type;
// $platform = $request->platform;
// $software = $request->software;

$name = $request ['name'];
$acronym = $request ['acronym'];


$query = "INSERT INTO airlines (name, acronym) VALUES ('$name','$acronym')";
$result = mysqli_query ($dbConnection, $query);
if($result) {
	echo "$name has been created.";
	mysqli_commit($dbConnection);
	mysqli_close($dbConnection);
} else {
	echo "Error when adding $name. " . mysqli_error ($dbConnection) . ".";
}
?>
