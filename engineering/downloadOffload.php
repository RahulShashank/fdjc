<!DOCTYPE html>
<?php
// session_start();
session_start();

$menu = 'downloadOffload';
/* include ("../common/sessionExpired.php"); */
include ("../common/getAircraftCodes.php");
require_once ("../common/validateUser.php");


$approvedRoles = [$roles["all"]];
$auth->checkPermission($hash, $approvedRoles);
$airlinesCodesArray = aircraftCodesArray();
$uid = $auth->getSessionUID($hash);
$user = $auth->getUser($uid);
$userArray = $user['email'];
$userArray = explode("@", $userArray);
$usr = explode(".", $userArray[0]);
$str = str_replace(".", " ", $userArray[0]);
$str = ucwords($str);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
require_once '../libs/PHPExcel/PHPExcel.php';
require_once '../libs/PHPExcel/PHPExcel/IOFactory.php';

require_once ("checkEngineeringPermission.php");
$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
$airlineIds = rtrim($airlineIds, ",");

$query = "SELECT a.id, a.acronym FROM airlines a WHERE id IN ($airlineIds) ORDER BY a.acronym";

$result = mysqli_query($dbConnection, $query);
$airlines = array();
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        $airlines[] = $row['acronym'];
    }
}

// get current date
$endDateTime = date('Y-m-d');
$startDate = date_create("$endDateTime");
date_sub($startDate, date_interval_create_from_date_string("6 days"));
$startDateTime = date_format($startDate, "Y-m-d");
$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
$airlineIds = rtrim($airlineIds, ",");

$downloadOffloadVisited = $_REQUEST ['downloadOffloadVisited'];

if($downloadOffloadVisited=='true'){
	error_log('Airline in timeline : '.$_SESSION['airline']);
}else{
		$_SESSION['airlineId'] =  '';
		$_SESSION['platform'] = '';
		$_SESSION['configType'] =  '';
		$_SESSION['dateFilter'] = '';
		$_SESSION['startDate'] =  '';
		$_SESSION['endDate'] =  '';
		$_SESSION['status'] =  '';
		$_SESSION['tailsign'] =  '';
		$_SESSION['depAirport'] = '';
		$_SESSION['arrAirport'] =  '';
		$_SESSION['source'] = '';
		$_SESSION['failureReason'] = '';
}

?>
<html lang="en" data-ng-app="myApp">

<head>
<!-- META SECTION -->
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/globe-icon.ico">
<title>BITE Analytics</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- <link rel="icon" href="favicon.ico" type="image/x-icon" />
         END META SECTION -->
<!-- END META SECTION -->
<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme"
	href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->
<script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script>

</head>
<style>
.dropdown-menu {
	min-width: 103px;
}

.modal-open {
	padding-right: 0px !important;
}
.form-control[disabled], .form-control[readonly], fieldset[disabled] .form-control {
    cursor: not-allowed;
    /* background-color: #eee; */
    /* opacity: 1; */
}
.form-control[disabled], .form-control[readonly] {
    color: #555;
}
.dateChange{
    background-color:#F9F9F9 !important;
    color:#000000 !important;
    cursor: auto !important;
}

</style>
<body id="ctrldiv" ng-controller="downloadOffloadsController">
	<!-- START PAGE CONTAINER -->
	<div class="page-container">
		<!-- START PAGE SIDEBAR -->
	<?php include("SideNavBar.php"); ?>
		<!-- END PAGE SIDEBAR -->
		<!-- PAGE CONTENT -->
		<div id="here" class="page-content" style="height: 100% !important;">
			<!-- START X-NAVIGATION VERTICAL -->
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel">
				<!-- TOGGLE NAVIGATION -->
				<li class="xn-icon-button"><a href="#" class="x-navigation-minimize"><span
						class="fa fa-dedent"></span> </a></li>
				<!-- END TOGGLE NAVIGATION -->
				<!-- SEARCH 
                  <li class="xn-search">
                  	<form role="form">
                  		<input type="text" name="search" placeholder="Search..." />
                  	</form>
                  </li>-->
				<!-- END SEARCH -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span> </a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->
			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">Download Offloads</li>
			</ul>
			<!-- END BREADCRUMB -->
			<!-- PAGE TITLE -->
			<div class="page-title">
				<h2>
					<!-- <span class="fa fa-arrow-circle-o-left"></span>-->
					Download Offloads
				</h2>
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
                				<input type="hidden" id="airlineIds" ng-model="airlineIds"
                					ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
								<div class="row">
									<div class="col-md-2">
										<div class="row">
											<b> Airline </b><input type="hidden" id="airlineIds"
												class="form-control" value="" />
										</div>
										<div class="row">
											<select id="airline"
												class="form-control selectpicker show-tick"
												data-live-search="true" value="selectedairline">
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<b> Platform </b>
										</div>
										<div class="row">
											<select id="selectedPlatform"
												class="form-control selectpicker show-tick"
												data-live-search="true" value="selectedPlatform">
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<b> Config Type </b>
										</div>
										<div class="row">
											<select id="selectedConfigType"
												class="form-control selectpicker show-tick"
												data-live-search="true" value="selectedConfigType">
											</select>
										</div>
									</div>

									<div class="col-md-2">
										<div class="row">
											<b> Tail Sign </b>
										</div>
										<div class="row">
											<select id="tailsign"
												class="form-control selectpicker show-tick"
												data-live-search="true" value="selectedTailsin">
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<b> Status </b>
										</div>
										<div class="row">
											<select id="status"
												class="form-control selectpicker show-tick"
												value="selectedStatus">
												<option value="">All</option>
												<option value="Processed">Processed</option>
												<option value="Rejected">Rejected</option>
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<b> Source </b>
										</div>
										<div class="row">
											<select id="source"
												class="form-control selectpicker show-tick"
												value="selectedSource">
												<option value="">All</option>
												<option value="Manual">Manual</option>
												<option value="Automatic">Automatic</option>
											</select>
										</div>
									</div>

								</div>
								<br />
								<div class="row">
									<div class="col-md-2">
										<div class="row">
											<b> Date Filter </b>
										</div>
										<div class="row">
											<select id="dateFilter"
												class="form-control selectpicker show-tick"
												value="selectedDateFilter">
												<option value="UploadDate">Upload Date</option>
												<option value="DepartureTime">Departure Time</option>
												<option value="ArrivalTime">Arrival Time</option>
												<option value="OffloadDate">Offload Date</option>
											</select>
										</div>
									</div>
									<!-- 		<div class="col-md-2">
										<div class="row">
											<b> From </b>
										</div>
										<div class="row">
											<input type="text" id="startDate"
												class="form-control datepicker" value="" />
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<b> To </b>
										</div>
										<div class="row">
											<input type="text" id="endDate"
												class="form-control datepicker" value="" />
										</div>
									</div> -->

									<div class="col-md-2">
										<div class="row">
											<b> From </b>
										</div>
										<div class="row">
											<input id="startDate" type="text" name="startDateTime"
												size="15" class="form-control dateChange" readonly='true'>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<b> To </b>
										</div>
										<div class="row">
											<input id="endDate" type="text" name="endDateTime"
												size="15" class="form-control dateChange" readonly='true'>
										</div>
									</div>

									<div class="col-md-2">
										<div class="row">
											<b> Dep Airport </b>
										</div>
										<div class="row">
											<select id="depAirport"
												class="form-control selectpicker show-tick"
												data-live-search="true" value="selectedairline">
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<b>Arr Airport</b>
										</div>
										<div class="row">
											<select id="arrAirport"
												class="form-control selectpicker show-tick"
												data-live-search="true" value="selectedairline">
											</select>
										</div>
									</div>
									<div class="col-md-2">
										<div class="row">
											<b> Failure Reason </b>
										</div>
										<div class="row">
											<select id="failureReason"
												class="form-control selectpicker show-tick"
												value="selectedFailureReason" data-live-search="true">
												<option value="">All</option>
												<option value="Empty XML file">Empty XML file</option>
												<option value="Empty tgz file">Empty tgz file</option>
												<option value="Empty tar file">Empty tar file</option>
												<option value="Failed loading XML">Failed loading XML</option>
												<option value="File not supported">File not supported</option>
												<option value="Tail Sign not recognized">Tailsign not
													recognized</option>
												<option value="New Tail Sign">New Tail Sign</option>
												<option value="This file has already been uploaded.">File
													already uploaded</option>
											</select>
										</div>
									</div>
								</div>
								<br />
								<div class="row">
									<div class="col-md-12 text-center">
										<button id="filter" class="btn btn-primary"
											data-ng-click="filterOffloads()">Filter</button>
										&nbsp;&nbsp;&nbsp;
										<button id="reset" type="button" class="btn btn-primary"
											ng-click="resetOffloads();">Reset</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="loadingTabData" align="center">
					<img src="../img/loadingicon1.gif" style="height: 50px;"> <br />
					Loading Data...
				</div>
				<div class="row" id="downloadOffload">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div>
									<div class="table-responsive" style="overflow: auto;">

										<table id="offloadTabledata" data-classes="table"
											data-pagination="true" data-page-list="[25, 50, 100, All]"
											data-page-size="25" data-striped="true" data-search="true"
											data-search-align="left" data-show-export="true">
											<thead>
												<tr>
													<th data-field="fileName" data-formatter="downloadFile"
														data-sortable="true">File Name</th>
													<th data-field="fileSize" data-sortable="true">File Size</th>
													<!-- <th data-field="status" data-sortable="true">Status</th> -->
													<!-- <th data-field="fileName"
														data-formatter="reuploadFormatter">Action</th> -->
													<th data-field="status" data-sortable="true"
														data-events="operateEvents"
														data-formatter="operateFormatter">Status</th>
													<th data-field="tailsign" data-sortable="true">Tailsign</th>
													<th data-field="tailsignSource" data-sortable="true">Tailsign Source</th>
													<th data-field="flightNumber" data-sortable="true"
														data-formatter="formatFlightLeg">Flight Number</th>
													<!-- <th data-field="flightLegId" data-sortable="true"
														data-formatter="formatFlightLeg">Flight Leg #</th> -->
													<th data-field="depTime" data-sortable="true">Departure
														Time</th>
													<th data-field="arrTime" data-sortable="true">Arrival Time</th>
													<th data-field="depAirport" data-formatter="stateFormatter"
														data-sortable="true">City Pair</th>
													<th data-field="offloadDate" data-sortable="true">Offload
														Date</th>
													<th data-field="uploadedTime" data-sortable="true">Upload
														Date</th>
													<th data-field="failureReason" data-sortable="true">Failure
														Reason</th>
													<th data-field="source" data-sortable="true">Source</th>
													<th data-field="remarks" data-sortable="true">Remarks</th>


												</tr>
											</thead>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
			<!-- PAGE CONTENT WRAPPER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->
	<div class="message-box animated fadeIn" data-sound="alert"
		id="modalTable">

		<div class="modal-dialog modal-md" style="background-color: #f5f5f5; margin-top: 100px; border-radius: 6px;">
			
			<div class="modal-content" style="border-radius: 5px;border-width:0px;">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Reupload Offload Data</h4>
				</div> 
				<div class="modal-body">
					<div>
					<div class="row">
						<div class="col-md-3">FileName</div>
						<div class="col-md-8">		
							<label id="fileNameLabel" ></label>				
							
						</div>						
					</div>
					<br />
					<div class="row">
						<div class="col-md-3" style="padding-top: 7px;">Airline</div>
						<div class="col-md-5">
							<select id="rairlines"
								class="form-control selectpicker show-tick"
								data-live-search="true" value="rselectedairline">
							</select>

						</div>
						<div class="col-md-3" style="padding-top: 2px;">
							<div class="alert alert-danger" id="errorMsg" role="alert"
								style="padding-top: 7px; display: block; line-height: 9px;">
								<span style="color: white;">Select Airline</span>
							</div>
						</div>
					</div>
					<br />
					<div class="row">
						<div class="col-md-3" style="padding-top: 7px;">Tailsign</div>
						<div class="col-md-5">
							<select id="rtailsign"
								class="form-control selectpicker show-tick"
								data-live-search="true" value="rselectedtailsign">
							</select>

						</div>
					</div>
					<br />
					<div class="row">
						<div class="col-md-10 text-center"
							style="padding-top: 5px; padding-bottom: 9px;">
							<button id="reuploadfilter" class="btn btn-primary active"
								data-ng-click="">Upload</button>
						</div>
					</div>
					<div class="row">
						<div id="rloadingTabData" align="center">
							<img src="../img/loadingicon1.gif" style="height: 50px;"> <br />
							File Reuploading...
						</div>
					</div>
					<div class="row">
						<div class="col-md-3"></div>
						<div id="rSccessData" class="col-md-5 alert alert-success"
							role="alert">
							<span id="rSccessDataMsg" style="color: white;">Reuploaded the
								file successfully !!.</span>
						</div>
					</div>
				</div>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Logout page -->
	<?php include("../logout.php"); ?>
	<!-- END Logout page-->
	
	<!-- START SCRIPTS -->
	<!-- START PLUGINS -->
	<script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script>
	<script type="text/javascript"
		src="../js/plugins/jquery/jquery-ui.min.js"></script>
	<script type="text/javascript"
		src="../js/plugins/bootstrap/bootstrap.min.js"></script>
	<!-- END PLUGINS -->
	<!-- START THIS PAGE PLUGINS-->
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript"
		src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
	<script type="text/javascript"
		src="../js/plugins/bootstrap/bootstrap-select.js"></script>
	<script type="text/javascript"
		src="../js/plugins/blueimp/jquery.blueimp-gallery.min.js"></script>
	<script src="../js/plugins/dropzone/dropzone.min.js"></script>
	<script type="text/javascript" src="../js/plugins/icheck/icheck.min.js"></script>
	<script src="../js/angular.js"></script>
	<script src="../js/angular-route.js"></script>
	<script src="../js/angular-cookies.js"></script>

	<!-- START SCRIPTS -->


	<!-- START THIS PAGE PLUGINS-->

	<script type="text/javascript"
		src="../js/plugins/datatables/jquery.dataTables.min.js"></script>
	<script type="text/javascript"
		src="../js/plugins/tableexport/tableExport.js"></script>
	<script type="text/javascript"
		src="../js/plugins/tableexport/jquery.base64.js"></script>
	<script type="text/javascript"
		src="../js/plugins/tableexport/html2canvas.js"></script>
	<script type="text/javascript"
		src="../js/plugins/tableexport/jspdf/libs/sprintf.js"></script>
	<script type="text/javascript"
		src="../js/plugins/tableexport/jspdf/jspdf.js"></script>
	<script type="text/javascript"
		src="../js/plugins/tableexport/jspdf/libs/base64.js"></script>
	<!-- <script type="text/javascript"
		src="../js/plugins/bootstrap/bootstrap-datepicker.js"></script>
	<script type="text/javascript"
		src="../js/plugins/bootstrap/bootstrap-timepicker.min.js"></script> -->
	<!-- END THIS PAGE PLUGINS-->
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<!-- END TEMPLATE -->
	<script type="text/javascript">
				var app = angular.module("myApp", []);
				app.filter('startFrom', function() {
				    return function(input, start) {
				        if(input) {
				            start = +start; //parse to int
				            return input.slice(start);
				        }
				        return [];
				    }
				});
	            var id = $('#airline').val();
	            var airlineAcronym = '';
	            var toDate;
                var fromDate;
                var startDateTime= "<?php echo "$startDateTime";?>"; 
                var endDateTime= "<?php echo "$endDateTime";?>";

                var airlineIDs= "<?php echo "$airlineIds";?>";

                var toDate;
                var fromDate;

                if(<?php echo $downloadOffloadVisited;?>){
                	var session_AirlineId="<?php echo $_SESSION['airlineId'];?>";
                	var session_Platform="<?php echo $_SESSION['platform'];?>";
                	var session_Config="<?php echo $_SESSION['configType'];?>";
                	var session_dateFilter="<?php echo $_SESSION['dateFilter'];?>";
                	var session_startDate="<?php echo $_SESSION['startDate'];?>";
                	var session_endDate="<?php echo $_SESSION['endDate'];?>";
                	var session_status="<?php echo $_SESSION['status'];?>";
                	var session_tailsign="<?php echo $_SESSION['tailsign'];?>";
                	var session_depAirport="<?php echo $_SESSION['depAirport'];?>";
                	var session_arrAirport="<?php echo $_SESSION['arrAirport'];?>";
                	var session_source="<?php echo $_SESSION['source'];?>";
                	var session_failureReason="<?php echo $_SESSION['failureReason'];?>";                	
            	
                	var downloadOffloadVisited=true;
                }else{
                	var downloadOffloadVisited=false;
                }
                
	            $(document).ready(function() {
	            	toDate = new Date();
                    fromDate = new Date();
                    fromDate.setDate(fromDate.getDate() - 1);
                    $('#rloadingTabData').hide();
	            	/*  $('#startDate').datetimepicker({
                         format: "Y-m-d",
                         value: fromDate,
                         timepicker: false,
                         weeks: true
                     });

	            	 $('#endDate').datetimepicker({
                         format: "Y-m-d",
                         value: toDate,
                         step: 15,
                         timepicker: false,
                         weeks: true
                     }); */

	            	 $('#errorMsg').hide();
	            	 $('#rSccessData').hide();
	            	 
                    $('#airline').selectpicker({
                        size: 6
                    });
                    $('#depAirport').selectpicker({
                        size: 6
                    });
                    $('#arrAirport').selectpicker({
                        size: 6
                    });
                    $('#tailsign').selectpicker({
                        size: 6
                    });
                    $('#selectedConfigType').selectpicker({
                        size: 6
                    });
                    $('#selectedPlatform').selectpicker({
                        size: 6
                    });
                    $('#failureReason').selectpicker({
                        size: 6
                    });
                  
                    $('#rairlines').selectpicker({
                        size: 6
                    });

                    $('#rtailsign').selectpicker({
                        size: 6
                    });

                    
                    $('#airline').on('change', function(){
                	    angular.element($("#ctrldiv")).scope().loadPlatforms();
                	  });

                	$('#selectedPlatform').on('change', function(){
                	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
                	  });

                	$('#selectedConfigType').on('change', function(){
                	    angular.element($("#ctrldiv")).scope().loadTailsign();
                	  });
                	$('#tailsign').on('change', function(){
                	    angular.element($("#ctrldiv")).scope().getDepArrAirportList();
                	  });
                	$('#rairlines').on('change', function(){
                	    angular.element($("#ctrldiv")).scope().loadRTailsign();
                	  });
                    
                	$('#dateFilter').on('change', function(){
                		angular.element($("#ctrldiv")).scope().getDepArrAirportList();
                	});
                	
                	$('#startDate').on('blur', function(){
                		console.log('Start Date changed');
                		angular.element($("#ctrldiv")).scope().getDepArrAirportList();
                	});
                 	
                	$('#endDate').on('blur', function(){
                		console.log('End Date changed');
                		angular.element($("#ctrldiv")).scope().getDepArrAirportList();
                	});
                	
                });
	            
	            function stateFormatter(value, row, index) {
				       return row.depAirport+' > ' +row.arrAirport;
				    }

	            function reuploadFormatter(value, row, index) {
	            	if(row.status=='Processed') {
	            		return "-";
			        }else{
			        	return "<button class='btn btn-default' id='infomodal' onclick='reuploadOffload()' ><i class='fa fa-pencil-square-o'></i></button>";
	            	}
				}

	            function reuploadOffload(){
	            	var el = document.getElementById('myModal');
	            	el.style.display='block';
		        }

	            function downloadFile(value, row, index, field){
	            	var method = "javascript:downloadFilex(" + row.id + ",'"+row.fileName+"')";	 	            	          	
	            	return "<a href=" + method + ">" + value + "</a>";
		        }

	            function downloadFilex(id,fileName) {	           
		            var url = "../ajax/downloadOffloadFile.php?id="+ id+"&filename="+fileName;		                 	
	                var win = window.open(url, '_self');
	                win.focus();
				};

	            function formatFlightLeg(value, row, index, field) {
	            	if(row.status=='Processed') {
	            		if(value){
		            		value = value.replace(/,/g, ", ");
	            		}
	            		var method = 'javascript:analyzeFlightLegs('+'"' + row.flightLegIds +'"'+ ',' + row.aircraftId + ')';

	            		return "<a href='" + method + "'>" + value + "</a>";
	            	} else {
	            		if(row){
	            			return "<span >" + value + "</span>";
		            	}else{
	            			return '-';
	            		}
	            	}
	            }
	            function analyzeFlightLegs(flightLegIds, aircraftId) {
	            	var url = "FlightAnalysis.php?aircraftId="+aircraftId+"&flightLegs="+flightLegIds+"&mainmenu=DownloadOffload";
	                var win = window.open(url, '_self');
	                win.focus();
	            }

	            function operateFormatter(value, row, index) {
	            	if(row.status=='Processed') {
		            	return value;
		            }else{
		                return value+[
		                    '&nbsp;&nbsp;<a class="info" href="#" style="color:#c9302c;" data-toggle="modalTable" data-target="#modalTable">',
		                    '<span class="fa fa-cloud-upload" style="font-size:17px;" ></span>',
		                    '</a>'
		                ].join('');
	                }
	            }

	            
	            	            
	          
			</script>

</body>
<script src="../controllers/downloadOffloadsController.js"></script>
<link href="../css/jquery.datetimepicker.css" rel="stylesheet"
	type="text/css" />

<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<script src="../js/bootstrap-table-export.js"></script>
<script src="../js/tableExport.js"></script>
<link rel="stylesheet"
	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
</html>
