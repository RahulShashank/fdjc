<?php
session_start ();
$menu = 'Airlines';
require_once("checkEngineeringPermission.php");
/* require_once "../database/connecti_database.php";
include ("../engineering/BlockCustomer.php"); */
include ("BlockCustomer.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );
error_log('airline Ids..'.$airlineIds);
?>
<!DOCTYPE html>
<html lang="en" data-ng-app="myApp">
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/icon.ico">
<title>BITE Analytics</title>
<link rel="stylesheet" href="../css/fontawesome/font-awesome.min.css">
<link rel="stylesheet" href="../css/chosen/chosen.min.css">
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<script src="../js/jquery-1.11.2.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
</head>
<style>
.search2 {
    padding: 6px 15px 6px 30px;
    margin: 3px;
    background: url(../img/search.png) no-repeat 8px 6px;
    outline: none;
    border: 1px solid #d0d0d0;
}
.rounded {
    -webkit-border-radius: 13px;
}
</style>
<body id="bodyDiv" ng-controller="airlinesCtrl">	
	<!-- START PAGE CONTAINER -->
	<div class="page-container">
		<!-- START PAGE SIDEBAR -->
            <?php include("SideNavBar.php"); ?>
        <!-- END PAGE SIDEBAR -->

		<!-- PAGE CONTENT -->
		<div class="page-content">
			<!-- START X-NAVIGATION VERTICAL -->
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel">
				<!-- TOGGLE NAVIGATION -->
				<li class="xn-icon-button"><a href="#" class="x-navigation-minimize"><span
						class="fa fa-dedent"></span></a></li>
				<!-- END TOGGLE NAVIGATION -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span></a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->

			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">Airline Dashboard</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title" style="padding-right: 12px;">
				<h2>Airline Dashboard</h2>				
          	</div>
          
	        <div id="ctrldiv" class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">					
							<div class="panel-body">
	          					<div class="container-fluid">
	          						<div class="lighter text-center">
	            						<span><input type="text" class="search2 rounded" placeholder="Search..." size="50" data-ng-model="searchKeyword"></span>
	            						<br><br>
	          						</div>
	          						<div id="loading" align="center"><img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...</div>	              					
	              					<div class="row">
										<div class="col-xs-12 col-sm-6 col-md-3" data-ng-repeat="airline in airlines | filter: searchKeyword | orderBy: ['-offloadStatus','acronym']">
											<!-- <div class="card" ng-class="{'cardDanger': (airline.status == 2), 'cardWarning': (airline.status == 1), 'cardOK': (airline.status == 0)}"}> -->
											<div class="card" ng-class="{'cardOffloadStatus': (airline.offloadStatus == true)}"}>
												<div class="cardHeaderFooter">
													{{ airline.name }}
												</div>
							
												<div class="cardBody">
													<strong style="color:#428bca">{{ airline.acronym }}<strong>
												</div>
												<div class="cardHeaderFooter">
													<a href="AircraftTimeline.php?aircraftVisited=false&airlineId={{airline.id}}"><i class="fa fa-signal fa-fw" aria-hidden="true" title="Timeline"></i></a>
													&nbsp;
													<a href="offloadsCoverage.php?airlineId={{airline.id}}"><i class="fa fa-file-archive-o fa-fw" aria-hidden="true" title="Offloads Coverage"></i></a>
													&nbsp;
													<a href="fleetStatus.php?airlineId={{airline.id}}"><i class="fa fa-tasks fa-fw" aria-hidden="true" title="Fleet Status"></i></a>
													&nbsp;
													<a href="HardwareRevisionsMods.php?airlineId={{airline.id}}"><i class="fa fa-table fa-fw" aria-hidden="true" title="Hardware Rev & Mod"></i></a>													
												</div>
											</div>
										</div>
	              					</div>
	          					</div> 
	          				</div>
	          			</div>
	          		</div>
				</div>
			</div>
	
			<!-- END PAGE CONTENT WRAPPER -->
		</div>
			<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->
	
	<!-- Logout page -->
	<?php include("../logout.php"); ?>
	<!-- END Logout page-->
	
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	
</body>
<script>

var app = angular.module('myApp', []);
app.controller('airlinesCtrl', function($scope, $http) {
    init();
    function init() {
        getAirlines();
    }
    function getAirlines() {
        $http
            .get("../ajax/getAirlinesnOffloadStatus.php", {
                params: {
                    airlineIds: '<?php echo "$airlineIds";?>'
                }
             })
             .success(function (data,status) {
                console.log(data);
                  $scope.airlines = data;
                  $("#loading").hide();
             });
    }
});
</script>

</html>