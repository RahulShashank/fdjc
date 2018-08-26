<?php
session_start ();
$menu = 'MaintenanceActivities';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );
error_log('airline Ids..'.$airlineIds);
// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "6 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );
$submenu=$_SESSION['submenu'];
$maintenanceActivitiesVisited = $_REQUEST ['maintenanceActivitiesVisited'];
error_log('maintenanceActivitiesVisited : '.$submenu);

if($maintenanceActivitiesVisited=='true'){
	error_log('Airline in timeline : '.$_SESSION['airlineId']);
}else{
		$maintenanceActivitiesVisited=false;
		$_SESSION['airlineId'] =  '';
		$_SESSION['platform'] =  '';
		$_SESSION['configType'] =  '';
		$_SESSION['tailsignList'] =  '';
		$_SESSION['software'] = '';
		$_SESSION['startDate'] = ''; 
		$_SESSION['endDate'] =  '';
		$_SESSION['hostNamelru']='';
		$_SESSION['serialNumberRemoval']='';
		$_SESSION['hostnameRemoval']='';
		$_SESSION['hwPartNumberRemoval']='';
		$_SESSION['serialNumber']='';
		$_SESSION['submenu']='';
	
}

$aircraftId = $_REQUEST['aircraftId'];
error_log("Aircraft Id from another page: " . $aircraftId);

$airlineId = 0;
$platform = "";
$configuration = "";
$software = "";
$tailsign = "";

if($aircraftId > 0) {
    $query = "select airlineId, platform, Ac_Configuration as configuration, software, tailsign from aircrafts where id=$aircraftId";
    
    $result = mysqli_query($dbConnection, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        if ($row = mysqli_fetch_assoc($result)) {
            $airlineId = $row['airlineId'];
            $platform = $row['platform'];
            $configuration = $row['configuration'];
            $software = $row['software'];
            $tailsign = $row['tailsign'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-ng-app="myApp">
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/icon.ico">
<title>BITE Analytics</title>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->
<script src="../js/jquery-1.11.2.js"></script>
<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/angular-route.js"></script>
<script src="../js/angular-cookies.js"></script>
<!--<script src="../js/plugins/jquery/jquery.min.js"></script>-->
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<link rel="stylesheet"	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
<script src="../js/bootstrap-multiselect.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>
<link href="../css/vis.css" rel="stylesheet">
<script src="../js/vis.min.js"></script>
<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>

</head>
<style>
		.dataTables_length, .dataTables_info {
			float:left;
		}
		
		.dataTables_filter label {
			margin-right: 5px;
		}
		
		.dataTables_paginate {
			margin-top: 5px !important;
		}
		
		.html5buttons, .dataTables_paginate {
			float:right;
		}
		
		hc-chart {
			width: 100%;
			display: block;
		}
		
		.loader,
		.loader:before,
		.loader:after {
		  border-radius: 50%;
		  width: 2.5em;
		  height: 2.5em;
		  -webkit-animation-fill-mode: both;
		  animation-fill-mode: both;
		  -webkit-animation: load7 1.8s infinite ease-in-out;
		  animation: load7 1.8s infinite ease-in-out;
		}
		.loader {
		  color: #0080ff;
		  font-size: 10px;
		  margin: 80px auto;
		  position: relative;
		  text-indent: -9999em;
		  -webkit-transform: translateZ(0);
		  -ms-transform: translateZ(0);
		  transform: translateZ(0);
		  -webkit-animation-delay: -0.16s;
		  animation-delay: -0.16s;
		}
		.loader:before,
		.loader:after {
		  content: '';
		  position: absolute;
		  top: 0;
		}
		.loader:before {
		  left: -3.5em;
		  -webkit-animation-delay: -0.32s;
		  animation-delay: -0.32s;
		}
		.loader:after {
		  left: 3.5em;
		}
		@-webkit-keyframes load7 {
		  0%,
		  80%,
		  100% {
			box-shadow: 0 2.5em 0 -1.3em;
		  }
		  40% {
			box-shadow: 0 2.5em 0 0;
		  }
		}
		@keyframes load7 {
		  0%,
		  80%,
		  100% {
			box-shadow: 0 2.5em 0 -1.3em;
		  }
		  40% {
			box-shadow: 0 2.5em 0 0;
		  }
		}
		
		.dateChange{
            background-color:#F9F9F9 !important;
            color:#000000 !important;
            cursor: auto !important;
        }
        .chart-panel {
		    background-color: #FCFCFC;
		    border: 1px solid #E8E8E8;
		    padding: 10px;
		}
		.chart-legend li span {
		    display: inline-block;
		    width: 12px;
		    height: 12px;
		    margin-right: 5px;
		    border-radius: 2px;
		}
		.text-muted {
			color: #777 !important;
		}
	</style>
<body>
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
				<li class="active">Maintenance Activities</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Maintenance Activities</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div  class="page-content-wrap">				
				<div class="tab-content" role="tab" data-toggle="tab">
						<div id="tabularData">
							<br /> <br />
							<!-- Nav tabs -->
							<ul class="nav nav-tabs" role="tablist" id="myTabs" style="padding:0px 10px;">
								<li role="presentation" id="lruRemovalPanel" class="active">
									<a href="#lruRemovalTab" aria-controls="lruRemovalTab" role="tab" data-toggle="tab" style="font-family: 'Open Sans', sans-serif; font-size: 13px;">LRU Removal</a>
								</li>
								<li role="presentation" id="lruHistoryPanel">
									<a href="#lruHistoryTab" aria-controls="lruHistoryTab" role="tab" data-toggle="tab" style="font-family: 'Open Sans', sans-serif; font-size: 13px;">LRU History</a>
								</li>											
							</ul>
							<!-- Tab panes -->
							<div class="tab-content">
								<div role="tabpanel" class="tab-pane active" id="lruRemovalTab" ng-controller="MaintenanceActivitiesController">
									<?php include("LRURemoval.php");?>
								</div>
								<div role="tabpanel" class="tab-pane" id="lruHistoryTab" ng-controller="LRUHistoryMaintenanceActivitiesController">
									<?php include("LRUHistory.php");?>									
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

	<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
	<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>	
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
</body>
<script src="../js/FileSaver.min.js"></script>
<script src="../js/canvas-toBlob.js"></script>
<script>
var airlineId_nav = "<?php echo "$airlineId";?>";
var platform_nav = "<?php echo "$platform";?>";
var configuration_nav = "<?php echo "$configuration";?>";
var software_nav = "<?php echo "$software";?>";
var tailsign_nav = "<?php echo "$tailsign";?>";

var startDateTime= "<?php echo "$startDateTime";?>"; 
var endDateTime= "<?php echo "$endDateTime";?>";
var tabVisited= "<?php echo $submenu;?>";
var visited='<?php echo $maintenanceActivitiesVisited;?>';
console.log('visited'+tabVisited);
function clearLruRemovalData(){
	var session_AirlineId='';
	var session_Platform='';
	var session_Config='';
	var session_Tailsign='';
	var session_StartDate='';
	var serialNumberRemoval='';
	var hostnameRemoval='';
	var hwPartNumberRemoval='';
}

function clearLruHistory_SerialData(){
	var serialNumberlru='';
	var session_AirlineIdlru='';
}

function clearLruHistory_HostnameData(){
	var session_AirlineIdlru='';
	var session_AirlineIdhw='';
	var session_Platformhw='';
	var session_Confighw='';
	var session_Tailsignhw='';
	var session_StartDatehw='';
	var hostNamelru ='';
}

if(visited!=''){
		var session_submenu="<?php echo $_SESSION['submenu'];?>";
		if(session_submenu=='lruRemoval'){
			var session_AirlineId="<?php echo $_SESSION['airlineId'];?>";		
	
			<?php 
					if($_SESSION ['platform']!=null){
						$platform = rtrim ( implode ( ",", $_SESSION ['platform'] ), "," );
						$platforms = rtrim ( $platform, "," );					
					}
			?>	
				
			var session_Platform="<?php echo $platforms; ?>";
			
			<?php 
					if($_SESSION ['configType']!=null){
						$configType = rtrim ( implode ( ",", $_SESSION ['configType'] ), "," );
						$configTypes = rtrim ( $configType, "," );
					}
			?>	
				
			var session_Config='<?php echo $configTypes; ?>';	
			<?php 
					if($_SESSION ['tailsignList']!=null){
						$tailsignList = rtrim ( implode ( ",", $_SESSION ['tailsignList'] ), "," );
						$tailsignLists = rtrim ( $tailsignList, "," );
					}
			?>	
				
			var session_Tailsign='<?php echo $tailsignLists; ?>';	
			var session_StartDate="<?php echo $_SESSION['startDate'];?>";
			var session_EndDate="<?php echo $_SESSION['endDate'];?>";
		
			//Removal
			var serialNumberRemoval = "<?php echo $_SESSION['serialNumberRemoval']; ?>";
			var hostnameRemoval = "<?php echo $_SESSION['hostnameRemoval']; ?>";
			var hwPartNumberRemoval = "<?php echo $_SESSION['hwPartNumberRemoval']; ?>";
			clearLruHistory_SerialData();
			clearLruHistory_HostnameData();
		}else if(session_submenu==''){
			clearData();
		}else{
			if(session_submenu=='lruHistory_SerialNumber'){
				var session_AirlineIdlru="<?php echo $_SESSION['airlineId'];?>";		
				
				<?php 
						if($_SESSION ['platform']!=null){
							$platform = rtrim ( implode ( ",", $_SESSION ['platform'] ), "," );
							$platforms = rtrim ( $platform, "," );					
						}
				?>	
				var serialNumberlru = "<?php echo $_SESSION['serialNumber']; ?>";
				var session_StartDatelru="<?php echo $_SESSION['startDate'];?>";
				var session_EndDatelru="<?php echo $_SESSION['endDate'];?>";
				
				clearLruRemovalData();
				clearLruHistory_HostnameData();
			}else if(session_submenu=='lruHistory_partNumber'){
				var session_AirlineIdhw="<?php echo $_SESSION['airlineId'];?>";		
				
				<?php 
						if($_SESSION ['platform']!=null){
							$platform = rtrim ( implode ( ",", $_SESSION ['platform'] ), "," );
							$platforms = rtrim ( $platform, "," );					
						}
				?>	
					
				var session_Platformhw="<?php echo $platforms; ?>";
				
				<?php 
						if($_SESSION ['configType']!=null){
							$configType = rtrim ( implode ( ",", $_SESSION ['configType'] ), "," );
							$configTypes = rtrim ( $configType, "," );
						}
				?>	
					
				var session_Confighw='<?php echo $configTypes; ?>';	
				<?php 
						if($_SESSION ['tailsignList']!=null){
							$tailsignList = rtrim ( implode ( ",", $_SESSION ['tailsignList'] ), "," );
							$tailsignLists = rtrim ( $tailsignList, "," );
						}
				?>	
					
				var session_Tailsignhw='<?php echo $tailsignLists; ?>';	
				var session_StartDatehw="<?php echo $_SESSION['startDate'];?>";
				var session_EndDatehw="<?php echo $_SESSION['endDate'];?>";

				//Hostname
				var hostNamelru = "<?php echo $_SESSION['hostNamelru']; ?>";
				clearLruHistory_SerialData();
				clearLruRemovalData();
			}
		}
		
		var maintenanceActivitiesVisited=true;	
}else{
	var maintenanceActivitiesVisited=false;
}
$(document).ready(function(){

	var session_submenu="<?php echo $_SESSION['submenu'];?>";

	if(session_submenu=='lruRemoval'){
		$("#lruHistoryTab").removeClass("active");
		$("#lruRemovalTab").addClass("active");
		$("#lruHistoryPanel").removeClass("active");
		$("#lruRemovalPanel").addClass("active");
	}else if(session_submenu==''){
		$("#lruHistoryTab").removeClass("active");
		$("#lruRemovalTab").addClass("active");
		$("#lruHistoryPanel").removeClass("active");
		$("#lruRemovalPanel").addClass("active");
	}else{		
		$("#lruRemovalTab").removeClass("active");
		$("#lruHistoryTab").addClass("active");
		$("#lruRemovalPanel").removeClass("active");
		$("#lruHistoryPanel").addClass("active");
		if(session_submenu=='lruHistory_SerialNumber'){
			$('#lruHistoryData').val('serialNumber');
			$('#lruHistoryData').selectpicker('refresh');
			angular.element($("#dropdowndiv")).scope().showlruHistoryData();					
		}else if(session_submenu=='lruHistory_partNumber'){
			$('#lruHistoryData').val('hwPartNumber');
			$('#lruHistoryData').selectpicker('refresh');
			angular.element($("#dropdowndiv")).scope().showlruHistoryData();			
		}
	}
	
	$('#airline').selectpicker({                              
        size: 6
  	});
  	
    $('#platform').selectpicker({                              
            size: 6
      	});
      	
    $('#configType').selectpicker({                              
            size: 6
      	});
      	
    $('#software').selectpicker({                              
            size: 6
      	});
      	
    $('#tailsign').selectpicker({                              
            size: 6
      	});
	
	
	$('#airline').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadPlatforms();
	  });

	$('#platform').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
	  });

	$('#configType').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadSoftwares();
	  });

	$('#software').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadTailsign();
	});	

	$('#airlinelru').on('change', function(){
	   // angular.element($("#ctrldivlru")).scope().loadPlatformslru();
	  });


	$('#lruHistoryData').on('change', function(){
	    angular.element($("#dropdowndiv")).scope().showlruHistoryData();
	});	
	

	$('#airlinelru').selectpicker({                              
        size: 6
  	});
	
    $('#airlinehw').on('change', function(){
	    angular.element($("#partNumberDiv")).scope().loadPlatformshw();
	  });

	$('#platformhw').on('change', function(){
	    angular.element($("#partNumberDiv")).scope().loadConfigTypeshw();
	  });

	$('#configTypehw').on('change', function(){
	    angular.element($("#partNumberDiv")).scope().loadTailsignhw();
	  });


	$('#airlinehw').selectpicker({                              
        size: 6
  	});
  	
    $('#platformhw').selectpicker({                              
            size: 6
      	});
      	
    $('#configTypehw').selectpicker({                              
            size: 6
      	});      	
   
    $('#tailsignhw').selectpicker({                              
            size: 6
      	});

    function clearData(){
    	var session_AirlineId='';
		var session_Platform='';
		var session_Config='';
		var session_Tailsign='';
		var session_StartDate='';
		var serialNumberRemoval='';
		var hostnameRemoval='';
		var hwPartNumberRemoval='';
		var hostNamelru ='';
		var serialNumberlru='';
		var session_AirlineIdlru='';
		var session_AirlineIdhw='';
		var session_Platformhw='';
		var session_Confighw='';
		var session_Tailsignhw='';
		var session_StartDatehw='';
    }
    function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    	document.getElementById("errormsgForSerial").style.display = "none";
    	document.getElementById("errormsgForhostname").style.display = "none"; 

    	var today = new Date();
		var priorDate = new Date(new Date().setDate(today.getDate()-6));
		var startDate = formatDate(priorDate);
		var endDate = formatDate(today);
		$('#startDateTimePicker').val(startDate);
		$('#endDateTimePicker').val(endDate);
		$('#startDateTimePickerhw').val(startDate);
		$('#endDateTimePickerhw').val(endDate);
		$('#startDateTimePickerlru').val(startDate);
		$('#endDateTimePickerlru').val(endDate);
		clearData();
    });   

    
});

</script>
<script src="../controllers/MaintenanceActivitiesController.js"></script>
<script src="../controllers/LRUHistoryMaintenanceActivitiesController.js"></script>
</html>