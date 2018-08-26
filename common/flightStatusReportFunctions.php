
<?php
function getSVDUFailuresListForAvantFlightStatus() {
	return array
	(
		array("SVDU", 10042400001, 4, "The SVDU-G4 is not responding, as reported by the attached Seat Box LRU"),
		array("SVDU", 10042000101, 1, "The SVDU-G4 reported a POST Error"),
		array("SVDU", '10042048001, 10042049001', 6, "No hardware part number reported by Config Check for SVDU-G4.<br><br>Mismatch with expected hardware part number and version for SVDU-G4"),
		array("SVDU", 10042209001, 6, "The SVDU-G4 reported a link failure on the network switch"),
		array("SVDU", 10042228001, 1, "SDXC Card not mounted on SVDU-G4"),
		array("SVDU", 10042047001, 4, "Expected software part number not reported by Config Check for SVDU-G4")
	);
}

function getTPMUFailuresListForAvantFlightStatus() {
	return array
	(
		array("TPMU", 10041400001, 4, "TPMU is not responding to the neighboring LRUs"),
		array("TPMU", 10041049001, 4, "No hardware part number reported by Config Check for TPMU."),
		array("TPMU", '10041047001, 1004105001, 10041046001', 4, "Expected software part number not reported by Config Check for TPMU.<br><br>---<br><br>Unexpected software part number reported on TPMU.")
	);
}

function getHeadEndFailuresListForAvantFlightStatus() {
	return array
	(
		array("DSU", 10044400001, 0, "The DSUD4 is not responding, as reported by multiple LRU's"),
		array("ADBG", 10025400001, 0, "The ADBG is not responding, as reported by multiple LRU's"),
		array("LAIC", 10045400001, 0, "The LAIC is not responding, as reported by multiple LRU's")
	);
}

function getSeatEndFailuresListForAvantFlightStatus() {
	$svduFailures = getSVDUFailuresListForAvantFlightStatus();
	$tpmuFailures = getTPMUFailuresListForAvantFlightStatus();

	return array_merge($svduFailures, $tpmuFailures);
}

function getFlightPhaseStatus($dbName, $flightLegId) {
	$status = -1;
	// check if flight leg had all the relevant flight phases
	$query = "SELECT GROUP_CONCAT(idFlightPhase SEPARATOR ',') AS flightPhases
				FROM $dbName.SYS_flightPhase 
				WHERE idFLightLeg = $flightLegId";

	$result = mysql_query($query);
	if($result) {
		$row = mysql_fetch_array($result);
		$phases = $row['flightPhases'];		
		if(strpos($phases, "1,2,3,4,5,6,7,8") !== false) {
			$status = 0;
		} else {
			$status = 2;
		}
	} else {
		echo "error with query: $query";
	}

	return $status;
}

function getFlightPhaseStatusNew($dbConnection, $dbName, $flightLegId) {
	$status = -1;
	// check if flight leg had all the relevant flight phases
	$query = "SELECT GROUP_CONCAT(idFlightPhase SEPARATOR ',') AS flightPhases
				FROM $dbName.SYS_flightPhase 
				WHERE idFLightLeg = $flightLegId";

	$result = mysqli_query($dbConnection, $query);
	if($result) {
		$row = mysqli_fetch_array($result);
		$phases = $row['flightPhases'];
		if(strpos($phases, "1,2,3,4,5,6,7,8,9") !== false) {
			$status = 0;
		} else {
			$status = 2;
		}
	} else {
		echo "error with query: $query";
	}

	return $status;
}

function getQueryForFailure($dbName, $flightLegId, $unitType, $failureCodes, $frequency, $monitorState) {
	$query = "SELECT accusedHostName, COUNT('idFailure') AS count, GROUP_CONCAT(correlationDate ORDER BY correlationDate SEPARATOR '\n') AS times
	FROM $dbName.BIT_failure a
	WHERE accusedHostName LIKE '$unitType%' 
	AND idFlightLeg = '$flightLegId'
	AND a.failureCode IN ($failureCodes) 
	AND monitorState IN ($monitorState) 
	GROUP BY accusedHostName 
	HAVING count >= $frequency";
	// echo $query."<br><br>";
	return $query;
}

function getQueryForFailureNew( $flightLegId) {
	$query = "SELECT accusedHostName, COUNT('idFailure') AS failure
	FROM BIT_failure a, sys_failureinfo b
	WHERE accusedHostName LIKE ?
	AND idFlightLeg = '$flightLegId'
	AND a.failureCode IN (?) 
	AND monitorState IN (1,3) 
	AND a.failureCode = b.failureCode
	GROUP BY accusedHostName 
	HAVING failure >= ?";

	return $query;
}

function getQueryForCommandedResets($dbName, $flightLegId) {
	return "SELECT t.eventData, COUNT('idEvent') AS 'count', GROUP_CONCAT(lastUpdate ORDER BY lastUpdate SEPARATOR '\n') AS times
            FROM $dbName.BIT_events t
            WHERE t.eventData LIKE 'SVDU%' 
                AND eventName = 'CommandedReboot'
                AND idFlightLeg = $flightLegId
            GROUP BY t.eventData 
            HAVING COUNT('idEvent') >= 2
            ORDER BY count, lastUpdate DESC";
}

function getQueryForUncommandedResets($dbName, $flightLegId) {
	return "SELECT t.eventData, COUNT('idEvent') AS 'count', GROUP_CONCAT(lastUpdate ORDER BY lastUpdate SEPARATOR '\n') AS times
            FROM $dbName.BIT_events t
            WHERE t.eventData LIKE 'SVDU%' 
                AND eventName = 'UncommandedReboot'
                AND idFlightLeg = $flightLegId
            GROUP BY t.eventData 
            HAVING COUNT('idEvent') >= 5
            ORDER BY count, lastUpdate DESC";
}

function getCorrectiveAction($failureCode) {
	$query = "SELECT caText1, caProb1, caTime1, caText2, caProb2, caTime2, caText3, caProb3, caTime3 
				FROM sys_failureinfo 
				WHERE failureCode IN ($failureCode)";
	$result = mysql_query($query);

	if (mysql_num_rows ($result) > 0) {
	 	$row = mysql_fetch_array($result);
	 	$caText1 = $row['caText1'];
	 	$caProb1 = $row['caProb1'];
	 	$caTime1 = $row['caTime1'];
	 	$caText2 = $row['caText2'];
	 	$caText3 = $row['caText3'];

		$tooltip =	"Corrective action 1\n[Duration: $caTime1 min / Recovery: $caProb1%]\n$caText1";

		if($caText2 != '') {
			$caProb2 = $row['caProb2'];
	 		$caTime2 = $row['caTime2'];
			$tooltip .= "\n\nCorrective action 2\n[Duration: $caTime2 min / Recovery: $caProb2%]\n$caText2";
		}

		if($caText3 != '') {
			$caProb3 = $row['caProb3'];
	 		$caTime3 = $row['caTime3'];
			$tooltip .= "\n\nCorrective action 3\n[Duration: $caTime3 min / Recovery: $caProb3%]\n$caText3";
		}
	} else {
		$tooltip = "No corrective action found";
	}

	return $tooltip;
}
?>