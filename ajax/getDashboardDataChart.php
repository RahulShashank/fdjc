<?php

date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$dataChartType = $_REQUEST['dataChartType'];
$airlineId = $_REQUEST['airlineId'];


if($dataChartType == 'aircrafts') {
    $query = "SELECT type, count('type') AS count
    FROM aircrafts 
    WHERE airlineId = $airlineId
    GROUP BY type
    ORDER BY count DESC";

    $result = mysqli_query ($dbConnection, $query );

    $actypes = array ();
    $i = 0;
    while ( $row = mysqli_fetch_assoc ( $result ) ) {
        $type = $row ['type'];
        $count = $row ['count'];

        $dataChart[] = array(
            'value' => $count,
            'color' => getPieBackgroundColor($i),
            'highlight' => getPieHighlightColor($i),
            'label' => "$type"
        );

        $i++;
    }
} else if($dataChartType == 'platforms') {
    $query = "SELECT platform, count('platform') AS count
    FROM aircrafts 
    WHERE airlineId = $airlineId
    GROUP BY platform
    ORDER BY count DESC";

    $result = mysqli_query ($dbConnection, $query );

    $actypes = array ();
    $i = 0;
    while ( $row = mysqli_fetch_assoc ( $result ) ) {
        $type = $row ['platform'];
        $count = $row ['count'];

        $dataChart[] = array(
            'value' => $count,
            'color' => getPieBackgroundColor($i),
            'highlight' => getPieHighlightColor($i),
            'label' => "$type"
        );

        $i++;
    }
}  else if($dataChartType == 'softwares') {
    $query = "SELECT software, count('software') AS count
    FROM aircrafts 
    WHERE airlineId = $airlineId
    GROUP BY software
    ORDER BY count DESC";

    $result = mysqli_query ($dbConnection, $query );

    $actypes = array ();
    $i = 0;
    while ( $row = mysqli_fetch_assoc ( $result ) ) {
        $type = $row ['software'];
        $count = $row ['count'];

        $dataChart[] = array(
            'value' => $count,
            'color' => getPieBackgroundColor($i),
            'highlight' => getPieHighlightColor($i),
            'label' => "$type"
        );

        $i++;
    }
}

echo json_encode($dataChart, JSON_NUMERIC_CHECK );
?>
