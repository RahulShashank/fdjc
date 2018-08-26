<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
require_once "../map/airports.php";

require_once("checkEngineeringPermission.php");

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];


if(isset($aircraftId)) {
    checkAircraftPermission($dbConnection, $aircraftId);
}

if($aircraftId != '') {
    // Get information to display in header
    $query = "SELECT a.tailsign, b.id, b.name, a.databaseName, a.isp FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $aircraftTailsign = $row ['tailsign'];
      $airlineId = $row['id'];
      $airlineName = $row['name'];
      $dbName = $row['databaseName'];
	  $aircraftIsp = $row['isp'];
    } else {
      echo "error: " . mysqli_error ( $error );
    }
} else if($sqlDump != '') {
    $dbName = $sqlDump;
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}

$selected = mysqli_select_db($dbConnection, $dbName)
		or die("Could not select ".$dbName);

// data for map
$displayMap = false;

$flightLegsArray = explode (",", $flightLegs);
$flightLegsCount = count($flightLegsArray);

if($flightLegsCount == 1 ) {
	$flightLegId = $flightLegsArray[0];
	
	$query = "SELECT departureAirportCode, arrivalAirportCode FROM SYS_flight WHERE idFlightLeg = $flightLegId";
	$result = mysqli_query($dbConnection, $query);
	if($result) {
		$row = mysqli_fetch_array($result);
		$departureAirportCode = trim($row['departureAirportCode']);
		$arrivalAirportCode = trim($row['arrivalAirportCode']);
		
		//echo "*$departureAirportCode* - *$arrivalAirportCode*";
		
		if($departureAirportCode != "" && $arrivalAirportCode != "" && strpos($$departureAirportCode, '-') === false && strpos($arrivalAirportCode, '-') === false) {
			$departureAirportInfo = getAirportInfo($departureAirportCode);
			if($departureAirportInfo != "") {
				$departureAirportLat = $departureAirportInfo['lat'];
				$departureAirportLong = $departureAirportInfo['long'];
				$departureAirportName = $departureAirportInfo['name'];

				$arrivalAirportInfo = getAirportInfo($arrivalAirportCode);			
				if($arrivalAirportInfo != "") {
					$arrivalAirportLat = $arrivalAirportInfo['lat'];
					$arrivalAirportLong = $arrivalAirportInfo['long'];
					$arrivalAirportName = $arrivalAirportInfo['name'];

					// Need to do some workaround to have flights crossing the Pacific Ocean and not the entire world
					if($departureAirportLong >= 90 && $arrivalAirportLong <= -20) {
						$arrivalAirportLong += 360;
					} else if($departureAirportLong <= -20 && $arrivalAirportLong >= 90) {
						$departureAirportLong += 360;
					}
					
					$displayMap = true;
				}
			}			
		}
	} else {
		echo "Problem with query $query"; exit;
	}
}
//echo $displayMap; exit;
?>
<!DOCTYPE html>
<html lang="en" data-ng-app="myApp">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="../img/favicon.ico">

    <title>BITE Analytics</title>

    <link href="../css/styles.css" rel="stylesheet">
    <!-- Bootstrap core CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/bootstrap-theme.min.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="../css/jquery.datetimepicker.css" rel="stylesheet" />
    <link href="../css/vis.css" rel="stylesheet">
	<link rel="stylesheet" href="../css/leaflet/leaflet.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../css/font-awesome.min.css">
	<link rel="stylesheet" href="../css/chosen/chosen.min.css">

    <script src="../js/jquery-1.11.2.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/angular.js"></script>
    <script src="../js/jquery.datetimepicker.js"></script>
    <script src="../js/vis.min.js"></script>
    <script src="../js/Chart.js"></script>
	<script src="../js/leaflet/leaflet.js"></script>
	<script src="../js/chosen/chosen.jquery.min.js"></script>
  </head>

  <body>

    <?php
    include("topNavBar.html");
	include("flightBreadCrumb.html");
   ?>
	
<div class="container-fluid">
    <div class="row">
        <?php
          include("flightSideBar.html");
        ?>
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
            <h2 class="page-header">Summary</h2>
			<?php
				if($displayMap) {
					echo "<div id=\"map\" style=\"height: 300px\"></div>";
					echo "<br><br>";
				}
			?>
            <div class="row" ng-controller="flightStatusController">
				<div class="col-md-4" data-ng-repeat="status in statuses">
					<div class="card" ng-class="{'cardDanger': (status.value == 2), 'cardWarning': (status.value == 1), 'cardOK': (status.value == 0)}"}>
						<div class="cardStatus">
							{{ status.name }}
						</div>
					</div>
				</div>
            </div>
            <br>
            <div id="flightTimeline" class="flightTimeline"></div>
            <div id="loadingTimeline">
                <img src="../img/ajaxLoading.gif"> Loading Timeline...
            </div>
        </div>
    </div>
</div>
</body>
<script>
	<?php
	echo "var displayMap = "; echo $displayMap ?  "true;" : "false;"; 
	?>
	if(displayMap) {
		<?php
			echo "var departureAirportCode = '$departureAirportCode';";
			echo "var departureAirportName = '$departureAirportName';";
			if($departureAirportLat != '') echo "var departureAirportLat = $departureAirportLat;";
			if($departureAirportLong != '') echo "var departureAirportLong = $departureAirportLong;";

			echo "var arrivalAirportCode = '$arrivalAirportCode';";
			echo "var arrivalAirportName = '$arrivalAirportName';";
			if($arrivalAirportLat != '') echo "var arrivalAirportLat = $arrivalAirportLat;";
			if($arrivalAirportLong != '') echo "var arrivalAirportLong = $arrivalAirportLong;";
			
			
		?>
	
		var map = L.map('map').fitBounds(
									[
										[departureAirportLat, departureAirportLong],
										[arrivalAirportLat, arrivalAirportLong]
									],
									{padding: [20,20]}
								);

		// var url = 'http://otile4.mqcdn.com/tiles/1.0.0./sat/{z}/{x}/{y}.png';
		var url = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';

		L.tileLayer(url, {
			maxZoom: 18,
			id: 'mapbox.streets'
		}).addTo(map);


		var departureIcon = L.icon({
			iconUrl: '../img/departure.png',
			shadowUrl: '../img/marker-shadow.png',
			iconAnchor: [15,50],
			popupAnchor:  [2, -40]
		});

		var arrivalIcon = L.icon({
			iconUrl: '../img/arrival.png',
			shadowUrl: '../img/marker-shadow.png',
			iconAnchor: [15,50],
			popupAnchor:  [2, -40]
		});
		
		L.marker(
				[
					departureAirportLat, 
					departureAirportLong
				],
				{icon: departureIcon}
			)
			.addTo(map)
			.bindPopup(
				"<b>Airport Name</b>: " + departureAirportName + "<br>" +
				"<b>Code</b>: " + departureAirportCode +
				"<br><br>This the <b>DEPARTURE</b> airport."
			);
			
		L.marker(
				[
					arrivalAirportLat, 
					arrivalAirportLong
				]
				,
				{icon: arrivalIcon}
			).addTo(map)
			.bindPopup(
				"<b>Airport Name</b>: " + arrivalAirportName + "<br>" +
				"<b>Code</b>: " + arrivalAirportCode +
				"<br><br>This the <b>ARRIVAL</b> airport."
			);
		
		var polyline = L.polyline(
			[
				[departureAirportLat, departureAirportLong],
				[arrivalAirportLat, arrivalAirportLong]
			], 
			{color: 'red', weight: 3, opacity: 0.9, dashArray: "10,5"}
		).addTo(map);
	}
</script>
<script>
$(document).ready(function(){
    $('.nav-sidebar li').removeClass('active');
    $("#sideBarSummary").addClass("active");
	
    data = {
        <?php 
            if($aircraftId != '')  {
                echo "aircraftId: $aircraftId";
            }
            else {
                echo "sqlDump: '$sqlDump'";
            }
            echo ",
                flightLegs: '$flightLegs'";
        ?>
    };

    getTimeLineData(data);

	<?php
		include('chosen.html');
	?>
});

function getTimeLineData(data) {
    $.ajax({
        type: "POST",
        dataType: "json",
        url: "../ajax/getAircraftTimeLineData.php",
        data: data,
        success: function(data) {
            //console.log(data);

            $('#startDateTimePicker').datetimepicker({
                value: data.options.start,
                step:15,
                weeks:true
            });

            $('#endDateTimePicker').datetimepicker({
                value: data.options.end,
                step:15,
                weeks:true
            });

            createTimeline(data, 'flightTimeline', 'loadingTimeline');
        },
        error: function (err) {
            console.log('Error', err);
        }
    });
}



function createTimeline(data, timelineId, loadingId) {
    $('#'+loadingId).hide();

    var container = document.getElementById(timelineId);

    var groups = new vis.DataSet(
        data.groups
        );

    var items = new vis.DataSet(
        data.items
        );

    var options = {
        orientation: 'both',
        start: data.options.start,
        end: data.options.end,
        min: data.options.min,
        max: data.options.max,
        clickToUse: true,
        stack: false,
        multiselect: true
    };

    timeline = new vis.Timeline(container, items,  groups, options);
    timeline.on('select', function (properties) {
        var selectedItems = properties.items;
        flightLegs = ''; // reset flight Legs

        if(selectedItems != null) {
            for (i = 0; i < selectedItems.length; i++) { 
                var selectedItem = selectedItems[i];
                var res = selectedItem.split("/");
                if(res[0] == 'FLI') {
                    if(flightLegs != '') {
                        flightLegs += ',';
                    }
                    flightLegs += res[1];
                }
            }
        }
    });
    timeline.on('contextmenu', function (props) {
      //alert('Right click!');
      props.event.preventDefault();
    });
}
</script>
<script>
var app = angular.module('myApp', []);
app.controller('flightStatusController', function($scope, $http) {
        init();

        function init() {
            getFlightStatuses();
        }

        function getFlightStatuses() {
            <?php 
                if($aircraftId != '')  {
                    $param = "aircraftId=$aircraftId";
                }
                else {
                    $param = "sqlDump=$sqlDump";
                }
                $param .= "&flightLegs=$flightLegs";
            ?>

            $http.get("../ajax/getFlightStatusData.php?<?php echo $param; ?>")
                .success(function (data) {
                    //console.log(data);
                    $scope.statuses = data;
                });
        }
});
</script>
</html>
