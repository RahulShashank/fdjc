app.controller('downloadOffloadsController',
		function($scope, $http, $log, $window, $timeout, $parse) {
					var firstTime = true;
					$log.log("inside downloadOffloadsController");
					$('#downloadOffload').hide();
					//var startDate = $window.startDateTime;
					//var endDate = $window.endDateTime;
					var startDate = new Date();
					var endDate = new Date();
					startDate.setDate(startDate.getDate() - 6);
					
					$scope.filteredItems = 0;
				
					
					var downloadOffloadVisited=$window.downloadOffloadVisited;
					console.log('downloadOffloadVisited ...'+downloadOffloadVisited);
					
	$('#startDate').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: startDate
	});
	
	$('#endDate').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: endDate
	});
	
	$scope.loadAirlines = function() {
		$http.get("../common/AirlineDAO.php", {
	        params: {
	        	action: "GET_AIRLINES_BY_IDS",
	            airlineIds: $("#airlineIds").val()
	        }
	    }).success(function (data,status) {
			$("#airline").append('<option value="">All</option>');
         	var airlineList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < airlineList.length; i++) {
				var al = airlineList[i];
				$("#airline").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
			}
			$('#airline').selectpicker('refresh');
			if(downloadOffloadVisited){
				$('#airline').val($window.session_AirlineId);
				$('#airline').selectpicker('refresh');
			}
			$('#airline').selectpicker('refresh');				
	        $scope.loadPlatforms();
	    });
	};
	
	$scope.loadAirlines();
	
	$scope.downloadFile = function(id) {
		window.location = "../ajax/downloadOffloadFile.php?id="
				+ id;
	};

				
	$scope.filterOffloads = function() {
		$('#loadingTabData').show();
		$('#downloadOffload').hide();
		$('#offloadTabledata').bootstrapTable('destroy');
		var status = $('#status').val();
		var tailsign = $('#tailsign').val();
		var depAirport = $('#depAirport').val();
		var arrAirport = $('#arrAirport').val();
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();
		var dateOption = $('#dateFilter').val();
		var source = $('#source').val();
		var failureReason = $('#failureReason').val();

		var uploadStartDate = "";
		var uploadEndDate = "";
		var offloadStartDate = "";
		var offloadEndDate = "";
		var depStartDate = "";
		var depEndDate = "";
		var arrStartDate = "";
		var arrEndDate = "";

		if (dateOption == "UploadDate") {
			uploadStartDate = startDate;
			uploadEndDate = endDate;
		} else if (dateOption == "OffloadDate") {
			offloadStartDate = startDate;
			offloadEndDate = endDate;
		} else if (dateOption == "DepartureTime") {
			depStartDate = startDate;
			depEndDate = endDate;
		} else if (dateOption == "ArrivalTime") {
			arrStartDate = startDate;
			arrEndDate = endDate;
		}

		$scope.getOffloads('getOffloads', status, tailsign,
				depAirport, arrAirport, depStartDate,
				depEndDate, arrStartDate, arrEndDate,
				offloadStartDate, offloadEndDate,
				uploadStartDate, uploadEndDate, source,
				failureReason);
	};
	
	$scope.getOffloads = function(action, status, tailsign,depAirport, arrAirport, depStartDate, depEndDate, 
			arrStartDate, arrEndDate, offloadStartDate, offloadEndDate, uploadStartDate, uploadEndDate, source, failureReason) {
		console.log('Start Date : '+uploadStartDate);
		console.log('End Date : '+uploadEndDate);
		
		var alId;
		if($('#airline').val().length > 0) {
			airlineId = $("#airline").val();
		} else {
			airlineId = $("#airlineIds").val();
		}

		var selectedPlatform = $('#selectedPlatform').val();
		var selectedConfigType = $('#selectedConfigType').val();
		
		var data = $.param({
			'airlineId' : airlineId,
			'action' : 'getOffloads',
			'status' : status,
			'platform' : selectedPlatform,
			'configType' : selectedConfigType,
			'tailsign' : tailsign,
			'depAirport' : depAirport,
			'arrAirport' : arrAirport,
			'depStartDate' : depStartDate,
			'depEndDate' : depEndDate,
			'arrStartDate' : arrStartDate,
			'arrEndDate' : arrEndDate,
			'offloadStartDate' : offloadStartDate,
			'offloadEndDate' : offloadEndDate,
			'uploadStartDate' : uploadStartDate,
			'uploadEndDate' : uploadEndDate,
			'source' : source,
			'failureReason' : failureReason,
			'config' : $('#selectedConfigType').val(),
			'platform' : $('#selectedPlatform').val(),
			'dateFilter' : $('#dateFilter').val()

		});
		var config = {
			headers : {
				'Content-Type' : 'application/x-www-form-urlencoded;charset=utf-8;'
			}
		};

		$http.post('getDownloadOffloadData.php', data, config)
		.success(function(data, status, headers, config) {
			createDownloadOffloadTable();
			$('#offloadTabledata').bootstrapTable('load', {data : data});
			$('#loading').hide();
			
			$('#loadingTabData').hide();
			$('#downloadOffload').show();
		});
	};

	function createDownloadOffloadTable() {
		$('#offloadTabledata').bootstrapTable({
			formatNoMatches : function() {
				return 'No data available for the selected duration or selected filters';
			}
		});
	}

	$scope.loadPlatforms = function() {console.log('inside load platform');
		var selairlineId;
		
		if($('#airline').val().length > 0) {
			selairlineId = $('#airline').val();
		} else {
			selairlineId = $("#airlineIds").val();
		}

		var data = $.param({
			airlineId : selairlineId,
			action : 'GET_PLATFORMS'
		});
		
		var config = {
			headers : {
				'Content-Type' : 'application/x-www-form-urlencoded;charset=utf-8;'
			}
		};

		$http.post('getDownloadOffloadData.php', data,config)
		.success(function(data, status, headers, config) {
			$('#selectedPlatform').empty();

			var platformList = JSON.parse(JSON.stringify(data));
			$("#selectedPlatform").append('<option value="">All</option>');
			for ( var i = 0; i < platformList.length; i++) {
				var al = platformList[i];
				$("#selectedPlatform").append('<option value=' + al.platform + '>' + al.platform + '</option>');
			}
			$('#selectedPlatform').selectpicker('refresh');
			$scope.selectedPlatform = platformList[0];
											if(downloadOffloadVisited){
												$('#selectedPlatform').val($window.session_Platform);
												$('#selectedPlatform').selectpicker('refresh');
											}
			$scope.loadConfigTypes(selairlineId,$scope.selectedPlatform);
		}).error(function(data, status, header, config) {});
	};
	
	$scope.loadConfigTypes = function() {
		var selairlineId;
						
		if($('#airline').val().length > 0) {
			selairlineId = $('#airline').val();
		} else {
			selairlineId = $("#airlineIds").val();
		}
						
		var selPlatform = $("#selectedPlatform").val();
		var selectedPlatform = selPlatform;
		var airlineId = selairlineId;
		$('#selectedConfigType').empty();
		var data = $.param({
			airlineId : airlineId,
			platform : selectedPlatform,
			action : 'GET_CONFIG_TYPE'
		});

		var config = {
			headers : {
				'Content-Type' : 'application/x-www-form-urlencoded;charset=utf-8;'
			}
		};
		
		$http.post('getDownloadOffloadData.php', data,config)
		.success(function(data, status, headers, config) {
			var configList = JSON.parse(JSON.stringify(data));
			$("#selectedConfigType").append('<option value="">All</option>');
			for ( var i = 0; i < configList.length; i++) {
				var al = configList[i];
				var selOption = al.configType;
				$("#selectedConfigType").append("<option value='"+ selOption+ "'>"+ selOption+ "</option>");
			}
			$('#selectedConfigType').selectpicker('refresh');
			$scope.selectedConfigType = configList[0];
			var cc = configList[0];
			if(downloadOffloadVisited){
				$('#selectedConfigType').val($window.session_Config);
				$('#selectedConfigType').selectpicker('refresh');
			}
			$scope.loadTailsign(airlineId,selectedPlatform, cc);
		}).error(function(data, status, header, config) {
		});
	};

	$scope.changeAirline = function() {
		id = $('#airline').val();
		
		getTailSignList(id);
		getDepArrAirportList(id);
	};

	$scope.loadTailsign = function() {
		var airlineId;
		if($('#airline').val().length > 0) {
			airlineId = $('#airline').val();
		} else {
			airlineId = $("#airlineIds").val();
		}
		
		var selectedPlatform = $('#selectedPlatform').val();
		var selectedConfigType = $('#selectedConfigType').val();
		$('#tailsign').empty();

		var data = $.param({
			airlineId : airlineId,
			platform : selectedPlatform,
			configType : selectedConfigType,
			action : 'GET_TS_FOR_AIRLINE_PLTFRM_CNFG_SW'
		});

		var config = {
			headers : {
				'Content-Type' : 'application/x-www-form-urlencoded;charset=utf-8;'
			}
		};
		$("#tailsign").append('<option value="">All</option>');

		$http.post('../common/AirlineDAO.php', data, config)
		.success(function(data, status, headers, config) {
			var tailsignList = JSON.parse(JSON.stringify(data));
			for ( var i = 0; i < tailsignList.length; i++) {
				var ts = tailsignList[i];
				$("#tailsign").append('<option value='+ ts.tailsign+ '>'+ ts.tailsign+ '</option>');
			}
			$('#tailsign').selectpicker('refresh');
			if(downloadOffloadVisited){
				$('#tailsign').val($window.session_tailsign);
				$('#tailsign').selectpicker('refresh');
				$('#startDate').val($window.session_startDate);
				$('#endDate').val($window.session_endDate);
			}			
			$scope.getDepArrAirportList();
		});
	};

					$scope.getDepArrAirportList = function() {
						var tailsign = $('#tailsign').val();
						var selectedPlatform = $('#selectedPlatform').val();
						var selectedConfigType = $('#selectedConfigType').val();
						var dateOption = $('#dateFilter').val();
						var startDate = $("#startDate").val();
						var endDate = $("#endDate").val();
						
						console.log('Tailsign: ' + tailsign);
						var airlineId;
						if($('#airline').val().length > 0) {
							airlineId = $('#airline').val();
						} else {
							airlineId = $("#airlineIds").val();
						}

						var uploadStartDate = "";
						var uploadEndDate = "";
						var offloadStartDate = "";
						var offloadEndDate = "";
						var depStartDate = "";
						var depEndDate = "";
						var arrStartDate = "";
						var arrEndDate = "";

						if (dateOption == "UploadDate") {
							uploadStartDate = startDate;
							uploadEndDate = endDate;
						} else if (dateOption == "OffloadDate") {
							offloadStartDate = startDate;
							offloadEndDate = endDate;
						} else if (dateOption == "DepartureTime") {
							depStartDate = startDate;
							depEndDate = endDate;
						} else if (dateOption == "ArrivalTime") {
							arrStartDate = startDate;
							arrEndDate = endDate;
						}
						
						var data = $.param({
							airlineId : airlineId,
							platform : selectedPlatform,
							configType : selectedConfigType,
							action : 'GET_TS_FOR_AIRLINE_PLTFRM_CNFG_SW'
						});

						var data = $.param({
							'airlineId' : airlineId,
							'platform' : selectedPlatform,
							'configType' : selectedConfigType,
							'tailsign' : tailsign,
							'depStartDate' : depStartDate,
							'depEndDate' : depEndDate,
							'arrStartDate' : arrStartDate,
							'arrEndDate' : arrEndDate,
							'offloadStartDate' : offloadStartDate,
							'offloadEndDate' : offloadEndDate,
							'uploadStartDate' : uploadStartDate,
							'uploadEndDate' : uploadEndDate,
							'action' : 'getDepArrAirportList'
						});
						var config = {
							headers : {
								'Content-Type' : 'application/x-www-form-urlencoded;charset=utf-8;'
							}
						};

						$http.post('../engineering/getDownloadOffloadData.php',	data, config)
							.success(function(data, status, headers, config) {
											$('#arrAirport').empty();
											$('#depAirport').empty();
											$("#depAirport").append('<option value="">Select</option>');
											$("#arrAirport").append('<option value="">Select</option>');
											data = JSON.parse(JSON.stringify(data));
											if (data[0].depAirportList != null) {

												var depAirportList = data[0].depAirportList.split(",");
												depAirportList = depAirportList.filter(Boolean);
												depAirportList.sort();
												for ( var i = 0; i < depAirportList.length; i++) {
													var ts = depAirportList[i];
													$("#depAirport").append('<option value='+ ts+ '>'+ ts+ '</option>');
												}

												$('#depAirport').selectpicker('refresh');
											}
											if (data[0].arrAirportList != null) {
												// $('#arrAirport').empty();
												var arrAirportList = data[0].arrAirportList.split(",");
												arrAirportList = arrAirportList.filter(Boolean);
												arrAirportList.sort();
												for ( var i = 0; i < arrAirportList.length; i++) {
													var ts = arrAirportList[i];
													$("#arrAirport").append('<option value='+ ts+ '>'+ ts+ '</option>');
												}
												$('#arrAirport').selectpicker('refresh');
											}
											if(downloadOffloadVisited){
												$('#depAirport').val($window.session_depAirport);
												$('#depAirport').selectpicker('refresh');
												$('#arrAirport').val($window.session_arrAirport);
												$('#arrAirport').selectpicker('refresh');
												$('#source').val($window.session_source);
												$('#source').selectpicker('refresh');
												$('#failureReason').val($window.session_failureReason);
												$('#failureReason').selectpicker('refresh');
												$('#dateFilter').val($window.session_dateFilter);
												$('#dateFilter').selectpicker('refresh');
												$('#status').val($window.session_status);
												$('#status').selectpicker('refresh');
												
												$scope.filterOffloads();
											}else{
												if (firstTime) {
													$scope.filterOffloads();
													firstTime = false;
												}
											}
											
											$('#depAirport').selectpicker('refresh');
											$('#arrAirport').selectpicker('refresh');
											
										});						

					}

				$scope.loadrAirlines = function() {
					$('#rairlines').empty();
					$('#rtailsign').empty();
					$('#rairlines').selectpicker('refresh');
					$('#rtailsign').selectpicker('refresh');
					$http.get("../common/AirlineDAO.php", {
				        params: {
				        	action: "GET_AIRLINES_BY_IDS",
				            airlineIds: $("#airlineIds").val()
				        }
				    }).success(function (data,status) {
						$("#rairlines").append('<option value="">All</option>');
						var airlineList = JSON.parse(JSON.stringify(data));
						for ( var i = 0; i < airlineList.length; i++) {
							var al = airlineList[i];
							$("#rairlines").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
						}
			
						$('#rairlines').selectpicker('refresh');
						// $scope.loadRTailsign();
					}).error(function(data, status, header, config) {
					});
				};
					
					$scope.loadrAirlines();
					$scope.loadRTailsign = function() {
						$('#errorMsg').hide();
						var airlineId = $('#rairlines').val();
						var selectedPlatform = $('#selectedPlatform').val();
						var selectedConfigType = $('#selectedConfigType').val();
						$('#rtailsign').empty();

						var data = $.param({
							airlineId : airlineId,
							platform : selectedPlatform,
							configType : selectedConfigType,
							action : 'GET_TS_FOR_AIRLINE_PLTFRM_CNFG_SW'
						});

						var config = {
							headers : {
								'Content-Type' : 'application/x-www-form-urlencoded;charset=utf-8;'
							}
						};
						$("#rtailsign").append('<option value="">All</option>');

						$http.post('../common/AirlineDAO.php', data, config)
								.success(function(data, status, headers, config) {
											var tailsignList = JSON.parse(JSON.stringify(data));
											for ( var i = 0; i < tailsignList.length; i++) {
												var ts = tailsignList[i];
												$("#rtailsign").append('<option value='+ ts.tailsign+ '>'+ ts.tailsign+ '</option>');
											}
											$('#rtailsign').selectpicker('refresh');
											$scope.getDepArrAirportList();
										});
					};
					// $scope.loadRTailsign();
					$scope.resetOffloads = function() {
						
						if(downloadOffloadVisited){
							var url = "downloadOffload.php?downloadOffloadVisited=false";
							var win = window.open(url, '_self');
							win.focus();
						}else{
							downloadOffloadVisited=false;
							$scope.loadAirlines();
							document.getElementById("startDate").value = $window.startDateTime;
							document.getElementById("endDate").value = $window.endDateTime;
							$("#dateFilter").val("UploadDate");
							$("#status").val("");
							$("#source").val("");
							$("#airline").val("");
							$('#airline').selectpicker('refresh');
							$('#dateFilter').selectpicker('refresh');
							$('#status').selectpicker('refresh');
							$('#source').selectpicker('refresh');
						}
						
					};
					
					var $tablemodal = $('#tablemodal'); 
		            var offloadId = '';
		            var fileName = '';
		            var source = '';
		            
		            $("#reuploadfilter").click(function(event) {
		            	$('#errorMsg').hide();
		            	$('#rloadingTabData').show();
		            	var rAirline = $('#rairlines').val();
						var rTailsign = $('#rtailsign').val();

						if(rAirline!=''){
		            		$.ajax({
		                                type: "GET",
		                                url: "../ajax/ReuploadFile.php",
		                                data: {                                    
		                                    'airlineId': rAirline,
		                                    'tailsign': rTailsign,
		                                    'offloadId': offloadId,
		                                    'fileName': fileName,
		                                    'source': 'REUPLOAD'
		                                },
		                                success: function(data) {          
		                                    $('#rloadingTabData').hide();
		                                    $('#rSccessData').show();
		                                    var element =document.getElementById('reuploadfilter'); 
		                                    element.disabled=true;                              	
		                                    $scope.filterOffloads();
		                                    
		                                },
		                                error: function(err) {
		                                    console.log('Error', err);
		                                }
		                            });
	                    }else{
	                    	 $('#rloadingTabData').hide();
								//alert('Select the Airline');
	                    	 	$('#errorMsg').show();
	                        }
			        });	            

		            window.operateEvents = {
		            	'click .info' : function(value, row, index) {
		            		
		            		offloadId = index.id;
		            		fileName = index.fileName;
		            		source = 'reupload';	            		
		            		document.getElementById("rairlines").value = "";	            		
		            		$('#rairlines').selectpicker('refresh');
		            		$('#rtailsign').empty();
		            		$('#rtailsign').selectpicker('refresh');
		            		$('#fileName').val(fileName);
		            		$("#fileNameLabel").text(fileName);
		            		$('#errorMsg').hide();
		            		$('#rSccessData').hide();
	                        var element =document.getElementById('reuploadfilter'); 
	                        element.disabled=false;  
	                        
		            		$('#modalTable').modal('show');
		            	}
		            };
		            
				});
