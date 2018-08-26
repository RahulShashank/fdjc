<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$airlineId = $_REQUEST['airlineId'];
$dataChartType = $_REQUEST['dataChartType'];
$inputPlatform = $_REQUEST['platform'];
$inputConfiguration = $_REQUEST['configuration'];

// Get a/c configurations the airline
$query = "SELECT DISTINCT(Ac_Configuration), platform
FROM aircrafts 
WHERE airlineId = $airlineId
AND Ac_Configuration != '' ";

if(is_array($inputPlatform)) {
    $query .= " and platform in(";
    foreach ($inputPlatform as $p) {
        $query .= "'" . $p . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if(!empty($inputPlatform)) {
    $query .= " and platform='$inputPlatform'";
}

if(is_array($inputConfiguration)) {
    $query .= " and Ac_Configuration in(";
    foreach ($inputConfiguration as $configType) {
        $query .= "'" . $configType . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if(!empty($inputConfiguration)) {
    $query .= " and Ac_Configuration='$inputConfiguration'";
}

$query .=" GROUP BY platform, Ac_Configuration ORDER BY platform, Ac_Configuration";
// error_log("Query: " . $query);
$resultArray = array();

$result = mysqli_query ($dbConnection, $query );

if($result){
    if( mysqli_num_rows($result) ) {
        echo "<div class=\"row placeholders\" style=\"margin-bottom:5px\">";
        
        while ( $row = mysqli_fetch_array ( $result ) ) {
            $platform = $row ['platform'];
            $configuration = $row ['Ac_Configuration'];
            $label = $platform . " - " . $configuration;
            
            echo
                "<div class=\"col-xs-12 col-sm-6 col-md-3 placeholder\">
					<div class=\"chart-panel\">
						<canvas id=\"" . $platform . "-" . $configuration . $dataChartType. "Chart\" width=\"200\" height=\"200\"></canvas>
					</div>
				</div>
				<script>";
            $labelArray = array();
            $valueArray = array();
            
            $query2 = "SELECT $dataChartType, COUNT(*) as count FROM aircrafts
						WHERE Ac_Configuration = '$configuration'
						AND platform = '$platform'
						AND airlineId = $airlineId
						GROUP BY $dataChartType
						ORDER BY $dataChartType DESC";
            
            $result2 = mysqli_query ($dbConnection, $query2);
            if($result2) {
                while ( $row2 = mysqli_fetch_array ( $result2 ) ) {
                    $label1 = $row2["$dataChartType"];
                    if($label1 == ''){
                        $label1 = "No $dataChartType";
                    }
                    array_push($labelArray, $label1);
                    array_push($valueArray, $row2["count"]);
                }
            } else{
                echo "Error $query2 :" . mysqli_error($dbConnection); exit;
            }
            
            $labelString = "\"" . rtrim(implode('","', $labelArray), ',"') . "\"";
            $valueString = implode(",", $valueArray);
            $bgColor = "\"#2096BA\", \"#315b7d\",\"#765285\",\"#E25F70\",\"#351C4D\"";
//             $bgColor = "\"#a3c2db\", \"#6c9dc6\",\"#4682b4\",\"#396a93\",\"#24435c\"";
            //             backgroundColor: [\"#b6cee2\", \"#91b6d4\",\"#6c9dc6\",\"#4682b4\",\"#396a93\"],
            
            echo "new Chart(document.getElementById(\"" . $platform . "-" . $configuration . $dataChartType. "Chart\"), {
                        type: 'pie',
                        data: {
                          labels: [$labelString],
                          datasets: [{
                            backgroundColor: [$bgColor],
                            data: [$valueString],
                            borderWidth: 0.75
                          }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: false,
                            tooltips: {
                                 enabled: false
                            },
                            legend: {
                                labels: {
                                    boxWidth: 13
                                }
                            },
                            title: {
                                display: true,
                                text: '$label',
                                fontSize: 16,
                                position: 'bottom',
                            },
                            pieceLabel: {
                                render: 'value',
                                fontSize: 13,
                                fontStyle: 'bold',
                                fontColor: 'white'
                            }
                        }
                    });
                </script>";
        }
        
        echo "</div>";
    }
} else{
    echo "Error $query :" . mysqli_error($dbConnection); exit;
}
?>
