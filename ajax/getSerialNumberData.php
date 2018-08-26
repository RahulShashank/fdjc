<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";

$airlineId = $_REQUEST['airlineId'];
$tailsign = $_REQUEST['tailsign'];
$config = $_REQUEST['configType'];
$platform = $_REQUEST['platform'];
$software = $_REQUEST['software'];
$serialNumber = trim($_REQUEST['serialNumber']);

// A1480788
// A1395646
// A1308259

if($serialNumber == '') {
	$error = 'No given serial number.';
} else if($airlineId != '') {
	$query = "SELECT id, tailsign, databaseName FROM aircrafts a WHERE airlineId = $airlineId";
	
	if($tailsign != ''){
		$query.=" AND tailsign='$tailsign'";
	}
	if($config != ''){
		$query.=" AND Ac_Configuration='$config'";
	}
	if($platform != ''){
		$query.=" AND platform='$platform'";
	}
	if($software != ''){
		$query.=" AND software='$software'";
	}
	error_log($query);
	$result = mysqli_query($dbConnection, $query );

	$i =0;
	if ($result && mysqli_num_rows ( $result ) > 0) {
	  $tailsigns = array();
	  $groups = array();
	  $items = array();
			
	  while($row = mysqli_fetch_array ( $result )) {
		$tailsign = $row["tailsign"];
		$dbName = $row["databaseName"];

		if ($i > 0) {
			$queryBitLru .= " UNION ALL ";
			$queryBitRemoval .= " UNION ALL ";
		} else {
			$queryBitLru = "SELECT * FROM (";
			$queryBitRemoval = "SELECT DISTINCT (removalDate), tailsign, hostName, serialNumber, newSerialNumber FROM (";
			// Note: use discting as we could still have several entries for the same removal in the database
		}

		$queryBitLru .= "SELECT '$tailsign' as tailsign, hostName, serialNumber, lruType, lruSubType, hwPartNumber, revision, model, macAddress, lastUpdate
					FROM $dbName.BIT_lru a
					WHERE serialNumber = '$serialNumber'";
		$queryBitRemoval .= "SELECT '$tailsign' as tailsign, hostName, removalDate, serialNumber, newSerialNumber
					FROM $dbName.BIT_removedLru
					WHERE ( (serialNumber = '$serialNumber') OR (newSerialNumber = '$serialNumber') )";
					
		$i++;
	  }		
	  $queryBitLru .= ") AS t ORDER BY lastUpdate DESC LIMIT 1";
	  $queryBitRemoval .= ") AS t";
		//echo $queryBitLru;exit;
		
		$resultBitLru = mysqli_query ( $dbConnection, $queryBitLru );
		if ($resultBitLru) {			
			$rowBitLRU = mysqli_fetch_array ( $resultBitLru );
			if( $rowBitLRU['serialNumber'] == null ) {
				$error = "Serial Number not found.";
			} else {
				$lruName = getLruName($rowBitLRU['lruType'], $rowBitLRU['lruSubType']);
				
				$serialNumberData = array(
						'tailsign' => $rowBitLRU['tailsign'],
						'hostname' => $rowBitLRU['hostName'],
						'serialNumber' => $rowBitLRU['serialNumber'],
						'lruName' => $lruName,
						'hwPartNumber' => $rowBitLRU['hwPartNumber'],
						'revision' => $rowBitLRU['revision'],
						'model' => getModVal( $rowBitLRU['model'] ),
						'macAddress' => $rowBitLRU['macAddress'],
						'lastUpdate' => date('Y-m-d', strtotime($rowBitLRU['lastUpdate'])),
					);
			}
		} else {
			echo "error $queryBitLru: " . mysqli_error ( $dbConnection );
		}
		
		$serialNumberRemovals = array();
		$resultBitRemoval = mysqli_query ( $dbConnection, $queryBitRemoval );
		if ($resultBitRemoval) {			
			
			while( $rowBitRemoval = mysqli_fetch_array ( $resultBitRemoval ) ) {;
				$tail = $rowBitRemoval['tailsign'];
				if(!in_array($tail, $tailsigns)){
					$groups[] = array(
						'id' => $tail,
						'content' => "<strong>$tail<strong>"
					);
					$tailsigns[] = $tail;
				}
				
				$items[] = array(
					'group' => $rowBitRemoval['tailsign'],					
					'content' => $rowBitRemoval['hostName'],
					'title' => $rowBitRemoval['removalDate'],
					'start' => $rowBitRemoval['removalDate'],
					'type' => 'point',
					'className' => 'removed'
				);
			}
			
		} else {
			echo "<br>error $queryBitRemoval: " . mysqli_error ( $dbConnection ) . "<br>";
		}
	} else {
	  // echo "error here: " . mysqli_error ( $dbConnection );
		// there is no aircrafts corresponding to the filter options.Return an empty array.
		echo json_encode(array(), JSON_NUMERIC_CHECK );
		exit;
	}
} else {
	echo "no airlineId";
	exit;
}

$data = array(
	'error' => $error,
	'current' => $serialNumberData,
	'removals' => array('groups' => $groups, 'items' => $items),
);

echo json_encode($data);
?>