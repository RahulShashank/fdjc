<?php
require_once "../database/connecti_database.php";
require_once("../engineering/checkEngineeringPermission.php");

$aircraftId = $_REQUEST['aircraftId'];
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];

if($aircraftId != '') {
	$query = "SELECT a.databaseName FROM aircrafts a WHERE a.id = $aircraftId" ;
	$result = mysqli_query($dbConnection,$query);

	if($result && mysqli_num_rows($result) > 0 ) {
		$row = mysqli_fetch_array($result);
		$dbName = $row['databaseName'];
	}
} else {
	echo json_encode(array("status"=>"fail", "message"=>"aircraft id missing"));
	exit;
}

if(isset($startDate) AND isset($endDate) AND isset($aircraftId)){
	//$faultCountQuery = "SELECT a.hostName as hostName, date(a.detectionTime) as createDate, count(*) as faultCount FROM (select * from $dbName.BIT_fault where detectionTime BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' AND faultCode IN (400, 404, 420231, 420230, 420228, 430228)) a JOIN (SELECT * from $dbName.SYS_flightPhase where idFlightPhase in (4,5,6) AND startTime BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') b ON a.idFlightLeg = b.idFlightLeg AND ((a.monitorState=3 AND a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.monitorState=1 AND (a.detectionTime BETWEEN b.startTime AND b.endTime) AND (TIMESTAMPDIFF(MINUTE,a.detectionTime,a.lastUpdate)>= 5))) GROUP BY a.hostName, date(a.detectionTime)";
	//$faultCountQuery = "SELECT c.hostName as hostName, date(c.detectionTime) as createDate, count(*) as faultCount FROM (SELECT * from $dbName.SYS_flight where createDate BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') a JOIN (SELECT * from $dbName.SYS_flightPhase where idFlightPhase in (4,5,6)) b JOIN (select * from $dbName.BIT_fault where detectionTime BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' AND faultCode IN (400, 404, 420231, 420230, 420228, 430228)) c ON a.idFlightLeg=b.idFlightLeg AND b.idFlightLeg=c.idFlightLeg AND ((c.monitorState=3 AND c.detectionTime BETWEEN b.startTime AND b.endTime) OR (c.monitorState=1 AND (c.detectionTime BETWEEN b.startTime AND b.endTime) AND (TIMESTAMPDIFF(MINUTE,c.detectionTime,c.lastUpdate)>= 5))) GROUP BY c.hostName, date(c.detectionTime)";
	$faultCountQuery = "select hostName, date(detectionTime) as createDate, count(*) as faultCount from(".
		"select bf.hostName, bf.detectionTime, bf.monitorState,bf.idFlightLeg ".
		"from $dbName.bit_fault bf inner join $dbName.sys_flightphase sfp ".
		"on bf.idFlightLeg=sfp.idFlightLeg ".
		"and ((bf.monitorState=3 and bf.detectionTime<=sfp.endTime) ". 
		"or (bf.monitorState=1 and ((bf.detectionTime between sfp.startTime and sfp.endTime) ". 
		"or (bf.clearingTime between sfp.startTime and sfp.endTime) ".
		"or (bf.detectionTime <= sfp.startTime and bf.clearingTime >=sfp.endTime)) ".
		"and (TIMESTAMPDIFF(MINUTE,bf.detectionTime,bf.clearingTime)>= 5))) ".
		"and bf.faultCode in (400, 404, 420231, 420230, 420228, 430228) ".
		"and sfp.idFlightPhase in (4,5,6) ".
		"and bf.detectionTime BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' ". 
		"group by bf.faultCode, bf.hostName,bf.detectionTime,bf.monitorState,bf.idFlightLeg) c ".
		"group by hostName, date(detectionTime)";
	
	error_log($faultCountQuery);
	$result = mysqli_query($dbConnection,$faultCountQuery);
	$faultData = array();
	while($row = mysqli_fetch_assoc($result)){
		$hostName = $row['hostName'];
		if(array_key_exists($hostName, $faultData)){
			$faultData[$hostName][$row['createDate']] = $row['faultCount'];
		}else{
			$faultData[$hostName] = array($row['createDate']=>$row['faultCount']);
		}
	}
	//$flightCountQuery = "SELECT date(createDate) as flightDate, count(idFLightLeg) as flightCount FROM $dbName.SYS_flight where createDate BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' AND flightLeg LIKE 'OPP%' group by date(createDate)";
	//$flightCountQuery = "SELECT date(startTime) as flightDate, count(distinct date(startTime),idFlightLeg) as flightCount FROM $dbName.SYS_flightPhase where startTime BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' AND idFlightPhase IN(4,5,6) group by date(startTime)";
	$flightCountQuery = "SELECT date(createDate) as flightDate, count(DISTINCT date(createDate), a.idFlightLeg) as flightCount FROM (SELECT * from $dbName.SYS_flight where createDate BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') a JOIN (SELECT * from $dbName.SYS_flightPhase where idFlightPhase in (4,5,6)) b ON a.idFlightLeg=b.idFlightLeg GROUP BY date(createDate)";	
	$result = mysqli_query($dbConnection,$flightCountQuery);
	$flightData = array();
	while($row = mysqli_fetch_assoc($result)){
		$flightData[$row['flightDate']] = $row['flightCount'];
	}
	$returnResult = array("status"=>"success", "faultCountData"=>$faultData, "flightCountData"=>$flightData);
	echo json_encode($returnResult);
}else{
	echo json_encode(array("status"=>"fail", "message"=>"startDate or endDate or aircraftID missing"));
}

// Close msql connection
mysqli_close($dbConnection);
?>