<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";

require_once('../engineering/checkEngineeringPermission.php');

$dataChartType = $_REQUEST['dataChartType'];
$lruType = $_REQUEST['lruType'];
$lruSubType = $_REQUEST['lruSubType'];
$lruTypesData = $_REQUEST['lruTypesData'];
$airlineId = $_REQUEST['airlineId'];
$platform = $_REQUEST['platform'];
$configType = $_REQUEST['configType'];
$tailsign = $_REQUEST['tailsign'];

$hwRevModArray = array ();

$dbNameQuery = "select al.id, al.acronym, al.name, ac.platform, ac.Ac_Configuration as configuration, ac.tailsign, ac.databaseName FROM $mainDB.aircrafts ac, airlines al where 1=1";

if(!isNullOrEmptyString($airlineId)) {
    $dbNameQuery .= " and ac.airlineID=$airlineId";
}

if(is_array($platform)) {
    $dbNameQuery .= " and ac.platform in(";
    foreach ($platform as $platform) {
        $dbNameQuery .= "'" . $platform . "',";
    }
    $dbNameQuery = rtrim($dbNameQuery, ",") . ")";
} else if(!isNullOrEmptyString($platform)) {
    $dbNameQuery .= " and ac.platform=$platform";
}

if(is_array($configType)) {
    $dbNameQuery .= " and ac.Ac_Configuration in(";
    foreach ($configType as $configType) {
        $dbNameQuery .= "'" . $configType . "',";
    }
    $dbNameQuery = rtrim($dbNameQuery, ",") . ")";
} else if(!isNullOrEmptyString($configType)) {
    $dbNameQuery .= " and ac.Ac_Configuration='$configType'";
}

if(is_array($tailsign)) {
    $dbNameQuery .= " and ac.tailsign in(";
    foreach ($tailsign as $tailsign) {
        $dbNameQuery .= "'" . $tailsign . "',";
    }
    $dbNameQuery = rtrim($dbNameQuery, ",") . ")";
} else if(!isNullOrEmptyString($tailsign)) {
    $dbNameQuery .= " and ac.tailsign='$tailsign'";
}

$dbNameQuery .= " and ac.airlineId = al.id order by ac.tailsign";

$result = mysqli_query($dbConnection, $dbNameQuery);
if ($result && mysqli_num_rows ( $result ) > 0) {
    while ($row = mysqli_fetch_assoc ($result) ) {
        $dbName = $row['databaseName'];
        
        if(mysqli_select_db($dbConnection, $dbName)) {
            $revModQuery = "SELECT a.hostName, a.hwPartNumber, a.serialNumber, a.revision, a.model, a.lastUpdate ".
                "FROM $dbName.BIT_lru a ".
                "WHERE hostName<>'' ".
                "AND a.hwPartNumber != '' ".
                "AND a.lastUpdate = ( ".
                	"SELECT MAX(b.lastUpdate) ".
                	"FROM $dbName.BIT_lru b ".
                	"WHERE a.hostName = b.hostName ".
                	"AND a.hostName<>'' ".
                ") GROUP BY a.hostName ORDER BY a.hostName";
            
            $revModResult = mysqli_query($dbConnection, $revModQuery);
            if ($revModResult && mysqli_num_rows ( $revModResult ) > 0) {
                while ($revModRow = mysqli_fetch_assoc ($revModResult) ) {
                    $model = getModval( $revModRow ['model']);
    				$data = array(
    				    "airline" => $row['name'],
    				    "platform" => $row['platform'],
    				    "configuration" => $row['configuration'],
    				    "tailsign" => $row['tailsign'],
    				    "hostName" => $revModRow['hostName'],
    				    "hwPartNumber" => $revModRow['hwPartNumber'],
    				    "serialNumber" => $revModRow['serialNumber'],
    				    "revision" => $revModRow ['revision'],
    					"model" => $model,
    				    "lastUpdate" => $revModRow['lastUpdate']
    				);
    				$hwRevModArray [] = $data;
                }
            }
        }
    }
}

echo json_encode($hwRevModArray);

function isNullOrEmptyString($str) {
    return (! isset($str) || trim($str) === '');
}

?>