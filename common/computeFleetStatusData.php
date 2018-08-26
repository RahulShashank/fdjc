<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
//require_once("validateUser.php");
//$approvedRoles = [$roles["engineer"]];
//$auth->checkPermission($hash, $approvedRoles);

require_once "../common/datesConfiguration.php";

//require_once "../common/computeFlightStatus.php";
require_once "../common/flightSeatsDetails.php";

$flightPhases = getFlightPhases();


///////////// COMPUTE STATUSES

$alertFailuresAvant =  array
	(
		array("DSU", 10044400001, 0),
		array("ADBG", 10025400001, 0),
		array("LAIC", 10045400001, 0)
	);

$alertFailuresI5000 =  array
	(
		array("AVCD", 10024400001, 0)
	);

$alertFailuresI8000 =  array
	(
	);


function computeFlightLegStatus($dbName, $platform, $flightLegId, $firstClassSeats, $businessClassSeats, $totalEconomyClassSeats) {
	//echo "Compute Inputs >>>".$dbName.">>>".$platform.">>>".$flightLegId.">>>".$firstClassSeats.">>>".$businessClassSeats.">>>".$totalEconomyClassSeats.">>>";
	
	//Don't compute flightStatus for the flightLeg, if the flightLeg is not in the phases (4,5).
	$query = "SELECT COUNT(*) AS count FROM $dbName.SYS_flightPhase WHERE idFlightPhase IN (4,5) AND idFlightLeg = $flightLegId ";
	$result= mysqli_query($GLOBALS['dbConnection'], $query);
	if($result){
		$row = mysqli_fetch_array($result);
		if($row['count'] == 0){
			//echo "Not Computing flightStatus for flightLeg : $flightLegId ";
			return;
		}
	}else{
		echo "Error ". mysqli_error($GLOBALS['dbConnection']);
	}
	
	
	$systemResetStatus = computeSystemResetStatus($dbName, $flightLegId);
	//$headEndStatus = computeHeadEndStatus($dbName, $platform, $flightLegId);
	
	//MBS : Invoking Basudev's api
	$headEndStatus = computeHeadEndLrusStatus($dbName, $platform, $flightLegId);

	// compute first, business, economy, 
	//MBS : Integration from Basudev's code
	$firstClassStatus = computeFlightFirstClassStatus($dbName,$platform, $flightLegId, $firstClassSeats);
	$businessClassStatus = computeFlightBusinessClassStatus($dbName,$platform, $flightLegId, $businessClassSeats);
	$economyClassStatus = computeFlightEconomyClassStatus($dbName,$platform, $flightLegId, $firstClassSeats, $businessClassSeats, $totalEconomyClassSeats);

	// Store statuses in database
	/*
	$query = "INSERT INTO flightStatus (idFlightLeg, systemResetStatus, headEndStatus, firstClassStatus, businessClassStatus, economyClassStatus)
			VALUES ($flightLegId, $systemResetStatus, $headEndStatus, -1, -1, -1)";
	*/
	$query = "INSERT INTO flightstatus (idFlightLeg, systemResetStatus, headEndStatus, firstClassStatus, businessClassStatus, economyClassStatus, connectivityStatus )
			VALUES ($flightLegId, $systemResetStatus, $headEndStatus, $firstClassStatus, $businessClassStatus, $economyClassStatus , -1 )";

			
	$result = mysqli_query($GLOBALS['dbConnection'],$query);
}

function computeSystemResetStatus($dbName, $flightLegId) {
	$status = 0;

	// Get the number of SVDUs
	$query = "SELECT count(*) AS 'count' 
				FROM $dbName.BIT_lru  a
				WHERE hostName LIKE 'SVDU%'
				AND a.lastUpdate = (
					SELECT MAX(b.lastUpdate)
					FROM $dbName.BIT_lru b
					WHERE a.hostName = b.hostName
				)";
	
	$result = mysqli_query($GLOBALS['dbConnection'], $query);
	if($result) {
		$row = mysqli_fetch_array($result);
		$nbLru = $row['count'];
	}
	
	// Create system restarts (computation is done for an interval of 5 minutes / 300 seconds)
	// Use filter inputs for this request in the where close
	// But it looks putting the flight leg condition on this query will reduce the number of count
	// So I don't filter on the flight lef id but after in the results
	$query = " SELECT count(*) AS 'count', lastUpdate
				FROM (
					SELECT a.idFlightLeg, eventData, a.lastUpdate 
					FROM $dbName.BIT_events a
					INNER JOIN $dbName.SYS_flightPhase b
                    ON a.idFlightLeg = b.idFlightLeg
                    AND b.idFlightPhase IN (4,5)
                    AND a.lastUpdate >= b.startTime AND a.lastUpdate <= b.endTime
					AND a.idFlightLeg IN ($flightLegId)
				) AS a
				WHERE eventData LIKE 'SVDU%'
				GROUP BY UNIX_TIMESTAMP(lastUpdate) DIV 300 ORDER BY lastUpdate";
	
	$result = mysqli_query($GLOBALS['dbConnection'], $query);
	if($result) {
		$threshold = $nbLru * getSystemResetSvduRatio() / 100;
		while ($row = mysqli_fetch_array($result)) {
			$count = $row['count'];
			// echo "1<br>";
			if($count >= $threshold) {
				$status = 2;
			}
		}
	} else {
			echo "Error: " . mysqli_error($GLOBALS['dbConnection']) . " - $query";
		}

	return $status;
}


function computeHeadEndStatus($dbName, $platform, $flightLegId) {
	$status = 0;

	if($platform == 'AVANT') {
		$alertFailures = $GLOBALS['alertFailuresAvant'];
	} else if($platform == 'i5000') {
		$alertFailures = $GLOBALS['alertFailuresI5000'];
	} else if($platform == 'i8000') {
		$alertFailures = $GLOBALS['alertFailuresI8000'];
	}

	foreach ($alertFailures as $alertFailure) {
		$query = getQueryForFailure($dbName, $flightLegId, $alertFailure[0], $alertFailure[1], $alertFailure[2]);
		$result = mysqli_query ($GLOBALS['dbConnection'], $query );
		if($result) {
			$row = mysqli_fetch_array($result);
			$count = $row['count'];
			if($count > 0) {
				$status = 2;
				break;
			}
		} else {
			echo "Error: " . mysqli_error($GLOBALS['dbConnection']) . " - $query";
		}
	}

	if($status < 2) {
		// do warning failures
	}

	return $status;
}

function getQueryForFailure($dbName, $flightLegId, $unitType, $failureCodes, $frequency, $monitorState = '1,3') {
	$query = "SELECT count(*) AS 'count'
		FROM(
			SELECT accusedHostName, COUNT(DISTINCT(idFailure)) AS count
			FROM $dbName.BIT_failure a
			INNER JOIN $dbName.SYS_flightPhase b
			ON a.idFlightLeg = b.idFlightLeg
			AND b.idFlightPhase IN (" . $GLOBALS['flightPhases'] .")
			AND accusedHostName LIKE '$unitType%' 
			AND a.idFlightLeg IN ($flightLegId)
			AND a.failureCode IN ($failureCodes) 
			AND monitorState IN ($monitorState) 
			AND correlationDate >= b.startTime
			AND correlationDate <= b.endTime
			GROUP BY accusedHostName 
			HAVING count >= $frequency
		) AS t";

	return $query;
}


///////////// RETRIEVE STATUSES

function getAirlineStatus($airlineId) {
	$status = -1;

	$query = "SELECT id, databaseName
			FROM aircrafts
			WHERE airlineId=$airlineId";

	$result = mysqli_query ($GLOBALS['dbConnection'], $query );
	if($result) {
		while ($row = mysqli_fetch_array($result)) {
			$aircraftId = $row['id'];
			$dbName = $row['databaseName'];

			$status = getAircraftStatus($aircraftId, $dbName);

			if($status == 2) {
				break;
			}
		}
	} else {
		echo "Error: " . mysqli_error($GLOBALS['dbConnection']);
	}

	return $status;
}


function getAircraftStatus($aircraftId, $dbName) {
	$status = -1;

	$flightLegs = getLatestFlightLegs($dbName);

	if($flightLegs) {
		$status = getFlightStatus($dbName, $flightLegs);
	}

	// Note: if we don't find any flight legs for the searched period, that means we have missing data.
	// So the status will be displayed as grey for that aircraft

	return $status;
}


function getFlightStatus($dbName, $flightLegs) {
	$status = -1;

	$query = "SELECT systemResetStatus, headEndStatus, firstClassStatus, businessClassStatus, economyClassStatus
				FROM $dbName.flightstatus
				WHERE idFlightLeg IN ($flightLegs)";
	$result = mysqli_query ($GLOBALS['dbConnection'], $query );

	if($result) {
		while ($row = mysqli_fetch_array($result)) {
			if($row['systemResetStatus'] == 2 || $row['headEndStatus'] == 2 || $row['firstClassStatus'] == 2 || $row['businessClassStatus'] == 2 || $row['economyClassStatus'] == 2) {
				$status = 2;
				break;
			} else {
				$status = 0;
			}
		}
	} else {
		echo "error with $query : ". mysqli_error($GLOBALS['dbConnection']);
	}

	return $status;
}

function getAircraftDetailedStatus($dbName) {
	$flightLegs = getLatestFlightLegs($dbName);
	
	if($flightLegs) {
		// $headEndStatus = getFlightDetailedStatus($dbName, $flightLegs, "headEndStatus");
		// $systemResetStatus = getFlightDetailedStatus($dbName, $flightLegs, "systemResetStatus");
		// $firstClassStatus = getFlightDetailedStatus($dbName, $flightLegs, "firstClassStatus");
		// $businessClassStatus = getFlightDetailedStatus($dbName, $flightLegs, "businessClassStatus");
		// $economyClassStatus = getFlightDetailedStatus($dbName, $flightLegs, "economyClassStatus");
		
		$allStatus = getFlightDetailedStatusForFlightlegs($dbName, $flightLegs);
		
		$headEndStatus = $allStatus['headEndStatus'];
		$systemResetStatus = $allStatus['systemResetStatus'];
		$firstClassStatus = $allStatus['firstClassStatus'];
		$businessClassStatus = $allStatus['businessClassStatus'];
		$economyClassStatus = $allStatus['economyClassStatus'];
		$connectivityStatus = $allStatus['connectivityStatus'];
	} else {
		// Note: if we don't find any flight legs for the searched period, that means we have missing data.
		// So the status will be displayed as grey for that aircraft

		$headEndStatus = -1;
		$systemResetStatus = -1;
		$firstClassStatus = -1;
		$businessClassStatus = -1;
		$economyClassStatus = -1;
		$connectivityStatus = -1;
	}

	$statuses['headEndStatus'] = $headEndStatus;
	$statuses['systemResetStatus'] = $systemResetStatus;
	$statuses['firstClassStatus'] = $firstClassStatus;
	$statuses['businessClassStatus'] = $businessClassStatus;
	$statuses['economyClassStatus'] = $economyClassStatus;
	$statuses['connectivityStatus'] = $connectivityStatus;

	return $statuses;
}

// Get latest flights for that aircraft
function getLatestFlightLegs($dbName) {
	$end = time();
	$start = strtotime('-' . $GLOBALS['aircraftStatusPeriod'] . ' days', $end);
	$start = date("Y-m-d H:i", $start);
	$end = date("Y-m-d H:i", $end);
	
	$flightLegs = "";

	$query = "SELECT DISTINCT a.idFlightLeg
              FROM $dbName.SYS_flight a
              INNER JOIN $dbName.SYS_flightPhase b
              ON a.idFlightLeg = b.idFlightLeg
              AND b.idFlightPhase IN (" . $GLOBALS['flightPhases'] .")
              AND (
                  ( '$start' <= a.createDate AND '$end' >= a.lastUpdate)
              OR
                  ( '$start' <= a.lastUpdate AND '$end' >= a.createDate)
                 )
             ORDER BY a.createDate DESC";
    
    $result = mysqli_query ($GLOBALS['dbConnection'], $query );
	if($result) {
		$i = 0;
		while ($row = mysqli_fetch_array($result)) {
			if($i>0){
				$flightLegs .= ",";
			}
			$flightLegs .= $row['idFlightLeg'];
			$i++;
		}
	} else {
		echo "Error: " . mysqli_error($GLOBALS['dbConnection']);
	}

	return $flightLegs;
}

function getFlightDetailedStatus($dbName, $flightLegs, $option) {
	$status = 0;

	$query = "SELECT $option
				FROM $dbName.flightstatus
				WHERE idFlightLeg IN ($flightLegs)";

	$result = mysqli_query ($GLOBALS['dbConnection'], $query );
	if(!$result) {
		 echo $query;exit;
	}

	if(mysqli_num_rows($result) > 0) {
		while($row = mysqli_fetch_array($result)) {
			if($row["$option"] == 2) {
				$status = 2;
				break;
			}
		}
	} else {
		$status = -1;
	}
	

	return $status;
}

/**
 * Function which returns the status of flight(headEndStatus, systemResetStatus, firstClassStatus, businessClassStatus, economyClassStatus) for the given flight legs. This is the optimized implementation of getFlightDetailedStatus() 
 * @param unknown $dbName
 * @param unknown $FlightLegId
 * @return array - array of status for headEndStatus, systemResetStatus, firstClassStatus, businessClassStatus, economyClassStatus (If isStatus -1 then it's not computed),2(KO) and 0(OK)
 */
function getFlightDetailedStatusForFlightlegs($dbName, $flightLegs) {

	$query = "SELECT headEndStatus, systemResetStatus, firstClassStatus, businessClassStatus, economyClassStatus, connectivityStatus FROM $dbName.flightstatus
				WHERE idFlightLeg IN ($flightLegs)";

	$result = mysqli_query ($GLOBALS['dbConnection'], $query );
	if(!$result) {
		 echo "Error in $query : " . mysqli_error($GLOBALS['dbConnection']);exit;
	}

	$flightStatus = array();
	while($row = mysqli_fetch_array($result)) {
			array_push($flightStatus , $row);
	}
	
	// default status of -1 ,if there is no data.
	$allStatus = array('headEndStatus' => -1, 'systemResetStatus' => -1, 'firstClassStatus' => -1, 'businessClassStatus' => -1, 'economyClassStatus' => -1, 'connectivityStatus' => -1);

	foreach($flightStatus as $status){
		if($allStatus['headEndStatus'] < $status['headEndStatus'])
			$allStatus['headEndStatus'] = $status['headEndStatus'];
		if($status['headEndStatus'] == 2)
			break; // break if status is 2
	}
	
	reset($flightStatus);

	foreach($flightStatus as $status){
		if($allStatus['systemResetStatus'] < $status['systemResetStatus'])
			$allStatus['systemResetStatus'] = $status['systemResetStatus'];
		if($status['systemResetStatus'] == 2)
			break; // break if status is 2
	}

	reset($flightStatus);
	
	foreach($flightStatus as $status){
		if($allStatus['firstClassStatus'] < $status['firstClassStatus'])
			$allStatus['firstClassStatus'] = $status['firstClassStatus'];
		if($status['firstClassStatus'] == 2)
			break; // break if status is 2
	}
	
	reset($flightStatus);

	foreach($flightStatus as $status){
		if($allStatus['businessClassStatus'] < $status['businessClassStatus'])
			$allStatus['businessClassStatus'] = $status['businessClassStatus'];
		if($status['businessClassStatus'] == 2)
			break; // break if status is 2
	}
	
	reset($flightStatus);

	foreach($flightStatus as $status){
		if($allStatus['economyClassStatus'] < $status['economyClassStatus'])
			$allStatus['economyClassStatus'] = $status['economyClassStatus'];
		if($status['economyClassStatus'] == 2)
			break; // break if status is 2
	}
	
	reset($flightStatus);

	foreach($flightStatus as $status){
		if($allStatus['connectivityStatus'] < $status['connectivityStatus'])
			$allStatus['connectivityStatus'] = $status['connectivityStatus'];
		if($status['connectivityStatus'] == 2)
			break; // break if status is 2
	}
	
	return $allStatus;
}

//MBS : Code from computeFlightStatus.php : START

// include (dirname ( __FILE__ ) . '/database/connecti_database.php');
// include (dirname ( __FILE__ ) . '/common/flightSeatsDetails.php');
// include ("../common/functions.php");
// require_once "../common/generateAircraftWordReport.php";
/**
 * First Class Seats Status information Calculation.
 * @param unknown $dbName
 * @param unknown $FlightLegId
 * @param unknown $fClassSeats-
 * @return number - -1(If isStatus -1 then it's not computed),2(it's KO) and 0(means it's OK)
 */
function computeFlightFirstClassStatus($dbName,$platform, $FlightLegId, $fClassSeats) {
	
	// If Status -1 then it's not computed
	$isStatus = - 1;
	// echo "Test";
	/**
	 * 1F/C Seat or more seats has service interruption in cruise mode for more than 30mns.
	 */
	if ( isset($fClassSeats) && !is_null($fClassSeats) && $fClassSeats != '') {
		$firstClassSeatsWithSvdu = getFirstClassSeatsWithSvdu ( $fClassSeats );
		// $isFirstClass = 1;
		$firstCstatusEstimate_query = getQueryForFlightEstimationDetails ( $dbName,$platform, $firstClassSeatsWithSvdu, $FlightLegId, 0 );
		
		//echo "<br><br> Calculate Flight Status query:---$firstCstatusEstimate_query---</br></br>";
		$result_OfStatusEstimate = mysqli_query ( $GLOBALS ['dbConnection'], $firstCstatusEstimate_query );
		
		if ($result_OfStatusEstimate != false) {
			$count = mysqli_num_rows ( $result_OfStatusEstimate );
			if ($count >= 1) {
				$isStatus = 2; // means it's KO and it's Red
			} else {
				$isStatus = 0; // means it's OK and it's Green.
			}
		}
	}
	return $isStatus;
}
/**
 * Business Class Seats Status information Calculation.
 * @param unknown $dbName
 * @param unknown $FlightLegId
 * @param unknown $bClassSeats
 * @return number- -1(If isStatus -1 then it's not computed),2(it's KO) and 0(means it's OK)
 */
function computeFlightBusinessClassStatus($dbName,$platform, $FlightLegId, $bClassSeats) {
	
	// If isStatus -1 then it's not computed
	$isStatus = - 1;
	/**
	 * 3 or more of B/C seats has service interruption in cruise mode for more than 30mns.
	 */
	if (isset($bClassSeats) && !is_null($bClassSeats) && $bClassSeats != '') {
		
		$secondClassseatsWithSvdu = getBusinessClassSeatsWithSvdu ( $bClassSeats );
		// echo "--->>$secondClassseatsWithSvdu-->>";
		// $isBusinessClass = 2;
		$businessCEstimate_query = getQueryForFlightEstimationDetails ( $dbName,$platform, $secondClassseatsWithSvdu, $FlightLegId, 0 );
		
		//echo "<br><br> Calculate Flight Status query:---$businessCEstimate_query---</br></br>";
		$result_set = mysqli_query ( $GLOBALS ['dbConnection'], $businessCEstimate_query );
		if ($result_set != false) {
			$rowCount = mysqli_num_rows ( $result_set );
			
			if ($rowCount >= 3) {
				// echo "test2";
				$isStatus = 2; // means it's KO and it's Red
					               // $flightClasss [$isBusinessClass] = $isBClassStatusOk;
			} else {
				
				$isStatus = 0; // means it's OK and it's Green.
			// echo ">>>Else--$isStatusOk>>>>";
			}
		}
	} // End of Business Class Query
	return $isStatus;
}
/**
 * Economy Class Seats Status information Calculation.
 * @param unknown $dbName
 * @param unknown $FlightLegId
 * @param unknown $fClassSeats
 * @param unknown $bClassSeats
 * @param unknown $totalEconomyClassCount
 * @return number - -1(If isStatus -1 then it's not computed),2(it's KO) and 0(means it's OK)
 */
function computeFlightEconomyClassStatus($dbName,$platform, $FlightLegId, $fClassSeats, $bClassSeats, $totalEconomyClassCount) {
	
	// If isStatus -1 then it's not computed
	$isStatus = - 1;
	
	if(!is_null($fClassSeats) && !is_null($bClassSeats) && !is_null($totalEconomyClassCount)) {
	
		$fristClassSeatsWithSvdu = '';
		if ($fClassSeats != '') {
			$fristClassSeatsWithSvdu = getFirstClassSeatsWithSvdu ( $fClassSeats );
		}
		
		// echo "--->>>.$seatsWithSvdu--->>>";
		$businessClassseatsWithSvdu = getBusinessClassSeatsWithSvdu ( $bClassSeats );
		
		$headEndSVDU = array ();
		$economyClassSeatsCount = '';
		/**
		 * 10% or more of E/C seats has service interruption in cruise mode for more than 30mns.
		 */
		
		/**
		 * Query to retrieve Hadean Seats Name .
		 */
		$query_ForHeadEndSVDU = "SELECT  DISTINCT `hostName` FROM $dbName.BIT_lru  WHERE `hostName` LIKE 'SVDU____'";
		
		// echo "<br><br> HADEANSVDU:---$query_ForHadeanSVDU---</br></br>";
		
		$result = mysqli_query ( $GLOBALS ['dbConnection'], $query_ForHeadEndSVDU );
		
		while ( $row = mysqli_fetch_array ( $result ) ) {
			if ($row ['hostName'] != '') {
				$headEndSVDU [] = $row ['hostName'];
			}
		}
		$headEndSVDUName = "'" . implode ( "','", $headEndSVDU ) . "'";
		
		// echo "HADEANSVDU:-$hadeanSVDUName";
		
		/**
		 * Query for economy class total seats count.
		 * It's a temporary solutions because we are getting wrong economy class seats count from database for A380 Aircarft.AS per seatguru A380 Aircraft has 461 standard Economy class seats,based on this value I am calculating
		 * 10% of economy class seats for A380 Aircraft.
		 */
		if ($fClassSeats == '') {
			
			$query_ForEconomyClassSeatsCount = "SELECT  DISTINCT `hostName` FROM $dbName.BIT_lru WHERE";
			/*
			 * if ($fClassSeats != '') {
			 * $query_ForEconomyClassSeatsCount .= " `hostName` NOT IN ($seatsWithSvdu)";
			 * } else {
			 */
			$query_ForEconomyClassSeatsCount .= " `hostName` NOT IN ($businessClassseatsWithSvdu)
					 AND `hostName` NOT LIKE 'SVDU____'
					 AND `hostName` LIKE 'SVDU%'";
			// }
			// echo "<br><br> Economy Class Seats Count:---$query_ForEconomyClassSeatsCount---</br></br>";
			
			$result_setOfEconomyClassSeatsCount = mysqli_query ( $GLOBALS ['dbConnection'], $query_ForEconomyClassSeatsCount );
			if ($result_setOfEconomyClassSeatsCount != false) {
				$result_setRowCout = mysqli_num_rows ( $result_setOfEconomyClassSeatsCount );
				$economyClassSeatsCount = number_format ( ($result_setRowCout * 10) / 100 ); // we need to calculate 10% of economy class seats.
																								 // echo ">>>> $economyClassSeatsCount >>>>>";
			}
		}  // End of A350 Aircraft.
		else {
			$economyClassSeatsCount = number_format ( ($totalEconomyClassCount * 10) / 100 );
			// echo ">>>> $economyClassSeatsCount >>>>>";
		}
		
		// Start Economy Class Flight Estimation Details
		// $isEconomyClass = 3;
		
		$economyCEstimate_query = getQueryForEconomyFlightEstimationDetails ( $dbName,$platform, $fClassSeats, $headEndSVDUName, $fristClassSeatsWithSvdu, $businessClassseatsWithSvdu, $FlightLegId, 0 );
		
		//echo "<br><br> Calculate Flight Status query:---$economyCEstimate_query---</br></br>";
		$result_set_EconomyClass = mysqli_query ( $GLOBALS ['dbConnection'], $economyCEstimate_query );
		if ($result_set_EconomyClass != false) {
			$rowCount_EC = mysqli_num_rows ( $result_set_EconomyClass );
			if ($rowCount_EC >= $economyClassSeatsCount) {
				$isStatus = 2; // means it's KO and it's Red.
			} else {
				
				$isStatus = 0; // means it's OK and it's Green.
			}
		}
	
	}
	
	return $isStatus;
}
/**
 * This function will provides First Class SVDU Names.
 * @param unknown $fClassSeats
 * @return string
 */
function getFirstClassSeatsWithSvdu($fClassSeats) {
	
	// Get First Class Seats Name
	$fCSeats = getFlightSeats ( $fClassSeats );
	
	/*
	 * If Array contains String then use this $seatsWithSvdu = "'" . implode("','",$fCSeats) . "'";
	 * otherwise $seatsWithSvdu = implode(",",$fCSeats);
	 */
	
	$seatsWithSvdu = "'" . implode ( "','", $fCSeats ) . "'";
	// echo "-----First:-$seatsWithSvdu----";
	
	return $seatsWithSvdu;
}
/**
 * This function will provides Business Class SVDU Names.
 * @param unknown $bClassSeats
 * @return string
 */
function getBusinessClassSeatsWithSvdu($bClassSeats) {
	
	// Get Business Class Seats Name
	$secondCSeats = getFlightSeats ( $bClassSeats );
	
	/*
	 * If Array contains String then use this $seatsWithSvdu = "'" . implode("','",$fCSeats) . "'";
	 * otherwise $seatsWithSvdu = implode(",",$fCSeats);
	 */
	
	// Second Class Seats information.
	$businessClassseatsWithSvdu = "'" . implode ( "','", $secondCSeats ) . "'";
	// echo "-----Second:-$secondClassseatsWithSvdu----";
	
	return $businessClassseatsWithSvdu;
}

/**
 * Provide Head End Status Information
 * 
 * @param unknown $commonDbName        	
 * @param unknown $db_Name        	
 * @param unknown $platform
 *        	- Currently I considered This for AVANT And I5000.
 * @param unknown $idFlightLeg        	
 * @return number - HeadEnd Status -1(If isStatus -1 then it's not computed),2(means it's KO) and 0(means it's OK)
 */
function computeHeadEndLrusStatus($db_Name, $platform, $idFlightLeg) {
	// If isStatus -1 then it's not computed
	$isStatus = - 1;
	$headEndLruEstimation_Query = getQueryForHeadEndFlightEstimationDetails ( $db_Name, $platform, $idFlightLeg );
	// echo "QueryOfHeadENDLRU-->>>>$headEndLruEstimation_Query--->>>>";
	$result_OfHeadEndLruEstimation = mysqli_query ( $GLOBALS ['dbConnection'], $headEndLruEstimation_Query );
	if ($result_OfHeadEndLruEstimation != false) {
		$rowCount_OfHeadEnd = mysqli_num_rows ( $result_OfHeadEndLruEstimation );
		if ($rowCount_OfHeadEnd > 0) {
			$isStatus = 2; // means it's KO and it's Red.
				               // echo "Test";
		} else {
			
			$isStatus = 0; // means it's OK and it's Green.
		}
	}
	return $isStatus;
}

/**
 * Provide Head End Frequency Status Information
 * 
 * @param unknown $commonDbName        	
 * @param unknown $db_Name        	
 * @param unknown $platform
 *        	- Currently I considered This for AVANT And I5000.
 * @param unknown $idFlightLeg        	
 * @return number - HeadEnd Status -1(If isStatus -1 then it's not computed),2(means it's KO) and 0(means it's OK)
 */
function getHeadEndLrusFrequencyStatus($db_Name, $platform, $idFlightLeg) {
	// If isStatus -1 then it's not computed
	$isStatus = - 1;
	$headEndLruFrequencyEstimation_Query = getQueryForHeadEndFaultFrequency ( $db_Name, $platform, $idFlightLeg );
	 //echo "QueryOfHeadENDLRUFrequency-->>>>$headEndLruFrequencyEstimation_Query--->>>>";
	$result_OfHeadEndLruFrequencyEstimation = mysqli_query ( $GLOBALS ['dbConnection'], $headEndLruFrequencyEstimation_Query );
	if ($result_OfHeadEndLruFrequencyEstimation != false) {
		$rowCount_OfHeadEnd = mysqli_num_rows ( $result_OfHeadEndLruFrequencyEstimation );
		if ($rowCount_OfHeadEnd > 0) {
			$isStatus = 2; // means it's KO and it's Red.
				               // echo "Test2";
		} else {
			
			$isStatus = 0; // means it's OK and it's Green.
		}
	}
	return $isStatus;
}
/**
 * Query For First Class and Business Class.
 *
 * @param unknown $db_Name        	
 * @param unknown $seatsWithSvdu        	
 * @param unknown $idFlightLeg        	
 * @param unknown $count        	
 * @return string
 */
function getQueryForFlightEstimationDetails($db_Name,$platform, $seatsWithSvdu, $idFlightLeg, $count) {
	$query = "SELECT B.hostName, B.idFlightLeg
	FROM $db_Name.BIT_fault B
	INNER JOIN $db_Name.SYS_flightPhase P
	ON B.idFlightLeg = P.idFlightLeg
	AND P.idFlightPhase in (4,5)
	AND B.faultCode = 400
	AND B.idFlightLeg IN ($idFlightLeg)
	AND B.hostName IN ($seatsWithSvdu)";
	if($platform =='i5000' || $platform == 'i8000') {
	$query .= " AND ( B.hostName LIKE 'VCC%'
	OR 
	B.hostName LIKE 'RCC%' )";
	}
	$query .= " AND (
	B.monitorState IN (3)
	OR
	(TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime) > 30 AND B.monitorState IN (1))
	)
    AND
	(
	B.detectionTime >= P.startTime
	OR
	B.detectionTime <= P.endTime
	)
	 GROUP BY B.hostName ";
	// echo "Query For Flight Estimation--->>>>$query---->>>>>";
	return $query;
	// GROUP BY `hostName`
}
/**
 * Query for economy class.
 *
 * @param unknown $db_Name        	
 * @param unknown $hadeanSVDUName        	
 * @param unknown $seatsWithSvdu        	
 * @param unknown $secondClassseatsWithSvdu        	
 * @param unknown $idFlightLeg        	
 * @param unknown $count        	
 * @return string
 */
function getQueryForEconomyFlightEstimationDetails($db_Name,$platform, $fClassSeats, $hadeanSVDUName, $fristClassSeatsWithSvdu, $businessClassseatsWithSvdu, $idFlightLeg, $count) {
	$query = "SELECT B.hostName, B.idFlightLeg
	FROM $db_Name.BIT_fault B
	INNER JOIN $db_Name.SYS_flightPhase P
	ON  B.idFlightLeg = P.idFlightLeg
	AND P.idFlightPhase in (4,5)
	AND B.faultCode = 400
	AND B.idFlightLeg IN ($idFlightLeg)
	";
	if ($fClassSeats == '') {
		$query .= " AND B.hostName NOT IN ($businessClassseatsWithSvdu)";
	} else {
		$query .= " AND B.hostName NOT IN ($fristClassSeatsWithSvdu)
		AND B.hostName NOT IN ($businessClassseatsWithSvdu)";
	}
	$query .= "  AND B.hostName NOT IN ($hadeanSVDUName)
	AND B.hostName LIKE 'SVDU%'";
	if($platform =='i5000' || $platform == 'i8000') {
		$query .= " AND ( B.hostName LIKE 'VCC%'
		OR 
		B.hostName LIKE 'RCC%' )";
	}
	$query .= " AND (
	B.monitorState IN (3)
	OR
	(TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime) > 30 AND B.monitorState IN (1))
	)
	AND
	(
	B.detectionTime >= P.startTime
	OR
	B.detectionTime <= P.endTime
	)
	GROUP BY B.hostName ";
	
	// echo "Query For Economy Class Estimation--->>>>$query---->>>>>";
	return $query;
	// GROUP BY `hostName`
}

/**
 * Query for Flight HeadEnd Status Calculation.
 * 
 * @param unknown $commonDbName        	
 * @param unknown $db_Name        	
 * @param unknown $idFlightLeg        	
 * @return string - Reported hostName with count(Number of times it's match the condition)
 */
function getQueryForHeadEndFlightEstimationDetails($db_Name, $platform, $idFlightLeg) {
	$query = " SELECT B.hostName, COUNT(hostName) AS count ,B.reportingHostName, B.idFlightLeg,B.detectionTime,B.clearingTime
	FROM $db_Name.BIT_fault B
	INNER JOIN $db_Name.SYS_flightPhase P
	ON B.idFlightLeg = P.idFlightLeg
	AND P.idFlightPhase in (4,5)
	AND B.faultCode = 400
	AND B.idFlightLeg = '$idFlightLeg'
	AND 
	( B.hostName LIKE 'ADBG%' ";
	if ($platform == 'i5000' || $platform == 'i8000') {
		$query .= " OR B.hostName LIKE 'AVCD%' ";
	} else {
		$query .= " OR B.hostName LIKE 'LAIC%' ";
	}
	$query .= "
	OR 
	B.hostName LIKE 'DSU%'
	)
	AND 
	(
	B.monitorState IN (3)
	OR
	(B.monitorState IN (1) AND TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime) > 30 )
	)
	AND
	(
	B.detectionTime >= P.startTime
	OR
	B.detectionTime <= P.endTime
	)
	GROUP BY `reportingHostName`";
	// echo "Query For Head End Estimation:--->>>>$query---->>>>>";
	return $query;
}

/**
 * It's Part of the Frequency of the fault And now it's for 400.
 *
 * @param unknown $db_Name
 *        	- Bite Database or SqlDatabase.
 * @param unknown $faultCode
 *        	- it's now 400.
 * @param unknown $idFlightLeg
 *        	- It's a dynamic value.
 */
function getQueryForHeadEndFaultFrequency($db_Name, $platform, $idFlightLeg) {
	$query = "SELECT B.hostName, B.faultCode, COUNT(faultCode) AS count, B.idFlightLeg
	FROM $db_Name.BIT_fault B
	INNER JOIN $db_Name.SYS_flightPhase P
	ON B.idFlightLeg = P.idFlightLeg
	INNER JOIN $db_Name.SYS_flight C
	ON B.idFlightLeg = C.idFlightLeg
	AND P.idFlightPhase = 5
	AND B.faultCode = 400
	AND B.idFlightLeg = '$idFlightLeg'
	AND 
	( B.hostName LIKE 'ADBG%'
	";
	if ($platform == 'i5000' || $platform == 'i8000') {
		$query .= " OR B.hostName LIKE 'AVCD%' ";
	} else {
		$query .= " OR B.hostName LIKE 'LAIC%' ";
	}
	$query .= "
	OR 
	B.hostName LIKE 'DSU%'
	)
	AND (
	TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime) < 30
	)
	AND
	(
	B.detectionTime >= P.startTime
	OR
	B.detectionTime <= P.endTime
	)
	GROUP BY `hostName`
	HAVING (COUNT(`faultCode`) > 5 )
	ORDER BY `count` DESC";
	// echo "Query For Head End FREQUENCY Estimation:--->>>>$query---->>>>>";
	return $query;
}

//MBS : Code from computeFlightStatus.php : END


function computeAndUpdateAircraftStatus($aircraftDB, $mainDB, $aircraftId, $tailsign, $datetime){

	$aircraftStatus = getAircraftDetailedStatus($aircraftDB);
	$overallAircraftStatus = -1;
	if($aircraftStatus['headEndStatus'] == -1 && $aircraftStatus['systemResetStatus'] == -1 &&  $aircraftStatus['firstClassStatus'] == -1 &&  $aircraftStatus['businessClassStatus'] == -1 &&  $aircraftStatus['economyClassStatus'] == -1 && $aircraftStatus['connectivityStatus'] == -1 ) {
		$overallAircraftStatus = -1;
	} else if($aircraftStatus['headEndStatus'] == 2 || $aircraftStatus['systemResetStatus'] == 2 ||  $aircraftStatus['firstClassStatus'] == 2 ||  $aircraftStatus['businessClassStatus'] == 2 ||  $aircraftStatus['economyClassStatus'] == 2 ||  $aircraftStatus['connectivityStatus'] == 2 ) {
		$overallAircraftStatus = 2;
	} else if($aircraftStatus['headEndStatus'] == 1 || $aircraftStatus['systemResetStatus'] == 1 ||  $aircraftStatus['firstClassStatus'] == 1 ||  $aircraftStatus['businessClassStatus'] == 1 ||  $aircraftStatus['economyClassStatus'] == 1 ||  $aircraftStatus['connectivityStatus'] == 1 ) {
		$overallAircraftStatus = 1;
	}  else {
		$overallAircraftStatus = 0;
	}
	
	$selected = mysqli_select_db($GLOBALS['dbConnection'],$mainDB)
		or die("Could not select ".$mainDB);
	
	//MBS: lastStatusComputed is derived from the last file processed. Alternative for finding last flightLeg processed. Confirm this once.
	$query = "UPDATE $mainDB.aircrafts SET systemResetStatus = ".$aircraftStatus['systemResetStatus'].", headEndStatus = ".$aircraftStatus['headEndStatus'].", firstClassStatus = ".$aircraftStatus['firstClassStatus'].", businessClassStatus = ".$aircraftStatus['businessClassStatus'].", economyClassStatus = ".$aircraftStatus['economyClassStatus'].", connectivityStatus = ".$aircraftStatus['connectivityStatus'].", status = ".$overallAircraftStatus.", lastStatusComputed = '".$datetime."' ";
	
	if(!isset($aircraftId)) {
		$query .= "WHERE tailsign='$tailsign'";
	}  else {
		$query .= "WHERE id = $aircraftId";
	}
	//echo "<br/>Aircraft Status Update Query : " . $query;
	$result = mysqli_query($GLOBALS['dbConnection'],$query);
	if(!$result){
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ko.png\" style=\"vertical-align:top\"> Error updating Status in <b>$mainDB.aircrafts</b> for query $query. Error: " . mysqli_error($GLOBALS['dbConnection']) . "<br>";
	}
	
	mysqli_commit($GLOBALS['dbConnection']);

}

function computeAndUpdateAirlineStatus($mainDB, $airlineId, $datetime){

	$query = "SELECT id, status, lastStatusComputed FROM $mainDB.aircrafts WHERE airlineId=".$airlineId;
	$result = mysqli_query($GLOBALS['dbConnection'],$query);
	$fleetStatusPeriod = $GLOBALS['fleetStatusPeriod'];
	$dateTimeThreshold = date_modify(new DateTime(), "- $fleetStatusPeriod day");
	$airlineStatus = -1;
	if($result){
		while($row = mysqli_fetch_array($result)){
			$lastStatusComputedDateTime = new DateTime( $row['lastStatusComputed'] );
			if( $lastStatusComputedDateTime > $dateTimeThreshold ){
				if($airlineStatus < $row['status'])
					$airlineStatus = $row['status'];
				
				if($row['status'] == 2){
					break;	//Break, if any of the aircraft status is known to be '2' for this airline
				}
			}
		}
	}else{
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ko.png\" style=\"vertical-align:top\"> Error fetching Status in <b>$mainDB.airlines</b> for query $query. Error: " . mysqli_error($GLOBALS['dbConnection']) . "<br>";
	}
	
	//Update Airline Status
	//TODO : Check the usage of $datetime for setting lastStatusComputed column
	$query = "UPDATE $mainDB.airlines SET status=".$airlineStatus.", lastStatusComputed='".$datetime."' WHERE id = ".$airlineId;
	$result = mysqli_query($GLOBALS['dbConnection'],$query);
	//echo "<br/>Airline Status Update Query : " . $query;
	if(!$result){
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src=\"../img/ko.png\" style=\"vertical-align:top\"> Error updating Status in <b>$mainDB.airlines</b> for query $query. Error: " . mysqli_error($GLOBALS['dbConnection']) . "<br>";
	}
	
	mysqli_commit($GLOBALS['dbConnection']);

}

?>
