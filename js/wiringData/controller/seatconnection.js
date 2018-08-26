app.controller("SeatConnection",[ 'dbdisplayFactory', '$scope', '$http', '$location', '$compile', '$routeParams', '$rootScope', '$timeout', function (dbdisplayFactory, $scope, $http, $location, $compile, $routeParams, $rootScope, $timeout) {
    $("body").tooltip({
        selector: '[data-toggle="tooltip"]'
    });
    
    $scope.seatJSON = {};
    $scope.flightDecks = {};
    $scope.lrus =[];
    $scope.legendCabinDetails = {};
    $scope.defaultDeck = "";    
    $scope.traversedItemInPath = {};
    
	
    /*
     * Called on click of 'NeighbourLink.dat' option. Initialises the connections of
     * 'headend' of seat layout on the UI.
     *
     * @param {JSON} -  connection data
     */
    $scope.initlizeComponent = function (data) {
        var currentDeck = "";
        angular.element("#flightDesign").html("");
        if(data.NeighborLink.Overhead.length == 0){
			$("#lruType option[value='Overhead']").attr("disabled","disabled");
		}else{
			$("#lruType option[value='Overhead']").removeAttr("disabled");
		}
        $scope.seatJSON = data;
        $scope.NeighborLink = data.NeighborLink;
        
        for (var headConnection = 0; headConnection < data.NeighborLink.Headend.length; headConnection++) {
            if ($.inArray(data.NeighborLink.Headend[headConnection].hostname, $scope.lrus) == -1) {
                $scope.lrus.push(data.NeighborLink.Headend[headConnection].hostname);
            }
            if ($.inArray(data.NeighborLink.Headend[headConnection].neighborName, $scope.lrus) == -1) {
                $scope.lrus.push(data.NeighborLink.Headend[headConnection].neighborName);
            }
        }
        
        $scope.lrus.sort().reverse();
        
        $scope.flightDecks = $scope.seatJSON.seatLayout.Decks_cabins;
        $scope.$parent.flightDecks = $scope.seatJSON.seatLayout.Decks_cabins;
        
        //initial deck
        $scope.defaultDeck = Object.keys($scope.flightDecks)[0];
        //create Deck
        
        currentDeck = Object.keys($scope.flightDecks)[0];
        if ($("#deckSelection").val()) {
            currentDeck = $("#deckSelection").val();
        }
        angular.element("#seatConnectionContainer").show();
        $scope.createDeck("", $scope.flightDecks, currentDeck, $scope.flightDecks[currentDeck]);
    };
    
    
    /*
     * Called from UI when user selects what deck to view - List count 1 for single deck and 2 for double deck
     *
     *   @param {action} - action triggered on selection
     *   		{String} - selected drop down value
     */
    
    $scope.displyDecks = function (action, e) {
        $scope.initlizeComponent($scope.seatJSON);
    };
    
    
    /*
     * Called from 'çreateDeck' function. Creates the Head end section of the flight.
     */
    $scope.createHeadEnd = function () {
    	// console.log('inside createHeadEnd');
        var headEndElement = "";
        
        for (var lru = 0; lru < $scope.lrus.sort($scope.naturalSort).length; lru += 2) {
            headEndElement += '<div class="rows row' + lru + ' ">' +
            '<div class="seaters seaters0" style="width:28%">' +
            '<div class="floatLeft">';
            if ($scope.lrus[lru]) {
				headEndElement += '<div class="seats floatLeft seatWidth missingDevices" id="' + $scope.lrus[lru] + '"><span>' + $scope.lrus[lru] + '</span><div  class="portWrapper"><div class="topPort"></div><div class="leftPort"></div><div class="rightPort"></div><div class="bottomPort"></div></div></div>';
            }
            headEndElement += '</div>' +
            '</div>' +
            '<div class="columnSpacer columnSpacer0" id="columnSpacer41151" style="width:8%"></div><div class="seaters seaters1" style="width:28%"></div><div class="columnSpacer columnSpacer1" id="columnSpacer41152" style="width:8%"></div>' +
            '<div class="seaters seaters2" style="width:28%">' +
            '<div class="floatRight">';
            if ($scope.lrus[lru + 1]) {
                headEndElement += '<div class="seats floatLeft seatWidth missingDevices" id="' + $scope.lrus[lru + 1] + '"><span>' + $scope.lrus[lru + 1] + '</span><div class="portWrapper"><div class="topPort"></div><div class="leftPort"></div><div class="rightPort"></div><div class="bottomPort"></div></div></div>';
            }
            headEndElement += '</div>' +
            '</div>' +
            '</div><div class="rowSpacer row' + Math.floor(Math.random() * (50000 - 100000) + 100000) + '"></div>';//<div class="rowSpacer row1"></div>
        }
        
        angular.element("#headEnd").append(headEndElement);
    };
    /*
     *  Sorts the items of columns in a table
     * @param {String} - column selected by the user
     * @param {String} - column selected by the user
     */
    $scope.naturalSort = function (as, bs) {
        var a, b, a1, b1, i = 0, L, rx = /(\d+)|(\D+)/g, rd = /\d/;
        if (isFinite(as) && isFinite(bs)) return as - bs;
        a = String(as).toLowerCase();
        b = String(bs).toLowerCase();
        if (a === b) return 0;
        if (!(rd.test(a) && rd.test(b))) return a > b ? 1: -1;
        a = a.match(rx);
        b = b.match(rx);
        L = a.length > b.length ? b.length: a.length;
        while (i < L) {
            a1 = a[i];
            b1 = b[i++];
            if (a1 !== b1) {
                if (isFinite(a1) && isFinite(b1)) {
                    if (a1.charAt(0) === '0') a1 = '.' + a1;
                    if (b1.charAt(0) === '0') b1 = '.' + b1;
                    return a1 - b1;
                } else return a1 > b1 ? 1: -1;
            }
        }
        return a.length - b.length;
    };
    
    
    /*
     * Create the deck based on the selected deck from user. Creates all the Rows and Columns
     * and places the seats.
     * @param {action} - action triggered on dropdown change
     * 		 {JSON} - data containing the decks info and cabin info.
     * 		 {String} - deck selected by user
     * 		 {String} - Selected drop down value
     */
    $scope.createDeck = function (action, decks, key, value) {   
        var flightDesign = angular.element("#flightDesign_seatConnection"),
        deckDetails = key.toLowerCase().replace(" ", "");
        
        flightDesign.html('<div id="leftColumnWrapper" class="columnSpacer2"></div><div id="rightColumnWrapper" class="columnSpacer3"></div><div id="headEnd"></div><div class="deckWrapper"><div id="' + deckDetails + '" class="decks">' + $scope.createCabin(key, value) + '</div></div>');
        
        //After creation of deck and cabin. create rows
        $scope.createHeadEnd();
        $scope.createRows(key, decks);
        $scope.createColumns(key, decks);
       // console.log('after creating headend rows and columns');
//        if (action != "") {
//            $scope.removeRows();
//            $scope.removeCabins();
//        }        
//        $(".seats").popover();
    };
    
    /*
     * Set cabin colors for the respective class
     * @param {Number} - cabin index
     */
    $scope.setCabinColors = function (index) {
        var cabinColors =[ "#000000", "#87D9F7", "#71D6B1", "#F0A182", "#a0a19e", "#F787AD", "#87ADF7", "#ecd783", "#9787F7", "#F78787"];
        return cabinColors[index];
    };
    
    
    /*
     * Create layout for cabins inside each deck
     * @param {String} - deck selected by user
     * {JSON}  - max seats and cabin details, Eg "Upper Deck":{"MaxSeat":[3,4,3],"cabin3":{"totalRows":10,"classAbbr":"UE","className":"UpperEconomy"}
     */
    $scope.createCabin = function (decks, cabinDetails) {
        var cabins = "",
        cabinObj = "",
//        cabinDetails = angular.toJson(cabinDetails);
        cabinDetails = JSON.parse(angular.toJson(cabinDetails));
//        cd = angular.copy(cabinDetails);
//        console.log(JSON.stringify(cd));
        deckLength = Object.keys(cabinDetails).length - 1;
//        deckLength = Object.keys(cd).length - 1;
        
        for (var cabinNumber = 1; cabinNumber <= deckLength; cabinNumber++) {
            cabinObj = cabinDetails[ "cabin" + cabinNumber];
            cabins += '<div class="cabins cabin' + cabinNumber + '" id="' + cabinObj.classAbbr + '" data-deck="' + decks + '"><div class="cabinClassLabelWrapper" style="background-color:' + $scope.setCabinColors(cabinNumber) + '"><div class="cabinClassLabel">' + cabinObj.className + '</div></div></div>';
        }        
        return cabins;
    };
    
    /*
     * Create Rows in each cabin for the selected deck and draw dummy rows for missing device placement
     * @param {String} - deck selected by user
     * 		  {JSON} - data containing deck details for each deck
     */
    $scope.createRows = function (selectedDeck, decks) {
    	// console.log('inside createRows');
        var cabins = angular.element(".cabins"),
        currentElement = "",
        cabinNumber = "",
        deckDetails = "",
        rowCount = 1,
        randomNumber = 0,
        deckRowCount = $scope.getRowCount(decks);
        
        if (selectedDeck != $scope.defaultDeck) {
            rowCount = deckRowCount[$scope.defaultDeck] + 1;
        }
        
        for (var cabin = 0; cabin < cabins.length; cabin++) {
            randomNumber = Math.floor(Math.random() * (50000 - 100000) + 100000);
            currentElement = angular.element(cabins[cabin]);
            deckDetails = currentElement.attr("data-deck");
            cabinNumber = currentElement.attr("class").replace("cabins ", "");
            
            currentElement.append('<div class="rows row' + randomNumber + '"></div><div class="rowSpacer row' + randomNumber + '"></div>');
            
            for (var row = 1; row <= decks[deckDetails][cabinNumber].totalRows; row++) {
                randomNumber = Math.floor(Math.random() * (50000 - 100000) + 100000);
                currentElement.append('<div class="rows row' + rowCount + '"><div class="rowIdentifier"></div></div><div class="rowSpacer row' + rowCount + '"></div><div class="rows row' + randomNumber + '"></div><div class="rowSpacer row' + randomNumber + '"></div>');
                rowCount++;
            }
        }
    };
    
    /*
     * Get the total number of rows for each Deck in the flight
     * @param {JSON} - data with deck information
     */
    $scope.getRowCount = function (decks) {
        var tempDecks = decks,
        toalCount = 0,
        totalCounts = {
        };
        
        $.each(tempDecks, function (key, value) {
            toalCount = 0;
            $.each(tempDecks[key], function (keys, values) {
                if (keys != "MaxSeat") {
                    toalCount += parseInt(tempDecks[key][keys].totalRows, 10);
                    totalCounts[key] = toalCount;
                }
            });
        });
        return totalCounts;
    };
    
    
    /*
     * Manage placement of seats by calculating column width based on number of columns in flight.
     * Get Column count as MaxSeat value. Iterate through rows and the columns for each row and
     * Create individual seat using the row and column index.
     * @param {String} - Selected deck
     * 		  {JSON} - data containing deck info
     */
    $scope.createColumns = function (selectedDeck, decks) {
		// console.log('inside createColumns');
        var rows = angular.element(".cabins .rows"),
        currentElement = "",
        parentNode = "",        
        deckDetails = "",
        column = [],
        columnWidth = 0,
        columnSeprationWidth = 0,
        rowCount = 0,
        deckRowCount = $scope.getRowCount(decks),
        lruType = $("#lruType option:selected").val();
        // console.log("Selected LRU Type: " + lruType);
       
        if (selectedDeck != $scope.defaultDeck) {
            rowCount = deckRowCount[$scope.defaultDeck];
        }
        
        for (var row = 0; row < rows.length; row++) {
		
            currentElement = angular.element(rows[row]);
            parentNode = currentElement.parent();           
            deckDetails = parentNode.attr("data-deck");
            column = decks[deckDetails].MaxSeat;
            
            if (currentElement.children(".rowIdentifier").length == 1) {
				rowCount++;
				
            }
			 if (column.length == 1) {
				columnSeprationWidth = 0;
                columnWidth = 100;             
                currentElement.append('<div class="seaters seaters0" style="width:' + columnWidth + '%"><div class="floatLeft"><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div></div></div><div class="columnSpacer columnSpacer0" id="columnSpacer' + Math.floor(Math.random() * (9999 - 1 + 1)) + 1 + '" style="width:' + columnSeprationWidth + '%"></div><div class="seaters seaters1" style="width:' + columnWidth + '%"><div class="floatRight"><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div></div></div>');
            }else if (column.length == 2) {
				columnSeprationWidth = 40;
                columnWidth = 60 / 2;
                $scope.$parent.columnSelector = {
                    "Right": "1"
                };
               
                currentElement.append('<div class="seaters seaters0" style="width:' + columnWidth + '%"><div class="floatLeft"><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div></div></div><div class="columnSpacer columnSpacer0" id="columnSpacer' + Math.floor(Math.random() * (9999 - 1 + 1)) + 1 + '" style="width:' + columnSeprationWidth + '%"></div><div class="seaters seaters1" style="width:' + columnWidth + '%"><div class="floatRight"><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div></div></div>');
            } else if (column.length == 3) {
                columnSeprationWidth = 8;
                columnWidth = 84 / 3;
                $scope.$parent.columnSelector = {
                    "Center": "1", "Right": "2"
                };
                currentElement.append('<div class="seaters seaters0" style="width:' + columnWidth + '%"><div class="floatLeft"><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div></div></div><div class="columnSpacer columnSpacer0" id="columnSpacer' + Math.floor(Math.random() * (9999 - 1 + 1)) + 1 + '" style="width:' + columnSeprationWidth + '%"></div><div class="seaters seaters1" style="width:' + columnWidth + '%"><div class="floatCenter"><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div></div></div><div class="columnSpacer columnSpacer1" id="columnSpacer' + Math.floor(Math.random() * (9999 - 1 + 1)) + 1 + '" style="width:' + columnSeprationWidth + '%"></div><div class="seaters seaters2" style="width:' + columnWidth + '%"><div class="floatRight"><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div><div class="seats floatLeft seatWidth visibilityHidden" id="" data-original-title="" title=""></div></div></div>');
            }
            for (var seater = 0; seater < column.length; seater++) {
                //insert seats after creating the columns and dummy seats
                $scope.seatPlaceMent(rowCount, seater, column[seater]);
            }
        }
        if(!$scope.$$phase) {
        	$scope.$apply();
    	}
        
        
        //insert missing devices
        $("#deviceLegend tbody tr").html("");
        
        $scope.insertMissingDevices($scope.seatJSON.NeighborLink.Seatend, column.length);
        
        if ($("#lruType").val() == "Seatend") {
			$("#dispCol").val("");
			$(".cabins .seaters >div").css("display", "none");
            $(".cabins .seaters >.floatLeft").css("display", "block");
        } else if ($("#lruType").val() == "Overhead") {
            $(".cabins .seaters >div").css("display", "block");
        } else {
            $(".cabins .seaters >div").css("display", "block");
        }
        
        $scope.drawLayout(lruType, $scope.seatJSON.NeighborLink[lruType]);
    };
    
    /*
     * Clear the already placed rows on the screen before Creating new deck
     */
    $scope.removeRows = function () {
        var rows = angular.element(".rows"),
        row = "";
        
        for (var i = 0; i < rows.length; i++) {
            row = angular.element(rows[i]);
            
            if (row.find(".seats").length == row.find(".visibilityHidden").length) {
                row.next(".rowSpacer").remove();
                row.remove();
            }
        }
    };
    
    /*
     * Clear the already placed cabins on the screen before Creating new deck
     */
    $scope.removeCabins = function () {
        var cabins = angular.element(".cabins"),
        cabin = "";
        
        for (var i = 0; i < cabins.length; i++) {
            cabin = angular.element(cabins[i]);
            if (cabin.find(".rows").length == 0) {
                cabin.remove();
            }
        }
    };
    
    /*
     * Place each in the respective columns and align them.
     * @param {Number} - rowIndex
     * 		  {Number} - column Index
     * 		  {Number} - seatPosition
     */
    $scope.seatPlaceMent = function (rowIndex, columnIndex, seatPosition) {
        var currentIndex = "",
        seatJson = $scope.seatJSON.seatLayout,
        currentRow = "",
        currentSeat = "";
        
        for (var seat = 1; seat <= 4; seat++) {
            currentIndex = rowIndex + "-" +(columnIndex + 1) + "-" + (seat);
            if (seatJson.seats[currentIndex] && seatJson.seats[currentIndex].pavamapRow < 600) {
                
                currentRow = angular.element(".deckWrapper .row" + rowIndex);
                currentRow.find(".rowIdentifier").attr("id", "rowNumber_" + seatJson.seats[currentIndex].pavamapRow).html(seatJson.seats[currentIndex].pavamapRow);
                currentSeat = currentRow.find(".seaters" + columnIndex).children().children()[seat - 1];
                
                $(currentSeat).attr({
                    "id": "SVDU" + seatJson.seats[currentIndex].seat
                }).removeClass("visibilityHidden").html('<div class="portWrapper"><div class="seatLetter">' + seatJson.seats[currentIndex].acSeatLetter + '</div><div class="topPort"></div><div class="leftPort"></div><div class="rightPort"></div><div class="bottomPort"></div></div>');
            }
        }
    };
    
    
    /*
     * Start connecting the ports based on Hostname, Neighbour information and Head end/Seat end/Overhead info.
     * @param {JSON Array Element} - Data containing host and neightbour information
     * 		  {String}  - lru type - Head end/Seat end/Overhead
     */
    $scope.createConnection = function (connectionType, lruType) {
        var seatPosition = "",
        nearestColumn = "",
        startElement = angular.element("#" + connectionType.hostname),
        endElement = angular.element("#" + connectionType.neighborName),
        connectingSeat = "",
        headendElements =[ "DSU", "ICM", "ADB", "AVC", "LAI", "TPM"];
        if (startElement.parent().attr('style') == "display: none;" || endElement.parent().attr('style') == "display: none;") {
            if ($.inArray(connectionType.hostname.substring(0, 3), headendElements) == -1 && $.inArray(connectionType.neighborName.substring(0, 3), headendElements) == -1) {
                if (startElement.parent().attr('style') == "display: none;" && endElement.parent().attr('style') != "display: none;") {
                    connectingSeat = connectionType.hostname;
                    $scope.insertColumnConnectionSeat(connectingSeat, endElement, lruType, connectionType);
                } else if (startElement.parent().attr('style') != "display: none;" && endElement.parent().attr('style') == "display: none;") {
                    connectingSeat = connectionType.neighborName;
                    $scope.insertColumnConnectionSeat(connectingSeat, startElement, lruType, connectionType);
                }
            }
        } else {
            
            // get coordinates for the two elements
            if (startElement.length == 1 && endElement.length == 1 && startElement.parent().attr('style') != "display: none;" && endElement.parent().attr('style') != "display: none;") {
                //identify end element location from start element
                seatPosition = $scope.identifySeatPosition(startElement, endElement);
				/* Seat with self connections */
                if (connectionType.hostname == connectionType.neighborName){	
					var tooltip ='<div class= "textAlignLeft">Host Name : ' + connectionType.hostname + '<br>Host Port : ' + connectionType.hostPortId + '<br>Neighbor Name : ' + connectionType.neighborName + '<br>Neighbor Port : ' + connectionType.neighborPortId + '</div>',
						tooltipexists = "";
				
					 if($("#" + connectionType.hostname).attr("data-original-title") ){
						tooltipexists = $("#" + connectionType.hostname).attr("data-original-title"); 
					 }
						 
					if(tooltipexists == ""){
						$("#" + connectionType.hostname).attr({"data-html":"true","data-toggle":"tooltip","data-placement":"bottom","data-original-title":tooltip,"data-container":"body"});
					} else {
						$("#" + connectionType.hostname).attr("data-original-title",$("#" + connectionType.hostname).attr("data-original-title")+"<br/>"+tooltip);
					}
				
				}else{ 
					/* Seat with other connections */
	                if (seatPosition == "far") {
	                    if (lruType == "Headend") {
	                        $scope.insertHeadendFarPort(startElement, endElement, connectionType, "far");
	                        nearestColumn = $scope.nearestColumnHeadend(startElement, connectionType.hostPortId);
	                    } else {
	                        nearestColumn = $scope.nearestColumn(startElement);
							
	                        $scope.insertPort(startElement, endElement, connectionType, nearestColumn.direction, "far");
	                    }
	                    $scope.drawFarpath(connectionType.hostPortId, connectionType.neighborPortId, nearestColumn, startElement, endElement, lruType);
	                } else {
	                    $scope.insertPort(startElement, endElement, connectionType, seatPosition, "near");
	                    $scope.drawnearestpath(connectionType.hostPortId, connectionType.neighborPortId, seatPosition, startElement, endElement, lruType);
	                }
				}
            }
        }
        $("#seatConnectionWrapper").height($("#flightDesign_seatConnection").height());
        // console.log("height of seat connection wrapper: " + $("#flightDesign_seatConnection").height());
		// console.log('after setting height');
    };
    
    /*
     * Start inserting column connection based on visibleSeat, lruType, connectionType information.
     * 		  {String}  - lru type - Head end/Seat end/Overhead
     */
    $scope.insertColumnConnectionSeat = function (connectingSeat, visibleSeat, lruType, connectionType) {
    	// console.log('inside insertColumnConnectionSeat');
        var nearestColumn = $scope.nearestColumn(visibleSeat),
        seatPosition = "";
        
        if (nearestColumn.direction == "left") {
            visibleSeat.append('<div id="bubble' + connectingSeat + '" class ="seatconnectingbubble floatRight">' + connectingSeat + '</div>');
        } else if (nearestColumn.direction == "right") {
            visibleSeat.append('<div id="bubble' + connectingSeat + '" class ="seatconnectingbubble floatLeft">' + connectingSeat + '</div>');
        } else {
            visibleSeat.append('<div id="bubble' + connectingSeat + '" class ="seatconnectingbubble floatRight">' + connectingSeat + '</div>');
        }
        
        if (connectionType.hostname === connectingSeat) {
            if (nearestColumn.direction == "bottom") {
                seatPosition = "middle";
            } else {
                seatPosition = $scope.identifySeatPosition(angular.element("#bubble" + connectingSeat), visibleSeat);
            }
            
            $scope.drawbubblepath(seatPosition, angular.element("#bubble" + connectingSeat), visibleSeat, lruType, connectionType.hostPortId, connectionType.neighborPortId);
        } else {
            $scope.drawbubblepath(nearestColumn.direction, visibleSeat, angular.element("#bubble" + connectingSeat), lruType, connectionType.hostPortId, connectionType.neighborPortId);
        }
    };
    /*
     * Insert Ports for each Seat based on Host and Neightbour Port info.
     * @param {Element} - Start Element(seat) as on the display.
     * 	      {Element} - End Element(seat) as on the display.
     * 		  {JSON Array Element} - Data containing host and neightbour port id information
     *        {String} - Seat Postion(left,right, etc)
     *        {String} - Far or near seat
     */
    $scope.insertPort = function (startElement, endElement, connectionType, seatPosition, seatDistance) {
        var portAlignment = {
            "right": {
                "hostPortPosition": "rightPort", "neighborPortPosition": "leftPort", "startPosition": "", "endPosition": ""
            },
            "left": {
                "hostPortPosition": "leftPort", "neighborPortPosition": "rightPort", "startPosition": "", "endPosition": ""
            },
            "top": {
                "hostPortPosition": "topPort", "neighborPortPosition": "bottomPort", "startPosition": "", "endPosition": ""
            },
            "toprightdiagonal": {
                "hostPortPosition": "topPort", "neighborPortPosition": "bottomPort", "startPosition": "floatRight", "endPosition": "floatLeft"
            },
            "topleftdiagonal": {
                "hostPortPosition": "topPort", "neighborPortPosition": "bottomPort", "startPosition": "floatLeft", "endPosition": "floatRight"
            },
            "bottom": {
                "hostPortPosition": "bottomPort", "neighborPortPosition": "topPort", "startPosition": "", "endPosition": ""
            },
            "bottomrightdiagonal": {
                "hostPortPosition": "bottomPort", "neighborPortPosition": "topPort", "startPosition": "floatRight", "endPosition": "floatLeft"
            },
            "bottomleftdiagonal": {
                "hostPortPosition": "bottomPort", "neighborPortPosition": "topPort", "startPosition": "floatLeft", "endPosition": "floatRight"
            }
        },		
        neighbourPortPosition = (seatDistance == "far") ? "bottomPort": portAlignment[seatPosition].neighborPortPosition,
        startElementPorts = angular.element("#" + startElement[0].id + " ." + portAlignment[seatPosition].hostPortPosition),
        endElementPorts = angular.element("#" + endElement[0].id + " ." + neighbourPortPosition);
	
        if (startElementPorts.find("div#" + connectionType.hostPortId).length == 0) {
            startElementPorts.append('<div class="port ' + portAlignment[seatPosition].startPosition + '" id="' + connectionType.hostPortId + '"></div>');
        }
        
        if (endElementPorts.find("div#" + connectionType.neighborPortId).length == 0) {
            endElementPorts.append('<div class="port ' + portAlignment[seatPosition].endPosition + '" id="' + connectionType.neighborPortId + '"></div>');
        }
    };
    
    /*Inserting port for headend for Far path 
     *   @param {Element} - Start Element(seat) as on the display.
     * 	      {Element} - End Element(seat) as on the display.
     * 		  {JSON Array Element} - Data containing host and neightbour port id information         
     *        {String} - Far or near seat
     */
    
    
    $scope.insertHeadendFarPort = function (startElement, endElement, connectionType, seatDistance) {
        var portAlignment = {
            "right": {
                "hostPortPosition": "rightPort", "neighborPortPosition": "leftPort", "startPosition": "", "endPosition": ""
            },
            "left": {
                "hostPortPosition": "leftPort", "neighborPortPosition": "rightPort", "startPosition": "", "endPosition": ""
            },
        },
        neighbourPortPosition = "",
        startElementPorts = "",
        endElementPorts = "",
        seatPosition = "";
        
        if (angular.element(startElement[0].parentNode).attr("class") == "floatLeft") {
            seatPosition = "left";
        } else if (angular.element(startElement[0].parentNode).attr("class") == "floatRight") {
            seatPosition = "right";
        }
        
        neighbourPortPosition = (seatDistance == "far") ? "bottomPort": portAlignment[seatPosition].neighborPortPosition,
        startElementPorts = angular.element("#" + startElement[0].id + " ." + portAlignment[seatPosition].hostPortPosition),
        endElementPorts = angular.element("#" + endElement[0].id + " ." + neighbourPortPosition);
        
        if (startElementPorts.find(".port").length > 4) {
            if (seatPosition == "right") {
                startElementPorts = angular.element("#" + startElement[0].id + " ." + portAlignment[ "left"].hostPortPosition);
            } else {
                startElementPorts = angular.element("#" + startElement[0].id + " ." + portAlignment[ "right"].hostPortPosition);
            }
        }
        if (startElementPorts.find("div#" + connectionType.hostPortId).length == 0) {
            startElementPorts.append('<div class="port ' + portAlignment[seatPosition].startPosition + '" id="' + connectionType.hostPortId + '"></div>');
        }
      //  // console.log(startElementPorts);
        if (endElementPorts.find("div#" + connectionType.neighborPortId).length == 0) {
            endElementPorts.append('<div class="port ' + portAlignment[seatPosition].endPosition + '" id="' + connectionType.neighborPortId + '"></div>');
        }
    };
    
    /*
     * Insert SDB devices in seat end for respective SVDUs,
     * Insert Overhead SVDU seats
     * Insert ETU based on the Overhead SVDU seats
     * @param {JSON} - data containing seat end information
     */
    $scope.insertMissingDevices = function (seatEnd, columns) {
        
        var lruType = $("#lruType").val();
        
        for (var devices = 0; devices < seatEnd.length; devices++) {
            /*
             * Identify whether the device is overhead or not
             * If device is between 600 to 700 we are assuming it as overhead
             */
            
            if (lruType == "Overhead") {
                
                if ((seatEnd[devices].neighborName.replace(/\D/g, '') >= 600 && seatEnd[devices].neighborName.replace(/\D/g, '') <= 700)) {
                    $scope.insertOverHead(seatEnd[devices].neighborName, seatEnd[devices].neighborName.split(/((\d+[A-Z])[A-Z]?)/).filter(Boolean), columns, seatEnd[devices].hostname);
                } else if ((seatEnd[devices].hostname.replace(/\D/g, '') >= 600 && seatEnd[devices].hostname.replace(/\D/g, '') <= 700)) {
                    $scope.insertOverHead(seatEnd[devices].hostname, seatEnd[devices].hostname.split(/((\d+[A-Z])[A-Z]?)/).filter(Boolean), columns, seatEnd[devices].neighborName);
                }
            }
            
            
            /*
             * place the device other than overhead
             */
            
            if (! document.getElementById(seatEnd[devices].neighborName)) {
                $scope.insertHeadEnd(seatEnd[devices].neighborName, columns, lruType);
            }
            if (! document.getElementById(seatEnd[devices].hostname)) {
                $scope.insertHeadEnd(seatEnd[devices].hostname, columns, lruType);
            }
        }
        
        $scope.removeRows();
        $scope.removeCabins();
    };
    
    /*
     * Insert ETU in seat end for respective SVDUs,
     * Insert Overhead ETU seats     
     * @param {string} - neighbor seat name
     * @param {string} - host seat name
     */
    $scope.insertETU = function (neighborName, hostname) {
        var svduElement = "",
        etuElement = "";
        
        if (neighborName.indexOf("ETU") >= 0) {
            svduElement = hostname;
            etuElement = neighborName;
        } else {
            svduElement = neighborName;
            etuElement = hostname;
        }
        
        if (! document.getElementById(etuElement)) {
            $scope.insertLegend("ETU");
            if ($('#' + svduElement).parent().attr("class") == "floatRight") {
                $('#' + svduElement).next("div").addClass("legend_ETU").removeClass("visibilityHidden").attr("id", etuElement).html('<span>' + etuElement.replace("ETU", "") + '</span><div class="portWrapper"><div class="topPort"></div><div class="leftPort"></div><div class="rightPort"></div><div class="bottomPort"></div></div>');
            } else {
                $('#' + svduElement).prev("div").addClass("legend_ETU").removeClass("visibilityHidden").attr("id", etuElement).html('<span>' + etuElement.replace("ETU", "") + '</span><div class="portWrapper"><div class="topPort"></div><div class="leftPort"></div><div class="rightPort"></div><div class="bottomPort"></div></div>');
            }
        }
    };
    
    /*
     * Insert Overhead in seat end for respective SVDUs,
     * Insert Overhead ETU seats     
     * @param {string} - current seat.
     * @param {string} - device name.
     * @param {number} - column number.
     * @param {string} - device name that has to be mapped with current device.
     */
    $scope.insertOverHead = function (currentDevice, device, columns, mappingDevice) {
        var rowElement = "",
        overheadAlignIndex = 0,
        overHeadSeatNumber = device[2].replace(/[A-Z]$/, '');
        
        if (columns == 2) {
            if (device[2].replace(/^[0-9]+/, '') == 'L') {
                overheadAlignIndex = 0;
            } else if (device[2].replace(/^[0-9]+/, '') == 'R') {
                overheadAlignIndex = 1;
            }
        } else {
            if (device[2].replace(/^[0-9]+/, '') == 'L') {
                overheadAlignIndex = 0;
            } else if (device[2].replace(/^[0-9]+/, '') == 'M') {
                overheadAlignIndex = 1;
            } else if (device[2].replace(/^[0-9]+/, '') == 'R') {
                overheadAlignIndex = 2;
            }
        }
        
        rowElement = $("#rowNumber_" + overHeadSeatNumber.replace(/^60?/, '')).parents(".rows").prev().prev();
        
        //check in row number already exists for current row
        if ($(rowElement).find(".rowIdentifier").length == 0) {
            angular.element(rowElement.prepend('<div class="rowIdentifier">' + overHeadSeatNumber + '</div>'));
        }
        
        angular.element(rowElement.children('.seaters').eq(overheadAlignIndex).children().children()[1]).removeClass("visibilityHidden").addClass("overheadSvduSeat").attr("id", currentDevice).html('<span>' + device[2].replace(/^[0-9]+/, '') + '</span><div class="portWrapper"><div class="topPort"></div><div class="leftPort"></div><div class="rightPort"></div><div class="bottomPort"></div></div>');
        
        if (currentDevice.indexOf("ETU") >= 0 || mappingDevice.indexOf("ETU") >= 0) {
            $scope.insertETU(currentDevice, mappingDevice);
        }
    };
    /*
     * Insert Lengend in seat end for respective SVDUs,
     * Insert Overhead ETU seats     
     * @param {string} - device name.
     */
    $scope.insertLegend = function (deviceName) {
        var deviceLegend = $("#deviceLegend tbody tr");
        
        if (deviceLegend.find(".legend_" + deviceName).length == 0) {
            deviceLegend.append('<td> <div class="legend legend_' + deviceName + '"></div>' + deviceName + '</td>');
        }
    };
    /*
     * Insert Headend in seat end for respective SVDUs,
     * Insert Overhead ETU seats     
     * @param {string} - current seat.    
     * @param {number} - column number.
     * @param {number} - lru type of device.
     */
    $scope.insertHeadEnd = function (currentDevice, columns, lruType) {
        var placementIndex = 0,
        rowElement = "",
        currentDeviceStr = "";
        
        //currentDevice
        
        if (lruType == "Seatend") {
            if (currentDevice.indexOf("SDB") >= 0) {
                if (currentDevice.indexOf("SDB") >= 0) {
                    $scope.insertLegend("SDB");
                    currentDeviceStr = currentDevice.split(/[0-9]+/)[0, 1].charAt(0);
                    placementIndex = $('#SVDU' + parseInt(currentDevice.replace(/\D/g, ''), 10) + currentDeviceStr);
                    rowElement = placementIndex.parents(".rows").next().next();
                    
                    $(angular.element(rowElement.find("." + placementIndex.parent().attr("class") + ":first")).children()[0]).removeClass("visibilityHidden").addClass("legend_SDB").attr("id", currentDevice).html('<span>' + currentDevice.replace("SDB", "") + '</span><div class="portWrapper"><div class="topPort"></div><div class="leftPort"></div><div class="rightPort"></div><div class="bottomPort"></div></div>');
                    
                    
                    //$('#'+currentDevice).parents(".rows").prev().addClass("minRowSpacer");
                    //$('#'+currentDevice).parents(".rows").next().addClass("minRowSpacer");
                } else {
                    //insert other than SDB
                }
            }
        }
    };
    
    
    /*
     * Locate the nearest left or right column for any particular seat.
     * @param {Element} - Start seat Element
     * @return {JSON} - key/value contanining 'çolumnspacer' and the 'direction' from seat
     */
    
    $scope.nearestColumn = function (startElement) {
        
        var previousColumn = startElement.parents(".seaters").prev(".columnSpacer"),
        nextColumn = startElement.parents(".seaters").next(".columnSpacer"),
        seatPosition = startElement[0].parentNode.childNodes,
        seatsVisible =[],
        j = 0;
        
        if (previousColumn.length == 0) {
            previousColumn = angular.element("#leftColumnWrapper");
        }
        
        if (nextColumn.length == 0) {
            nextColumn = angular.element("#rightColumnWrapper");
        }
        
        for (var i = 0; i < seatPosition.length; i++) {
            if (!($(seatPosition[i]).hasClass("visibilityHidden"))) {
                seatsVisible[j] = seatPosition[i];
                j++;
            }
        }
        
        
        //identify whether the startelement is a middle seat
        if (startElement[0].id != seatsVisible[0].id && startElement[0].id != seatsVisible[seatsVisible.length - 1].id) {
            return {
                "columnSpacer": nextColumn.attr("class").replace("columnSpacer columnSpacer", "columnSpacer"),
                "direction": "bottom"
            };
        } else if (startElement[0].id == seatsVisible[0].id) {
            //if the current seat is the first seat / left seat in a column
            if (seatsVisible.length == 1 && angular.element(startElement[0].parentNode).attr("class") == "floatRight") {
                return {
                    "columnSpacer": nextColumn.attr("class").replace("columnSpacer columnSpacer", "columnSpacer"),
                    "direction": "right"
                };
            } else {
                return {
                    "columnSpacer": previousColumn.attr("class").replace("columnSpacer columnSpacer", "columnSpacer"),
                    "direction": "left"
                };
            }
        } else {
            return {
                "columnSpacer": nextColumn.attr("class").replace("columnSpacer columnSpacer", "columnSpacer"),
                "direction": "right"
            };
        }
    };
    
    /*nearestColumn for Headend for Far Path
     * @param {Element} - Start seat Element
     * @param {Number} - Host port Id
     */
    
    $scope.nearestColumnHeadend = function (startElement, hostPortId) {
        
        var previousColumn = startElement.parents(".seaters").prev(".columnSpacer"),
        nextColumn = startElement.parents(".seaters").next(".columnSpacer");
        
        if (previousColumn.length == 0) {
            previousColumn = angular.element("#leftColumnWrapper");
        }
        
        if (nextColumn.length == 0) {
            nextColumn = angular.element("#rightColumnWrapper");
        }
        
        if (angular.element(startElement[0].parentNode).attr("class") == "floatRight") {
            if (startElement.children().find("#" + hostPortId).parent().attr("class") == "rightPort") {
                return {
                    "columnSpacer": nextColumn.attr("class").replace("columnSpacer columnSpacer", "columnSpacer"),
                    "direction": "right"
                };
            } else if (startElement.children().find("#" + hostPortId).parent().attr("class") == "leftPort") {
                return {
                    "columnSpacer": previousColumn.attr("class").replace("columnSpacer columnSpacer", "columnSpacer"),
                    "direction": "left"
                };
            }
        } else if (angular.element(startElement[0].parentNode).attr("class") == "floatLeft") {
            
            if (startElement.children().find("#" + hostPortId).parent().attr("class") == "rightPort") {
                return {
                    "columnSpacer": nextColumn.attr("class").replace("columnSpacer columnSpacer", "columnSpacer"),
                    "direction": "right"
                };
            } else if (startElement.children().find("#" + hostPortId).parent().attr("class") == "leftPort") {
                
                return {
                    "columnSpacer": previousColumn.attr("class").replace("columnSpacer columnSpacer", "columnSpacer"),
                    "direction": "left"
                };
            }
        }
    };
    /*
     * Identify the end seat location w.r.t the start seat location (left, right, top, bottom, etc)
     * @param {Element} - Start seat Element
     * 		  {Element} - End seat Element
     * @return {String} - location of end seat w.r.t start seat
     */
    $scope.identifySeatPosition = function (startElement, endElement) {
        
        //Assumption1 : Between column to column there is no connection
        //Assumption2 : Between cabin to cabin there is no connection
        
        var startEleCoord = startElement[0].getBoundingClientRect(),
        endEleCoord = endElement[0].getBoundingClientRect();
        
        //identify left or right seat
        if (startEleCoord.top == endEleCoord.top) {
            if (startEleCoord.right < endEleCoord.right) {
                return "right";
            }
            
            return "left";
            //identify top and top diagonal seat
        } else if (startEleCoord.top > endEleCoord.top) {
            
            if (startElement.parents().prev(".rowSpacer").attr("class") != endElement.parents().next(".rowSpacer").attr("class")) {
                return "far";
            } else {
                if ((startEleCoord.right < endEleCoord.right)) {
                    return "toprightdiagonal";
                } else if ((startEleCoord.right > endEleCoord.right)) {
                    return "topleftdiagonal";
                }
            }
            
            return "top";
            
            //identify bottom and bottom diagonal seat
        } else if (startEleCoord.top < endEleCoord.top) {
            
            if (startElement.parents().next(".rowSpacer").attr("class") != endElement.parents().prev(".rowSpacer").attr("class")) {
                return "far";
            } else {
                
                if ((startEleCoord.right < endEleCoord.right)) {
                    return "bottomrightdiagonal";
                } else if ((startEleCoord.right > endEleCoord.right)) {
                    return "bottomleftdiagonal";
                }
            }
            return "bottom";
        }
    };
    
    
    /*
     * Connect 2 far seats based on seat element coordinates, port ids, nearest column and lrutype.
     * Create SVG path with the traverse directions constructed based on all the coordinates.
     * @param {Number} - HostPortId
     *        {Number} - NeighbourPortId
     *        {JSON} - Nearest column info
     *        {Element} - Start Element
     *        {Element} - End Element
     *        {String} - LRU Type
     */
    $scope.drawFarpath = function (hostPortId, neighborPortId, nearestColumn, startElement, endElement, lruType) {       
        
        var startEleCoored = startElement[0].getBoundingClientRect(),
        endEleCoored = endElement[0].getBoundingClientRect(),
        endSeatRow = endElement.parents(".rows").next(".rowSpacer"),
        startSeatRow = startElement.parents(".rows").next(".rowSpacer"),
//        svgDiff = $("svg")[0].getBoundingClientRect().top,
        svgDiff = $("#seatConnection")[0].getBoundingClientRect().top,
        columnTraverse = $scope.getTravelledPathCount(nearestColumn.columnSpacer),
        rowTraverse = $scope.getTravelledPathCount(endSeatRow.attr("class").replace("rowSpacer row", "rowSpacer")),
        hostPort = angular.element("#" + startElement[0].id + " #" + hostPortId)[0].getBoundingClientRect(),
        neighborPort = angular.element("#" + endElement[0].id + " #" + neighborPortId)[0].getBoundingClientRect(),
        seatVerticalCenter = (hostPort.top - svgDiff) +((hostPort.bottom - hostPort.top) / 2),
        pattern1 = 0,
        pattern2 = (endSeatRow[0].getBoundingClientRect().top - svgDiff) + rowTraverse,
        //pattern3 = ( (endEleCoored.left + (endEleCoored.right - endEleCoored.left)/2) - $("svg")[0].getBoundingClientRect().left),//seat horizontal center
        pattern3 = ((neighborPort.left + (neighborPort.right - neighborPort.left) / 2) - $("#seatConnection")[0].getBoundingClientRect().left),//seat horizontal center
        //pattern4 = (pattern2 - (rowTraverse + 5 ) );
        pattern4 = endEleCoored.bottom - svgDiff + 5,
        leftLineArrowPos = "",
        downLineArrowPos = "",
        rightLineArrowPos = "",
        nearestColSpacerEle = angular.element("." + nearestColumn.columnSpacer)[0],
        linePathLinking = Math.floor(Math.random() * (50000 - 100000) + 100000),
        nearestColumnSpacerDiff = "",
		rowTraverseMiddleSeat = "",
		patternY1 = "",
		patternY2 = "",
		patternX = "";
        
        /*left-bottom-left-top
        ______ start seat
        |
        |
        end seat	|
        |		|
        ---------
        
         */
        
        
        if (nearestColumn.columnSpacer != "columnSpacer2" && nearestColumn.columnSpacer != "columnSpacer3") {
            if (lruType == "Headend") {
                nearestColSpacerEle = angular.element("#headEnd").find(angular.element("." + nearestColumn.columnSpacer))[0];
            } else if (lruType == "Seatend" || lruType == "Overhead") {
                nearestColSpacerEle = angular.element(".deckWrapper").find(angular.element("." + nearestColumn.columnSpacer))[0];
            }
        }
        
        if (nearestColumn.direction == "left") {
            //angular.element(nearestColSpacerEle)[0].getBoundingClientRect().right
            nearestColumnSpacerDiff = nearestColSpacerEle.getBoundingClientRect().right - startEleCoored.left;
            pattern1 = (startEleCoored.left - $("#seatConnection")[0].getBoundingClientRect().left) - columnTraverse + nearestColumnSpacerDiff;
            //pattern1 = (angular.element("."+nearestColumn.columnSpacer)[0].getBoundingClientRect().right - $("svg")[0].getBoundingClientRect().left) - columnTraverse;
            
            
            leftLineArrowPos = ((startEleCoored.left - $("#seatConnection")[0].getBoundingClientRect().left) + pattern1) / 2;
            downLineArrowPos = (seatVerticalCenter + pattern2) / 2;
            rightLineArrowPos = (pattern1 + pattern3) / 2;
            $scope.createSvgLine((startEleCoored.left - $("#seatConnection")[0].getBoundingClientRect().left), seatVerticalCenter, pattern1, seatVerticalCenter, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' +(startEleCoored.left - $("#seatConnection")[0].getBoundingClientRect().left) + ' ' + seatVerticalCenter + ' L ' + leftLineArrowPos + ' ' + seatVerticalCenter + ' L ' + pattern1 + ' ' + seatVerticalCenter, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern1, seatVerticalCenter, pattern1, pattern2, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern1 + ' ' + seatVerticalCenter + ' L ' + pattern1 + ' ' + downLineArrowPos + ' L ' + pattern1 + ' ' + pattern2, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern1, pattern2, pattern3, pattern2, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern1 + ' ' + pattern2 + ' L ' + rightLineArrowPos + ' ' + pattern2 + ' L ' + pattern3 + ' ' + pattern2, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern3, pattern2, pattern3, pattern4, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern3 + ' ' + pattern2 + ' L ' + pattern3 + ' ' + pattern4, false, true, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
        } else if (nearestColumn.direction == "right") {
            nearestColumnSpacerDiff = nearestColSpacerEle.getBoundingClientRect().left - startEleCoored.right;
            
            pattern1 = (startEleCoored.right - $("#seatConnection")[0].getBoundingClientRect().left) + columnTraverse + nearestColumnSpacerDiff;
            //pattern1 = (angular.element("."+nearestColumn.columnSpacer)[0].getBoundingClientRect().left - $("svg")[0].getBoundingClientRect().left) + columnTraverse;
            
            rightLineArrowPos = ((startEleCoored.right - $("#seatConnection")[0].getBoundingClientRect().left) + pattern1) / 2;
            downLineArrowPos = (seatVerticalCenter + pattern2) / 2;
            leftLineArrowPos = (pattern1 + pattern3) / 2;
            $scope.createSvgLine((startEleCoored.right - $("#seatConnection")[0].getBoundingClientRect().left), seatVerticalCenter, pattern1, seatVerticalCenter, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' +(startEleCoored.right - $("#seatConnection")[0].getBoundingClientRect().left) + ' ' + seatVerticalCenter + ' L ' + rightLineArrowPos + ' ' + seatVerticalCenter + ' L ' + pattern1 + ' ' + seatVerticalCenter, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern1, seatVerticalCenter, pattern1, pattern2, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern1 + ' ' + seatVerticalCenter + ' L ' + pattern1 + ' ' + downLineArrowPos + ' L ' + pattern1 + ' ' + pattern2, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern1, pattern2, pattern3, pattern2, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern1 + ' ' + pattern2 + ' L ' + leftLineArrowPos + ' ' + pattern2 + ' L ' + pattern3 + ' ' + pattern2, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern3, pattern2, pattern3, pattern4, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern3 + ' ' + pattern2 + ' L ' + pattern3 + ' ' + pattern4, false, true, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
        } else if (nearestColumn.direction == "bottom") {
            nearestColumnSpacerDiff = nearestColSpacerEle.getBoundingClientRect().left - startEleCoored.right;
            pattern1 = (startEleCoored.right - $("#seatConnection")[0].getBoundingClientRect().left) + columnTraverse + nearestColumnSpacerDiff;
            rowTraverseMiddleSeat = $scope.getTravelledPathCount(startSeatRow.attr("class").replace("rowSpacer row", "rowSpacer"));
            /*Middle Seat Pattern*/
            patternY1 = startEleCoored.bottom - svgDiff;
            patternX = ((hostPort.left + (hostPort.right - hostPort.left) / 2) - $("#seatConnection")[0].getBoundingClientRect().left);
            patternY2 = (startSeatRow[0].getBoundingClientRect().top - svgDiff) + rowTraverseMiddleSeat;
            
            rightLineArrowPos = ((startEleCoored.right - $("#seatConnection")[0].getBoundingClientRect().left) + pattern1) / 2;
            downLineArrowPos = (seatVerticalCenter + pattern2) / 2;
            leftLineArrowPos = (pattern1 + pattern3) / 2;
            
            $scope.createSvgLine(patternX, patternY1, patternX, patternY2, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + patternX + ' ' + patternY1 + ' L ' + patternX + ' ' + patternY2, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(patternX, patternY2, pattern1, patternY2, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + patternX + ' ' + patternY2 + ' L ' + rightLineArrowPos + ' ' + patternY2 + ' L ' + pattern1 + ' ' + patternY2, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern1, patternY2, pattern1, pattern2, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern1 + ' ' + patternY2 + ' L ' + pattern1 + ' ' + downLineArrowPos + ' L ' + pattern1 + ' ' + pattern2, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern1, pattern2, pattern3, pattern2, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern1 + ' ' + pattern2 + ' L ' + leftLineArrowPos + ' ' + pattern2 + ' L ' + pattern3 + ' ' + pattern2, true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(pattern3, pattern2, pattern3, pattern4, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + pattern3 + ' ' + pattern2 + ' L ' + pattern3 + ' ' + pattern4, false, true, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
        }
        
        
        /*left-bottom-right-top
        ______ start seat
        |
        |
        |	   end Seat
        |		|
        ---------
        
         */
    };
    
    /*
     * Calculate the traverse paths inside a partilucar Column Spacer
     * @param {String} - Column Spacer
     */
    $scope.getTravelledPathCount = function (travellingItem) {
        
        //check if path exists. if not add the path and set default count as 1
        if (travellingItem == 'columnSpacer0') {
            // console.log('+++++++++' + $scope.traversedItemInPath[travellingItem]);
        }
        if ($scope.traversedItemInPath[travellingItem]) {
            $scope.traversedItemInPath[travellingItem].count = parseInt($scope.traversedItemInPath[travellingItem].count, 10) + 1;
        } else {
            $scope.traversedItemInPath[travellingItem] = {
            };
            $scope.traversedItemInPath[travellingItem].count = 1;
        }
        return $scope.traversedItemInPath[travellingItem].count * 10;
    };
    
    
    /*
     * Draw the near paths(left, right, etc) based on port info and start and end elements.
     * @param {Number} - HostPortId
     *        {Number} - NeighbourPortId
     *        {String} - Seat direction
     *        {Element} - Start Element
     *        {Element} - End Element
     *        {String} - LRU Type
     */
    $scope.drawnearestpath = function (hostPortId, neighborPortId, seatPosition, startElement, endElement, lruType) {
//        var svgRect = $("svg")[0].getBoundingClientRect();
        var svgRect = $("#seatConnection")[0].getBoundingClientRect(),
        svgDiffTop = svgRect.top,
        svgDiffLeft = svgRect.left,
//        startEleCoord = angular.element("#" + startElement[0].id + " #" + hostPortId)[0].getBoundingClientRect(),
//        endEleCoord = angular.element("#" + endElement[0].id + " #" + neighborPortId)[0].getBoundingClientRect(),
        startEleCoord = $("#" + startElement[0].id + " #" + hostPortId)[0].getBoundingClientRect(),
        endEleCoord = $("#" + endElement[0].id + " #" + neighborPortId)[0].getBoundingClientRect(),
        startEleTop = startEleCoord.top - svgDiffTop,
        startEleBottom = startEleCoord.bottom - svgDiffTop,
        startEleLeft = startEleCoord.left - svgDiffLeft,
        startEleRight = startEleCoord.right - svgDiffLeft,
        endEleTop = endEleCoord.top - svgDiffTop,
        endEleBottom = endEleCoord.bottom - svgDiffTop,
        endEleLeft = endEleCoord.left - svgDiffLeft,
        endEleRight = endEleCoord.right - svgDiffLeft,
        eleBottomMiddle = (startEleTop) + parseInt(startEleCoord.height * 0.5),
        elecenter = ((startEleCoord.left + (startEleCoord.right - startEleCoord.left) / 2) - svgDiffLeft),
        seatConnectionObj = {
            "left": {
                "pathEnd": eleBottomMiddle
            },
            "right": {
                "pathEnd": eleBottomMiddle
            },
            "top": {
                "pathEnd": elecenter
            },
            "bottom": {
                "pathEnd": elecenter
            }
        },
        linePathLinking = Math.floor(Math.random() * (50000 - 100000) + 100000),
        rowTraverse = "", endSeatRow = "";
        
        if (seatPosition !== 'left' && seatPosition !== 'right' && seatPosition !== 'top' && seatPosition !== 'bottom') {
            if (seatPosition == 'bottomrightdiagonal' || seatPosition == 'bottomleftdiagonal') {
                endSeatRow = endElement.parents(".rows").prev(".rowSpacer");
                rowTraverse = $scope.getTravelledPathCount(endSeatRow.attr("class").replace("rowSpacer row", "rowSpacer"));
            } else if (seatPosition == 'toprightdiagonal' || seatPosition == 'topleftdiagonal') {
                endSeatRow = endElement.parents(".rows").next(".rowSpacer");
                rowTraverse = $scope.getTravelledPathCount(endSeatRow.attr("class").replace("rowSpacer row", "rowSpacer"));
            }
        }
        
        switch (seatPosition) {
            case "left": //right to left
            $scope.drawPath(lruType, startEleLeft, seatConnectionObj[seatPosition].pathEnd, endEleRight + 5, seatConnectionObj[seatPosition].pathEnd, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            break;
            case "right"://left to right
            $scope.drawPath(lruType, startEleRight, seatConnectionObj[seatPosition].pathEnd, endEleLeft - 5, seatConnectionObj[seatPosition].pathEnd, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            break;
            case "top":
            $scope.drawPath(lruType, seatConnectionObj[seatPosition].pathEnd, startEleTop, seatConnectionObj[seatPosition].pathEnd, endEleBottom + 5, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            break;
            case "bottom":
            $scope.drawPath(lruType, seatConnectionObj[seatPosition].pathEnd, startEleBottom, seatConnectionObj[seatPosition].pathEnd, endEleTop - 5, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            break;
            case "bottomrightdiagonal":
            $scope.createSvgLine(startEleRight, startEleBottom, startEleRight,(startEleBottom + rowTraverse), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + startEleRight + ' ' + startEleBottom + ' L ' + startEleRight + ' ' +(startEleBottom + rowTraverse), true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(startEleRight,(startEleBottom + rowTraverse), endEleLeft,(endEleTop - rowTraverse), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + startEleRight + ' ' +(startEleBottom + rowTraverse) + ' L ' + endEleLeft + ' ' +(endEleTop - rowTraverse), true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(endEleLeft, (endEleTop - rowTraverse), endEleLeft, (endEleTop -5), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + endEleLeft + ' ' +(endEleTop - rowTraverse) + ' L ' + endEleLeft + ' ' +(endEleTop - 5), false, true, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            break;
            case "bottomleftdiagonal":
            $scope.createSvgLine(startEleLeft, startEleBottom, startEleLeft,(startEleBottom + rowTraverse), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + startEleLeft + ' ' + startEleBottom + ' L ' + startEleLeft + ' ' +(startEleBottom + rowTraverse), true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(startEleLeft,(startEleBottom + rowTraverse), endEleRight,(endEleTop - rowTraverse), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + startEleLeft + ' ' +(startEleBottom + rowTraverse) + ' L ' + endEleRight + ' ' +(endEleTop - rowTraverse), true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(endEleRight, (endEleTop - rowTraverse), endEleRight, (endEleTop -5), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + endEleRight + ' ' +(endEleTop - rowTraverse) + ' L ' + endEleRight + ' ' +(endEleTop - 5), false, true, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            break;
            case "toprightdiagonal":
            $scope.createSvgLine(startEleRight, startEleTop, startEleRight,(startEleTop - rowTraverse), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + startEleRight + ' ' + startEleTop + ' L ' + startEleRight + ' ' +(startEleTop - rowTraverse), true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(startEleRight,(startEleTop - rowTraverse), endEleLeft,(endEleBottom + rowTraverse), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + startEleRight + ' ' +(startEleTop - rowTraverse) + ' L ' + endEleLeft + ' ' +(endEleBottom + rowTraverse), true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(endEleLeft, (endEleBottom + rowTraverse), endEleLeft, (endEleBottom + 5), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + endEleLeft + ' ' +(endEleBottom + rowTraverse) + ' L ' + endEleLeft + ' ' +(endEleBottom + 5), false, true, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            break;
            case "topleftdiagonal":
            $scope.createSvgLine(startEleLeft, startEleTop, startEleLeft,(startEleTop - rowTraverse), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + startEleLeft + ' ' + startEleTop + ' L ' + startEleLeft + ' ' +(startEleTop - rowTraverse), true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(startEleLeft,(startEleTop - rowTraverse), endEleRight,(endEleBottom + rowTraverse), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + startEleLeft + ' ' +(startEleTop - rowTraverse) + ' L ' + endEleRight + ' ' +(endEleBottom + rowTraverse), true, false, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(endEleRight, (endEleBottom + rowTraverse), endEleRight, (endEleBottom + 5), linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + endEleRight + ' ' +(endEleBottom + rowTraverse) + ' L ' + endEleRight + ' ' +(endEleBottom + 5), false, true, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
            break;
        }
    };
    
    /*
     * Draw the  bubble path(left, right, etc) based on port info and start and end elements.
     * @param {Number} - HostPortId
     *        {Number} - NeighbourPortId
     *        {String} - Seat direction
     *        {Element} - Start Element
     *        {Element} - End Element
     *        {String} - LRU Type
     */
    $scope.drawbubblepath = function (seatPosition, startElement, endElement, lruType, hostPortId, neighborPortId) {
        var svgRect = $("#seatConnection")[0].getBoundingClientRect(),
        svgDiffTop = svgRect.top,
        svgDiffLeft = svgRect.left,
        startEleCoord = angular.element("#" + startElement[0].id)[0].getBoundingClientRect(),
        endEleCoord = angular.element("#" + endElement[0].id)[0].getBoundingClientRect(),
        startEleTop = startEleCoord.top - svgDiffTop,
        startEleBottom = startEleCoord.bottom - svgDiffTop,
        startEleLeft = startEleCoord.left - svgDiffLeft,
        startEleRight = startEleCoord.right - svgDiffLeft,
        endEleBottom = endEleCoord.bottom - svgDiffTop,
        endEleLeft = endEleCoord.left - svgDiffLeft,
        endEleRight = endEleCoord.right - svgDiffLeft,
        eleBottomMiddle = (startEleTop) + parseInt(startEleCoord.height * 0.5),
        elecenter = ((startEleCoord.left + (startEleCoord.right - startEleCoord.left) / 2) - svgDiffLeft),
        seatConnectionObj = {
            "left": {
                "pathEnd": eleBottomMiddle
            },
            "right": {
                "pathEnd": eleBottomMiddle
            },
            "top": {
                "pathEnd": elecenter
            },
            "bottom": {
                "pathEnd": elecenter
            }
        },
        linePathLinking = Math.floor(Math.random() * (50000 - 100000) + 100000),
        rowTraverse = "", endSeatRow = "",
        startElementBubble = angular.element("#" + startElement[0].id.replace("bubble", ""));
        endElement = angular.element("#" + endElement[0].id.replace("bubble", ""));
        if (seatPosition == 'middle') {
            endSeatRow = endElement.parents(".rows").prev(".rowSpacer");
            rowTraverse = $scope.getTravelledPathCount(endSeatRow.attr("class").replace("rowSpacer row", "rowSpacer"));
        }
        
        switch (seatPosition) {
            case "left": //right to left
            $scope.drawPath(lruType, startEleLeft, seatConnectionObj[seatPosition].pathEnd, endEleRight + 5, seatConnectionObj[seatPosition].pathEnd, linePathLinking, startElementBubble, endElement, hostPortId, neighborPortId);
            break;
            case "right"://left to right
            $scope.drawPath(lruType, startEleRight, seatConnectionObj[seatPosition].pathEnd, endEleLeft - 5, seatConnectionObj[seatPosition].pathEnd, linePathLinking, startElementBubble, endElement, hostPortId, neighborPortId);
            break;
            case "middle":
            $scope.createSvgLine(elecenter, startEleBottom, elecenter,(startEleBottom + rowTraverse), linePathLinking, startElementBubble, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + elecenter + ' ' + startEleBottom + ' L ' + elecenter + ' ' +(startEleBottom + rowTraverse), true, false, linePathLinking, startElementBubble, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine(elecenter,(startEleBottom + rowTraverse),(endEleLeft + 5),(endEleBottom + rowTraverse), linePathLinking, startElementBubble, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' + elecenter + ' ' +(startEleBottom + rowTraverse) + ' L ' +(endEleLeft + 5) + ' ' +(endEleBottom + rowTraverse), true, false, linePathLinking, startElementBubble, endElement, hostPortId, neighborPortId);
            $scope.createSvgLine((endEleLeft + 5), (endEleBottom + rowTraverse), (endEleLeft + 5), (endEleBottom + 5), linePathLinking, startElementBubble, endElement, hostPortId, neighborPortId);
            $scope.createSvgPath(lruType, 'M ' +(endEleLeft + 5) + ' ' +(endEleBottom + rowTraverse) + ' L ' +(endEleLeft + 5) + ' ' +(endEleBottom + 5), false, true, linePathLinking, startElementBubble, endElement, hostPortId, neighborPortId);
            break;
        }
    };
    
    /*
     * Draw near SVG path based on x1, y1, x2, y2.
     * @param {String} - LRU Type
     * 		  {Number} - X1
     *        {Number} - Y1
     *		  {Number} - X2
     *		  {Number} - Y2
     */
    $scope.drawPath = function (lruType, partStartX, partStartY, lineStart, lineEnd, linePathLinking, startElement, endElement, hostPortId, neighborPortId) {
        $scope.createSvgLine(partStartX, partStartY, lineStart, lineEnd, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
        $scope.createSvgPath(lruType, 'M ' + partStartX + ' ' + partStartY + ' L ' + lineStart + ' ' + lineEnd, false, true, linePathLinking, startElement, endElement, hostPortId, neighborPortId);
    };
    
    
    /*
     * Draw SVG path using the traverse path for near/far seats
     * @param {String} - LRU type
     *        {String} - SVG Traverse path
     *        {Boolean} - Specifying marker mid is present or not for the path
     *        {Boolean} - Specifying marker end is present or not for the path
     */
    $scope.createSvgPath = function (lruType, traversePath, boolMarkerMid, boolMarkerEnd, linePathLinking, startElement, endElement, hostPortId, neighborPortId) {
        var path = "",
        tooltipData = '<div class= "textAlignLeft">Host Name : ' + startElement.selector.substring(1) + '<br>Host Port : ' + hostPortId + '<br>Neighbor Name : ' + endElement.selector.substring(1) + '<br>Neighbor Port : ' + neighborPortId + '</div>';
        path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', lruType);
        path.setAttribute('d', traversePath);
        path.setAttribute('fill', '#000');
        path.setAttribute('stroke', '#000');
        path.setAttribute('data-toggle', 'tooltip');
        path.setAttribute('data-placement', 'left');
        path.setAttribute('data-original-title', tooltipData);
        path.setAttribute('data-container', 'body');
        path.setAttribute('data-html', 'true');
        path.setAttribute('data-linking', linePathLinking);
        path.setAttribute('data-hostname', startElement.selector.substring(1));
        path.setAttribute('data-neighbourname', endElement.selector.substring(1));
        //path.setAttribute('stroke-width','2');
        //path.setAttribute('stroke-dasharray','1,3');
        if (boolMarkerMid) {
            path.setAttribute('marker-mid', 'url(#markermid)');
        }
        if (boolMarkerEnd) {
            path.setAttribute('marker-end', 'url(#markerArrow)');
        }
        $("svg").append(path);
    };
    
    /*
     * Create SVG line based on x1, y1, x2, y2. Create a white patch so that white space
     * is present when 2 connections overlap
     * @param {Number} - X1
     *        {Number} - Y1
     *		  {Number} - X2
     *		  {Number} - Y2
     */
    $scope.createSvgLine = function (x1, y1, x2, y2, linePathLinking, startElement, endElement, hostPortId, neighborPortId) {
        var path = "",
        tooltipData = '<div class= "textAlignLeft">Host Name : ' + startElement.selector.substring(1) + '<br>Host Port : ' + hostPortId + '<br>Neighbor Name : ' + endElement.selector.substring(1) + '<br>Neighbor Port : ' + neighborPortId + '</div>';
        path = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        path.setAttribute('stroke-linecap', 'butt');
        path.setAttribute('x1', x1);
        path.setAttribute('y1', y1);
        path.setAttribute('x2', x2);
        path.setAttribute('y2', y2);
        path.setAttribute('stroke', '#fff');
        path.setAttribute('stroke-width', '5');
        path.setAttribute('data-toggle', 'tooltip');
        path.setAttribute('data-placement', 'left');
        path.setAttribute('data-original-title', tooltipData);
        path.setAttribute('data-container', 'body');
        path.setAttribute('data-html', 'true');
        path.setAttribute('data-linking', linePathLinking);
        path.setAttribute('data-hostname', startElement.selector.substring(1));
        path.setAttribute('data-neighbourname', endElement.selector.substring(1));
        $("svg").append(path);
    };
    
    /*
     * Called when deck is changed by user from dropdown
     */
    
    $('#dbDisplayGraphicalView').on('change', '#deckSelection', function (e) {
        /* Remove existing ports to avaid multiple creation*/
		$(".topPort, .bottomPort, .rightPort, .leftPort").html("");
		angular.element("#lruLoaderModal").show();
        
        $timeout(function () {
            var deck = $("#deckSelection option:selected").text();
            $scope.createDeck('deckChange', $scope.$parent.flightDecks, deck, $scope.flightDecks[Object.keys($scope.flightDecks)[$("#deckSelection").prop('selectedIndex')]]);
            angular.element("#lruLoaderModal").hide();
        },
        10);
    });
    
    
    /*
     * Called when LRU Type is changed by user from dropdown selection
     * Displays graphical view.
     * @param {String} - Selected table data from user.
     */
    $('#dbDisplayGraphicalView').on('change', "#lruType", function (e) {
        /* Remove existing ports to avaid multiple creation*/
		$(".topPort, .bottomPort, .rightPort, .leftPort").html("");
		angular.element("#lruLoaderModal").show();
        
        $timeout(function () {
            // on change of lru type we are redrawing the layout
            $scope.initlizeComponent($scope.seatJSON);
            if (e.target.value == "Seatend") {
                
                $("#displayColumnId").removeClass("displayNone");
                $("#dispCol").val("");
                $(".cabins .seaters >div").css("display", "none");
                $(".cabins .seaters >.floatLeft").css("display", "block");
            } else if (e.target.value == "Overhead") {
                $("#displayColumnId").addClass("displayNone");
                $(".cabins .seaters >div").css("display", "block");
            } else {
                $("#displayColumnId").addClass("displayNone");
                $(".cabins .seaters >div").css("display", "block");
            }
            angular.element("#lruLoaderModal").hide();
        },
        10);
    });
    
    /*
     * Intialises the drawing of SVG connections on the screen
     * @param {String} - LRU Type
     * 		  {JSON} - connection data for the specfic LRU Type
     */
    $scope.drawLayout = function (connectionType, connectionData) {
    	// console.log('inside drawLayout');
        //reset the path count
        $scope.traversedItemInPath = {
        };
        
        $("svg").children("line").remove();
        $("svg").children("path").remove();
       
        for (var seatConnection = 0; seatConnection < connectionData.length; seatConnection++) {
            if (connectionType == "Seatend") {
                if ((connectionData[seatConnection].neighborName.replace(/\D/g, '') <= 600) &&(connectionData[seatConnection].hostname.replace(/\D/g, '') <= 600)) {
                    $scope.createConnection(connectionData[seatConnection], connectionType);
                } 
            } else if (connectionType == "Headend") {
                $scope.createConnection(connectionData[seatConnection], connectionType);
            } else if (connectionType == "Overhead") {
                $scope.createConnection(connectionData[seatConnection], connectionType);
            }
        }
    };
    
    /*
     * Called when display column is changed by user from dropdown selection
     * Displays graphical view.
     * @param {String} - Selected table data from user.
     */
    
    $('#dbDisplayGraphicalView').on('change', '#dispCol', function (e) {
		/* Remove existing ports to avaid multiple creation*/
		$(".topPort, .bottomPort, .rightPort, .leftPort").html("");
        $(".seatconnectingbubble").remove();
        if ($('#dispCol').val() == "") {
            $(".cabins .seaters >div").css("display", "none");
            $(".cabins .seaters >.floatLeft").css("display", "block");
        } else {
            $(".cabins .seaters >div").css("display", "none");
            $(".cabins .seaters >.float" + $('#dispCol option:selected').text()).css("display", "block");
        }
        $scope.drawLayout("Seatend", $scope.seatJSON.NeighborLink.Seatend);
		
    });
    
    
    /*
     * Returns false when data is not sorted.
    @param {String} - data selected by user
     */
    $scope.notSorted = function (obj) {
		 if (! obj) {
            return[];
        }
        return Object.keys(obj);
    };
    /*
     * Called when svg is clicked by user.
     * Displays graphical view.
     * @param {String} - Selected table data from user.
     */
    $('#dbDisplayGraphicalView').on('click', 'svg', function (event) {
        var linking = 0;
        $("path[data-linking]").attr("stroke", "#000");
        $("line[data-linking]").attr("stroke", "#fff");
        if ($(event.target).data("linking")) {
            linking = 0;
            if (event.target.tagName.toLowerCase() == "line" || event.target.tagName.toLowerCase() == "path") {
                linking = $(event.target).data("linking");
				$("path[data-linking=" + linking + "]").attr("stroke", "#cc0001");
                $("line[data-linking=" + linking + "]").attr("stroke", "#FBDF0D");
            }
        }
    });
    /*
     * Called when seats is clicked by user.
     * Displays graphical view.
     * @param {String} - Selected table data from user.
     */
    $('#dbDisplayGraphicalView').on('click', '.seats', function (e) {
        var name = $(e.target).parents(".seats").attr('id');		
        $('path[data-neighbourname]').attr("stroke", "#000");
        $('line[data-neighbourname]').attr("stroke", "#fff");
        $('path[data-hostname]').attr("stroke", "#000");
        $('line[data-hostname]').attr("stroke", "#fff");
        if ($(e.target).parents(".seats").attr('id')) {
			$('path[data-neighbourname="' + name + '"]').attr("stroke", "#cc0001");
            $('line[data-neighbourname="' + name + '"]').attr("stroke", "#FBDF0D");
            $('path[data-hostname="' + name + '"]').attr("stroke", "#cc0001");
            $('line[data-hostname="' + name + '"]').attr("stroke", "#FBDF0D");
		 }
    });
}]);