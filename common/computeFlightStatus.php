<?php
include ("../database/connecti_database.php");
require_once "../common/flightSeatsDetails.php";
//require_once("validateUser.php");
//$approvedRoles = [$roles["engineer"]];
//$auth->checkPermission($hash, $approvedRoles);

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
function getFlightFirstClassStatus($dbName,$platform, $FlightLegId, $fClassSeats) {
	
	// If Status -1 then it's not computed
	$isStatus = - 1;
	// echo "Test";
	/**
	 * 1F/C Seat or more seats has service interruption in cruise mode for more than 30mns.
	 */
	if (isset($fClassSeats) && !is_null($fClassSeats) && $fClassSeats != '') {
		$firstClassSeatsWithSvdu = getFirstClassSeatsWithSvdu ( $fClassSeats );
		// $isFirstClass = 1;
		$firstCstatusEstimate_query = getQueryForFlightEstimationDetails ( $dbName,$platform, $firstClassSeatsWithSvdu, $FlightLegId, 0 );
		
		// echo "<br><br> Calculate Flight Status query:---$firstCstatusEstimate_query---</br></br>";
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
function getFlightBusinessClassStatus($dbName,$platform, $FlightLegId, $bClassSeats) {
	
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
		// echo "<br><br> Calculate Flight Status query:---$businessCEstimate_query---</br></br>";
		$result_set = mysqli_query ( $GLOBALS ['dbConnection'], $businessCEstimate_query );
		//file write

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
function getFlightEconomyClassStatus($dbName,$platform, $FlightLegId, $fClassSeats, $bClassSeats, $totalEconomyClassCount) {
	
	// If isStatus -1 then it's not computed
	$isStatus = - 1;
	if(!is_null($fClassSeats) && !is_null($bClassSeats) && !is_null($totalEconomyClassCount)){
	
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
function getHeadEndLrusStatus($db_Name, $platform, $idFlightLeg) {
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
	ON B.idFlightLeg = P.idFlightLeg";
	if($platform =='i5000') {
		$query .= " AND P.idFlightPhase IN (4,5)";
	}else{
		$query .= " AND P.idFlightPhase = 5";
	}
	$query .= " AND B.faultCode = 400
	AND B.idFlightLeg IN ($idFlightLeg)
	AND B.hostName IN ($seatsWithSvdu)";
	if($platform =='i5000') {
	$query .= " AND ( B.hostName LIKE 'VCC%'
	OR 
	B.hostName LIKE 'RCC%' )";
	}
	$query .= " AND (
	(B.monitorState = 3 AND B.detectionTime <= P.endTime)
	OR
	(B.monitorState = 1 AND TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime) > 30 AND ((B.detectionTime BETWEEN P.startTime AND P.endTime) OR (B.clearingTime BETWEEN P.startTime AND P.endTime) OR (B.detectionTime <= P.startTime AND B.clearingTime >= P.endTime)))
	)
	GROUP BY B.hostName";
	//echo "Query For Flight Estimation--->>>>$query---->>>>>";
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
	ON  B.idFlightLeg = P.idFlightLeg";
	if($platform =='i5000') {
		$query .=  " AND P.idFlightPhase IN (4,5)";
	}else{
		$query .=  " AND P.idFlightPhase = 5";
	}
	$query .= " AND B.faultCode = 400
	AND B.idFlightLeg IN ($idFlightLeg)";
	if ($fClassSeats == '') {
		$query .= " AND B.hostName NOT IN ($businessClassseatsWithSvdu)";
	} else {
		$query .= " AND B.hostName NOT IN ($fristClassSeatsWithSvdu)
		AND B.hostName NOT IN ($businessClassseatsWithSvdu)";
	}
	$query .= "  AND B.hostName NOT IN ($hadeanSVDUName)
	AND B.hostName LIKE 'SVDU%'";
	if($platform =='i5000') {
		$query .= " AND ( B.hostName LIKE 'VCC%'
		OR 
		B.hostName LIKE 'RCC%' )";
	}
	$query .= " AND (
	(B.monitorState = 3 AND B.detectionTime <= P.endTime)
	OR
	(B.monitorState = 1 AND TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime) > 30 AND ((B.detectionTime BETWEEN P.startTime AND P.endTime) OR (B.clearingTime BETWEEN P.startTime AND P.endTime) OR (B.detectionTime <= P.startTime AND B.clearingTime >= P.endTime)))
	)
	GROUP BY B.hostName";
	
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
	ON B.idFlightLeg = P.idFlightLeg";
	if ($platform == 'i5000') {
		$query .= " AND P.idFlightPhase IN (4,5)";
	}else{
		$query .= " AND P.idFlightPhase = 5";
	}
	$query .= " AND B.faultCode = 400
	AND B.idFlightLeg = '$idFlightLeg'
	AND 
	( B.hostName LIKE 'ADBG%' ";
	if ($platform == 'i5000') {
		$query .= " OR B.hostName LIKE 'AVCD%' ";
	} else {
		$query .= " OR B.hostName LIKE 'LAIC%' ";
	}
	$query .= "
	OR 
	B.hostName LIKE 'DSU%'
	)
	AND (
	(B.monitorState = 3 AND B.detectionTime <= P.endTime)
	OR
	(B.monitorState = 1 AND TIMESTAMPDIFF(MINUTE,B.detectionTime,B.clearingTime) > 30 AND ((B.detectionTime BETWEEN P.startTime AND P.endTime) OR (B.clearingTime BETWEEN P.startTime AND P.endTime) OR (B.detectionTime <= P.startTime AND B.clearingTime >= P.endTime)))
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
	ON B.idFlightLeg = P.idFlightLeg";
	if ($platform == 'i5000') {
		$query .= " AND P.idFlightPhase IN (4,5)";
	}else{
		$query .= " AND P.idFlightPhase = 5";
	}
	$query .= " AND B.faultCode = 400
	AND B.idFlightLeg = '$idFlightLeg'
	AND 
	( B.hostName LIKE 'ADBG%'";
	if ($platform == 'i5000') {
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
	(B.detectionTime BETWEEN P.startTime AND P.endTime) 
	OR 
	(B.clearingTime BETWEEN P.startTime AND P.endTime) 
	OR 
	(B.detectionTime <= P.startTime AND B.clearingTime >= P.endTime)
	)
	GROUP BY `hostName`
	HAVING (COUNT(`faultCode`) > 5 )
	ORDER BY `count` DESC";
	// echo "Query For Head End FREQUENCY Estimation:--->>>>$query---->>>>>";
	return $query;
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

?>
