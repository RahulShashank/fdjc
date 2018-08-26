<?php
session_start ();
$menu = 'ConnectivityUpload';
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
include ("BlockCustomer.php");

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );

$firstTime = $_REQUEST['firstTime'];
error_log('First time : '.$firstTime);
$aircraftId = $_REQUEST['aircraftId'];

//SB:code added for date filter
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];

$airlineId = $_REQUEST['airlineId'];
$tailsign = $_REQUEST['tailsign'];

if($firstTime){
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
	} else {
	    echo "Error - no aircraftId";
	    exit;
	}
	
	// Find latest date
	$cursor = $collection->find(array("tailSign" => $aircraftTailsign), array("startTime"))->sort(array("startTime" => -1))->limit(1);
	//var_dump($cursor->getNext());exit;
	
	$fields = array('_id',
					'startTime',
					'idFlightLeg',
					'cityPair',
					'flightNumber',
					'test',
					'flightFailure',
					'altitudeEvent.startTime',
					'altitudeEvent.endTime',
					'wifiAvailabilityEvents.manualFailureEntry',
					'wifiAvailabilityEvents.computedFailure',
					'omtsAvailabilityEvents.computedFailure',
					'omtsAvailabilityEvents.manualFailureEntry',
					'wifiAvailability.totalOnPercentage',
					'wifiAvailability.manualPercentageOn',
					'omtsAvailability.totalOnPercentage',
					'wifiAvailability.manualPercentageOn',
					'wifiAvailabilityEvents.description');
					
	if($startDate == '' && $endDate == ''){		
		$cursor = $collection->find(array("tailSign" => $aircraftTailsign),$fields)->sort(array("startTime" => 1));
		//get current date
		$endDate=date('Y-m-d H:i:s');
		$startDate1 = date_create("$endDateTime");												
		date_sub($startDate1,date_interval_create_from_date_string("6 days"));
		$startDate = date_format($startDate1,"Y-m-d H:i:s");
		
		$where = array('$and' => array(	array("startTime" => array('$gte' => $startDateTime,'$lte' => $endDateTime )),
									array("tailSign" => $aircraftTailsign)));
		$cursor = $collection->find($where,$fields)->sort(array("startTime" => 1));
	
	}else{
		$where = array('$and' => array(	array("startTime" => array('$gte' => $startDate,'$lte' => $endDate )),
									array("tailSign" => $aircraftTailsign)));
		$cursor = $collection->find($where,$fields)->sort(array("startTime" => 1));
	}
}

// iterate cursor to display title of documents
//SB:Code added to get aircraftId as per the timestamp.
function getFlightLegId($timeStamp,$dbConnection,$dbName)
{
	$query = "SELECT idFlightLeg FROM $dbName.SYS_flight WHERE createDate <= '$timeStamp' AND lastUpdate >= '$timeStamp' ";
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $idFlightLeg = $row['idFlightLeg'];
    } 
	return $idFlightLeg;	
}
// iterate cursor to display title of documents

?>
<!DOCTYPE html>
<html lang="en" data-ng-app="myApp">
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/globe-icon.ico">
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
<script	type="text/javascript" src="../js/alertify/alertify.js"></script>
<script	type="text/javascript" src="../js/alertify/alertify.min.js"></script>
<link rel="stylesheet" href="../css/alertify/alertify.min.css" />
</head>
<style>
.dateChange{
    background-color:#F9F9F9 !important;
    color:#000000 !important;
    cursor: auto !important;
}
.dropzone {
    border: 2px dashed #D5D5D5 !important;
}
.modal-content {
	border-width: 1px;
    border-radius: 6px;
    border: 1px solid #d5d5d5;
}
.modal-dialog {
    width: 600px;
    margin: 317px auto;
}
</style>
<body id="ctrldiv" ng-controller="ConnectivityController">
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
						class="fa fa-dedent"></span> </a></li>
				<!-- END TOGGLE NAVIGATION -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span> </a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->

			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">Connectivity Upload</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>Connectivity Upload</h2>
				<a href="#" class="info" data-toggle="modal" data-target="#modalTable">					 
					<i class="glyphicon glyphicon-info-sign pull-right" style="font-size: 20px; color: #656d78;cursor: pointer;padding-top: 6px; padding-right: 3px;">
					</i>
				</a>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">

				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-heading ui-draggable-handle">
								<h2 class="panel-title">
									<span class="fa fa-upload"></span> Connectivity Uploads
								</h2>
							</div>
							<div class="panel-body" style="padding-left: 0px; padding-right: 0px; padding-bottom: 5px;">
								<div class="row">
									<div id="uploadDiv" class="col-md-12">
	                					<div id="airlineDiv" class="col-md-2">
	                						<input type="hidden" id="airlineIds" ng-model="airlineIds"
	                					ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>" value="<?php echo $airlineIds ?>" />
	                						<label for="airline">Airline</label>
	                						<div>
	                							<select id="airline" class="selectpicker show-tick" data-width="100%" data-live-search="true" ></select>
	                						</div>
	                					</div>
	                					<div id="tailsignDiv" class="col-md-2">
	                						<label for="tailsign">Tailsign</label>
	                						<div>
	                							<select id="tailsign" class="selectpicker show-tick" data-width="100%" 
	                							data-selected-text-format="count > 3" data-live-search="true"></select>
	                						</div>
	                					</div>
	                					<br/>
	                					<div id="connectivity">                					
											<div align="center">
												<div class="block push-up-10" id="dropzone" style="margin-bottom: 10px !important;" >
													<form id="connectivityDropzone" action="processConnectivityFiles.php"
														method="post" class="dropzone dropzone-mini">
														<input type="hidden" id="aircraftId" name="aircraftId" />
														<input type="hidden" id="aircraftISP" name="aircraftISP" />	
														<input type="hidden" id="airlineId" name="airlineId" />	
														<div class="dz-message" style="margin-top: -42px;color: #656d78;font-size: 12px;">Drop Connectivity Log file here or click to upload.</div>
													</form>
												</div>
												<br>
												<!-- <div class="legend" align="center"></div> -->
												<br>
											</div>
										</div>
									</div>
									<div id="errorDiv" class="col-md-6" >
										<div >
											<div class="legendHeader" style="font-size: 13px;"></div>
											<div class="legend" style="height: 265px;overflow: auto;"></div>
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
							<div class="panel-heading ui-draggable-handle">
								<h2 class="panel-title">
									<span class="fa fa-upload"></span> Connectivity Upload History
								</h2>
							</div>
							<div class="panel-body">
								<div class="row">
									<div class="col-md-2">
                						<label for="airlinetable">Airline</label>
                						<div>
                							<select id="airlinetable" class="selectpicker show-tick" multiple data-live-search="true"  title="All" data-dropup-auto="false"  data-width="100%" ></select>
                						</div>
                					</div>
                					<div class="col-md-2">
                						<label for="tailsigntable">Tailsign</label>
                						<div>
                							<select id="tailsigntable" class="selectpicker show-tick" data-width="100%" 
                							data-selected-text-format="count > 3" data-dropup-auto="false"  data-live-search="true"  multiple title="All" ></select>
                						</div>
                						<input type="hidden" id="aircraftIdTable" name="aircraftIdTable" />
                					</div> 
									<div class="col-md-2">										
										<label for="startDateTimePicker">From</label>
										<input class="form-control dateChange" id="startDateTimePicker"	type="text" name="startDateTimePicker" style="width: 100%;" readonly='true'>																				
									</div>
									<div class="col-md-2">										
										<label for="endDateTimePicker">To</label>
										<input class="form-control dateChange" id="endDateTimePicker" type="text" name="endDateTimePicker" style="width: 100%;" readonly='true'>											
									</div>	
									<div class="col-md-4">
                						<label for="buttons">&nbsp;&nbsp;</label>
                						<div>
											<button id="filterbtn" class="btn btn-primary">Filter</button>&nbsp;&nbsp;&nbsp;&nbsp;<button id="reset" type="button" class="btn btn-reset">Reset</button>
                						</div>
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
								<div id="connectivityHistoryDiv" >
									<table id="connectivityHistoryTable" data-classes="table"
										data-pagination="true" data-page-list="[25, 50, 100, All]"
										data-page-size="25" data-striped="true" data-search="true"
										data-search-align="left" data-show-export="true">
										<thead>
											<tr>
												<th data-field="id" data-sortable="true">Id</th>
												<th data-field="name" data-sortable="true">Airline</th>
												<th data-field="tailsign" data-sortable="true">Tailsign</th>
												<th data-field="filename" data-sortable="true">Filename</th>
												<th data-field="date" data-sortable="true">Upload Date</th>												
											</tr>
										</thead>
									</table>
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
						<h4 class="modal-title">CONNECTIVITY LOG FILE</h4>
				</div> 
				<div class="modal-body">
					<u>Rules for uploading Connectivity Log Files:</u> <br> <br>
					<ul>
						<li>Please select the airline and aircraft to upload connectivity log file.</li>
						<li>Only <b>ONE FILE</b> at a time. To upload another file you must have to reload the page.</li>
						<li>gz, log and zip (of gz or log) files only are accepted.</li>
					</ul>
    				<br/>					
				</div>
			</div>
		</div>
	</div>
		
</body>
<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
<script type="text/javascript" src="../js/plugins.js"></script>
<script type="text/javascript" src="../js/actions.js"></script>
<script type="text/javascript" src="../controllers/ConnectivityController.js"></script>
<script src="../js/FileSaver.min.js"></script>
<script src="../js/canvas-toBlob.js"></script>
<link href="../css/dropzone.css" type="text/css" rel="stylesheet" />
<script src="../js/dropzone.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>
<script type="text/javascript">		
		$('#errorDiv').hide();
		 $('#connectivityDropzone').click(function(e) {                                
             e.preventDefault();
             e.stopPropagation();
            
             return false;
         });

		 function notyConfirm(dropZoneObj, file){
			 bootbox.confirm({
				 	title: "Confirm Upload",
				    message: "Do you want to upload the file for the selected tailsign?",
				    buttons: {
				    	 cancel: {
				             label: '<i class="fa fa-times"></i> Cancel'
				         },
				         confirm: {
				             label: '<i class="fa fa-check"></i> Confirm'
				         }
				        
				    },
				    callback: function (result) {
					    if(result){		        
	                    	dropZoneObj.enqueueFile(file);
						}else{
	                    	dropZoneObj.removeFile(file);
						}
				        
				    }
				});                                                  
		    } 
		    
		Dropzone.options.connectivityDropzone = {
		  autoQueue: false,
		  maxFiles: 1,
		  init: function() {
		    this.on("queuecomplete", function(file, responseText) {
		      	// Handle the responseText here. For example, add the text to the preview element:
		     	console.log("upload complete: " + responseText);
		    });
		    this.on("addedfile", function (file) {
			        	
		    	var op = notyConfirm(this, file);
		    	/*console.log(op);
		    	if(!op){
	                this.removeFile(file);
	                return false;
	            }*/

	        });
		    this.on("success", function(file, responseText) {
		      	// Handle the responseText here. For example, add the text to the preview element:	
		      	// console.log(responseText);	      	
				var message = '';
				var icon = '';
				if(responseText.indexOf("ko.png") > -1 || responseText.indexOf("error") > -1) {
					message = 'Upload done with errors.';
					icon = 'ko_big.png';
				} else if(responseText.indexOf("warning.png") > -1) {
					message = 'Upload done with warnings.';
					icon = 'warning_big.png';
				} else {
					message = 'Upload done successfully.';
					icon = 'ok_big.png';
				}	
				$('#uploadDiv').removeClass('col-md-12');
				$('#uploadDiv').addClass('col-md-6');
				$('#airlineDiv').removeClass('col-md-2');$('#airlineDiv').addClass('col-md-4');
				$('#tailsignDiv').removeClass('col-md-2');$('#tailsignDiv').addClass('col-md-4');				
				$('#errorDiv').show();
				
				$('.legendHeader').html("<br/><img src=\"../img/" + icon + "\" width=\"20px\" height=\"20px\">&nbsp;&nbsp;<b style=\"font-weight: bolder;\">" + message + "</b><br/><br/>");			
		      	$('.legend').html("<br><br><div align=\"left\" style=\"padding-left: 12px;padding-right: 12px;\">" + responseText + "</div>");
            	$('#loading_spinner').hide();
            	$("#disableTailsignCheck").attr("disabled", false);
		    });

		    this.on("error", function(file, responseText) {
		      	// Handle the responseText here. For example, add the text to the preview element:
		      	message = responseText;
				icon = 'ko_big.png';
				$('.legendHeader').html("<br/><img src=\"../img/" + icon + "\" width=\"20px\" height=\"20px\">&nbsp;&nbsp;<b style=\"font-weight: bolder;\">" + message + "</b><br/><br/>");
		      	$('.legend').html("<div align=\"center\" style=\"padding-left: 12px;padding-right: 12px;\"><br/><br/><br/><br/><a href=\"javascript:window.location.reload()\">Click here to upload new files.</a></div>");
            	$('#loading_spinner').hide();
            	$("#disableTailsignCheck").attr("disabled", false);
		    });

		    this.on("addedfile", function(file) {
		    	console.log("added file: " + file);
			  // if (file.type.match('application/zip')) {
			    // This is not an image, so Dropzone doesn't create a thumbnail.
			    // Set a default thumbnail:
			    this.emit("thumbnail", file, "../img/zip.png");

			    // You could of course generate another image yourself here,
			    // and set it as a data url.
				$('.legendHeader').html("<br/><img id=\"loading_spinner\" src=\"../img/loading.gif\" width=\"20px\" height=\"20px\"><br/><br/>");
			    $('.legend').html("<h5>Files are being processed. Please wait...</h5>");
			    $("#disableTailsignCheck").attr("disabled", true);
			  // }
			 });
		  },
		  // accept: function(file, done) {
		  //   if (!file.type.match ('application/zip') || !file.type.match ('application/x-zip')) {
		  //     done("Invalid format");
		  //   }
		  //   else {
		  //   	done();
		  //   }
		  // }
		};

	$('#disableTailsignCheck').click(function () {
		var status = $(this).is(':checked');
		if(status) {
			if(confirm("Are you sure to disable the tailsign verification?\n\nThis option is recommended to be used when the tailsign in an XML file is empty or wrong (e.g. the aircraft type is used instead of the tailsign).\n\nWhen disabled, if you upload the data for another aircraft by mistake, you will not be able to delete them.\n")){
				$.post('../ajax/disableTailsignCheck.php', 
			  			{value: true},
			  			function() {
			  				// console.log('done');
			  			}
			  		)
			} else {
					$(this).prop('checked', false);
				}
		    } else {
		    	$.post(
		  			'../ajax/disableTailsignCheck.php', 
		  			{value: false},
		  			function() {
		  				// console.log('done');
		  			}
		  		)
			}
	});

	var today = new Date();
	var priorDate = new Date(new Date().setDate(today.getDate()-6));
	var startDatetime = formatDate(priorDate);
	var endDatetime = formatDate(today);
	var session_airline='<?php echo "$airlineId";?>';
	var session_tailsign='<?php echo "$tailsign";?>';
	
	$(document).ready(function(){
			var firstTime=true;
			var endDate= "<?php echo "$endDate";?>";
			var startDate= "<?php echo "$startDate";?>"; 
			
			if(endDate=="" && startDate==""){
				$('#startDateTimePicker').datetimepicker({
					timepicker:false,
					format: "Y-m-d",
					value: startDatetime
				});
				
				$('#endDateTimePicker').datetimepicker({
					timepicker:false,
					format: "Y-m-d",
					value: endDatetime
				});
			}else{			
				$('#startDateTimePicker').datetimepicker({
					timepicker:false,
					format: "Y-m-d",
					value: startDate
				});
				
				$('#endDateTimePicker').datetimepicker({
					timepicker:false,
					format: "Y-m-d",
					value: endDate
				});
			}
			
	}); 
	$("#filterbtn").click(function(event){		
		getConnectivityHistory();
	});

	function getConnectivityHistory(){
		$('#connectivityHistoryTable').bootstrapTable("destroy");
		$('#loadingTabData').show();
		$('#connectivityHistoryDiv').hide();
		data = {        
			aircraftId: $('#aircraftIdTable').val(),
			startDateTime: $("#startDateTimePicker").val(), 
			endDateTime: $("#endDateTimePicker").val(),
			firstTime:false
		};
		firstTime=false;
		var aircraftId= $('#aircraftIdTable').val();
		var tailsignfortable= $('#tailsigntable').val();
		var airlineIdfortable= $('#airlinetable').val();
		var startDate = $("#startDateTimePicker").val();
		var endDate = $("#endDateTimePicker").val();
	    //call same URL with start and end date as parameters in addition to aircraftId			
		//window.location.href = "ConnectivityUpload.php?aircraftId="+aircraftId+"&startDate="+startDate+"&endDate="+endDate+"&firstTime="+firstTime+"&tailsign="+tailsignfortable+"&airlineId="+airlineIdfortable;
		$.ajax({
            type: "GET",
            url: "../ajax/getConnectivityHistory.php",
            data: {                
                'tailsigns': tailsignfortable,
                'airlines': airlineIdfortable,
                'startDate':startDate,
                'endDate':endDate
            },
            success: function(data) {            	
                var jsonData=JSON.parse(data);
            	$('#connectivityHistoryTable').bootstrapTable({
					data: jsonData,
					exportOptions: {
						fileName: 'ConnectivityHistory'
					}
				});
            	$('#loadingTabData').hide();
            	$('#connectivityHistoryDiv').show();
               
            },
            error: function(err) {
                console.log('Error', err);
                $('#loadingTabData').hide();
            }
        });
	}
	
	$("#reset").click(function(event){	
		window.location.href = "ConnectivityUpload.php";
	});

	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}

	</script>
</html>