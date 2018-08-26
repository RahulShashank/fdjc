<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$airlineId = $_REQUEST['airlineId'];
$dataChartType = $_REQUEST['dataChartType'];

// Get a/c configurations the airline
$query = "SELECT DISTINCT(Ac_Configuration), platform
FROM aircrafts 
WHERE airlineId = $airlineId
AND Ac_Configuration != ''
GROUP BY platform, Ac_Configuration
ORDER BY platform, Ac_Configuration";

$result = mysqli_query ($dbConnection, $query );

if($result){
	if( mysqli_num_rows($result) ) {
		echo "<div class=\"row placeholders\" style=\"margin-bottom:5px\">";
		
		while ( $row = mysqli_fetch_array ( $result ) ) {
			$platform = $row ['platform'];
			$configuration = $row ['Ac_Configuration'];
			$label = $platform . " - " . $configuration;
			
			echo 
				"<div class=\"col-xs-12 col-sm-6 col-md-4 placeholder\">
					<div class=\"chart-panel\">
						<div class=\"text-left\">
							<div style=\"margin: 0 auto\" id=\"" . $platform . "-" . $configuration . "-" . $dataChartType . "legendDiv\" class=\"chart-legend\"></div>
						</div>
						<div>
							<canvas id=\"" . $platform . "-" . $configuration . $dataChartType. "Chart\"></canvas>
						</div>
						<h4 title=\"$tooltip\">$label</h4>
					</div>
				</div>
				<script>
					var ctx = document.getElementById(\"" . $platform . "-" . $configuration . $dataChartType. "Chart\").getContext(\"2d\");
					var data = [";
					
			$query2 = "SELECT $dataChartType, COUNT(*) as count FROM aircrafts 
						WHERE Ac_Configuration = '$configuration' 
						AND platform = '$platform'
						AND airlineId = $airlineId 
						GROUP BY $dataChartType
						ORDER BY $dataChartType DESC";
						
			$result2 = mysqli_query ($dbConnection, $query2);
			if($result2) {
				$i = 1;
				while ( $row2 = mysqli_fetch_array ( $result2 ) ) {
					$count = $row2['count'];
					$label = $row2["$dataChartType"];
					if($label == ''){
						$label = "No $dataChartType";
					}
					echo "{
							value: $count,
							color: \"" . getPieBackgroundColor($i) ."\",
							highlight: \"" . getPieHighlightColor($i) ."\",
							label: \"$label\"
						},";
					$i++;
				}
			} else{
				echo "Error $query2 :" . mysqli_error($dbConnection); exit;
			}
				
			echo
					"];
					var myPieChart = new Chart(ctx).Pie(data, {
						animation: false,
						responsive: true,
					})
					document.getElementById(\"" . $platform . "-" . $configuration . "-" . $dataChartType . "legendDiv\").innerHTML = myPieChart.generateLegend();
				</script>";
		}
		
		echo "</div>";
	}
} else{
	echo "Error $query :" . mysqli_error($dbConnection); exit;
}

?>
