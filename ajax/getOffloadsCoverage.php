<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

// $airlineId = $_REQUEST['airlineId'];
// $platform = $_REQUEST['platform'];
// $actype = $_REQUEST['actype'];
// $startDate = $_REQUEST['startDate'];
// $endDate = $_REQUEST['endDate'];

$airlineId = $_REQUEST['airlineId'];
$platform = $_REQUEST['platform'];
$configType = $_REQUEST['configuration'];
$tailsign = $_REQUEST['tailsign'];
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];

error_log("Start Date: " . $startDate);
error_log("End Date: " . $endDate);

// Convert start date to correct format
$startDate = date('Y-m-d', strtotime($startDate));
// Add one day as the query to mysql will be in the form YYYY-MM-DD 00:00:00 so we don't miss the last day
$endDate = date('Y-m-d', strtotime($endDate . ' +1 day'));
// Get all dates between start date and end date
$dates = createDateRangeArray($startDate, $endDate);

error_log("Start Date: " . $startDate);
error_log("End Date: " . $endDate);

// Columns to be returned
$columnsData = array();
$columnsData[] = 'Tailsign';
$columnsData[] = 'A/C Type';
$columnsData[] = 'Platform';
for($i = 0 ; $i < count($dates) - 1 ; $i++) {
	$columnsData[] = date('m/d', strtotime($dates[$i]));
}

$flightPhases = getFlightPhases();

// Get aircrafts databases for the airline
$query = "SELECT id, databaseName, tailsign, noseNumber, type, platform
FROM aircrafts 
WHERE airlineId = $airlineId";

// if($platform != '') {
//     $query .= " AND platform = '$platform'";
// }
// if($actype != '') {
//     $query .= " AND type = '$actype'";
// }

if(is_array($platform)) {
    $query .= " and platform in(";
    foreach ($platform as $p) {
        $query .= "'" . $p . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if($platform != '') {
    $query .= " and platform='$platform'";
}

if(is_array($configType)) {
    $query .= " and Ac_Configuration in(";
    foreach ($configType as $c) {
        $query .= "'" . $c . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if($configType!= '') {
    $query .= " and Ac_Configuration='$configType'";
}

if(is_array($tailsign)) {
    $query .= " and tailsign in(";
    foreach ($tailsign as $tailsign) {
        $query .= "'" . $tailsign . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if(!isNullOrEmptyString($tailsign)) {
    $query .= " and tailsign='$tailsign'";
}

$query .= " ORDER BY tailsign";

error_log("Query: " . $query);

$result = mysqli_query ($dbConnection, $query );


if($result){
	$tailsignsData = array();
	
	while ( $row = mysqli_fetch_assoc ( $result ) ) {
		$acid = $row ['id'];
		$dbName = $row ['databaseName'];
		$tailsign = $row ['tailsign'];
		$nose = $row ['noseNumber'];
		if($nose != '') {
			$tailsign .= " ($nose)";
		}
		$type = $row ['type'];
		$platform = $row ['platform'];
		
		$tailsignData = array();
// 		$tailsignData['Tailsign'] = "<a href=\"aircraftDashboard.php?aircraftId=$acid\">$tailsign</a>";
// 		$tailsignData['Tailsign'] = "<a href=\"AirlineDashboard.php?dashboardVisited=false&aircraftId=$acid\">$tailsign</a>";
		$tailsignData['Tailsign'] = "<a href=\"AircraftTimeline.php?aircraftVisited=false&aircraftId=$acid\">$tailsign</a>";
		$tailsignData['A/C Type'] = $type;
		$tailsignData['Platform'] = $platform;
		
		for($i=0 ; $i < count($dates) -1 ; $i++) { 
		    $count = 0;
			$start = $dates[$i];
			$end = $dates[$i+1];
			$query2 = "SELECT COUNT(*) AS count FROM (
							SELECT DISTINCT(a.idFlightLeg), a.flightNumber, a.departureAirportCode, a.arrivalAirportCode, a.createDate, a.lastUpdate 
							FROM $dbName.SYS_flight a
							INNER JOIN $dbName.SYS_flightPhase b
								ON a.idFlightLeg = b.idFlightLeg
								AND b.idFlightPhase IN ($flightPhases)
							AND a.createDate BETWEEN '$start' AND '$end'
							AND flightLeg LIKE 'OPP%'
						) AS t";
			$result2 = mysqli_query($dbConnection,$query2);
			if($result2) {
			    $row2 = mysqli_fetch_array($result2);
			    $count = $row2['count'];
			}
			
			$start = date('m/d', strtotime($start));
			$tailsignData[$start] = $count;
		}
		
		$tailsignsData[] = $tailsignData;
	}
}else{
	echo "Error $query :" . mysqli_error($dbConnection);
}

$data = array(
	'columns' => $columnsData,
	'tailsigns' => $tailsignsData
);

//echo json_encode($data);

# JSON-encode the response
echo $json_response = json_encode($data);

function createDateRangeArray($strDateFrom,$strDateTo)
{
    // takes two dates formatted as YYYY-MM-DD and creates an
    // inclusive array of the dates between the from and to dates.

    // could test validity of dates here but I'm already doing
    // that in the main script

    $aryRange=array();

    $iDateFrom=mktime(1,0,0,substr($strDateFrom,5,2),     substr($strDateFrom,8,2),substr($strDateFrom,0,4));
    $iDateTo=mktime(1,0,0,substr($strDateTo,5,2),     substr($strDateTo,8,2),substr($strDateTo,0,4));

    if ($iDateTo>=$iDateFrom)
    {
        array_push($aryRange,date('Y-m-d',$iDateFrom)); // first entry
        while ($iDateFrom<$iDateTo)
        {
            $iDateFrom+=86400; // add 24 hours
            array_push($aryRange,date('Y-m-d',$iDateFrom));
        }
    }
    return $aryRange;
}

function isNullOrEmptyString($str) {
    return (! isset($str) || trim($str) === '');
}

?>