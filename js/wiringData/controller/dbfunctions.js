app.controller("DbFunctions",['$rootScope', 'dbdisplayFactory', '$compile', '$scope', '$timeout', '$controller', 'utilityFactory', '$route', '$http', '$window', function ($rootScope, dbdisplayFactory, $compile, $scope, $timeout, $controller, utilityFactory, $route, $http, $window) {
	console.log('inside DbFunctions controller');
	$scope.aircraftId = $window.aircraftId;
	$scope.fileRepoPath = $window.fileRepoPath;
	$scope.requestURL = $window.requestURL;
	$scope.showFiles = true;
	$rootScope.showLoading = false;
    angular.element("#loadingGraphicalView").hide();

	console.log('aircraftID: ' + $window.aircraftId);
	console.log('File Repo Path: ' + $scope.fileRepoPath);
	
	init();
	
	function init() {
		getFiles();
	}

  function getFiles() {
      var data = $.param({
    	  aircraftId: $scope.aircraftId,
          action: 'GET_FILES'
      });

      var config = {
          headers : {
              'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
          }
      };

      $http.post('../ajax/UploadWiringDataDAO.php', data, config)
	      .success(function (data, status, headers, config) {
	      	$scope.files = data;
	      	if(Object.keys(data).length === 0) {
	      		$scope.showFiles = false;
	      	}
	  	})
	  	.error(function (data, status, header, config) {
	      	console.log('error in fetching files for an aircraft');
	  	});
  }
	
    $scope.tableFeed =[];
    $scope.columnSelector = {};
    $scope.dataWrapper = false;
//	 utilityFactory.scrollBody();
//	var tableWidth = document.getElementById('dbdisplayTable').scrollWidth;
//    $("#dbDisplayTheme #rowCount").parent("table").css("min-width",(tableWidth) + "px");
//    $("#dbDisplayTheme .scrollHead table").css("width", tableWidth + "px");	
    $scope.status = "";
//    var tableControllerInstance = $scope.$new();
//    $controller('Table', {
//        $scope: tableControllerInstance
//    });
	
    //Instantiate instance of Enhanced seat controller
    var enhancedSeatControllerInstance = $scope.$new();    
    $scope.devArr =[];
    $controller('EnhancedSeat', {
        $scope: enhancedSeatControllerInstance
    });
    
    //Instantiate instance of Neighbour controller
    var neighborLinkControllerInstance = $scope.$new();
    $controller('SeatConnection', {
        $scope: neighborLinkControllerInstance
    });
	
    $scope.isJsonObjectEmpty = function(jsonObject){
    	   return Object.keys(jsonObject).length === 0;
    }
    
	/*
     *  Resets the page based on modal popup     
     */
   
    $('#pnDiffModal').on('hidden.bs.modal', function (e) {
       $scope.baseText = '';
        $scope.newText = '';
        $scope.filename = "";
        $('#diffoutput').empty();
    });
    //Instantiate instance of lru controller
    var lruMapControllerInstance = $scope.$new();
    $controller('Lrumap', {
        $scope: lruMapControllerInstance
    });
    $("body").tooltip({
        selector: '[data-toggle="tooltip"]'
    });
    
    $("#dbDisplayTextView").hide();
    $("#dbDisplayGraphicalView").hide();
//	utilityFactory.scrollBody();
	
    /*
     * Called when View button is clicked on the modal pop up
     */
    $scope.showViewModal = function () {
    	// console.log('insideshowViewModel');
    	var highlightedRow = $(".highlightSelectedRow"),
			highlightedColumns = highlightedRow.find("td");
//			 utilityFactory.showLoader();
			dbdisplayFactory.getFileList("/ADCTData/dctsoftware/Thales/DCT/QTR/QTR-A350-1000_PI8B-CUS-PI-7/EngBuilds/A07_01/").then(function (data) {
				// console.log('inside getFileList success');
//			   utilityFactory.hideLoader();				
					if (data.status) {
						$scope.$emit('popup', {"title":data.status ,"status": "info", "reload" : "false"});
						
					} else {	
						$scope.selected = data.fileList;
				
						$("#seatConnectionContainer").hide();
						$("#seatContainer").hide();
						angular.element("#dbDisplayTextView").hide();
						angular.element("#dbDisplayGraphicalView").hide();
						
						document.getElementById("dbDisplayDetailsForm").reset();        
						
						$scope.viewCarrierName = localStorage.getItem("carrierMenuDetails");
						$scope.viewDbName = localStorage.getItem("dbName");
						$scope.viewBuildVer = highlightedColumns[1].textContent;
						
						$('#dbdisplay_view').modal("show");
						$('#selDisplayDetails').hide();
						
						$.each($scope.selected, function(index, value) {
							if(value == "enhancedSeat.dat"){
								$scope.filename = "enhancedSeat.dat";
								$("#files").val(index).trigger('change.customSelect');
								$("input:radio[id=GraphicalViewBtn]").attr("checked", "checked").removeAttr("disabled");
								$("#modalView").removeClass("buttonDisabledCreate").removeAttr("disabled");
								$scope.fileView();
							}
						});	
						
					 }				
            });
			
			$timeout(function () { 
				$("#files").customselect();			
			},1000);
    	
    };
	
	 /*
     * Called when option is selected from dropdown on the modal pop up
     */
    $scope.enableDisplay = function (e) {
		var filename =  $('#files option:selected').text(),
        filenameValue = filename.split(".")[0];
		$("#dbDisplayGraphicalView").hide();
        if ((filenameValue == 'enhancedSeat') || (filenameValue == 'neighborlink') ||(filenameValue == 'lrumap')) {
            $("#GraphicalViewBtn").prop("checked", true).removeAttr("disabled");
        } else {
			$("#TextViewBtn").prop("checked",true);
            $("#GraphicalViewBtn").attr("disabled", "disabled");
        }
    };
    
    /*
     * Populates and View the modal pop for the selected dat file.
     * @param {String} - File selected by user
     *        {String} - File path of the selected file
     */
    $scope.fileView = function () {
//    	alert($rootScope.showLoading);
//		$scope.showLoading = true;
//		document.getElementById('loadingGraphicalView').style.class='ng-show';
    	
    	$("#loadingGraphicalView").show();

    	$timeout(function () { 
            angular.element("#loadingGraphicalView").show();
        },
        300);
    	
    	var filePath = $scope.fileRepoPath + $scope.filename.acronym + "/" + $scope.filename.platform + "/" + $scope.filename.configType + "/" + $scope.filename.software + "/";
    	console.log('File Path: ' + filePath);
		 
		
//        var selectedFilePath = $(".highlightSelectedRow").find("td")[4].textContent,
//        var selectedFilePath = '/ADCTData/dctsoftware/Thales/DCT/QTR/QTR-A350-1000_PI8B-CUS-PI-7/EngBuilds/A07_01/',
        var selectedFilePath = filePath,
//			mode = $("input[name='textView']:checked").val(),
			mode = 'Graphical',
//			filename = 'neighborlink.dat',
			filename = $scope.filename.filename,
			fileTemplate = {
				"neighborlink": '<div id="seatConnectionContainer"><div class="row"><div class="col-xs-3"><div class="form-group"><label for="mode">Decks</label>&nbsp;&nbsp;<select name="deckSelection" id="deckSelection" class="select-picker show-tick wiring-select" ng-model="deckSelection" ng-options="key for (key, value) in flightDecks"><option value="" ng-show="false">Main Deck</option></select></div></div><div class="col-xs-3"><div class="form-group"><label for="mode">LRU Type</label>&nbsp;&nbsp;<select name="lruType" id="lruType" class="select-picker show-tick wiring-select"><option value="Headend">Headend</option><option value="Seatend">Seatend</option><option value="Overhead">Overhead</option></select></div></div><div class="col-xs-3 displayNone" id="displayColumnId"><div class="form-group"><!--key as value for (key , value) in notSorted(columnSelector)--><label for="mode">Display Column</label>&nbsp;&nbsp;<select name="dispCol" id="dispCol" class="form-control"  ng-model="fetchedColumn" ng-options="key for (key , value) in columnSelector track by value"><option value="">Left</option></select></div></div><br/><br/><div class="row"><div class="col-xs-12"><div id="devices"><table id="deviceLegend"><tbody><tr></tr></tbody></table></div></div></div><!--Seat Connections populated here--><div id="seatConnectionWrapper"><div class="lruModal lruFade" id="lruLoaderModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"><div id="lrudataLoaderWrapper">Loading....</div></div><svg id="seatConnection"><defs><marker id="markermid" markerWidth="10" markerHeight="10" refX="3" refY="5" orient="auto"><path d="M1,1 L1,10 L10,5 L1,1"/></marker><marker id="markerArrow" markerWidth="10" markerHeight="10" refX="3" refY="5" orient="auto"><path d="M1,1 L1,10 L10,5 L1,1"/></marker></defs></svg><div id="flightDesign_seatConnection"></div></div>',
				"enhancedSeat":(thalesAppSettings.build == "idct") ? '<div id="seatContainer"><div class="row"><div class="col-xs-3"><div class="form-group"><label for="mode">MODE</label>&nbsp;&nbsp;<select name="mode" id="mode" class="select-picker show-tick wiring-select"><option value="ClassId">Class</option><option value="pa">Passenger Announcement</option><option value="va">Video Announcement</option><option value="subtype">SVDU SubType</option><option value="primary">Primary Server</option><option value="secondary">Secondary Server</option><option value="server">Media Server</option></select></div></div><div class="col-xs-3 displayNone" id="devType"><div class="form-group"><label for="mode">DEVICE</label>&nbsp;&nbsp;<select id = "device" name="device" id="device" class="select-picker show-tick wiring-select" ng-model="device" ng-options="key.devName as key.devName for key in devArr"><option value="">--Select--</option></select></div></div></div><div id="flightLegend"><!--Mode details are present here for different modes based on selection--><!--<div class="legendTitle ng-binding">ENHANCED SEAT CATEGORY [<span id="modeSelection" style="font-weight:normal"></span>]</div>--><div class="accordianHeading"><span id="modeSelection"></span></div><div class="legendAccordian"><table class="table" id="modeDetails"></table></div></div><!--Flight system elements populated here--><div id="flightDesign"></div></div>': '<div id="seatContainer"><div class="row"><div class="col-xs-3"><div class="form-group"><label for="mode">MODE</label>&nbsp;&nbsp;<select name="mode" id="mode" class="select-picker show-tick wiring-select"><option value="ClassId">Class</option><option value="pa">Passenger Announcement</option><option value="va">Video Announcement</option><option value="subtype">SVDU SubType</option><option value="primary">Primary Server</option><option value="secondary">Secondary Server</option><option value="DecodeVA">Decode VA</option><option value="DecodePRAM">Decode PRAM</option><option value="DecodeVOE">Decode VOE</option><option value="DecodeVOR">Decode VOR</option><option value="DecodeBGM">Decode BGM</option><option value="dimming">Window Dimming</option><option value="rpo">Remote Power Outlet</option><option value="server">Media Server</option><option value="sac">Seat Actuator Control</option></select></div></div><div class="col-xs-3 displayNone" id="devType"><div class="form-group"><label for="mode">DEVICE</label>&nbsp;&nbsp;<select id = "device" name="device" id="device" class="select-picker show-tick wiring-select" ng-model="device" ng-options="key.devName as key.devName for key in devArr"><option value="">--Select--</option></select></div></div></div><div id="flightLegend"><!--Mode details are present here for different modes based on selection--><!--<div class="legendTitle ng-binding">ENHANCED SEAT CATEGORY [<span id="modeSelection" style="font-weight:normal"></span>]</div>--><div class="accordianHeading"><span id="modeSelection"></span></div><div class="legendAccordian"><table class="table" id="modeDetails"></table></div></div><!--Flight system elements populated here--><div id="flightDesign"></div></div>',
				"lrumap": '<div class="printIcon cursorPointer" ng-click="print()"></div><div id="lruMap"><div class="table-responsive "><table class="table"><thead><tr><th width="2%"></th><th data-sortparam="HostType" class="sorting" ng-click="order(\'HostType\',$event)">LRU TYPE</th><th data-sortparam="Devices[0]" class="sorting" ng-click="order(\'Devices[0]\',$event)">LRU GROUP</th><th data-sortparam="Devices.length" class="sorting" ng-click="order(\'Devices.length\',$event)">COUNT</th></tr></thead><tbody><tr ng-repeat-start="(index,hostName) in hostNames | orderBy:predicate:reverse" class="hostDetails"><td><div class="iconsWrapper"><div alt="plus" class="accordian cursorPointer plusGreyIcon" ng-click="accordian($event)"/></div></div></td><td>{{ hostName.HostType }}</td><td>{{splitHostName(hostName.Devices[0])}}</td><td>{{ hostName.Devices.length }}</td></tr><tr ng-repeat-end class=""><td colspan="4" class="paddingNone"><div ng-repeat="device in hostName.Devices.sort(naturalSort)" ng-if="$index % 6 == 0" class="lruBlock collapse devices{{index}}"><div class="lrus col-xs-2"><div>{{hostName.Devices[$index]}}</div></div><div class="lrus col-xs-2"><div>{{hostName.Devices[$index + 1]}}</div></div><div class="lrus col-xs-2"><div>{{hostName.Devices[$index + 2]}}</div></div><div class="lrus col-xs-2"><div>{{hostName.Devices[$index + 3]}}</div></div><div class="lrus col-xs-2"><div>{{hostName.Devices[$index + 4]}}</div></div><div class="lrus col-xs-2"><div>{{hostName.Devices[$index + 5]}}</div></div></div></td></tr></tbody></table></div></div>	'
			},
			fileTemplateController = {
				"neighborlink": neighborLinkControllerInstance,
				"enhancedSeat": enhancedSeatControllerInstance,
				"lrumap": lruMapControllerInstance
			},
			textDispScrollHeight = "";
       
        if (mode == 'Text') {
            angular.element("#textviewModal").show();
			$("#textViewerDisplay").css("height", "0");
            dbdisplayFactory.convertFileToHtmlAndText(filename, mode, selectedFilePath).then(function (data) {
                angular.element("#dbDisplayGraphicalView").hide();
                $scope.textData = data.result.replace(/<br[^>]*>/g, "\n");
			 $timeout(function () { 
				$("#textViewerDisplay").show();
                angular.element("#dbDisplayTextView").show();
				textDispScrollHeight = $("#textViewerDisplay")[0].scrollHeight;
                $("#textViewerDisplay").css("height", textDispScrollHeight);
				 },
            300);
            });
            $timeout(function () { 
                angular.element("#textviewModal").hide();
            },
            300);
        } else if (mode == 'Graphical') {
//            angular.element("#textviewModal").show();
        	$('#dbdisplay_view').modal("show");
            dbdisplayFactory.convertFileToHtmlAndText(filename, mode, selectedFilePath).then(function (data) {
            	// console.log('inside dbfunctions controller again');
                $scope.hostNames = data.result;
                angular.element("#dbDisplayTextView").hide();
				
                angular.element("#dbDisplayGraphicalView").html($compile(fileTemplate[filename.split(".")[0]])($scope)).show();
				
                if (filename.split(".")[0] == 'enhancedSeat') {
                    fileTemplateController[filename.split(".")[0]].initlizeComponent(data.result, $scope.devArr);
                } else {
                	console.log('File Name: ' + filename.split(".")[0]);
                    fileTemplateController[filename.split(".")[0]].initlizeComponent(data.result);
                }
            });
        }
        $scope.showLoading = false;
        $timeout(function () { 
            angular.element("#loadingGraphicalView").hide();
        },
        300);
    };
}]);