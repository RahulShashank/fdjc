<!DOCTYPE html>
<?php
// session_start();
session_start();

$menu = 'UploadnDownloadOffload';

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once '../libs/PHPExcel/PHPExcel.php';
require_once '../libs/PHPExcel/PHPExcel/IOFactory.php';
require_once ("checkEngineeringPermission.php");

$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
$airlineIds = rtrim($airlineIds, ",");

$downloadOffloadVisited = $_REQUEST ['downloadOffloadVisited'];

// get current date
$endDateTime = date('Y-m-d');
$startDate = date_create("$endDateTime");
date_sub($startDate, date_interval_create_from_date_string("6 days"));
$startDateTime = date_format($startDate, "Y-m-d");

if($downloadOffloadVisited=='true'){
	error_log('Airline in timeline : '.$_SESSION['airline']);
}else{
		$downloadOffloadVisited=false;
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
<link rel="shortcut icon" href="../img/icon.ico">
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

<style>
    body{
        background:#ffffff;
    }
    
    .loadFrame html>body{ background: #ffffff }
    
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
	 body.modal-open {
	    overflow: hidden !important;
	    position:fixed !important;
	    width: 100% !important;
	}
	
	.dropdown-menu{
        transform: translate3d(0px, 0px, 0px)!important;
    }
    
</style>
</head>

<body >
	<!-- START PAGE CONTAINER -->
	<div id="container" class="page-container">
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
				<!-- SEARCH 
            <li class="xn-search">
            	<form role="form">
            		<input type="text" name="search" placeholder="Search..." />
            	</form>
            </li>-->
				<!-- END SEARCH -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span></a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->
			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">Upload Offloads</li>
			</ul>
			<!-- END BREADCRUMB -->
			<!-- PAGE TITLE -->
			<div class="page-title">
				<h2>
					<!-- <span class="fa fa-arrow-circle-o-left"></span>-->
					Upload Offloads
				</h2>
				<a href="#" class="info" data-toggle="modal" data-target="#modalTable">					 
					<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px; color: #656d78;cursor: pointer;padding-top: 6px; padding-right: 3px;">
					</i>
				</a>
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">				
						<div class="panel panel-default" >
							<div class="panel-heading ui-draggable-handle">
								<h2 class="panel-title">
									<span class="fa fa-upload"></span> Upload Offloads
								</h2>
							</div>
							<div class="panel-body">
								<div id="uploadDiv" class="col-md-12"  style="padding: 0px;">
									<div align="center"  style="height: 100% !important;">
										<div class="block push-up-10" id="biteDropzoneDiv" style="margin-bottom: 10px !important;">
											<form id="biteDropzone" action="UploadDropzone.php"
												method="post" class="dropzone dropzone-mini">
												<input type="hidden" id="airlineId" name="airlineId"> <input
													type="hidden" id="acronym" name="acronym">
												<div class="dz-message">Drop BITE files here or click to
													upload.</div>
											</form>
										</div>
									</div>
								</div>
								<div id="errorDiv" class="col-md-6">
									<div class="errorBlock"></div>
										<div id="legend" class="legend"></div>
										<div id="loadDiv" style="float: left; border: 0px; width: 100%; display: none;">
										<div class="text-left" id="processStatus"></div>
										<br />
										<iframe id="loadarea" class="loadFrame" name="loadarea"	style="border: 0px; height: 339px;width: 100%; background: #FFFFFF;" ></iframe>
									</div>
									<!-- <div>
										<div class="row">
											<div class="col-md-12 text-center">
												<button id="Reload" class="btn" title="Click Reload ! Once files have been processed" style="float:right">Reload</button>
											</div>
										</div>
									</div> -->
								</div>
							</div>
						</div>
					</div>	
				</div>
				<div id="ctrldiv" ng-controller="downloadOffloadsController">
					<div class="row">
						<div class="col-md-12">
							<div class="panel panel-default">
								<div class="panel-heading ui-draggable-handle">
									<h2 class="panel-title">
										<span class="fa fa-download"></span> Download Offloads
									</h2>
								</div>
								<div class="panel-body">
	                				<input type="hidden" id="airlineIds" ng-model="airlineIds"
	                					ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
									<div class="row">
										<div class="col-md-2">
											<div class="row">
												<b> Airline </b>
											</div>
											<div class="row">
												<select id="airline"
													class="form-control selectpicker show-tick"
													data-live-search="true" data-dropup-auto="false" value="selectedairline">
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
													data-live-search="true" data-dropup-auto="false" value="selectedPlatform">
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
													data-live-search="true" data-dropup-auto="false" value="selectedConfigType">
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
													data-live-search="true" data-dropup-auto="false" value="selectedTailsin">
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
													data-dropup-auto="false" value="selectedStatus">
													<option value="">All</option>
													<option value="Processed">Processed</option>
													<option value="Rejected">Rejected</option>
													<option value="Unassigned">Unassigned</option>
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
													data-dropup-auto="false" value="selectedSource">
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
													data-dropup-auto="false" value="selectedDateFilter">
													<option value="UploadDate">Upload Date</option>
													<option value="DepartureTime">Departure Time</option>
													<option value="ArrivalTime">Arrival Time</option>
													<option value="OffloadDate">Offload Date</option>
												</select>
											</div>
										</div>
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
													data-live-search="true" data-dropup-auto="false" value="selectedairline">
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
													data-live-search="true" data-dropup-auto="false" value="selectedairline">
												</select>
											</div>
										</div>
										<div class="col-md-2">
											<div class="row">
												<b> Failure Reason </b>
											</div>
											<div class="row">
												<select id="failureReason"
													class="form-control selectpicker show-tick" data-dropup-auto="false" 
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
										<div class="col-md-12 text-left">
											<button id="filter" class="btn btn-primary"
												data-ng-click="filterOffloads()">Filter</button>
											&nbsp;&nbsp;&nbsp;
											<button id="reset" type="button" class="btn btn-reset"
												ng-click="resetOffloads();">Reset</button>
										</div>
									</div>
									<div class="row" style="margin-left: -25px; margin-right: -25px;">
										<div class="col-md-12 text-left">
											<hr style="border-top: 1px solid #E5E5E5;">
										</div>
									</div>
									<div id="loadingTabData" align="center">
										<br/>
										<img src="../img/loadingicon1.gif" style="height: 30px;"> <br />
										Loading Data...
									</div>
									
									<div id="downloadOffload" class="table-responsive" style="overflow: auto;">
	
											<table id="offloadTabledata" data-classes="table"
												data-pagination="true" data-page-list="[10,25, 50, 100, All]"
												data-page-size="10" data-striped="true" data-search="true"
												data-search-align="left" data-show-export="true">
												<thead>
													<tr>
														<th data-field="fileName" data-formatter="downloadFile"
															data-sortable="true">File Name</th>
														<th data-field="fileSize" data-formatter="formatFileSize" data-sortable="true">File Size</th>
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
				
				<!-- PAGE CONTENT WRAPPER -->
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
						<h4 class="modal-title">BITE FILE</h4>
				</div> 
				<div class="modal-body">
					<u>Rules for uploading BITE Files:</u> <br> <br>
					<ul>
						<li>Only <b>ONE FILE</b> can be uploaded at a time. <br>To upload
											another file you have to reload the page.
						</li>
						<li><b>Multiple xml and tgz files can be zipped to one .zip
												file and uploaded.</b></li>
						<li>Individual tgz or xml files are also accepted.</li>
						<li>Files of size up to 75MB can be uploaded.</li>
					</ul>
    				<br/>					
				</div>
			</div>
		</div>
	</div>
    	
    	<div class="message-box animated fadeIn" id="modalTable">
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
		
		<!-- START SCRIPTS -->
		<!-- START PLUGINS -->
		<script type="text/javascript"
			src="../js/plugins/jquery/jquery.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/jquery/jquery-ui.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/bootstrap/bootstrap.min.js"></script>
		<!-- END PLUGINS -->
		<!-- START THIS PAGE PLUGINS-->
		<script type='text/javascript'
			src='../js/plugins/icheck/icheck.min.js'></script>
		<script type="text/javascript"
			src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/bootstrap/bootstrap-select.js"></script>
		<script type="text/javascript"
			src="../js/plugins/blueimp/jquery.blueimp-gallery.min.js"></script>
		<script src="../js/plugins/dropzone/dropzone.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/icheck/icheck.min.js"></script>
		<script src="../js/angular.js"></script>
		<script src="../js/angular-route.js"></script>
		<script src="../js/angular-cookies.js"></script>
		<!-- END THIS PAGE PLUGINS-->
		<script type="text/javascript" src="../js/plugins.js"></script>
		<script type="text/javascript" src="../js/actions.js"></script>
		<!-- END TEMPLATE -->
		<script>
                        var app = angular.module("myApp", []);
                        var id = $('#airline').val();
                        var airlineAcronym = '';
                        $('#errorDiv').hide();
                        $(document).ready(function() {
                        	var startDateTime= "<?php echo "$startDateTime";?>"; 
    		                var endDateTime= "<?php echo "$endDateTime";?>";
    		                
							$('#errorSpan').hide();
							$('#errorDiv').hide();
                            $('#biteDropzone').click(function(e) {                                
                                e.preventDefault();
                                e.stopPropagation();
                                if ($('#airline').val() == '') {
                                    $('#errorSpan').show();                                    
                                }else{
                                	$('#errorSpan').hide();                                	
                                	$('#biteDropzone').dropzone({clickable: true});
                                }
                                return false;
                            });

                            var x = document.getElementById("loadarea");
                            var y = (x.contentWindow || x.contentDocument);
                            if (y.document)y = y.document;
                            y.body.style.backgroundColor = "white";
                            $('iframe').contents().find('body').css('background', '#FFF');
                        });

                        //Dropzone.autoDiscover = false;
                        Dropzone.options.biteDropzone = {
                            clickable:true,
                            maxFiles: 1,
                            timeout: 300000,
                            accept: function(file, done) {
                        	    if (file.size == 0) {
                        	      done("Empty files will not be uploaded.");
                        	    }
                        	    else { done(); }
                        	  },
                        	  init: function() {
                      		    this.on("queuecomplete", function(file, responseText) {
                      		      	// Handle the responseText here. For example, add the text to the preview element:
                      		      	var x = document.getElementById("loadarea");
                                    var y = (x.contentWindow || x.contentDocument);
                                    if (y.document)y = y.document;
                                    y.body.style.backgroundColor = "white";
                                    $('iframe').contents().find('body').css('background', '#FFF');
                      		     	console.log("upload complete: " + responseText);
                      		    });

                      		    this.on("success", function(file, responseText) {
                      		      	// Handle the responseText here. For example, add the text to the preview element:	
                      		      	// console.log(responseText);	      	
                      				var message = '';
                      				var icon = '';
                      				if(responseText.indexOf("ko.png") > -1 || responseText.indexOf("error") > -1) {
                      					message = 'Upload done with errors.';
                      					icon = 'ko_big.png';
                      					$('.legend').html("<img src=\"../img/" + icon + "\" width=\"75px\" height=\"75px\"><br><h2>" + message + "</h2><br><br><div align=\"left\" style=\"margin-right: 25%; margin-left: 25%\">" + responseText + "</div>");
                      				} else if(responseText.indexOf("warning.png") > -1) {
                      					message = 'Upload done with warnings.';
                      					icon = 'warning_big.png';
                      					$('.legend').html("<img src=\"../img/" + icon + "\" width=\"75px\" height=\"75px\"><br><h2>" + message + "</h2><br><br><div align=\"left\" style=\"margin-right: 25%; margin-left: 25%\">" + responseText + "</div>");
                      				} else {
                      					message = 'Upload done successfully.';
                      					icon = 'ok_big.png';

                      					if(responseText == 'NO_UID') {
                      						console.log('User id not found');
                      						window.open('../index.php','_parent');
                      					}
                      					
                      					//BeR 17Nov17: upload to server is successful -> defining functions for ajax call of ProcessOffloads.php
                      					
                      					function beforeSendCallback() {						
                      						//BeR 17Nov17: message while processing in progress
                      						console.log("entered beforeSendCallback");
                      						$('.legend').html("<img id=\"loading_spinner\" src=\"../img/loadingicon1.gif\" width=\"30px\" height=\"30px\">&nbsp;&nbsp;Files are being processed. Please wait...");
                      					}
                      					
                      					function successCallback() {
                      						//BeR 7Nov17: we should not need the successCallBack state. Basic message just in case to know where it came from
                      						console.log("entered sucessCallback");                      						
                      						//BeR 10Nov17: we use successCallback to return a processing that is complete						
                      						message = 'Upload done and data processed successfully.';
                      						icon = 'ok_big.png';
                      						$('.legend').html("<img src=\"../img/" + icon + "\" width=\"75px\" height=\"75px\"><br><h2>" + message + "</h2>");									
                      					}
                      					
                      					function completeCallback() {
                      						console.log("entered completeCallback");
                      						//BeR 10Nov17: we don't display result via completeCall back as it ends up there irrespectively coming from error or success
                      						//$('.legend').html("<br><h2>Data processed (depending on the network used, some data might still be under processed and will be completed shortly (around 5mns per 10Mb of data archived)</h2>");
                      					}
                      					
                      					function errorCallback() {
                      						console.log("entered errorCallback");
                      						//BeR 10Nov17: we use error to mitigate the 504 gateway error thrown sometimes. It means the processing is still on going ont he server						
                      						message = 'Data processing still in progress in background task. Wait a few minutes for completion (5mns per 10Mb archive)';
                      						icon = 'warning_big.png';
                      						$('.legend').html("<img src=\"../img/" + icon + "\" width=\"75px\" height=\"75px\"><br><h2>" + message + "</h2><br><br>");

                      					}
                      										
                      					//BeR 17Nov17: ajax call to ProcessOffloads.php
										$('#uploadDiv').removeClass('col-md-12');
										$('#uploadDiv').addClass('col-md-6');
										$('#errorDiv').show();
                      					$('#loadDiv').show();
                      					$('#loadDiv').focus();
                                        $('#biteDropzone').height($('iframe').height());
                      					document.getElementById('loadarea').src="ProcessOffloads.php";
                      					
                      				//	setTimeout(function() {
                      					//	$('#errorDiv').hide();
                      					//	$('#uploadDiv').removeClass('col-md-6');
    									//	$('#uploadDiv').addClass('col-md-12');
    									//	location.reload();    										
										//}, 2000);

                      				}				

                                  	$('#legend').focus();
                      		    });

                      		    this.on("error", function(file, responseText) {
                      		      	// Handle the responseText here. For example, add the text to the preview element:
                      		      	message = responseText;
                      				icon = 'ko_big.png';
                      		      	$('.legend').html("<img src=\"../img/" + icon + "\" width=\"75px\" height=\"75px\"><br><h2>" + message + "</h2><br><br><div align=\"center\" style=\"margin-right: 25%; margin-left: 25%\"><a href=\"javascript:window.location.reload()\">Click here to upload new files.</a></div>");
                                  	$('#loading_spinner').hide();
                      		    });

                      		    this.on("addedfile", function(file) {
                      		    	console.log("added file: " + file);
                      			  // if (file.type.match('application/zip')) {
                      			    // This is not an image, so Dropzone doesn't create a thumbnail.
                      			    // Set a default thumbnail:
                      			    this.emit("thumbnail", file, "../img/zip.png");

                      			    // You could of course generate another image yourself here,
                      			    // and set it as a data url.

                      			    $('.legend').html("<img id=\"loading_spinner\" src=\"../img/loadingicon1.gif\" width=\"30px\" height=\"30px\">&nbsp;&nbsp;Files are being processed. Please wait...");
                      			  // }
                      			 });
                      		  }
                      		};
                       
                    </script>
                    
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
						var downloadnUploadOffloadVisited='<?php echo $_REQUEST ['downloadOffloadVisited']; ?>';
						
		                if(downloadnUploadOffloadVisited=='true'){		                	
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
			            	var method = "javascript:downloadFilex(" + row.id + ",'" + row.status + "','" + row.fileName + "')";	 	            	          	
			            	return "<a href=" + method + ">" + value + "</a>";
				        }
		
			            function formatFileSize(value, row, index, field){
			            	var newValue = (value/1024).toFixed(2) + " KB";	 	            	          	
			            	return newValue;
				        }

			            function downloadFilex(id,status,fileName) {	           
				            var url = "../ajax/downloadOffloadFile.php?id="+ id+"&status="+status+"&filename="+fileName;		                 	
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
			            	var url = "FlightAnalysis.php?aircraftId="+aircraftId+"&flightLegs="+flightLegIds+"&mainmenu=UploadnDownloadOffload";
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
		
			            $('#Reload').click(function(){ 
			            	location.reload();
				        });
						
					</script>
		<!-- END SCRIPTS -->

</body>
<script src="../controllers/downloadOffloadsController.js"></script>
<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<script src="../js/bootstrap-table-export.js"></script>
<script src="../js/tableExport.js"></script>
<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
</html>