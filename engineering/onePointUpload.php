<!DOCTYPE html>
<?php
// session_start();
session_start();

$menu = 'onePointUpload';
/* include ("../common/sessionExpired.php"); */
include ("../common/getAircraftCodes.php");
require_once ("../common/validateUser.php");
$approvedRoles = [$roles["all"]
];
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

<style>
    body{
        background:#ffffff;
    }
    
    .loadFrame html>body{ background: #ffffff }
</style>
</head>

<body ng-controller="uploadController">
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
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<!-- <div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div class="col-md-1"
									style="padding-top: 7px; padding-right: 0px; padding-left: 0px; width: 43px;">
									<b> Airline </b>
								</div>
								<input type="hidden" id="airlineIds" ng-model="airlineIds"
									ng-init="airlineIds=<?php echo "'".$airlineIds . "'" ?>"
									value="<?php echo $airlineIds ?>" />
								<div class="col-md-3" style="padding-top: 2px;">
									<div>
										<select id="airline" class="form-control select"
											data-live-search="true" value="selectedairline"
											onchange="changeAirline()">
										</select>
									</div>
								</div>
								<div class="col-md-3" id="errorSpan"
									style="width: 20%; background-color: #E04B4A; color: #FFF; border-color: #af4342; padding: 5px; margin-top: 3px; margin-left: 11px; border-radius: 4px;">Please
									Select the Airline</div>
							</div>
						</div>
					</div> -->
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div>
								<div class="panel-body">
									<h4>
										<b>BITE FILE</b>
									</h4>
									<u>Rules for uploading BITE Files:</u> <br> <br>
									<ul>
										<li>Only <b>ONE FILE</b> can be uploaded at a time. To upload
											another file you have to reload the page.
										</li>
										<li><b>Multiple xml and tgz files can be zipped to one .zip
												file and uploaded.</b></li>
										<li>Individual tgz or xml files are also accepted.</li>
										<li>Files of size up to 75MB can be uploaded.</li>
									</ul>
									<div align="center">
										<div class="block push-up-10" id="biteDropzoneDiv">
											<form id="biteDropzone" action="UploadDropzone.php"
												method="post" class="dropzone dropzone-mini">
												<input type="hidden" id="airlineId" name="airlineId"> <input
													type="hidden" id="acronym" name="acronym">
												<div class="dz-message">Drop BITE files here or click to
													upload.</div>
											</form>
										</div>
<!-- 										<div class="errorBlock"></div> -->
<!--         								<div id="legend" class="legend"></div><br/><br/> -->
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="errorDiv" class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div align="center">
								<div class="panel-body">
									<div class="errorBlock"></div>
									<div id="legend" class="legend"></div>
									<div id="loadDiv"
										style="float: left; border: 0px; width: 100%; height: 500px; display: none;">
										<div class="text-left" id="processStatus"></div>
										<br />
										<iframe id="loadarea" class="loadFrame" name="loadarea"
											style="border: 0px; width: 100%; height: 93%;background: #FFFFFF;" ></iframe>
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
	
		<!-- START PRELOADS -->
		<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
		<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>
		<!-- END PRELOADS -->
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
                        app.controller('uploadController', function($scope, $http) {
                            $scope.loadAirlines = function() {
                                $http.get("../common/AirlineDAO.php", {
                                    params: {
                                        action: "GET_AIRLINES_BY_IDS",
                                        airlineIds: $("#airlineIds").val()
                                    }
                                }).success(function(data, status) {
                                    $("#airline").append('<option value="">Select</option>');
                                    var airlineList = JSON.parse(JSON.stringify(data));
                                    for (var i = 0; i < airlineList.length; i++) {
                                        var al = airlineList[i];
                                        $("#airline").append('<option value=' + al.id + '>' + al.name + '</option>');
                                    }
                                    $('#airline').selectpicker('refresh');

                                });
                            };
                            $scope.loadAirlines();
                        });

                        $(document).ready(function() {
                            $('#airline').selectpicker({
                                size: 6
                            });
							$('#errorSpan').hide();
							$('#errorDiv').hide();
                            $('#biteDropzone').click(function(e) {
                                //console.log('inside dropzone click');
                                e.preventDefault();
                                e.stopPropagation();

                                if ($('#airline').val() == '') {
                                    $('#errorSpan').show();
                                    
                                }else{
                                	$('#errorSpan').hide();
                                	//$('#biteDropzone').options.clickable=true;
                                	$('#biteDropzone').dropzone({
                                		clickable: true});
                                }
                                return false;
                            });

                            var x = document.getElementById("loadarea");
                            var y = (x.contentWindow || x.contentDocument);
                            if (y.document)y = y.document;
                            y.body.style.backgroundColor = "white";
                            $('iframe').contents().find('body').css('background', '#FFF');

                        });

                    /*    function changeAirline() {
                            id = $('#airline').val();
                            console.log('airline id' + id);
                            if ($('#airline').val() == '') {
                                $('#errorSpan').show();
                            }else{
                            	$('#errorSpan').hide();
                            }
                            $.ajax({
                                type: "GET",
                                url: "../ajax/getBiteData.php",
                                data: {
                                    'action': 'getAirlineAcroynm',
                                    'airlineId': id
                                },
                                success: function(data) {
                                    airlineAcronym = JSON.parse(data);
                                    console.log('airlineacroynm' + airlineAcronym);
                                    $("#airlineId").val(id);
                                    $("#acronym").val(airlineAcronym);
                                    if ($('#airline').val() == '') {
	                                    $('#errorSpan').show();
	                                    
	                                }else{
	                                	$('#errorSpan').hide();
	                                	$('#biteDropzone').dropzone({
	                                		clickable: true});
	                                }
                                },
                                error: function(err) {
                                    console.log('Error', err);
                                }
                            });
                        }*/
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
                      						$('.legend').html("<img id=\"loading_spinner\" src=\"../img/loading.gif\" width=\"75px\" height=\"75px\"><br><h2>Files are being processed. Please wait...</h2>");
                      					}
                      					
                      					function successCallback() {
                      						//BeR 7Nov17: we should not need the successCallBack state. Basic message just in case to know where it came from
                      						console.log("entered sucessCallback");
                      						//$('.legend').html("<br><h2>successCallBack</h2>");
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
                      						

                      						//BeR 7Nov17: display errors if any during processing. Maybe to consider if needed implememting like complete function to get feedback from processBiteFiles.php
                      						//message = 'Error during processing BITE files';
                      						//icon = 'ko_big.png';
                      						//$('.legend').html("<img src=\"../img/" + icon + "\" width=\"150px\" height=\"150px\"><br><h2>" + message + "</h2><br><br>");
                      						//$('.legend').html("<br><h2>errorCallBack</h2>");
                      					}
                      										
                      					//BeR 17Nov17: ajax call to ProcessOffloads.php
//                       					$.ajax({						
//                       						//type: "POST",
//                       						url:"ProcessOffloads.php",
//                       						beforeSend:beforeSendCallback,
//                       						success:successCallback,
//                       						complete:completeCallback,
//                       						error:errorCallback
//                       					});
//                       					$('.legend').html("");
										$('#errorDiv').show();
                      					$('#loadDiv').show();
                      					$('#loadDiv').focus();
                      					document.getElementById('loadarea').src="ProcessOffloads.php";
//                       					window.open("ProcessOffloads.php"); 
                      				}				
//                       		      	$('.legend').html("<img src=\"../img/" + icon + "\" width=\"150px\" height=\"150px\"><br><h2>" + message + "</h2><br><div align=\"left\">" + responseText + "</div>");
                      		      	//$('.legend').html("<div align=\"left\">" + responseText + "</div>");
                                  	//$('#loading_spinner').hide();
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

                      			    $('.legend').html("<img id=\"loading_spinner\" src=\"../img/loading.gif\" width=\"75px\" height=\"75px\"><br><h2>Files are being processed. Please wait...</h2>");
                      			  // }
                      			 });
                      		  }
                      		};
                    </script>
		<!-- END SCRIPTS -->

</body>

</html>
