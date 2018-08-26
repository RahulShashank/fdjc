<?php
session_start ();
$menu = 'activeMaintenance';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );
error_log('airline Ids..'.$airlineIds);
// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "30 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );
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
<script src="../js/Chart.HeatMap.S.js"></script>
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>

<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="../css/bootstrap-slider/bootstrap-slider.min.css">
<script src="../js/bootstrap-slider/bootstrap-slider.min.js"></script>
<link rel="stylesheet" href="../css/dataTables/datatables.min.css">	
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedcolumns/3.2.2/css/fixedColumns.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedheader/3.1.2/css/fixedHeader.dataTables.min.css"/>
<script src="../js/dataTables/datatables.min.js"></script>
<script src="../js/moment/moment.min.js"></script>
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
		.search_filter{
		}
		.search_filter *{
			display: inline
		}
		.search_filter div:nth-child(2){
			float: right
		}
		th, td{
			white-space: nowrap;
		}
		td:first-child{
			font-weight: bold;
		}
		th:first-child{
			width: 105px;
		}
		.date-Error{
			border: 1px solid red
		}
		.cell_bkg_yellow{
			background: rgba(255, 255, 0, 0.6);
			border-left: 1px solid #E5E5E5;
			border-right: 1px solid #E5E5E5;
		}
		.cell_bkg_orange{
			background: orange;
			border-left: 1px solid #E5E5E5;
			border-right: 1px solid #E5E5E5;
		}
		.cell_bkg_red{
			background: red;
			border-left: 1px solid #E5E5E5;
			border-right: 1px solid #E5E5E5;
		}
		.cell_bkg_light{
			/* background: #fcf8e3; */
			border-left: 1px solid #E5E5E5;
			border-right: 1px solid #E5E5E5;
		}
		.center_align{
			text-align: center;
			border-left: 1px solid #E5E5E5;
			border-right: 1px solid #E5E5E5;			
		}
		.sorting{
			border-left: 1px solid #E5E5E5;
			border-right: 1px solid #E5E5E5;			
		}
		.text_bold{
			font-weight: bold;
		}
		.sorting:before {
		    content: "";
		    opacity: 0;
		    filter: alpha(opacity = 30);
		}
		.sorting_asc:before {
		    content: "";
		}
		.sorting_desc:before {
		    content: "";
		}
		.sorting:before, .sorting_desc:before, .sorting_asc:before {
			height:0px;
		}
		.dataTables_filter {
		    width: 30px; 
		    float: left; 
		    padding-left: 6px !important;
		    padding: 0px 0px 0px;
		    border-bottom: 1px solid #ffffff;
		    font-size: 12px;
		}
		.modal-content {
			border-radius:6px;
			border:none;
			margin-top:10%;
		}
		
		/*table.dataTable thead .sorting_desc:after {
		    content: "";
    		background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAQAAADYWf5HAAAAkElEQ…YSV17OatFC4euts6z39GYMKRPCTKY9UnPQ6P+GtMRfGtPnBCiqhAeJPmkqAAAAAElFTkSuQmCC);
            background-position: right;
            /* background-repeat: no-repeat; 
		}*/
		
		.dt-button-collection dropdown-menu{
			min-width: 120px !important;
			cursor: pointer;
		}
		.drodownlist{			
    		min-width: 120px !important; 
    		cursor: pointer !important;   		
		}
		.dropdown-menu > li > a {
			cursor: pointer !important;
		}
		.sorting_asc{
			min-width: 100px !important;
		}
		.sorting_desc{
			min-width: 100px !important;
		}
		.sorting{
			min-width: 100px !important;
		}
		.odd{
			background-color: #F8FAFC !important;
		}
		.even{
			background-color: #FFFFFF !important;
		}
		.no_sorting{
			background-color: #ffffff !important;
		}
		.dataTables_length{
			border-bottom: 0px solid #E5E5E5;
			margin-left: 6px;
    		margin-top: 8px;
		}
		.dataTables_length select {
			width: 40px !important;
    		line-height: 17px !important;
		}
		table.dataTable thead .sorting::after,
        table.dataTable thead .sorting_asc::after {
            display:none;
        }
        
        table.dataTable thead .sorting_desc::after {
            display:none;
        }
        
        table.dataTable thead .sorting {
    /*        background-image: url(https://datatables.net/media/images/sort_both.png); */
           background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAQAAADYWf5HAAAAkElEQVQoz7XQMQ5AQBCF4dWQSJxC5wwax1Cq1e7BAdxD5SL+Tq/QCM1oNiJidwox0355mXnG/DrEtIQ6azioNZQxI0ykPhTQIwhCR+BmBYtlK7kLJYwWCcJA9M4qdrZrd8pPjZWPtOqdRQy320YSV17OatFC4euts6z39GYMKRPCTKY9UnPQ6P+GtMRfGtPnBCiqhAeJPmkqAAAAAElFTkSuQmCC");
           background-repeat: no-repeat;
           background-position: center right;
        }
        
        table.dataTable thead .sorting_asc {
    /*        background-image: url(https://datatables.net/media/images/sort_asc.png); */
           background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAYAAAByUDbMAAAAZ0lEQVQ4y2NgGLKgquEuFxBPAGI2ahhWCsS/gDibUoO0gPgxEP8H4ttArEyuQYxAPBdqEAxPBImTY5gjEL9DM+wTENuQahAvEO9DMwiGdwAxOymGJQLxTyD+jgWDxCMZRsEoGAVoAADeemwtPcZI2wAAAABJRU5ErkJggg==");
           background-repeat: no-repeat;
           background-position: center right;
        }
        
        table.dataTable thead .sorting_desc {
    /*        background-image: url(https://datatables.net/media/images/sort_desc.png); */
           background-image: url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAYAAAByUDbMAAAAZUlEQVQ4y2NgGAWjYBSggaqGu5FA/BOIv2PBIPFEUgxjB+IdQPwfC94HxLykus4GiD+hGfQOiB3J8SojEE9EM2wuSJzcsFMG4ttQgx4DsRalkZENxL+AuJQaMcsGxBOAmGvopk8AVz1sLZgg0bsAAAAASUVORK5CYII=");
           background-repeat: no-repeat;
           background-position: center right;
        }
        .dateChange{
            background-color:#F9F9F9 !important;
            color:#000000 !important;
            cursor: auto !important;
        }
        ul.dt-button-collection.dropdown-menu {
            min-width: 119px !important;        
        }
		body.modal-open {
		    overflow: hidden !important;
		    position:fixed !important;
		    width: 100% !important;
		}
    div.dataTables_wrapper div.dataTables_filter input {
        margin-left: 0em;
    } 
	
	</style>
<body id="bodyDiv" ng-controller="ActiveMaintenanceController">
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
				<li class="active">ActiveMaintenance</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Active Maintenance</h2>
				<a href="#" class="info" data-toggle="modal" data-target="#modalTable">					 
					<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px; color: #656d78;cursor: pointer;padding-top: 6px; padding-right: 3px;">
					</i>
				</a>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">

				<div class="row">
					<div class="col-md-12">
						<!--div class="alert alert-info" role="alert">
							Displays count for active and inactive faults with code 400, 404, 420231, 420230, 420228 or 430228 in cruise phase with duration more than 5 minutes.
						</div-->
						<div class="panel panel-default">
							<!-- div class="panel-heading">
                                    <h4 class="panel-title">Search Flight Score</h4>
                                </div-->
							<div class="panel-body">
								<input type="hidden" id="airlineIds" ng-model="airlineIds"
									ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
								<div class="row" style="padding-bottom: 12px;">
									<div class="col-md-2 from-group"
										style="padding-right: 5px; padding-left: 5px;">
										<label for="airline">Airline</label>
										<div>
											<select id="airline" class="selectpicker show-tick"	data-live-search="true" data-width="100%" style="max-width: 150px;"></select>
										</div>
									</div>
									<div class="col-md-2 from-group"
										style="padding-right: 5px; padding-left: 5px;">
										<label for="platform">Platform</label>
										<div>
											<select id="platform" class="selectpicker show-tick" data-width="100%" data-max-width="120px;"></select>
										</div>
									</div>
									<div class="col-md-2 from-group"
										style="padding-right: 5px; padding-left: 5px;">
										<label for="configType">Configuration</label>
										<div>
											<select id="configType" class="selectpicker show-tick"
												data-width="100%">
											</select>
										</div>
									</div>
									<div class="col-md-2 from-group"
										style="padding-right: 5px; padding-left: 5px;">
										<label for="software">Software</label>
										<div>
											<select id="software" class="selectpicker show-tick" data-width="100%" 
												data-live-search="true"
												data-selected-text-format="count > 3"></select>
										</div>
									</div>
									<div class="col-md-2 from-group"
										style="padding-right: 5px; padding-left: 5px;">
										<label for="tailsign">Tailsign</label>
										<div>
											<select id="tailsign" class="selectpicker show-tick"
												data-width="100%" 
												data-live-search="true"
												data-selected-text-format="count > 3"></select>
										</div>
									</div>
									<div class="col-md-1 from-group"
										style="padding-right: 5px; padding-left: 5px;">
										<label for="startDateTimePicker">From</label>
										<div>
											<input class="form-control dateChange" id="startDateTimePicker"
												type="text" name="startDateTimePicker" ng-model="startDate" style="width: 100%;" readonly='true'>
										</div>
									</div>
									<div class="col-md-1 from-group"
										style="padding-right: 5px; padding-left: 5px;">
										<label for="endDateTimePicker">To</label>
										<div>
											<input class="form-control dateChange" id="endDateTimePicker"
												type="text" name="endDateTimePicker" ng-model="endDate"  style="width: 100%;" readonly='true'>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12 text-left">
										<button id="filter" class="btn btn-primary"
											data-ng-click="getFlightScore()">Filter</button>
										&nbsp;&nbsp;&nbsp;
										<button id="reset" type="button" class="btn btn-reset"
											ng-click="resetActiveMaintenance()">Reset</button>
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
								<div align="center"  ng-if="loading"><img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...</div>
						          <div ng-show="!loading && !dataNotAvailable">
									<table id="resultTable" class="table" style="width: 100%;" data-show-export="true"></table>
								  </div>
            						<div ng-show="dataNotAvailable" id="noDataDiv" class="container-fluid text-center">
                						<label class="noData-label">No data available for the selected duration or filters</label>
                					</div>
								  
								  <div id="myModal" class="modal fade" role="dialog">
						              <div class="modal-dialog modal-lg">
						            
						                <!-- Modal content-->
						                <div class="modal-content">
						                    <div class="modal-header">
						                        <button type="button" class="close" data-dismiss="modal">&times;</button>
						                        <h4 id="hostnameTitle" class="modal-title">HostName</h4>
						                    </div> 
											<div class="modal-body">	
												<div align="center"  id="modalLoader"><img src="../img/loadingicon1.gif" style="height: 30px;"><br/>Loading Data...</div>	                    	
						                    	<table id="faultDetailsTable" data-classes="table table-no-bordered table-hover" data-pagination="true" data-page-list="[10, 25, 50, 100, All]"	data-page-size="10" data-striped="true"  >
													<thead>
														<tr>
															<!--th data-field="idFault" data-sortable="true" >Id</th-->
															<th data-field="detectionTime" data-sortable="true" >Fault Time</th>
															<th data-field="idFlightLeg" data-sortable="true">Flight Leg</th>
															<th data-field="hostname" data-sortable="true">Hostname</th>
															<th data-field="reportingHostName" data-sortable="true">Reporting Hostname</th>
															<th data-field="faultCode" data-sortable="true">Fault Code</th>
															<th data-field="faultDesc" data-sortable="true">Fault Description</th>
															<th data-field="monitorState" data-sortable="true">Monitor State</th>										
															<th data-field="serialNumber" data-sortable="true">Serial Number</th>
														</tr>
													</thead>
												</table>
						                    </div>
						                   <!-- <div class="modal-footer">
						                    	 <div class="row">
						    					<div class="text-center"> 
							                    	<button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
							                    </div>
							                    </div>
						                    </div>  -->
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
	
	<div class="modal" data-sound="alert" id="modalTable">
		<div class="modal-dialog modal-md" style="background-color: #f5f5f5; margin-top: 54px; border-radius: 6px;">				
			<div class="modal-content" style="border-radius: 5px;border-width:0px;">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Active Maintenance Info</h4>
				</div> 
				<div class="modal-body">
					<!--div style="background-color: #428bca; color: #FFF; border-color: #36b7e3; width: 100%; margin-bottom: 10px; line-height: 21px; padding: 15px; border: 1px solid transparent; border-radius: 4px;"-->
    				<div class="modal-alert-info">
    					Displays count for active and inactive faults with code 400, 404, 420231, 420230, 420228 or 430228 in cruise phase with duration more than 5 minutes.
    				</div>
    				<br/>
					
				</div>
			</div>
		</div>
	</div>

	<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
	<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>	
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<script src="../controllers/ActiveMaintenanceController.js"></script>
	<!--<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.8/angular.min.js"></script>
	<script src="../js/dataTables/angular-datatables.min.js"></script>
	<script src="../js/dataTables/angular-datatables.buttons.min.js"></script>	
	<script type="text/javascript" src="https://cdn.datatables.net/fixedcolumns/3.2.2/js/dataTables.fixedColumns.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/fixedheader/3.1.2/js/dataTables.fixedHeader.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.10.16/sorting/absolute.js"></script> -->
</body>

<script src="../controllers/ActiveMaintenanceController.js"></script>
<script src="../js/FileSaver.min.js"></script>
<script src="../js/canvas-toBlob.js"></script>
<script>
var startDateTime= "<?php echo "$startDateTime";?>"; 
var endDateTime= "<?php echo "$endDateTime";?>";

$.blockUI.defaults.overlayCSS.opacity = '0.3';
$.blockUI.defaults.css = { 
    padding:        0, 
    margin:         0, 
    width:          '30%', 
    top:            '40%', 
    left:           '35%', 
    textAlign:      'center', 
    cursor:         'wait' 
};

$(document).ready(function(){
	
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
	
    $('.navbar-nav li').removeClass('active');
    $("#activeMaintenance").addClass("active");

	
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

});
var aircraftID;
function getFaultCountDetails (hostname,dates) {	
		var aircraft=$('#tailsign').val();

        $.ajax({
            type: "GET",
            url: "../common/AirlineDAO.php",
            data: {
            	tailsign: aircraft,
                action: 'GET_AIRCRAFTID_FOR_TS'
            },
            success: function(data) {
            	var aircraft = JSON.parse(data);			
    			console.log(aircraft.id);
    			aircraftID=aircraft.id;
    			var startDate=$('#startDateTimePicker').val();
    			var endDate=$('#endDateTimePicker').val();
    			getFaultDetails(aircraftID,hostname,dates);	

            },
            error: function(err) {
                console.log('Error', err);
            }
        });
	
}
function getFaultDetails (aircraftID,hostname,dates) {
	$('#modalLoader').show();
	$('#faultDetailsTable').hide();	
	$('#faultDetailsTable').bootstrapTable('destroy');
	console.log('Selected Date: ' + dates);
	$.ajax({
        type: "GET",
        url: "../ajax/ActiveMaintenanceFaultDetailsData.php",
        data: {
        	'aircraftId': aircraftID,
			'startDate': $('#startDateTimePicker').val(),
			'endDate': $('#endDateTimePicker').val(),
			'detectionDate': dates,
			'hostname':hostname
        },
        success: function(data) {
            if (data) {
                //console.log(data);
                var jsonData=JSON.parse(data);
                $('#faultDetailsTable').bootstrapTable({
                    data: jsonData.faultData
                });
                $('#faultDetailsTable').show();
                $('#modalLoader').hide();
                $("#hostnameTitle").text("Hostname - "+jsonData.hostname);
            }

        },
        error: function(err) {
            console.log('Error', err);
        }
    });
}



</script>

</html>