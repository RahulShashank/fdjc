<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$airlineId = $_REQUEST['airlineId'];
$dataChartType = $_REQUEST['dataChartType'];
$platform = $_REQUEST['platform'];
$actype = $_REQUEST['actype'];
$lruType = $_REQUEST['lruType'];
$lruSubType = $_REQUEST['lruSubType'];
$lruTypesData = $_REQUEST['lruTypesData'];


$aircrafts = array();
if($airlineId != '') {
	$query = "SELECT id, tailsign, databaseName 
				FROM aircrafts a 
				WHERE airlineId = $airlineId";
	if($platform != '') {
		$query .= " AND platform = '$platform'";
	}
	if($actype != '') {
		$query .= " AND type = '$actype'";
	}
	$query .= " ORDER BY tailsign";
	$result = mysqli_query($dbConnection, $query );
	if ($result && mysqli_num_rows ( $result ) > 0) {
	  while($row = mysqli_fetch_array ( $result )) {
	  	$aircrafts[] = $row;
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

// MBS : Added below block to fetch the Defined HWRevsMods
$hardware_revs_mods = array();
$query = " SELECT * FROM banalytics.hardware_revs_mods ";
$result = mysqli_query($dbConnection, $query);
if($result){
	while($row=mysqli_fetch_array($result)){
		$hardware_revs_mods[$row['hwPartNumber']]=array('expectedRevisioin'=>$row['expectedRevisioin'],
			'expectedModel'=>$row['expectedModel']);
	}
}

$dataChart = array ();
$jsonNumericCheck = true;

if($dataChartType == 'revsModsChart') {
	$query = "SELECT T.hwPartNumber, T.revision, T.model, SUM(T.count) AS 'count' FROM (";
	$i = 0;
	foreach ($aircrafts as $aircraft) {
		$dbName = $aircraft['databaseName'];
		if ($i > 0) {
			$query .= " UNION ALL ";
		}

		$query .= "SELECT a.hwPartNumber, a.revision, a.model, COUNT(*) AS 'count'
				FROM $dbName.BIT_lru a
				WHERE a.lruType in ($lruType) AND a.lruSubType in ($lruSubType)
				AND a.hwPartNumber != ''
				AND hostName<>''
				AND a.lastUpdate = (
					SELECT MAX(b.lastUpdate) AS 'max'
					FROM $dbName.BIT_lru b
					WHERE a.hostName = b.hostName
					AND a.hostName<>''
				)
				GROUP BY a.hwPartNumber , a.revision, a.model";
		$i++;
	}
	$query .= " ) AS T
					GROUP BY T.hwPartNumber, T.revision, T.model
					ORDER BY count DESC";
	
    $result = mysqli_query ($dbConnection, $query );
    if($result ) {
    	if (mysqli_num_rows($result) > 0) {
		    $i = 1;
		    while ( $row = mysqli_fetch_assoc ( $result ) ) {
		        $hwPartNumber = $row ['hwPartNumber'];
		        $revision = $row ['revision'];
		        //$model = getDecimalMod( getBinaryMod( $row ['model'] ) );
				$model = getModval($row ['model']);
		        $count = $row ['count'];

		        $dataChart[] = array(
		            'value' => $count,
		            'color' => getPieBackgroundColor($i),
		            'highlight' => getPieHighlightColor($i),
		            'label' => "$hwPartNumber - $revision - $model "
		        );

		        $i++;
		    }
		} else {
			$dataChart[] = array(
	            'value' => -1,
	            'color' => '#949FB1',
	            'highlight' => '#A8B3C5',
	            'label' => "No LRU of this type"
	        );
		}
	} else {
		$dataChart[] = array(
	            'value' => -1,
	            'color' => '#F7464A',
	            'highlight' => '#FF5A5E',
	            'label' => "Error while retrieving data"
	        );
	}
} else if($dataChartType == 'revsModsTable') {
	$lruTypesData = explode(';', $lruTypesData);
	
	foreach ($lruTypesData as $lruTypeData) {
		$array = explode('-', $lruTypeData);
		$lruType = $array[0];
		$lruSubType = $array[1];

		$query = "SELECT SUM(total) as total FROM (";
		$i = 0;
		foreach ($aircrafts as $aircraft) {
			$dbName = $aircraft['databaseName'];
			if ($i > 0) {
				$query .= " UNION ALL ";
			}

			$query .= "SELECT count(*) AS 'total' 
					FROM $dbName.BIT_lru a
					WHERE a.lruType in ($lruType) AND a.lruSubType in ($lruSubType)
					AND a.hwPartNumber != ''
					AND hostName<>''
					AND a.lastUpdate = (
						SELECT MAX(b.lastUpdate) AS 'max'
						FROM $dbName.BIT_lru b
						WHERE a.hostName = b.hostName
						AND a.hostName<>''
					)";

			$i++;
		}
		$query .= ") AS T";

		$result = mysqli_query ( $dbConnection, $query );
		if ($result) {
			if (mysqli_num_rows ( $result ) > 0) {
				$row = mysqli_fetch_assoc ( $result );
				$total = $row['total'];
			} 
		} else {
			echo "error : " . mysqli_error ( $dbConnection );
		}

		$query = "SELECT T.lruType, T.lruSubType, T.hwPartNumber, T.revision, T.model, SUM(T.count) AS 'count' FROM (";
		$i = 0;
		foreach ($aircrafts as $aircraft) {
			$dbName = $aircraft['databaseName'];
			if ($i > 0) {
				$query .= " UNION ALL ";
			}

			$query .= "SELECT a.lruType, a.lruSubType, a.hwPartNumber, a.revision, a.model, COUNT(*) AS 'count'
					FROM $dbName.BIT_lru a
					WHERE a.lruType in ($lruType) AND a.lruSubType in ($lruSubType)
					AND a.hwPartNumber != ''
					AND hostName<>''
					AND a.lastUpdate = (
						SELECT MAX(b.lastUpdate) AS 'max'
						FROM $dbName.BIT_lru b
						WHERE a.hostName = b.hostName
						AND a.hostName<>''
					)
					GROUP BY a.hwPartNumber , a.revision, a.model";

			$i++;
		}
		$query .= " ) AS T
					GROUP BY T.hwPartNumber , T.revision, T.model
					ORDER BY T.hwPartNumber DESC, T.model DESC, count DESC";
		
		$result = mysqli_query ( $dbConnection, $query );
		if ($result) {
			if (mysqli_num_rows ( $result ) > 0) {
				while ( $row = mysqli_fetch_assoc ( $result ) ) {
					$count = $row['count'];
					$percentage = round(( $count / $total) * 100, 1);
					//$model = getDecimalMod( getBinaryMod( $row ['model'] ) );
					$model = getModval($row ['model']);
					
					//Calculate whether to alert user about outdated ModRev
					$alert = 'no';  //default
					$mods = explode(',',$model);
					if(isset($hardware_revs_mods[$row['hwPartNumber']])){
						$expectedMods =  explode(',',$hardware_revs_mods[$row['hwPartNumber']]['expectedModel']);
						if(count(array_intersect($expectedMods, $mods)) != count($expectedMods)){
							$alert = 'yes';
						}
					}
					
					$data = array(
						'lruTypeName' => getLruName($row['lruType'], $row['lruSubType']),
						'lruType' => $row['lruType'],
						'lruSubType' => $row['lruSubType'],
						'hwPartNumber' => $row['hwPartNumber'],
						'revision' => $row ['revision'],
						'modelHex' => $row ['model'],
						'model' => $model,
						'count' => $count,
						'percentage' => $percentage,
						'alert'	=> $alert
					);
					$dataChart [] = $data;
				}
			} 
		} else {
			echo "error : " . mysqli_error ( $dbConnection );
		}
	}
	
	$jsonNumericCheck = false;
} else if($dataChartType == 'revsModsTailsigns') {
	$hwPartNumber = $_REQUEST['hwPartNumber']; 
    $hwRevision = $_REQUEST['hwRevision'];  
    $hwModel = $_REQUEST['hwModel'];
    // Here is an interesting issue. We have multiple 0s in the database but when retrieved it is only one 0.
    // So we have to retransform to multiple 0. % is added because sometimes we have even more zeros...
    if($hwModel == '0') {
    	$hwModel = '00000000000%';
    }

	$i = 0;

	foreach ($aircrafts as $aircraft) {
		$dbName = $aircraft['databaseName'];
		$query = "SELECT COUNT(*) as count
				FROM $dbName.BIT_lru a
				WHERE a.hwPartNumber = '$hwPartNumber' AND a.revision = '$hwRevision' AND a.model LIKE '$hwModel' AND hostName<>''
				AND a.lastUpdate = (
					SELECT MAX(b.lastUpdate) AS 'max'
					FROM $dbName.BIT_lru b
					WHERE a.hostName = b.hostName
					AND a.hostName<>''
				)";

		$result = mysqli_query ( $dbConnection, $query );
		if ($result) {
			$row = mysqli_fetch_assoc ( $result );
			$count = $row['count'];
			if($count > 0 ) {
				$dataChart [] = array(
						'id' => $aircraft['id'],
						'tailsign' => $aircraft['tailsign']
					);
			}
		} else {
			echo "error $query: " . mysqli_error ( $dbConnection );
		}
	}


}

// I had the error "double INF does not conform to the JSON spec" when getting list data (chart data were OK) for certain aircrafts
// Not using the JSON_NUMERIC_CHECK parameter for the list solved the issue.
if($jsonNumericCheck == true) {
	echo json_encode($dataChart, JSON_NUMERIC_CHECK );
} else {
	echo json_encode($dataChart);
}
?>
