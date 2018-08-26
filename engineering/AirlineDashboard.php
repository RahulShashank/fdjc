<?php
session_start ();
$menu = 'DASHBOARD';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "7 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

$dashboardVisited = $_REQUEST ['dashboardVisited'];

if($dashboardVisited=='true'){
    error_log('Airline in Dashboard : '.$_SESSION['airlineId']);
}else{
    $_SESSION['airlineId'] =  '';
    $_SESSION['platform'] =  '';
    $_SESSION['configType'] =  '';
    $_SESSION['software'] = '';
    $_SESSION['startDate'] = '';
    $_SESSION['endDate'] =  '';
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
<script src="../js/jquery-1.11.2.js"></script>
<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<link rel="stylesheet" href="../css/bootstrap-slider/bootstrap-slider.min.css">
<link href="../css/vis.css" rel="stylesheet">
<!-- EOF CSS INCLUDE -->
<style>
.dropdown-menu{
	min-width: 103px;
}
body.modal-open {
    overflow: hidden !important;
    position:fixed !important;
    width: 100% !important;
}
.modal-backdrop {
  z-index: -1;
}

.rounded {
    -webkit-border-radius: 13px;
}

.search2 {
    padding: 6px 15px 6px 30px;
    margin: 3px;
    background: url(../img/search.png) no-repeat 8px 6px;
    outline: none;
    border: 1px solid #d0d0d0;
}

div[data-toggle="buttons"] label.active{
	color: #555;
	font-weight: bold;
}

</style>
</head>
<body>
	<!-- START PAGE CONTAINER -->
	<div class="page-container" >

		<!-- START PAGE SIDEBAR -->
            <?php include("SideNavBar.php"); ?>
            <!-- END PAGE SIDEBAR -->

		<!-- PAGE CONTENT -->
		<div id="ctrldiv" class="page-content" data-ng-controller="AirlineDashboardController">

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
				<li class="active">Aircraft Dashboard</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Aircraft Dashboard</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
			
                <div class="row">
					<div class="col-md-12 form-group text-left">
						<select id="view" class="selectpicker show-tick" data-width="20%" onchange="showView();">
							<option value="card">Card</option>
							<option value="timeline">Timeline</option>
						</select>
					</div>
    			</div>
				<br/>
                <div class="row">
                	<div class="col-md-12">
                		<div class="panel panel-default">
                			<div class="panel-body">
                				<input type="hidden" id="airlineIds" ng-model="airlineIds"
									ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
								<div style="margin-left: -10px; margin-right: -10px;">
                				<div class="row">
                					<div class="col-md-2">
                						<label for="airline">Airline</label>
                						<div>
                							<select id="airline" class="selectpicker show-tick" data-live-search="true" data-width="100%"></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="platform">Platform</label>
                						<div>
                							<select id="platform" class="selectpicker show-tick" data-width="100%" multiple title="All"></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="configType">Configuration</label>
                						<div>
                							<select id="configType" class="selectpicker show-tick" data-width="100%" multiple title="All"></select>
                						</div>
                					</div>
                					<div class="col-md-2" id="softwareFilterDiv">
                						<label for="software">Software</label>
                						<div>
                							<select id="software" class="selectpicker show-tick" data-width="100%" 
                							data-selected-text-format="count > 3" multiple title="All"></select>
                						</div>
                					</div>
                					<div class="col-md-2" id="fromDateFilterDiv">
                						<label for="startDateTimePicker">From</label>
                						<div>
                							<input class="form-control read-only-datepicker" id="startDateTimePicker"
                								type="text" name="startDateTimePicker" style="width: 100%;" readonly='true'>
                						</div>
                					</div>
                					<div class="col-md-2" id="toDateFilterDiv">
                						<label for="endDateTimePicker">To</label>
                						<div>
                							<input class="form-control read-only-datepicker" id="endDateTimePicker"
                								type="text" name="endDateTimePicker" style="width: 100%;" readonly="readonly">
                						</div>
                					</div>
                					<div class="col-md-3" id="filterButtonInline" style="display: none;">
										<label for="buttons">&nbsp;&nbsp;</label>
										<div>
											<button id="filter" class="btn btn-primary" data-ng-click="filter()">Filter</button>
											&nbsp;&nbsp;&nbsp;
											<button id="reset" type="button" class="btn btn-reset" data-ng-click="resetSearchPanel()">Reset</button>
										</div>
									</div>
	            				</div>
                				<br /> 
                				<div class="row" id="filterButtonRow">
                					<div class="col-md-12 text-left">
                						<button id="filter" class="btn btn-primary"
                							data-ng-click="filter()">Filter</button>
                						&nbsp;&nbsp;&nbsp;
                						<button id="reset" type="button" class="btn btn-reset"
                							data-ng-click="resetSearchPanel()">Reset</button>
                					</div>
								</div>
								</div>
                			</div>
                		</div>
                	</div>
                </div>
                
				<div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">                            
                            <div class="panel-body">
                				<div id="loadingDiv" style="text-align: center">
                					<img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading data...
                				</div>
                				
                                <div id="cardViewDiv">
                                	<?php include_once 'aircraftsCardView.php';?>
                                </div>
                                <div id="timelineViewDiv">
        							<div align="center" id="seatResetsChartLegend">
        								<ul style="padding-left: 0px;">
        									<a href="#" class="btn" style="color:#434a54; width: 130px; cursor: default;">
        										<span style="background-color: #fe5757"></span><i class="fa fa-square" style="font-size: 12px; color: #FE5757;"></i>Critical
        										issue</a>
        									<a href="#" class="btn" style="color:#434a54; width: 100px; cursor: default;">
        										<span style="background-color: #2ECC40"></span><i class="fa fa-square" style="font-size: 12px; color: #95b75d;"></i>No
        										issue</a>
        								</ul>
        							</div>
        							<div id="visualization"></div>
                                </div>
                            </div>
                        </div>                                                   
                        <!-- END TABS -->                        
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
<script>
var startDateTime= "<?php echo "$startDateTime";?>"; 
var endDateTime= "<?php echo "$endDateTime";?>";

if(<?php echo $dashboardVisited;?>){
	var session_AirlineId="<?php echo $_SESSION['airlineId'];?>";
	console.log("AirlineId: " + session_AirlineId);
	
	<?php
    	if($_SESSION ['platform']!=null){
    	    $platform = implode ( ",", $_SESSION ['platform'] );
    	}
	?>
	var session_Platform="<?php echo $platform;?>";
	console.log("Session Platform: " + session_Platform);	
	
	<?php
    	if($_SESSION ['configType']!=null){
    	    $configType = implode ( ",", $_SESSION ['configType'] );
    	}
	?>
	var session_Config="<?php echo $configType;?>";
	console.log("Session Config: " + session_Config);

	<?php 
		if($_SESSION ['software']!=null){
		    $software = implode ( ",", $_SESSION ['software'] );
		}
	?>	
	var session_Software='<?php echo $software;?>';
	console.log("Session Software: " + session_Software);	

	var session_StartDate="<?php echo $_SESSION['startDate'];?>";
	var session_EndDate="<?php echo $_SESSION['endDate'];?>";
	var dashboardVisited=true;
}else{
	var dashboardVisited=false;
}

function showView() {
	var view = $('#view').val();
	if(view == 'timeline') {
		$('#card_label').css({"class" : "btn active"});
		$('#timeline_label').css({"class" : "btn"});

		$('#timeline_button').removeClass("btn btn-default btn-rounded");
		$('#timeline_button').addClass("btn btn-info btn-rounded");
		$('#card_button').removeClass("btn btn-info btn-rounded");
		$('#card_button').addClass("btn btn-default btn-rounded");

		$('#fromDateFilterDiv').show();
		$('#toDateFilterDiv').show();
		$('#filterButtonInline').hide();
		$('#filterButtonRow').show();
		$('#timelineViewDiv').show();
		$('#cardViewDiv').hide();
	} else if(view == 'card') {
		$('#card_label').css({"class" : "btn"});
		$('#card_label').css({"class" : "btn active"});

		$('#timeline_button').removeClass("btn btn-info btn-rounded");
		$('#timeline_button').addClass("btn btn-default btn-rounded");
		$('#card_button').removeClass("btn btn-default btn-rounded");
		$('#card_button').addClass("btn btn-info btn-rounded");

		$('#fromDateFilterDiv').hide();
		$('#toDateFilterDiv').hide();
		$('#filterButtonInline').show();
		$('#filterButtonRow').hide();
		$('#timelineViewDiv').hide();
		$('#cardViewDiv').show();
	}
}

</script>
<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
<script type="text/javascript" src="../js/plugins.js"></script>
<script type="text/javascript" src="../js/actions.js"></script>
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.3.0/ui-bootstrap-tpls.min.js"></script>
<script src="../js/Chart.js"></script>
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<script src="../js/bootstrap-table.js"></script>
<script src="../js/Chart.HeatMap.S.js"></script>
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
<script src="../js/bootstrap-slider/bootstrap-slider.min.js"></script>
<script src="../js/vis.min.js"></script>
<script src="../controllers/AirlineDashboardController.js"></script>
</body>
</html>