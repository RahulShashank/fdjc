<?php
require_once "../database/connecti_database.php";
require_once "../common/seatAnalyticsData.php";

function getNumberOfSeats($dbConnection, $dbName) {
	$query = "SELECT count(*) AS 'count' 
				FROM $dbName.BIT_lru  a
				WHERE hostName LIKE 'SVDU%'
				AND a.lastUpdate = (
					SELECT MAX(b.lastUpdate)
					FROM $dbName.BIT_lru b
					WHERE a.hostName = b.hostName
				)";
	//TODO : Check with stephane , about above query
	$result = mysqli_query($dbConnection, $query);
	if($result) {
		$row = mysqli_fetch_array($result);
		return $row['count'];
	}
	
	return -1;
}

function getSystemResetsCount($dbConnection, $dbName, $idFlightLeg, $nbLru) {
	$query = " SELECT idFlightLeg, lastUpdate, count(*) AS 'count' 
				FROM (
					SELECT idFlightLeg, eventData, lastUpdate 
					FROM $dbName.BIT_events
				) AS a
				WHERE eventData LIKE 'SVDU%'
				AND idFlightLeg=$idFlightLeg
				GROUP BY UNIX_TIMESTAMP(lastUpdate) DIV 900 ORDER BY lastUpdate";
	//echo "sr: $query <br>";
	$systemResetsCount = 0;
	
	$result = mysqli_query($dbConnection, $query);
	if($result) {
		$threshold = $nbLru * 30 / 100;
		
		while ($row = mysqli_fetch_array($result)) {
				$count = $row['count'];
				//echo "$count / $threshold <br>";
				$start = $row['lastUpdate'];
				if($count >= $threshold) {
				
					// Check if system restart happened during cruise
					$query2 = "SELECT idFlightPhase
								FROM $dbName.SYS_flightPhase
								WHERE '$start' >= startTime AND '$start' <= endTime
								AND idFlightPhase IN (4,5)";
					$result2 = mysqli_query($dbConnection, $query2);
					if($result2) {
						if(mysqli_num_rows($result2) > 0) {
							$systemResetsCount += $count;
						}
					}
				}
		}
	}
	
	return $systemResetsCount;
}

function computeResetsForFlightLeg($dbConnection, $dbName, $idFlightLeg, $lruType, $resetType) {
	$startTime	= getStartTime($dbConnection,$idFlightLeg,$dbName);
	$endTime	= getEndTime($dbConnection,$idFlightLeg,$dbName);
				
	$query4= "SELECT COUNT(t.eventCount) as NewEvent,t.lru AS lru FROM (
					SELECT COUNT(idEvent) as eventCount,eventData as Lru,idflightLeg,lastUpdate,SUBSTRING(lastUpdate,1,16) as newdate FROM $dbName.BIT_events 
					WHERE idFlightLeg=$idFlightLeg
					AND eventName='$resetType'
					AND eventData like '$lruType%'
					AND (lastUpdate between '$startTime' AND '$endTime')
					GROUP BY eventData,idflightLeg,newdate)AS t
				GROUP BY t.lru";
	$result4 = mysqli_query ($dbConnection, $query4);
		
	$TotalReset=0;
	if($result4 && mysqli_num_rows($result4) > 0 ) {
		$CurrReset=0;
		$Reset=0;
		$NewReset=0;
		while( $row = mysqli_fetch_array($result4) ){
			$EventData = $row['NewEvent'];
			$lruName = $row['lru'];
																													
			if($EventData==1){
				$CurrReset++;
			} else {
				$qry2="SELECT COUNT(idEvent) AS eventCount,lastUpdate
					,SUBSTRING(lastUpdate,1,16) AS newdate 
					FROM $dbName.BIT_events 
					WHERE idFlightLeg=$idFlightLeg
					AND eventName='$resetType'
					AND eventData='$lruName'
					AND (lastUpdate BETWEEN '$startTime' AND '$endTime')
					GROUP BY eventData,idflightLeg,newdate
					ORDER BY newdate ASC";
					$reslt2 = mysqli_query ($dbConnection, $qry2);
					
				if($reslt2 && mysqli_num_rows($reslt2) > 0 ) {
					$CurrUpdate='0000-00-00 00:00:00';
					$newDate='0000-00-00 00:00:00';
					while( $row = mysqli_fetch_array($reslt2) ){
						$lastUpdate = $row['newdate'];																					
						if($lastUpdate!=$newDate && $lastUpdate>=$CurrUpdate){
							$newDate=$lastUpdate;
							$currentDate = strtotime($lastUpdate);
							$futureDate = $currentDate+(60*10);
							$CurrUpdate = date("Y-m-d H:i:s", $futureDate);
							$Reset++;
						}
					}
				}
			}																		
		}
	} else {
		$CurrReset=0;
		$Reset=0;
	}
	$TotalReset = $CurrReset + $Reset;
	//$TotalSeatReset = $TotalSeatReset + $TotalReset;
	
	return $TotalReset;
}
?>