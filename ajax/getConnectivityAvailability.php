<?php
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');

require_once "../database/connecti_mongoDB.php";
require_once "../common/functions.php";
require_once "../common/computeFleetStatusData.php";

$itemStyle = "font-family: Helvetica; font-size: 10px; text-align: left";
$systemResetSvduRatio = getSystemResetSvduRatio();
$flightPhases = getFlightPhases();
$aircraftId = $_REQUEST['aircraftId'];
$flightLegs = $_REQUEST['flightLegs'];

//SB:Added startTime And endTime
$startTime = $_REQUEST['startTime'];
$endTime = $_REQUEST['endTime'];

if($flightLegs !=''){
$flightLegsArray = getFlightInArray($flightLegs);
}else{
$flightLegsArray = '';
}

if($aircraftId != '') {
    // Get information to display in header
    $query = "SELECT a.tailsign, b.id, b.name, a.platform, a.databaseName FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $aircraftTailsign = $row ['tailsign'];
      $airlineId = $row['id'];
	  $platform = $row['platform'];	  
      $airlineName = $row['name'];
      $dbName = $row['databaseName'];
    } else {
      echo "error: " . mysqli_error ( $error );
    }
} else if($sqlDump != '') {
    $airlineName = $row['name'];
    $dbName = $row['databaseName'];
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}

$mainDB = $dbName;

//OMTS Percentage Availability
$omtsAvailabilityPercent = createOmtsAvailabilityData($dbConnection,$collection, $flightLegsArray,$startTime,$endTime,$mainDB,$aircraftTailsign);

// Wifi Availability
$wifiAvailabilityPercent = createWifiAvailabilityData($dbConnection,$collection, $flightLegsArray,$startTime,$endTime,$mainDB,$aircraftTailsign);	

$percentageOfWifiAndOmts = array(
								'WifiOnAvailability' => $wifiAvailabilityPercent,
								'WifiOffAvailability'=> round(100 - $wifiAvailabilityPercent,2),
								'OmtsOnAvailability'=> $omtsAvailabilityPercent,
								'OmtsOffAvailability' => round(100 - $omtsAvailabilityPercent,2));

echo json_encode($percentageOfWifiAndOmts, JSON_NUMERIC_CHECK );

//get the wifi availability percentage 
function createWifiAvailabilityData($dbConnection, $collection, $flightLegsArray,$startTime,$endTime,$mainDB,$aircraftTailsign)
{
	$totalWifiAvailbilityPercentage = -1;
	$isFirstTime = 1;	
	$fields = array('idFlightLeg',
					'tailSign',
					'wifiAvailability.totalOnPercentage',
					'wifiAvailability.totalOffPercentage',
					'wifiAvailability.manualPercentageOn');


	/*$where=array('$and' => array(	array("idFlightLeg" => array('$in' => $flightLegsArray)),
									array("tailSign" => $aircraftTailsign)));*/
	
	//SB:if added to support of flight leg is not mapped	
	if($flightLegsArray != ''){
		$where=array('$and' => array(	array("idFlightLeg" => array('$in' => $flightLegsArray)),
										array("tailSign" => $aircraftTailsign)));
	}else{
		$where = array('$and' => array(	array("startTime" => array('$gte' => $startTime,'$lte' => $endTime )),
								array("tailSign" => $aircraftTailsign)));
	}
		
	$cursor = $collection->find($where,$fields);
			
	foreach ($cursor as $doc) 
	{
		if($isFirstTime){
			$totalWifiAvailbilityPercentage = 0;
		}
		if($doc['wifiAvailability']['manualPercentageOn'] != null)
		{
			$totalWifiAvailbilityPercentage = $totalWifiAvailbilityPercentage + $doc['wifiAvailability']['manualPercentageOn'];
		}
		elseif(($doc['wifiAvailability']['totalOnPercentage'] != null) || ($doc['wifiAvailability']['totalOffPercentage'] != null))
		{
			$totalWifiAvailbilityPercentage = $totalWifiAvailbilityPercentage + $doc['wifiAvailability']['totalOnPercentage'];
		}
		else
		{
		//do nothing
		}
		$isFirstTime = 0;	
	}
	//$totalWifiPercentage = $totalWifiAvailbilityPercentage/count($flightLegsArray); 
	
	if($flightLegsArray != ''){
		$totalWifiPercentage = $totalWifiAvailbilityPercentage/count($flightLegsArray); 
	}else{
		$totalWifiPercentage = $totalWifiAvailbilityPercentage; 
	}
	
	return round($totalWifiPercentage,2);	
}

// Get the OMTS Avialibily Data value from Mongodb
function createOmtsAvailabilityData($dbConnection, $collection, $flightLegsArray,$startTime,$endTime,$mainDB,$aircraftTailsign)
{
	$totalOmtsAvailbilityPercentage = -1;	
	$isFirstTime = 1;
	$fields = array('idFlightLeg',
					'tailSign',					
					'omtsAvailability.totalOnPercentage',
					'omtsAvailability.totalOffPercentage',
					'omtsAvailability.manualPercentageOn');

	/*$where=array('$and' => array(	array("idFlightLeg" => array('$in' => $flightLegsArray)),
									array("tailSign" => $aircraftTailsign)));*/
									
	//SB:if added to support of flight leg is not mapped	
	if($flightLegsArray != ''){
			$where=array('$and' => array(	array("idFlightLeg" => array('$in' => $flightLegsArray)),
									array("tailSign" => $aircraftTailsign)));
	}else{
			$where = array('$and' => array(	array("startTime" => array('$gte' => $startTime,'$lte' => $endTime )),
								array("tailSign" => $aircraftTailsign)));
	}
		
	$cursor = $collection->find($where,$fields);
	
	foreach ($cursor as $doc) 
	{
		if($isFirstTime){
			$totalOmtsAvailbilityPercentage = 0;
		}
		
		if($doc['omtsAvailability']['manualPercentageOn'] != null)
		{
			$totalOmtsAvailbilityPercentage = $totalOmtsAvailbilityPercentage + $doc['omtsAvailability']['manualPercentageOn'];
		}
		elseif(($doc['omtsAvailability']['totalOnPercentage'] != null) || ($doc['omtsAvailability']['totalOffPercentage'] != null))
		{
			$totalOmtsAvailbilityPercentage = $totalOmtsAvailbilityPercentage + $doc['omtsAvailability']['totalOnPercentage'];

		}
		else
		{
			//do nothing
		}
		$isFirstTime = 0;
	}
	
	//$totalOmtsPercentage = $totalOmtsAvailbilityPercentage/count($flightLegsArray);
	
	if($flightLegsArray != ''){
		$totalOmtsPercentage = $totalOmtsAvailbilityPercentage/count($flightLegsArray); 
	}else{
		$totalOmtsPercentage = $totalOmtsAvailbilityPercentage; 
	}
	
	return round($totalOmtsPercentage,2);		
}


?>
