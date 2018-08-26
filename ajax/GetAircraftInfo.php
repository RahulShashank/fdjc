<?php

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/customerFilters.php";
require_once('../engineering/checkEngineeringPermission.php');
session_start ();

$aircraftId = $_REQUEST['aircraftId'];
//$sqlDump = $_REQUEST['sqlDump'];
$_SESSION['airline'] =  $_REQUEST['airline'];
$_SESSION['platform'] =  $_REQUEST['platform'];
$_SESSION['configType'] =  $_REQUEST['configType'];
$_SESSION['software'] =  $_REQUEST['software'];
$_SESSION['tailsign'] =  $_REQUEST['aircraftId'];
$_SESSION['startDate'] =  $_REQUEST['startDate'];
$_SESSION['endDate'] =  $_REQUEST['endDate'];

if($aircraftId != '') {
	$aircraftInfo=array();
    // Get information to display in header
    $query = "SELECT a.tailsign, a.noseNumber, a.msn, a.type, a.EIS, a.Ac_Configuration, a.platform, b.id, b.name, a.databaseName, a.isp, a.software, a.SW_PartNo, a.SW_installed, a.SW_Baseline, a.Map_Version, a.Content FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
    error_log('Aircraft QUery'.$query);
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
    	 
      $row = mysqli_fetch_assoc( $result );      
	  $aircraftInfo=$row;
	  //array_push($aircraftInfo, $row);
    } else {
      echo "error: " . mysqli_error ( $error );
    }
    # JSON-encode the response
    $json_response = json_encode($aircraftInfo);
    // # Return the response
    echo $json_response;
} else if($sqlDump != '') {
    $dbName = $sqlDump;
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}

?>