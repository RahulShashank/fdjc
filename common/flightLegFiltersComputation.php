<?php
//require_once("validateUser.php");
//$approvedRoles = [$roles["engineer"]];
//$auth->checkPermission($hash, $approvedRoles);

function getMinDateTime() {
	global $dbConnection;
	$query = "SELECT createDate FROM SYS_flight ORDER BY createDate ASC LIMIT 1";
    $result = mysqli_query($dbConnection,$query);
    if($result != null) { 
        $row = mysqli_fetch_array($result);
        $minDateTime = $row['createDate'];
    }
	
	return $minDateTime;
}

function getMaxDateTime($airline) {
	global $dbConnection;
	if($airline != '') {
    	$query = "SELECT lastUpdate FROM SYS_flight ORDER BY lastUpdate DESC LIMIT 1";
	    $result = mysqli_query($dbConnection,$query);
	    if($result != null) { // i5000 doesn't have this table -> to be checked with XML
	        $row = mysqli_fetch_array($result);
	        $maxDateTime = $row['lastUpdate'];
	    }
    } else {
    	// for a dump we don't have an end date from the SYS_flight table
    	// one way to get an end date is to get the max date of events from failures, faults, ext_app or event tables
    	$query = "SELECT MAX(correlationDate) AS lastFailureTime FROM BIT_failure";
    	$result = mysqli_query($dbConnection,$query);
	    if($result != null) {
	        $row = mysqli_fetch_array($result);
	        $lastFailureTime = strtotime($row['lastFailureTime']);
	    }

    	$query = "SELECT MAX(detectionTime) AS lastFaultTime FROM BIT_fault";
    	$result = mysqli_query($dbConnection,$query);
	    if($result != null) {
	        $row = mysqli_fetch_array($result);
	        $lastFaultTime = strtotime($row['lastFaultTime']);
	    }

    	$query = "SELECT MAX(lastUpdate) AS lastEventTime FROM BIT_event";
    	$result = mysqli_query($dbConnection,$query);
	    if($result != null) {
	        $row = mysqli_fetch_array($result);
	        $lastEventTime = strtotime($row['lastEventTime']);
	    }

    	$query = "SELECT MAX(detectionTime) AS lastExtAppEventTime FROM BIT_extAppEvent";
    	$result = mysqli_query($dbConnection,$query);
	    if($result != null) {
	        $row = mysqli_fetch_array($result);
	        $lastExtAppEventTime = strtotime($row['lastExtAppEventTime']);
	    }

	    $maxTime = max($lastFailureTime, $lastFaultTime, $lastEventTime, $lastExtAppEventTime);
	    $maxDateTime = date("Y-m-d H:i:s", $maxTime);
    }
	
	return $maxDateTime;
}

function getStartDateTime($airlineId, $startDateTimeInput, $minDateTime, $maxDateTime) {
	if($startDateTimeInput != '') {
    	// Date is coming from datetime picker - need to change format
        $date=strtotime($startDateTimeInput);
        $startDateTime = date("Y-m-d H:i:s", $date);         
    } else {
    	// put a default date based on the maxEndDateTime
    	$startDateTime = strtotime("-1 weeks", strtotime($maxDateTime));
    	if($startDateTime < strtotime($minDateTime)) {
    		// put minDateTime if we don't have 2 weeks of data
    		$startDateTime = strtotime($minDateTime);
    	}
        $startDateTime = date("Y-m-d H:i", $startDateTime);
    }

	return $startDateTime;
}
//For PhantomJS- Capture page for 24hr.
function getStartDateTimeForReport($airlineId, $startDateTimeInput, $minDateTime, $maxDateTime) {
	if($startDateTimeInput != '') {
		// Date is coming from datetime picker - need to change format
		$date=strtotime($startDateTimeInput);
		$startDateTime = date("Y-m-d H:i:s", $date);
	} else {
		// put a default date based on the maxEndDateTime
		$startDateTime = strtotime("-1 day", strtotime($maxDateTime));
		if($startDateTime < strtotime($minDateTime)) {
			// put minDateTime if we don't have 2 weeks of data
			$startDateTime = strtotime($minDateTime);
		}
		$startDateTime = date("Y-m-d H:i", $startDateTime);
	}

	return $startDateTime;
}
function getEndDateTime($airlineId, $endDateTimeInput, $maxDateTime) {
    if($endDateTimeInput != '') {
	    // Date is coming from datetime picker - need to change format
		$date=strtotime($endDateTimeInput);
	    $endDateTime = date("Y-m-d H:i:s", $date);	    
	} else {
	    $endDateTime = $maxDateTime;	    
	}
	
	return $endDateTime;
}

function getTimelineStartDateTime($startDateTime) {
	return date("Y-m-d H:i", strtotime("-5 hours", strtotime($startDateTime)));
}

function getTimelineEndDateTime($endDateTime) {
	return date("Y-m-d H:i", strtotime("+5 hours", strtotime($endDateTime))); 
}

function getDateCondition($airlineId, $startDateTime, $endDateTime) {
	if($airlineId != '') {
		$dateCondition = " createDate >= '$startDateTime' AND lastUpdate <= '$endDateTime'";
	} else {
		// we have a different here because the lastUpdate value is not reliable for a dump
		$dateCondition = " createDate >= '$startDateTime' AND createDate <= '$endDateTime'";
	}
	
	return $dateCondition;
}
//Newly added for the report.
function getDateConditionForReport($airlineId, $startDateTime, $endDateTime) {
	if($airlineId != '') {
		$dateConditionForReport = "( createDate >= '$startDateTime' AND lastUpdate <= '$endDateTime') OR ( '$startDateTime' <= lastUpdate AND '$endDateTime' >= createDate) ";
	} else {
		// we have a different here because the lastUpdate value is not reliable for a dump
		$dateConditionForReport = " createDate >= '$startDateTime' AND createDate <= '$endDateTime'";
	}

	return $dateConditionForReport;
}

function getFlightLegIdCondition($flightLegIdInput, $table = '') {
	if($table != '') {
		$column = "$table.idFlightLeg";
	} else {
		$column = "idFlightLeg";
	}
	
	if($flightLegIdInput != '') {
		if(strpos($flightLegIdInput, '-') > 0) {
			$leg1 = $type  = strtok($flightLegIdInput, '-');
			$leg2 = $type  = strtok('-');
			$flightLegIdCondition = " $column BETWEEN $leg1 AND $leg2";
			$flightLegIds = explode("-", $flightLegIdInput);
		} else if(strpos($flightLegIdInput, ',') > 0) {
			$flightLegIdCondition = " $column in ($flightLegIdInput)";
			$flightLegIds = explode(",", $flightLegIdInput);
		} else {
			$flightLegIdCondition = " $column = $flightLegIdInput";
			$flightLegIds = explode(" ", $flightLegIdInput);
		}
	}
	
	return $flightLegIdCondition;
}

function getDurationCondition($minDurationInput, $maxDurationInput) {
	if($minDurationInput != '' && $minDurationInput != 'none') {
		$durationCondition = " TIMESTAMPDIFF(MINUTE,createDate,lastUpdate) > $minDurationInput ";
	}
	if($maxDurationInput != '' && $maxDurationInput != 'none') {
		if($durationCondition != '') {
			$durationCondition .= " AND ";
		}
		$duration = $maxDurationInput * 60;
		$durationCondition .= " TIMESTAMPDIFF(MINUTE,createDate,lastUpdate) < $duration ";
	}
	
	return $durationCondition;
}

?>
