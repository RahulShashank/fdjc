<?php
session_start();
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/computeFleetStatusData.php";
require_once "../common/checkPermission.php";

// Performance parameters & timezone set below
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once("../engineering/checkEngineeringPermission.php");

$aircraftId = $_REQUEST['aircraftId'];
$flightLegId = $_REQUEST['flightLegs'];
$flightLegs = $flightLegId;
$sqlDump = $_REQUEST['sqlDump'];

if(isset($aircraftId)) {
    checkAircraftPermission($dbConnection, $aircraftId);
}

// Find out the aircraft database to be selected or sqlDump if provided.
if($aircraftId != '') {
    // Get information to display in header
    $query = "SELECT a.tailsign, b.id, b.name, a.databaseName, a.isp FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $aircraftTailsign = $row ['tailsign'];
      $airlineId = $row['id'];
      $airlineName = $row['name'];
      $db = $row['databaseName'];
	  $aircraftIsp = $row['isp'];
    } else {
      echo "error: " . mysqli_error ( $error );
    }
} else if($sqlDump != '') {
    $db = $sqlDump;
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}

/* MBS : Migration Code begins */

//$itemStyle = "\"style\": \"font-family: Helvetica; font-size: 10px; text-align: left\"";
//$itemStyle = "style: 'font-family: Helvetica; font-size: 10px; text-align: left'";
$itemStyle = "\"style\": \"font-family: Helvetica; font-size: 10px; text-align: left\"";
// Select the database (sqlDump or Aircraft db)
if($aircraftId != '') {
	$selected = mysqli_select_db($dbConnection, $db)
		or die("Could not select 1".$db);
} else {
	$selected = mysqli_select_db($dbConnection, $db)
		or die("Could not select 2".$db);
}


$filter = $_REQUEST['filter'];
//$filter = false;
error_log('Filter value : '.$filter);
if($filter == 'true') {	
	error_log('when Filter true ');
	$showFailures = $_REQUEST['showFailures'];
	$showFaults = $_REQUEST['showFaults'];
	$showReboots = $_REQUEST['showReboots'];
	$showAppEvents = $_REQUEST['showAppEvents'];
	$showImpServices = $_REQUEST['showImpServices'];

	$showDSU = $_REQUEST['showDSU'];
	$showAVCD_LAIC = $_REQUEST['showAVCD_LAIC'];
	$showADBG = $_REQUEST['showADBG'];
	$showICMT = $_REQUEST['showICMT'];
	//$showCIDS = $_REQUEST['showCIDS'];
	//$showCAMERA = $_REQUEST['showCAMERA'];
	//$showPRINTER= $_REQUEST['showPRINTER'];
	$showSVDU = $_REQUEST['showSVDU'];
	$showTPMU = $_REQUEST['showTPMU'];
	$showQSEB_SDB_VCSSDB = $_REQUEST['showQSEB_SDB_VCSSDB'];
	$showSPB = $_REQUEST['showSPB'];
	$otherLruType = $_REQUEST["otherLruType"];
	$noOfLruType = count($otherLruType);

	if($noOfLruType>0){
		$lruTypeFilter = 'on';
	}

	$hostnameInput = $_REQUEST['hostnameInput'];
	$biteCode = $_REQUEST['biteCode'];
	$notBiteCode = $_REQUEST['notBiteCode'];
	$severity = $_REQUEST['severity'];
	$monitorState = $_REQUEST['monitorState'];
} else {
	error_log('when Filter false ');
	$showFailures = false;
	$showFaults = true;
	$showReboots = false;
	$showAppEvents = false;
	$showImpServices = false;

	$showDSU = true;
	$showAVCD_LAIC = true;
	$showADBG = true;
	$showICMT = true;
	//$showCIDS = true;
	//$showCAMERA = true;
	//$showPRINTER= true;
	$showSVDU = true;
	$showTPMU = false;
	$showQSEB_SDB_VCSSDB = true;
	$showSPB = true;
	
	$hostnameInput = "";

	$biteCode = "";
	$notBiteCode = "";
	$severity = "all";
	$monitorState = "1,3";
}

// Handle single or multiple flight legs
if($flightLegId != '') {
	if(strpos($flightLegId, '-') > 0) {
		$leg1 = $type  = strtok($flightLegId, '-');
		$leg2 = $type  = strtok('-');
		$whereCondition = "WHERE idFlightLeg BETWEEN $leg1 AND $leg2";
	} else if(strpos($flightLegId, ',') > 0) {
		$whereCondition = "WHERE idFlightLeg in ($flightLegId)";
	} else {
		$whereCondition = "WHERE idFlightLeg = $flightLegId";
	}
}

$dataItems = array();
$i=0;

$query = "SELECT * FROM SYS_flight $whereCondition";
$result = mysqli_query($dbConnection, $query);

	if($result) {
		$i = 0;
		$keepLooping = true;
		$row = mysqli_fetch_array($result); // get first flight leg

		while($keepLooping) {
			$id = $row['idFlightLeg'];
			$flightNumber = $row['flightNumber'];
			$departureAirportCode = $row['departureAirportCode'];
			$arrivalAirportCode = $row['arrivalAirportCode'];
			$content = "$id - $flightNumber - $departureAirportCode - $arrivalAirportCode";
			$start = $row{'createDate'};
			if($i == 0 ) {
				$i++;
			}
			$minStartTime = $start;
			if($aircraftId != '') {
				$endFlightLeg = $row{'lastUpdate'};
			} else {
				// for db, we need to get the createDate of the next flight leg in order to get the end date
				$nextRow = mysqli_fetch_array($result);
				if($nextRow) {
					$endFlightLeg = $nextRow{'createDate'};
				} else {
					// we have reached the last flight leg
					// there are two cases: the
					$queryForCreateDate = "SELECT createDate FROM SYS_flight WHERE idFlightLeg = $id+1";
					$resultForCreateDate = mysqli_query($dbConnection,$queryForCreateDate);
					if($resultForCreateDate && mysqli_num_rows($resultForCreateDate) > 0) {
						$rowForCreateDate = mysqli_fetch_array($resultForCreateDate);
						$endFlightLeg = $rowForCreateDate['createDate'];
					} else {
				    	// it is the very last flight leg of the database
						$endFlightLeg = $maxDateTime;	//TODO: check origin and usage of $maxDateTime
					}

					$keepLooping = false;
				}
			}

			$flightLegName = $row{'flightLeg'};
			$duration = dateDifference($start, $endFlightLeg, '%h Hours %i Minute %s Seconds');
			$title = "$id - $flightLegName - $flightNumber - $departureAirportCode - $arrivalAirportCode / $start --> $endFlightLeg / $duration";
			
			if(strpos($flightLegName, 'CL') === 0) {
				$group = 'CL';
				//$subgroup = 1;
				//$class = 'closed';
			} else {
				$group = 'OPP';
				//$subgroup = 0;
				//$class = 'closed';	//TODO: open ?
			}
			// default flight leg class
			$class = 'closed';
			
			$query2 = "SELECT * FROM SYS_flightPhase WHERE idFlightLeg = $id ORDER BY startTime";
			$result2 = mysqli_query($dbConnection, $query2);
           /* $cnt = 0;*/
			if($result2) { // not every dump has the sys_flightphase table
				while ($row2 = mysqli_fetch_array($result2)) {
					$idFlightPhase = $row2{'idFlightPhase'};
					$contentFlightPhase = "$idFlightPhase - ".getFlightPhaseDesc($idFlightPhase);
					$startFlightPhase = $row2{'startTime'};
					$endFlightPhase = $row2{'endTime'};
					$subgroupOrderFlightPhase = getFlightPhaseOrder($idFlightPhase);
					
					$dataItems[$i++] = "{\"id\": \"FLG/$i\", \"group\":\"FP\", \"subgroup\":\"$subgroupOrderFlightPhase\", \"subgroupOrder\":\"$subgroupOrderFlightPhase\", \"content\":\"$contentFlightPhase\", \"title\":\"$contentFlightPhase\", \"start\":\"$startFlightPhase\", \"end\":\"$endFlightPhase\", $itemStyle}";
					if($sqlDump == ''){ //Don't compute flightStatus for sqldumps
						if($idFlightPhase == 5 || $idFlightPhase == 4) {
							// it is a real flight so let's compute its status
							if($aircraftId != '') {
								$status = getFlightStatus($dbName, $id, $platform);
							} else {
								$status = 0;
							}
							
							if($status == 0) {
								$class = 'statusOK';
							} else if($status == 1) {
								$class = 'statusWarning';
							} else if($status > 1) {
								$class = 'statusAlert';
							}
                           /* $cnt++;*/
						}
                     /*   elseif($platform == 'i5000' || $platform == 'i8000'){
                            if($idFlightPhase == 4) {
                                $statusForClimb = true;
                                if($aircraftId != '') {
                                    $status = getFlightStatus($dbName, $id, $platform);
                                } else {
                                    $status = 0;
                                }

                                if($status == 0) {
                                    $class = 'statusOK';
                                } else if($status == 1) {
                                    $class = 'statusWarning';
                                } else if($status > 1) {
                                    $class = 'statusAlert';
                                }
                            }
                        }*/
					}
				}
			}
		/*	if($cnt == 1){
                  $statusForClimb = true;
            }*/
			$dataItems[$i++] = "{\"className\":\"$class\", \"id\":\"FLI/$id\", \"group\":\"$group\", \"content\":\"$content\", \"title\":\"$title\", \"start\":\"$start\", \"end\":\"$endFlightLeg\", $itemStyle}";
			
			if($showFaults == 'on')
			{	
				$criticalFaults = getCriticalFaults();
				error_log('Inside DSU faults'.(int)$showDSU);
				error_log('Inside DSU faults'.$showDSU);
				//DSU
				if($showDSU) {
					
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "DSU", "DSU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// AVCD
				if($showAVCD_LAIC) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "AVCD", "AVCD_LAIC", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}

				// LAIC
				if($showAVCD_LAIC) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "LAIC", "AVCD_LAIC", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}

				// ADBG
				if($showADBG) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "ADB", "ADBG", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// SPB
				if($showSPB) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "SPB", "SPB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// QSEB
				if($showQSEB_SDB_VCSSDB) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "QSEB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}

				// SDB
				if($showQSEB_SDB_VCSSDB) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "SDB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				// VCSSDB
				if($showQSEB_SDB_VCSSDB) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "VCSSDB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}

				// ICMT
				if($showICMT) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "ICMT", "ICMT", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// SVDU
				if($showSVDU) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "SVDU", "SVDU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// TPMU
				if($showTPMU) {
					createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "TPMU", "TPMU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// otherLruType
				if($lruTypeFilter) {
					$r=0;
					while($r<$noOfLruType){
						if($otherLruType[$r]=="CIDSCSS"){
							createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "CIDS", "CIDSCSS", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
							createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, "CSS", "CIDSCSS", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
						}
						else{
							createFaultDataItems($database, $id, $criticalFaults, $dataItems, $i, $itemStyle, $otherLruType[$r], $otherLruType[$r], $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
						}
						$r++;
					}					
				}
				
			}
			
			if($showFailures == 'on')
			{
				$criticalFailures = getCriticalFailures();

				//DSU
				if($showDSU) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "DSU", "DSU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}

				// AVCD
				if($showAVCD_LAIC) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "AVCD", "AVCD_LAIC", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}

				// LAIC
				if($showAVCD_LAIC) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "LAIC", "AVCD_LAIC", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// ADBG
				if($showADBG) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "ADB", "ADBG", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// SPB
				if($showSPB) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "SPB", "SPB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// QSEB
				if($showQSEB_SDB_VCSSDB) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "QSEB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// SDB
				if($showQSEB_SDB_VCSSDB) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "SDB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// VCSSDB
				if($showQSEB_SDB_VCSSDB) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "VCSSDB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}

				// ICMT
				if($showICMT) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "ICMT", "ICMT", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}

				
				// SVDU
				if($showSVDU) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "SVDU", "SVDU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// TPMU
				if($showTPMU) {
					createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "TPMU", "TPMU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
				}
				
				// otherLruType
				if($lruTypeFilter) {					
					$t=0;
					while($t<$noOfLruType){
						if($otherLruType[$t]=="CIDSCSS"){
							createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "CIDS", "CIDSCSS", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
							createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, "CSS", "CIDSCSS", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
						}
						else{
							createFailureDataItems($database, $id, $criticalFailures, $dataItems, $i, $itemStyle, $otherLruType[$t], $otherLruType[$t], $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity);
						}
						$t++;
					}					
				}
			}
			
			//IMPACTED SERVICES CODE added Smita:31/07/2017
			if($showImpServices == 'on')
			{
				//$criticalFailures = getCriticalFailures();

				//DSU
				if($showDSU) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "DSU", "DSU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}

				// AVCD
				if($showAVCD_LAIC) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "AVCD", "AVCD_LAIC", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}

				// LAIC
				if($showAVCD_LAIC) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "LAIC", "AVCD_LAIC", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}
				
				// ADBG
				if($showADBG) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "ADB", "ADBG", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}
				
				// SPB
				if($showSPB) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "SPB", "SPB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}
				
				// QSEB
				if($showQSEB_SDB_VCSSDB) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "QSEB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}
				
				// SDB
				if($showQSEB_SDB_VCSSDB) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "SDB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}
				
				// VCSSDB
				if($showQSEB_SDB_VCSSDB) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "VCSSDB", "QSEB_SDB_VCSSDB", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}

				// ICMT
				if($showICMT) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "ICMT", "ICMT", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}

				
				// SVDU
				if($showSVDU) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "SVDU", "SVDU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}
				
				// TPMU
				if($showTPMU) {
					createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "TPMU", "TPMU", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
				}
				
				// otherLruType
				if($lruTypeFilter) {					
					$t=0;
					while($v<$noOfLruType){
						if($otherLruType[$v]=="CIDSCSS"){
							createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "CIDS", "CIDSCSS", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
							createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, "CSS", "CIDSCSS", $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
						}
						else{
							createServiceFailureDataItems($database, $id, $dataItems, $i, $itemStyle, $otherLruType[$v], $otherLruType[$v], $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState);
						}
						$t++;
					}					
				}
			}
			//IMPACTED SERVICES CODE end Smita:31/07/2017

			if($nextRow) {
				$row = $nextRow;
			} else {
				$row = mysqli_fetch_array($result);
			}

			if(!$row) {
				$keepLooping = false;
			} 
		}
	}
	
	//IMPACTED SERVICES CODE BEGINS Smita:31/07/2017
	function createServiceFailureDataItems($database, $idFlightLeg, &$dataItems, &$i, $itemStyle, $unitType, $group, $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState) {
		global $dbConnection;
		global $mainDB;
		$query = "SELECT idFailure, a.failureCode, accusedHostName, correlationDate, a.lastUpdate, monitorState, failureDesc,failureImpact,name,description
		FROM BIT_serviceFailure a
		LEFT JOIN $mainDB.sys_serviceFailureInfo b 
		ON a.failureCode = b.failureCode 
		LEFT JOIN $mainDB.sys_services c 
		ON a.idService = c.idService 
		WHERE a.idFlightLeg = $idFlightLeg  
		AND accusedHostName LIKE '$unitType%'		
		AND a.monitorState IN ($monitorState) ";
		if($hostnameInput) {
			$string = str_replace(" ", "", $hostnameInput);
			$string = "'" . str_replace(",", "','", $string) . "'";
			$query .= " AND a.accusedHostName IN ($string) ";
		} 
		if($biteCode) {
			$query .= " AND a.failureCode IN ($biteCode) ";
		}
		if($notBiteCode){
			$query .= " AND a.failureCode NOT IN ($notBiteCode) ";
		}

		$query .= " ORDER BY LENGTH(accusedHostName) DESC, accusedHostName DESC ";
		//echo $query;
		$result = mysqli_query($dbConnection , $query);
		if($result) {
			while ($row = mysqli_fetch_array($result)) {
				$id = $row{'idFailure'};
				$failureCode = $row{'failureCode'};
				$failureDesc = formatStringForTimeLine($row['failureDesc']);
				$serviceName = $row['name'];
				$monitorState = getMonitorStateDesc($row['monitorState']);
				$hostname = $row{'accusedHostName'};
				$start = $row{'correlationDate'};
				if($start < $GLOBALS['minStartTime']) {
					$start = $GLOBALS['minStartTime'];
				}
				$img = "<img src='../img/failure.png' style='vertical-align:top; width: 12px; height: 12px;'>";
				$content = "$img $hostname - $serviceName - $failureCode - $failureDesc - $monitorState";		

				if($row['monitorState'] == 1) { // inactive
					$end = $row{'lastUpdate'}; 
					$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
					$title = formatStringForTimeLine("$hostname - $serviceName - $failureCode - $failureDesc / Start: $start / End: $end / Duration: $duration / $monitorState");
						$class = 'active';
					
				} else { // Still active
					$end = $endFlightLeg;
					$title = formatStringForTimeLine("$hostname - $serviceName - $failureCode - $failureDesc / Start: $start / $monitorState");
						$class = 'criticalActive';

				}
				
				$dataItems[$i++] = "{\"className\":\"$class\", \"id\":\"$hostname/$id/$i\", \"content\":\"$content\", \"title\":\"$title\", \"start\":\"$start\", \"end\":\"$end\", $itemStyle, \"group\":\"$group\", \"subgroup\":\"$hostname\"}";
			}
		}
	}
	//IMPACTED SERVICES CODE END Smita:31/07/2017 
	
	function createFailureDataItems($database, $idFlightLeg, $criticalFailures, &$dataItems, &$i, $itemStyle, $unitType, $group, $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity) {
		global $dbConnection;
		global $mainDB;
		$query = "SELECT idFailure, a.failureCode, accusedHostName, correlationDate, a.lastUpdate, legFailureCount, monitorState, failureDesc
		FROM BIT_failure a
		LEFT JOIN $mainDB.sys_failureinfo b 
		ON a.failureCode = b.failureCode 
		WHERE a.idFlightLeg = $idFlightLeg  
		AND accusedHostName LIKE '$unitType%'		
		AND a.monitorState IN ($monitorState) ";
		if($hostnameInput) {
			$string = str_replace(" ", "", $hostnameInput);
			$string = "'" . str_replace(",", "','", $string) . "'";
			$query .= " AND a.accusedHostName IN ($string) ";
		} 
		if($biteCode) {
			$query .= " AND a.failureCode IN ($biteCode) ";
		}
		if($notBiteCode){
			$query .= " AND a.failureCode NOT IN ($notBiteCode) ";
		}
		if($severity == "critical") {
			$query .= " AND a.failureCode IN (".implode(",", $criticalFailures).") ";
		} else if($severity == "not_critical") {
			$query .= " AND a.failureCode NOT IN (".implode(",", $criticalFailures).") ";
		}
		$query .= " ORDER BY LENGTH(accusedHostName) DESC, accusedHostName DESC ";
		//echo $query;
		$result = mysqli_query($dbConnection , $query);
		if($result) {
			while ($row = mysqli_fetch_array($result)) {
				$id = $row{'idFailure'};
				$failureCode = $row{'failureCode'};
				$failureDesc = formatStringForTimeLine($row['failureDesc']);
				$legFailureCount = $row['legFailureCount'];
				$monitorState = getMonitorStateDesc($row['monitorState']);
				$hostname = $row{'accusedHostName'};
				$start = $row{'correlationDate'};
				if($start < $GLOBALS['minStartTime']) {
					$start = $GLOBALS['minStartTime'];
				}
				$img = "<img src='../img/failure.png' style='vertical-align:top; width: 12px; height: 12px;'>";
				$content = "$img $hostname - $legFailureCount x $failureCode - $failureDesc - $monitorState";		

				if($row['monitorState'] == 1) { // inactive
					$end = $row{'lastUpdate'}; 
					$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
					$title = formatStringForTimeLine("$hostname - $failureCode - $failureDesc / Start: $start / End: $end / Duration: $duration / $monitorState");
					if(in_array($failureCode, $criticalFailures)) {
						$class = 'critical';
					} else {
						$class = '';
					}
				} else { // Still active
					$end = $endFlightLeg;
					$title = formatStringForTimeLine("$hostname - $failureCode - $failureDesc / Start: $start / $monitorState");
					if(in_array($failureCode, $criticalFailures)) {
						$class = 'criticalActive';
					} else {
						$class = 'active';
					}
				}
				
				$dataItems[$i++] = "{\"className\":\"$class\", \"id\":\"$hostname/$id/$i\", \"content\":\"$content\", \"title\":\"$title\", \"start\":\"$start\", \"end\":\"$end\", $itemStyle, \"group\":\"$group\", \"subgroup\":\"$hostname\"}";
			}
		}
	}
	


	function createFaultDataItems($database, $idFlightLeg, $criticalFaults, &$dataItems, &$i, $itemStyle, $unitType, $group, $endFlightLeg, $hostnameInput, $biteCode, $notBiteCode, $monitorState, $severity) {
		
		global $dbConnection;
		global $mainDB;
		$query = "SELECT a.idFault, a.faultCode, a.hostName, a.detectionTime, a.clearingTime, a.monitorState, a.param1, a.param2, a.param3, a.param4, b.faultDesc 
		FROM BIT_fault a 
		LEFT JOIN $mainDB.sys_faultinfo b 
		ON a.faultCode = b.faultCode
		WHERE a.idFlightLeg = $idFlightLeg 		
		AND hostName LIKE '$unitType%' 
		AND a.monitorState IN ($monitorState)";
		if($hostnameInput) {
			$string = str_replace(" ", "", $hostnameInput);
			$string = "'" . str_replace(",", "','", $string) . "'";
			$query .= " AND a.hostName IN ($string) ";
		} 
		if($biteCode) {
			$query .= " AND a.faultCode IN ($biteCode) ";
		} 
		if($notBiteCode) {
			$query .= " AND a.faultCode NOT IN ($notBiteCode) ";
		} 
		if($severity == "critical") {
			$query .= " AND a.faultCode IN (".implode(",", $criticalFaults).") ";
		} else if($severity == "not_critical") {
			$query .= " AND a.faultCode NOT IN (".implode(",", $criticalFaults).") ";
		}
		$query .= " ORDER BY LENGTH(hostName) DESC, hostName DESC ";

		$result = mysqli_query($dbConnection, $query);
		
		if($result) {
			while ($row = mysqli_fetch_array($result)) {
				$id = $row{'idFault'};
				$faultCode = $row{'faultCode'};
				$failureDesc = $row{'faultDesc'};
				$monitorState = getMonitorStateDesc($row['monitorState']);
				$hostname = $row{'hostName'};
				$start = $row{'detectionTime'};
				if($start < $GLOBALS['minStartTime']) {
					$start = $GLOBALS['minStartTime'];
				}
				$param1 = $row['param1'];
				$param2 = $row['param2'];
				$param3 = $row['param3'];
				$param4 = $row['param4'];
				$content = "<img src='../img/fault.png' style='vertical-align:top; width: 12px; height: 12px;'> $hostname / $faultCode - $failureDesc - $param1 - $param2 - $param3 - $param4";
				if($row['monitorState'] == 1) {
					$end = $row{'clearingTime'};
					$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
					$title = "$hostname / $faultCode - $failureDesc - $param1 - $param2 - $param3 - $param4 / Start: $start / End: $end / Duration: $duration / $monitorState";
					if(in_array($faultCode, $criticalFaults)) {
						$class = 'critical';
					} else {
						$class = '';
					}
				} else {
					$end = $endFlightLeg;
					$title = "$hostname / $faultCode - $failureDesc - $param1 - $param2 - $param3 - $param4 / Start: $start / $monitorState";
					if(in_array($faultCode, $criticalFaults)) {
						$class = 'criticalActive';
					} else {
						$class = 'active';
					}
				}
				$dataItems[$i++] = "{\"className\":\"$class\", \"id\":\"$hostname/$id/$i\", \"content\":\"$content\", \"title\":\"$title\",\"start\":\"$start\", \"end\":\"$end\", $itemStyle,\"group\":\"$group\", \"subgroup\":\"$hostname\"}";
				

			}
		}
	}
	
	if($showReboots == 'on')
	{
		//DSU
		if($showDSU) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "DSU", "DSU", $hostnameInput);
		}
		
		// AVCD
		if($showADBG) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "AVCD", "AVCD_LAIC", $hostnameInput);
		}

		// LAIC
		if($showADBG) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "LAIC", "AVCD_LAIC", $hostnameInput);
		}

		// ADBG
		if($showADBG) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "ADB", "ADBG", $hostnameInput);
		}
		
		// SPB
		if($showSPB) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "SPB", "SPB", $hostnameInput);
		}
		
		// QSEB
		if($showQSEB_SDB_VCSSDB) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "QSEB", "QSEB_SDB_VCSSDB", $hostnameInput);
		}

		// SDB
		if($showQSEB_SDB_VCSSDB) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "SDB", "QSEB_SDB_VCSSDB", $hostnameInput);
		}
		
		// SDB
		if($showQSEB_SDB_VCSSDB) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "VCSSDB", "QSEB_SDB_VCSSDB", $hostnameInput);
		}

		// ICMT
		if($showADBG) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "ICMT", "ICMT", $hostnameInput);
		}
		
		// // SVDU
		if($showSVDU) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "SVDU", "SVDU", $hostnameInput);
		}
		
		// TPMU
		if($showTPMU) {
			createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "TPMU", "TPMU", $hostnameInput);
		}
		
		// otherLruType
		if($lruTypeFilter) {					
			$s=0;
			while($s<$noOfLruType){
				if($otherLruType[$t]=="CIDSCSS"){
					createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "CIDS", "CIDSCSS", $hostnameInput);
					createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, "CSS", "CIDSCSS", $hostnameInput);
				}
				else{
					createRebootDataItems($database, $whereCondition, $dataItems, $i, $itemStyle, $otherLruType[$s], $otherLruType[$s], $hostnameInput);
				}
				$s++;
			}					
		}
	}

	function createRebootDataItems($database, $whereCondition, &$dataItems, &$i, $itemStyle, $unitType, $group, $hostnameInput) {
		global $dbConnection;
		$query = "SELECT * 
					FROM BIT_events 
					$whereCondition 
					AND eventData LIKE '$unitType%'";
		if($hostnameInput) {
			$string = str_replace(" ", "", $hostnameInput);
			$string = "'" . str_replace(",", "','", $string) . "'";
			$query .= "AND eventData IN ($string)";
		} 
		$query .= "ORDER BY LENGTH(eventData) DESC, eventData DESC";
		$result = mysqli_query($dbConnection, $query);
        $itemStyleNew = "\"style\": \"font-family: Helvetica; font-size: 10px; text-align: left;background-color: white\"";
		if($result) {
			while ($row = mysqli_fetch_array($result)) {
                    $imgSrc =false;
				$id = $row{'idEvent'};
				$failureDesc = $row{'eventName'};
				$hostname = $row{'eventData'};
				$start = $row{'lastUpdate'};
                $eventInfo2 = $row{'eventInfo'};
				/*if(strpos($failureDesc, 'Commanded') === 0) {
				//	$content = "$hostname";
                // $title = "Commanded Reboot - $start";
					$title = "Commanded Reboot - $eventInfo2 / Time: $start";
					//$class = "";
				} else {
					//$content = "$hostname";
					//$title = "Uncommanded Reboot - $start";
                    $title = "Uncommanded Reboot - $eventInfo2 / Time: $start";
					//$class = "statusAlert";
                   // $class = "";
				}*/
                $title = "$failureDesc - $eventInfo2 / Time: $start";
                $eventInfo = str_replace(' ', '', $row{'eventInfo'});
                      if($eventInfo == 'SWINSTALLRESET'){
                    $imgSrc = '../img/swInstallReset.png';
                }
                elseif($eventInfo == 'POWERBUTTONRESET'){
                    $imgSrc = '../img/powerBtnReset.png';
                }
                elseif($eventInfo == 'CREWRESET'){
                   $imgSrc = '../img/crewReset.png';
                }
                elseif($eventInfo == 'UNKNOWNRESETREASON'){
                    $imgSrc = '../img/unknownReset.png';
                }
                elseif($eventInfo == 'SYSTEMREBOOT'){
                    $imgSrc = '../img/systemReboot.png';
                }
                elseif($eventInfo == 'SKCOLDRESET'){
                   $imgSrc = '../img/skColdReset.png';
                }
                elseif($eventInfo == 'POWERCOLDRESET'){
                    $imgSrc = '../img/powerColdReset.png';
                }
                elseif($eventInfo == 'KERNELPANICRESET'){
                    $imgSrc = '../img/kernelPanicReset.png';
                }
                elseif($eventInfo == 'GLIBCRESET'){
                    $imgSrc = '../img/glibcReset.png';
                }
                elseif($eventInfo == 'FSCHECKRESET'){
                    $imgSrc = '../img/fsCheckReset.png';
                }
                elseif($eventInfo == 'DUCATIRESET'){
                    $imgSrc = '../img/ducatiReset.png';
                }
                elseif($eventInfo == 'ADBREBOOTRESET'){
                   $imgSrc = '../img/adbRebootReset.png';
                }
                elseif($eventInfo == ''){
                     $failureDescNew = str_replace(' ', '', $failureDesc);
                    if($failureDescNew == 'UncommandedReboot'){
                         $imgSrc = '../img/uncommandedReset.png';
                    }
                    elseif($failureDescNew == 'CommandedReboot'){
                    $imgSrc = '../img/commandedReset.png';
                    }
                }
                if($imgSrc != false){
                $content = "<img src='$imgSrc' style='vertical-align:top; width: 16px; height: 16px;margin-top: -2px; margin-left: -3px;margin-right: 2px;'>$hostname";
                }
                else{
                    $imgSrc = '../img/defaultResetReason.png';
                     $content = "<img src='$imgSrc' style='vertical-align:top; width: 16px; height: 16px;margin-top: -2px;margin-left: -3px;margin-right: 2px;'>$hostname";
                }
				$dataItems[$i++] = "{\"id\":\"$hostname/$id/$i\", \"content\":\"$content\", \"title\":\"$title\", \"start\":\"$start\",$itemStyleNew,\"type\":\"point\", \"group\":\"$group\", \"subgroup\":\"$hostname\", \"eventInfo\" :\"$eventInfo2\"}";
                //type:"point'
                //\"className\":"$class'
			}
		}
	}
	
	if($showAppEvents == 'on')
	{
		// this data are only applicable to SVDUs

		// SVDU
		if($showSVDU) {
			$query = "SELECT * 
			FROM BIT_extAppEvent 
			$whereCondition 
			AND (hostName LIKE 'SVDU%' OR hostName LIKE 'DSU%')
			ORDER BY LENGTH(hostName) DESC, hostName DESC";
			$result = mysqli_query($dbConnection, $query);

			if($result) {
				while ($row = mysqli_fetch_array($result)) {
					$id = $row['idExtAppEvent'];
					$faultCode = $row['faultCode'];
					$faultDesc = getExtAppEventDesc($faultCode);
					$hostname = $row{'hostName'};
					$start = $row{'detectionTime'};
					$content = "$hostname <img src='../img/extappevent.png' style='vertical-align:top; width: 12px; height: 12px;'>";
					$title = "$faultCode - $faultDesc - $start";

					if(strpos($hostname, 'SVDU') >= 0) {
						$group = 'SVDU';
					} else {
						$group = 'DSU';
					}

					$dataItems[$i++] = "{\"id\":\"$hostname/$id/$i\", \"content\":\"$content\", \"title\":\"$title\",  \"start\":\"$start\", $itemStyle,  \"group\":\"$group\", \"subgroup\":\"$hostname\", \"type\":\"point\"}";
					//$dataItems[$i++] = "{\"id\":"$hostname/$id/$i", \"content\":"$content", \"title\":"$title",  \"start\":"$start", $itemStyle,  \"group\":"$group", \"subgroup\":"$hostname", type:"point'}";
					
				}
			}
		}
	}
	
	$finalString = "";
	foreach ($dataItems as $item) {
		error_log("Item: " . $item);
		$finalString .= $item . ",";
	}
	$finalString = rtrim($finalString, ",");
	
	error_log('Size of DataItems'.sizeof($dataItems));
	
	error_log("Data Array:");
	error_log(print_r($dataItems, TRUE));
	error_log("Final string:");
	error_log(print_r($finalString, TRUE));
	
	//$json_response = json_encode($finalArray);
	//return $json_response;
	//return $dataItems;
	echo $finalString;
?>