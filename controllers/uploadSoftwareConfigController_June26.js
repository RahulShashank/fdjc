angular.module('myApp', ['ngAnimate']);
angular.module('myApp').controller('uploadSoftwareConfigController', function($scope, $http,$anchorScroll,$window) {
	$scope.divAnimated = false;
	$scope.airlineAcronymList = [];
	$scope.fileUploadHistory = [];
	$scope.customer = "";
	$scope.type = "";
	$scope.platform = "";
	$scope.version = "";
	$scope.baseline = "";
	var configData = null;
	
	$(document).ready(function(){
		var xlf = document.getElementById('xlf');
		function handleFile(e) { do_file(e.target.files); }
		xlf.addEventListener('change', handleFile, false);
		initializeDropzone();
		initializeDropdowns();
//		alert('in the document ready');
		initializeUploadHistoryTable();
		$anchorScroll();
		$window.scrollTo(0,0);
	});
	
	var X = XLSX;
	var global_wb;
	var json_parse;

	function process_wb_OUT(wb) {
		global_wb = wb;
		json_parse = to_json(wb);
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

		function handleDrop(e) {
			handleDragleave();
			e.stopPropagation();
			e.preventDefault();
			do_file(e.dataTransfer.files);
		}

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
		drop.addEventListener('drop', handleDrop, false);
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
				$anchorScroll();
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
		var filename = $('#xlf').val().split('\\');
		dataToPost['filename'] = filename[filename.length-1];
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
		console.log(dataToPost);
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
			console.log('Filename : '+dataToPost.filename);
			$.ajax({
			type: "POST",
			dataType: "json",
			data: dataToPost,
			url: "../ajax/saveSWConfigData.php",
			success: function(data) {
				if(data.status=="success"){
					noty({text: 'Configuration saved Successfully', layout: 'topRight', type: 'success', timeout: 5000});
					//alert('after save');
					initializeUploadHistoryTable();
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
});
