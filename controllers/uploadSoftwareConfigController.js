angular.module('myApp', ['ngAnimate']);
angular.module('myApp').controller('uploadSoftwareConfigController', function($scope, $http,$window) {
	$scope.divAnimated = false;
	$scope.airlineAcronymList = [];
	$scope.fileUploadHistory = [];
	$scope.customer = "";
	$scope.type = "";
	$scope.platform = "";
	$scope.version = "";
	$scope.baseline = "";
	$scope.filename = "";
	var configData = null;
	var firstTime=true;
	$('#loadingDiv').hide();
	$(document).ready(function(){
		var xlf = document.getElementById('xlf');
		function handleFile(e) { do_file(e.target.files); }
		xlf.addEventListener('change', handleFile, false);
		initializeDropzone();
		initializeDropdowns();
//		alert('in the document ready');
		//initializeUploadHistoryTable();
//		$anchorScroll();
//		$window.scrollTo(0,0);
		
		setTimeout(function(){$(window).scrollTop(0);},600);
	});
	
	var X = XLSX;
	var global_wb;
	var json_parse;

	function process_wb_OUT(wb) {
		global_wb = wb;
		json_parse = to_json(wb);
//		console.log("JSON Data: " + JSON.stringify(json_parse));
		if(isFormatValid(json_parse)){
			var customer = json_parse['SPNL Template'][2][2];
			if(customer in configData){
				$scope.customer = json_parse['SPNL Template'][2][2];
				$scope.populateDropdowns('customer');
				$scope.type = json_parse['SPNL Template'][3][2];
				$scope.populateDropdowns('type');
				$scope.platform = json_parse['SPNL Template'][4][2];
				$scope.version = json_parse['SPNL Template'][8][2];
				$scope.baseline = json_parse['SPNL Template'][5][2];
				$scope.$apply();
				animateDiv();
			}else{
				noty({text: 'Customer not found.', layout: 'topRight', type: 'error', timeout: 5000});
			}
		}else{
			noty({text: 'Invalid file uploaded, the format does not match.', layout: 'topRight', type: 'error', timeout: 5000});
		}
	};

	function do_file(files) {
		if(files!==null && files.length>0){
			var f = files[0];
			$scope.filename = f.name;
//			console.log(f.name);
			var reader = new FileReader();
			reader.onload = function(e) {
				var data = e.target.result;
				process_wb_OUT(X.read(data, {type: 'binary'}));
			};
			reader.readAsBinaryString(f);
		}
	}
	
	function animateDiv(){
		if(!$scope.divAnimated){			
			$('#dropParentDiv').removeClass('width100');
			$('#dropParentDiv').addClass('col-md-6');
			$('#dropParentDiv').addClass('transitionTime');
			$('#configInputDiv').addClass('transitionTime');
			setTimeout(function(){$('#configInputDiv').show();},600);
			$scope.divAnimated = true;
			$('#fileInputDiv').show();
		}else{			
			$('#fileInputDiv').hide();
			$('#dropParentDiv').addClass('width100');
			$('#dropParentDiv').removeClass('col-md-6');
			$('#dropParentDiv').removeClass('transitionTime');
			$('#configInputDiv').removeClass('transitionTime');
			setTimeout(function(){$('#configInputDiv').show();},600);
			$scope.divAnimated = false;
		}
	}
	
	function to_json(workbook) {
		var result = {};
		workbook.SheetNames.forEach(function(sheetName) {
			var roa = X.utils.sheet_to_json(workbook.Sheets[sheetName], {header:1});
			if(roa.length) result[sheetName] = roa;
		});
		return result;
	}

	function initializeDropzone(){
		var drop = document.getElementById('drop');
		if(!drop.addEventListener) return;

//		function handleDrop(e) {console.log("drop : " + JSON.stringify(e));
//			handleDragleave();
//			e.stopPropagation();
//			e.preventDefault();
//			do_file(e.dataTransfer.files);
//		}

		function handleDragover(e) {
			$('#drop').css({background:"#def0f9", "border-style": "solid"});
			e.stopPropagation();
			e.preventDefault();
			e.dataTransfer.dropEffect = 'copy';
		}

		function handleDragleave(e){
			$('#drop').css({background:"none", "border-style": "dashed"});
		}

		drop.addEventListener('dragleave', handleDragleave, false);
		drop.addEventListener('dragenter', handleDragover, false);
		drop.addEventListener('dragover', handleDragover, false);
//		drop.addEventListener('drop', handleDrop, false);
		$('#drop').on('drop', function(e) {
			if(e.originalEvent.dataTransfer){
	            if(e.originalEvent.dataTransfer.files.length) {
	            	handleDragleave();
	                e.preventDefault();
	                e.stopPropagation();
	    			do_file(e.originalEvent.dataTransfer.files);
	            }   
	        }
		});
		
		drop.onclick = function(){
			document.getElementById('xlf').click();
		};
	}

	function initializeDropdowns(){
		$.ajax({
			type: "GET",
			dataType: "json",
			url: "../ajax/getLookupCustomData.php?filter=airline_type_platform",
			success: function(data) {
				configData = data;
				$scope.airlineAcronymList = Object.keys(data);
				$scope.$apply();
			},
			error: function (err) {
				console.log("Error Received while populating config dropdowns: ",err);
			}
		});
	}
	
	function initializeUploadHistoryTable(){//alert('1');
		$('#fileUploadHistoryTable').bootstrapTable('destroy');
		$.ajax({
			type: "GET",
			dataType: "json",
			url: "../ajax/getLookupCustomData.php?filter=lookup_history_data",
			success: function(data) {
				$scope.fileUploadHistory = data;
				createfileUploadHistoryTable();
				$('#fileUploadHistoryTable')
				.bootstrapTable('load', {
					data : data
				});//alert('2');
//				$scope.$apply();
//				$window.scrollTo(0,0);
//				$anchorScroll();
			},
			error: function (err) {
				console.log("Error encountered while fetching Lookup History data: ",err);
			}
		});
	}
	
	function createfileUploadHistoryTable() {
		$('#fileUploadHistoryTable').bootstrapTable({
				formatNoMatches : function() {
				return 'No data available';
			}
		});
	}
	
	$scope.populateDropdowns = function(eventParent){
		if(eventParent=='customer'){
			$scope.aircraftType = Object.keys(configData[$scope.customer]);
		}else if(eventParent=='type'){
			$scope.aircraftPlatform = configData[$scope.customer][$scope.type]
		}
	};
	
	function isFormatValid(json_data){
		if(json_data.hasOwnProperty('SPNL Template')){
			var data = json_data['SPNL Template'];
			for(var i=0;i<9;i++){
				if(data[i].length<3 || data[i].length>7)
					return false;
			}
			if(data[9].length<5)
				return false;
			for(var i=10;i<data.length;i++){
				if(data[i].length!==9 && data[i].length!==0)
					return false;
			}
			return true;
		}else{
			return false;
		}
	}
	
	function prepareDataToPost(data){
		//console.log(data);
		var data = data['SPNL Template'];
		var dataToPost = {};
		dataToPost['project'] = data[0][2];
		dataToPost['releaseTag'] = data[1][2];
		dataToPost['customer'] = $scope.customer;
		dataToPost['aircraftType'] = $scope.type;
		dataToPost['platform'] = $scope.platform;
		dataToPost['version'] = $scope.version;
		dataToPost['baseline'] = $scope.baseline;
		dataToPost['baselineMediaPN'] = data[6][2];
		dataToPost['currentReleaseMediaPN'] = data[7][2];
		if($scope.filename) {
			dataToPost['filename'] = $scope.filename;
		} else {
			var filename = $('#xlf').val().split('\\');
			dataToPost['filename'] = filename[filename.length-1];
		}
		dataToPost['LRU_LIST'] = [];
		for(var i=11;i<data.length;i++){
			if(data[i].length!==9)
				break;
			var lru_obj = {
				'lru_name': data[i][0],
				'sw_partnumber': data[i][1],
				'nomencalture': data[i][2],
				'baseline': data[i][3],
				'current_partnumber': data[i][4]
			};
			dataToPost['LRU_LIST'].push(lru_obj);
		}
//		console.log(dataToPost);
		return dataToPost;
	}
	
	
	$("#fileUploadForm").submit(function(event) {
		if(!isFormatValid(json_parse)){
			noty({text: 'Invalid file uploaded, the format does not match.', layout: 'topRight', type: 'error', timeout: 5000});
			event.preventDefault();
		}
		else if($scope.customer=="" || $scope.type=="" || $scope.platform=="" || $scope.version=="" || $scope.baseline=="" || 
			typeof $scope.customer == "undefined" || typeof $scope.type == "undefined" || typeof $scope.platform == "undefined" || typeof $scope.version == "undefined" || typeof $scope.baseline == "undefined"){
			noty({text: 'Fill out all the details', layout: 'topRight', type: 'error', timeout: 5000});
			event.preventDefault();
		}
		else{
			var dataToPost = prepareDataToPost(json_parse);
//			console.log('Datatopost: '+ JSON.stringify(dataToPost));
			$.ajax({
				type: "POST",
				dataType: "json",
				data: dataToPost,
				url: "../ajax/saveSWConfigData.php",
				success: function(data) {
					if(data.status=="success"){
						noty({text: 'Configuration saved Successfully', layout: 'topRight', type: 'success', timeout: 5000});
						//alert('after save');
						//initializeUploadHistoryTable();
						$scope.filter();
						animateDiv();
					}else{
						noty({text: "Failed to save. Configuration already exists.", layout: 'topRight', type: 'error', timeout:5000});
					}
				},
				error: function (err) {
					console.log("Error Received while populating config dropdowns: ",err);
				}
			});
		}
	});
	
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	
	var today = new Date();
	var priorDate = new Date(new Date().setDate(today.getDate()-6));
	var startDate = formatDate(priorDate);
	var endDate = formatDate(today);
	$scope.loading = true;
	$scope.startDate = startDate;
	$scope.endDate = endDate;
	var firstTime=true;

	$('#startDateTimePicker').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: startDate
	});
	
	$('#endDateTimePicker').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: endDate
	});

	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#software').selectpicker();
	$('#tailsign').selectpicker();


	$scope.loadAirlines = function() {
		clearAirlines();
		
		$.ajax({
			type: "GET",
			dataType: "json",
			url: "../common/AirlineDAO.php",
			data: {
	        	action: "GET_AIRLINES_BY_IDS",
	            airlineIds: $("#airlineIds").val()
	        },
			success: function(data) {
				var airlineList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < airlineList.length; i++) {
					var al = airlineList[i];
					$("#airline").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
				}
				//$('#airline').val(airlineList[0].id);
				$('#airline').selectpicker('refresh');
					
		        $scope.loadPlatforms();
			},
			error: function (err) {
				console.log("Error Received in Airline : ",err);
			}
		});
   };

    $scope.loadPlatforms = function() {
    	clearPlatformSelect();
    	clearConfigurationSelect();
    	clearSoftwareSelect();
    	clearTailsignSelect();    	
    	
		var airlineId = "";
		airlineId = getSelectedAirline();
		
		if(airlineId==null){
			if($("#airlineIds").val()!="-1"){
				airlineId = $("#airlineIds").val().split(',');
			}						
		}
	
        $.ajax({
			type: "GET",
			dataType: "json",
			url: "../common/AirlineDAO.php",
			data: {
	        	action: "GET_PLATFORMS_FOR_AIRLINE_ARRAY",
	        	airlineId: airlineId
	        },
			success: function(data) {
				var platformList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < platformList.length; i++) {
					var pf = platformList[i];
					$("#platform").append('<option value=' + pf.platform + '>' + pf.platform + '</option>');
				}
				$('#platform').selectpicker('refresh');
	            
	            $scope.loadConfigTypes();
			},
			error: function (err) {
				console.log("Error Received in Platform : ",err);
			}
		});
    };

    $scope.loadConfigTypes = function() {
    	clearConfigurationSelect();
    	clearSoftwareSelect();
    	clearTailsignSelect();    	
    	
		var airlineId = "";
		var platform = "";

		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
        
		if(airlineId==null){	
			if($("#airlineIds").val()!="-1"){
				airlineId = $("#airlineIds").val().split(',');
			}			
		}
		
        $.ajax({
			type: "GET",
			dataType: "json",
			url: "../common/AirlineDAO.php",
			data: {
	        	action: "GET_CONFIG_FOR_AIRLINE_ARRAY_PLATFORM",
	        	airlineId: airlineId,
	            platform: platform
	        },
			success: function(data) {
				var configTypeList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < configTypeList.length; i++) {
					var config = configTypeList[i];
					$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
				}
				$('#configType').selectpicker('refresh');

	            $scope.loadSoftwares();
			},
			error: function (err) {
				console.log("Error Received in Config Type : ",err);
			}
		});        
    };

    $scope.loadSoftwares = function() {
    	clearSoftwareSelect();
    	clearTailsignSelect();    	

		var airlineId = "";
		var platform = "";
		var configType = "";

		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();
		
		if(airlineId==null){	
			if($("#airlineIds").val()!="-1"){
				airlineId = $("#airlineIds").val().split(',');
			}
		}
		
        $.ajax({
			type: "GET",
			dataType: "json",
			url: "../common/AirlineDAO.php",
			data: {
	        	action: "GET_SOFTWARE_FOR_AIRLINE_ARRAY_PLATFORM_CONFIG",
	        	airlineId: airlineId,
	            platform: platform,
	            configType: configType
	        },
			success: function(data) {
				var softwareList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < softwareList.length; i++) {
					var sw = softwareList[i];
					$("#software").append('<option value="' + sw.software_version+ '">' + sw.software_version + '</option>');
				}
				$("#software").val(softwareList[0]);
				$('#software').selectpicker('refresh');
				if(firstTime) {
					$scope.filter();
					firstTime = false;
				}
	            //$scope.loadTailsign();
			},
			error: function (err) {
				console.log("Error Received in Config Type : ",err);
			}
		}); 

    };
    
    $scope.loadTailsign = function() {
    	clearTailsignSelect();    	

		var airlineId = "";
		var platform = "";
		var configType = "";
		var software = "";
		
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();		
		software = getSelectedSoftwares();
        
        $.ajax({
			type: "GET",
			dataType: "json",
			url: "../common/AirlineDAO.php",
			data: {
	        	action: "GET_TS_FOR_AIRLINEARRAY_PLTFRM_CNFG_SW",
	        	airlineId: airlineId,
	            platform: platform,
	            configType: configType,
	            software:software
	        },
			success: function(data) {
				var tailsignList = JSON.parse(JSON.stringify(data));

				for (var i = 0; i < tailsignList.length; i++) {
					var ts = tailsignList[i];
					$("#tailsign").append('<option value="' + ts.tailsign + '">' + ts.tailsign + '</option>');
				}
				$('#tailsign').val(tailsignList[0]);
				$('#tailsign').selectpicker('refresh');
			},
			error: function (err) {
				console.log("Error Received in Config Type : ",err);
			}
		});        
	};
    
	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}
	$scope.loadAirlines();
	
	function clearAirlines() {
        $('#airline').empty();
        $('#airline').selectpicker('refresh');
	}
	
	function clearPlatformSelect() {
        $('#platform').empty();
        $('#platform').selectpicker('refresh');
	}
	
	function clearConfigurationSelect() {
        $('#configType').empty();
        $('#configType').selectpicker('refresh');
	}
	
	function clearSoftwareSelect() {
        $('#software').empty();
        $('#software').selectpicker('refresh');
	}
	
	function clearTailsignSelect() {
        $('#tailsign').empty();
        $('#tailsign').selectpicker('refresh');
	}
	
	function getSelectedAirline() {		
		return $('#airline').val();
	}
	
	function getSelectedPlatform() {
		return $('#platform').val();
	}
	
	function getSelectedConfigType() {
		return $('#configType').val();
	}
	
	function getSelectedSoftwares() {
		return $('#software').val();
	}
	
	function getSelectedPerdiod() {
		return $('#period').val();
	}
	
	function getSelectedReportBy() {
		return $('#reportBy').val();
	}
	
	$scope.filter = function() {
		$('#loadingDiv').show();
		$('#fileUploadHistoryTable').bootstrapTable('destroy');		
		$.ajax({
			type: "GET",
			dataType: "json",
			data:{
				startDate:$('#startDateTimePicker').val(),
				endDate:$('#endDateTimePicker').val(),
				airline:$('#airline').val(),
				platform:$('#platform').val(),
				config:$('#configType').val(),
				software:$('#software').val(),
				tailsign:$('#tailsign').val(),
				status:$('#status').val()
			},
			url: "../ajax/getLookupCustomData.php?filter=lookup_history_data",
			success: function(data) {
				$scope.fileUploadHistory = data;
				createfileUploadHistoryTable();
				$('#fileUploadHistoryTable')
				.bootstrapTable('load', {
					data : data
				});
				$('#loadingDiv').hide();
			},
			error: function (err) {
				$('#loadingDiv').hide();
				console.log("Error encountered while fetching Lookup History data: ",err);
			}
		});
	}
	
	$scope.resetSearchPanel = function() {
		$('#status').val('');		
		$('#status').selectpicker('refresh');		
		clearAirlines();		
		var today = new Date();
		var priorDate = new Date(new Date().setDate(today.getDate()-6));
		var startDate = formatDate(priorDate);
		var endDate = formatDate(today);
		
		$('#startDateTimePicker').datetimepicker({value: startDate});
		$('#endDateTimePicker').datetimepicker({value: endDate});
		$scope.loadAirlines();
	}
});
