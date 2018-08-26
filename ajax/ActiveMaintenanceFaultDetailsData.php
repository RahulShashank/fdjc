<?php
require_once "../database/connecti_database.php";
require_once("../engineering/checkEngineeringPermission.php");
require_once "../common/functions.php";
require_once "../common/customerFilters.php";

$aircraftId = $_REQUEST['aircraftId'];
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];
$detectionDate = $_REQUEST['detectionDate'];
$hostname = $_REQUEST['hostname'];

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
	/*$faultCountQuery = "SELECT c.idFault, c.idFlightLeg,c.hostName, c.reportingHostName,c.faultCode,c.monitorState, c.detectionTime, c.insertionTime, c.clearingTime, c.lastUpdate,date(c.detectionTime) as createDate FROM (SELECT * from $dbName.SYS_flight where createDate BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') a JOIN (SELECT * from $dbName.SYS_flightPhase where idFlightPhase in (4,5,6)) b JOIN (select * from $dbName.BIT_fault where detectionTime BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' AND hostName='$hostname' AND faultCode IN (400, 404, 420231, 420230, 420228, 430228)) c ON a.idFlightLeg=b.idFlightLeg AND b.idFlightLeg=c.idFlightLeg AND ((c.monitorState=3 AND c.detectionTime BETWEEN b.startTime AND b.endTime) OR (c.monitorState=1 AND (c.detectionTime BETWEEN b.startTime AND b.endTime) AND (TIMESTAMPDIFF(MINUTE,c.detectionTime,c.lastUpdate)>= 5)))";
	$faultCountQuery = "SELECT c.idFault, c.idFlightLeg,c.hostName, c.reportingHostName,c.faultCode,c.monitorState,"
						." c.detectionTime, c.insertionTime, c.clearingTime, c.lastUpdate,date(c.detectionTime) as createDate ".
						"FROM (SELECT * from $dbName.SYS_flight where createDate BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') a ".
						"JOIN (SELECT * from $dbName.SYS_flightPhase where idFlightPhase in (4,5,6)) b JOIN ".
						"(select * from $dbName.BIT_fault where detectionTime BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' ".
						"AND hostName='$hostname' AND faultCode IN (400, 404, 420231, 420230, 420228, 430228)) c ".
						"ON a.idFlightLeg=b.idFlightLeg AND b.idFlightLeg=c.idFlightLeg AND ((c.monitorState=3 AND c.detectionTime ".
						"BETWEEN b.startTime AND b.endTime) OR (c.monitorState=1 AND (c.detectionTime BETWEEN b.startTime AND b.endTime) ".
						"AND (TIMESTAMPDIFF(MINUTE,c.detectionTime,c.lastUpdate)>= 5))) AND DATE(c.detectionTime) = '$detectionDate'";*/
						
	$faultCountQuery = "select bf.serialNumber,bf.idFault, bf.idFlightLeg,bf.hostName, bf.reportingHostName,bf.faultCode,".
						"bf.monitorState, bf.detectionTime, date(bf.detectionTime) as createDate ".
						"from $dbName.bit_fault bf inner join $dbName.sys_flightphase sfp ".
						"on bf.idFlightLeg=sfp.idFlightLeg ".
						"and ((bf.monitorState=3 and bf.detectionTime<=sfp.endTime) ". 
						"or (bf.monitorState=1 and ((bf.detectionTime between sfp.startTime and sfp.endTime) ". 
						"or (bf.clearingTime between sfp.startTime and sfp.endTime) ".
						"or (bf.detectionTime <= sfp.startTime and bf.clearingTime >=sfp.endTime)) ".
						"and (TIMESTAMPDIFF(MINUTE,bf.detectionTime,bf.clearingTime)>= 5))) ".
						"and bf.faultCode in (400, 404, 420231, 420230, 420228, 430228) ".
						"and sfp.idFlightPhase in (4,5,6) ".
						"and bf.hostName='$hostname' ";
	if(!empty($detectionDate)) {
		$faultCountQuery .= "and date(bf.detectionTime) = '$detectionDate' ";
	} else {
		$faultCountQuery .= "and bf.detectionTime BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' ";
	}
	 
	$faultCountQuery .= "group by bf.faultCode, bf.hostName,bf.detectionTime,bf.monitorState,bf.idFlightLeg";
						
	error_log($faultCountQuery);
	$result = mysqli_query($dbConnection,$faultCountQuery);
	$faultData = array();
	while($row = mysqli_fetch_assoc($result)){
		$faultCode=$row['faultCode'];		
		$faultDesc=getFaultDesc($faultCode);	
		error_log('getFaultDesc ..'.$faultDesc);	
		$faultData[] = array(
				'idFault' => $row['idFault'], 
				'idFlightLeg' => $row['idFlightLeg'],
				'hostname' => $row['hostName'],
				'reportingHostName' => $row['reportingHostName'],
				'faultCode' => $row['faultCode'],
				'faultDesc' => $faultDesc,
				'monitorState' => getMonitorStateDesc($row['monitorState']),
				'detectionTime' => $row['detectionTime'],
				'insertionTime' => $row['insertionTime'],
				'clearingTime' => $row['clearingTime'],
				'lastUpdate' => $row['lastUpdate'],
				'serialNumber' => $row['serialNumber'],
			);
	}
	
	$returnResult = array("status"=>"success", "faultData"=>$faultData,"hostname"=>$hostname);
	echo json_encode($returnResult);
}else{
	echo json_encode(array("status"=>"fail", "message"=>"startDate or endDate or aircraftID missing"));
}

function getFaultDesc($faultCode){
	error_log('getFaultDesc ..'.$faultCode);
	switch ($faultCode) {
	    case "400":	    	
	        return 'Lost communications to LRU Host';
	        break;
	    case "404":
	        return 'Island mode error';
	        break;
	    case "420231":
	        return 'SDXC card mounting error.';
	        break;
	    case "420230":
	        return 'SDXC card read-only error';
	        break;
	    case "420228":
	        return 'SDXC card communication error.';
	        break;
	    case "430228":
	        return 'SDXC card communication error.';
	        break;
	    default:
	        return "";
	}
}

// Close msql connection
mysqli_close($dbConnection);
?>