app.controller("EnhancedSeat",['$rootScope', 'dbdisplayFactory', '$scope', function ($rootScope, dbdisplayFactory, $scope) {
    
    $("body").tooltip({
        selector: '[data-toggle="tooltip"]'
    });
    
    $scope.seatJSON = {};
    var devArr =[];
   
    /*
     * Called on click of 'Enhancedseat.dat' option. Initialises the connections of
     * 'headend' of seat layout on the UI.
        @param {JSON} - data containing of initialization of components .
     * @param {string} - device info.
     */
    $scope.initlizeComponent = function (seatJSON, devArr) {
        $scope.seatJSON = seatJSON;
        $scope.devArr = devArr;
        $scope.flightDecks1 = $scope.seatJSON.Decks_cabins;
        $scope.createDeck(seatJSON.Decks_cabins);
    };
    
    /*
     * Create the deck based on the selected deck from user. Creates all the Rows and Columns
     * and places the seats.
     * @param {JSON} - data containing the decks info and cabin info.
     */
    $scope.createDeck = function (decks) {        
        var flightDesign = angular.element("#flightDesign"),
        deckDetails = "";
        
       $.each(decks, function (key, value) {
            deckDetails = key;
            deckDetails = deckDetails.toLowerCase().replace(" ", "");
            flightDesign.append('<div class="deckWrapper"><div class="flightFront"></div><div id="' + deckDetails + '" class="decks">' + $scope.createCabin(key, value) + '<div class="rightWing"></div><div class="leftWing"></div></div><div class="flightBack"></div></div>');
        });     
       
        //After creation of deck and cabin. create rows
        $scope.createRows(decks);
        $scope.createColumns(decks);  
        $scope.swap();  
        angular.element("#seatContainer").show();
    };
    
    /*
     * Swapping upper deck to first position.
     */
        $scope.swap = function (){
        	var upperDeck = $("#upperdeck").parents(".deckWrapper"),
        		mainDeck = upperDeck.prev().find("#maindeck").attr('id'),
        		row2 = $("#upperdeck").parents(".deckWrapper"), 
        		row1 = $("#maindeck").parents(".deckWrapper");
        	
        	if( mainDeck == 'maindeck'){
        		$("#upperdeck").parents(".deckWrapper").remove();                          
        	    $(row1).before(row2);
        	}
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
     * @param {String} - deck information
     * 		  {JSON}  - max seats and cabin details, Eg "Upper Deck":{"MaxSeat":[3,4,3],"cabin3":{"totalRows":10,"classAbbr":"UE","className":"UpperEconomy"}
     */
    $scope.createCabin = function (decks, cabinDetails) {
        var cabins = "",
        deckLength = Object.keys(cabinDetails).length - 1;
        
        for (var cabinNumber = 1; cabinNumber <= deckLength; cabinNumber++) {
            cabins += '<div class="cabins cabin' + cabinNumber + '" id="' + cabinDetails[ "cabin" + cabinNumber].classAbbr + '" data-deck="' + decks + '"><div class="cabinClassLabelWrapper" style="background-color:' + $scope.setCabinColors(cabinNumber) + '"><div class="cabinClassLabel">' + cabinDetails[ "cabin" + cabinNumber].className + '</div></div></div>';
        }        
        return cabins;
    };
    
    /*
     * Create Rows in each cabin for the selected deck
     * @param {JSON} - data containing deck details
     */
    $scope.createRows = function (decks) {
        var cabins = angular.element(".cabins"),
        currentElement = "",
        cabinNumber = "",
        deckDetails = "",
        rowCount = 1;
        
        for (var cabin = 0; cabin < cabins.length; cabin++) {
            currentElement = angular.element(cabins[cabin]);
            deckDetails = currentElement.attr("data-deck");
            cabinNumber = currentElement.attr("class").replace("cabins ", "");
            
            //if it is a different deck then reset the row count
            if (cabinNumber == "cabin1") {
                //rowCount = 1;
            }
            
            for (var row = 1; row <= decks[deckDetails][cabinNumber].totalRows; row++) {
                currentElement.append('<div class="rows row' + rowCount + '"><div class="rowIdentifier"></div></div>');
                rowCount++;
            }
        }
    };
    
    /*
     * Manage placement of seats by calculating column width based on number of columns in flight.
     * Get Column count as MaxSeat value. Iterate through rows and the columns for each row and
     * Create individual seat using the row and column index.
     * @param {JSON} - data containing deck info
     */
    $scope.createColumns = function (decks) {
        var rows = angular.element(".rows"),
        currentElement = "",
        parentNode = "",       
        deckDetails = "",
        column = 0,
        columnWidth = 0,
        rowCount = 0,
        verifyDetails = "",
        seatAlignMent = "floatLeft",
        textCenter = "";
        
        for (var row = 0; row < rows.length; row++) {
            currentElement = angular.element(rows[row]);
            parentNode = currentElement.parent();            
            deckDetails = parentNode.attr("data-deck");
            column = decks[deckDetails].MaxSeat;
            
            //if it is a different deck then reset the row count
            if (verifyDetails != deckDetails) {
                verifyDetails = deckDetails;
                //rowCount = 0;
            }
            
            rowCount++;
            
            for (var seater = 0; seater < column.length; seater++) {
                columnWidth = 100 / column.length;
                
                if (column.length == 2) {
                    $('.deckWrapper').addClass('twoSeater');
                    if (seater == 0) {
                        seatAlignMent = "floatLeft";
                    } else if (seater == 1) {
                        seatAlignMent = "floatRight";
                    }
                } else if (column.length == 3) {
                    if (seater == 0) {
                        seatAlignMent = "floatLeft";
                    } else if (seater == 1) {
                        seatAlignMent = "floatCenter";
                        textCenter = "text-align:center;";
                    } else if (seater == 2) {
                        seatAlignMent = "floatRight";
                    }
                }
                currentElement.append('<div class="seaters" style="' + textCenter + 'width:' + columnWidth + '%"><div class="' + seatAlignMent + '">' + $scope.seatPlaceMent(rowCount, seater, column[seater]) + '</div></div>');
            }
        }
        
        $scope.devArr = $scope.sortByKey($scope.devArr, "devName");
        $("#flightLegend").show();
        $scope.heighlightSeats("ClassId", "Class");
        $scope.removeRows();
        $scope.removeCabins();
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
		
        var seats = "";
        
        for (var seat = 0; seat < seatPosition; seat++) {
            var individualSeat = "",
            visibility = "visibilityHidden",
            seatLetter = "",
			currentSeat = rowIndex + "-" +(columnIndex + 1) + "-" + (seat + 1),
			seatRowNbr = "",
            pa = "",
            va = "",
            primary = "",
            secondary = "",
			DecodeVA = "",
            DecodePRAM = "",
            DecodeVOE = "",
            DecodeVOR = "",
            DecodeBGM = "",
			dimming = "",
			ClassId = "",            
            ClassName = "",
            subtype = "",
			rpo = "",
            server = "",
            sac = "",
            overHeadSeat = "";
            
            if ($scope.seatJSON.seats[currentSeat]) {
                visibility = "";
                seatLetter = $scope.seatJSON.seats[currentSeat].acSeatLetter;
                individualSeat = $scope.seatJSON.seats[currentSeat].seat;
                seatRowNbr = $scope.seatJSON.seats[currentSeat].seatRowNbr;
                angular.element(".row" + rowIndex + " .rowIdentifier").html($scope.seatJSON.seats[currentSeat].pavamapRow);
                
                pa = $scope.seatJSON.seats[currentSeat].pa;
                va = $scope.seatJSON.seats[currentSeat].va;
                primary = $scope.seatJSON.seats[currentSeat].primary;
                secondary = $scope.seatJSON.seats[currentSeat].secondary;
                DecodeVA = $scope.seatJSON.seats[currentSeat].DecodeVA;
                DecodePRAM = $scope.seatJSON.seats[currentSeat].DecodePRAM;
                DecodeVOE = $scope.seatJSON.seats[currentSeat].DecodeVOE;
                DecodeVOR = $scope.seatJSON.seats[currentSeat].DecodeVOR;
                DecodeBGM = $scope.seatJSON.seats[currentSeat].DecodeBGM;
                dimming = $scope.seatJSON.seats[currentSeat].dimming;
                sac = ($scope.seatJSON.seats[currentSeat].sac == "" ? "0": "1");
                ClassId = $scope.seatJSON.seats[currentSeat].ClassId;
                ClassName = $scope.seatJSON.seats[currentSeat].acClassName;
                
                rpo = $scope.seatJSON.seats[currentSeat].rpo;
                server = $scope.seatJSON.seats[currentSeat].server;
                subtype = $scope.seatJSON.seats[currentSeat].subtype;
                
                var devObj = {
                };
                
                if (($.inArray(primary, devArr)) == -1) {
                    devObj.devName = "DSU" + primary;
                    devObj.primary = 0;
                    devObj.secondary = 0;
                    $scope.devArr.push(devObj);
                    devArr.push(primary);
                }
                
                devObj = {
                };
                if (($.inArray(secondary, devArr)) == -1) {
                    devObj.devName = "DSU" + secondary;
                    devObj.primary = 0;
                    devObj.secondary = 0;
                    $scope.devArr.push(devObj);
                    devArr.push(secondary);
                }
                
                if ($scope.seatJSON.seats[currentSeat].pavamapRow > 600) {
                    overHeadSeat = "overheadSvduSeat";
                }
            }
            
            
            
            seats += '<div class="seats floatLeft ' + overHeadSeat + ' ' + visibility + '" ' +
            'data-rowIndex= "' + seatRowNbr + '"' +
            'data-ClassId= "' + ClassId + '"' +
            'data-ClassName= "' + ClassName + '"' +
            'data-DecodeVA= "' + DecodeVA + '"' +
            'data-DecodePRAM= "' + DecodePRAM + '"' +
            'data-DecodeVOE= "' + DecodeVOE + '"' +
            'data-DecodeVOR= "' + DecodeVOR + '"' +
            'data-DecodeBGM= "' + DecodeBGM + '"' +
            'data-primary= "' + primary + '"' +
            'data-secondary= "' + secondary + '"' +
            'data-pa = "' + pa + '"' +
            'data-va = "' + va + '"' +
            'data-dimming = "' + dimming + '"' +
            'data-rpo = "' + rpo + '"' +
            'data-sac = "' + sac + '"' +
            'data-subtype = "' + subtype + '"' +
            'data-server = "' + server + '"' +
            'title="SVDU - ' + individualSeat + '">' + seatLetter + '</div>';
        }
        return seats;		
    };
    
    
    /*
     * sort the data by key.
     * @param {string} - data of selected device.
     * @param {string} -  key value of selected device.
     */
    $scope.sortByKey = function (array, key) {
        return array.sort(function (a, b) {
            var x = a[key]; var y = b[key];
            return ((x < y) ? -1: ((x > y) ? 1: 0));
        });
    };
    
    /*
     * Display Graphical view on change of mode
     * @param {String} - Selected mode from user.
     */
    $('#dbDisplayGraphicalView').on('change', '#mode', function (e) {
        //$scope.heighlightSeats(e.target.value, e.target.selectedOptions[0].text);
        if (e.target.value == "") {
            $("#flightLegend").hide();
        } else if (e.target.value == "server") {
            var seats = $('.seats'),
            currentSeat = "",
            primary = 0,
            secondary = 0;
            
            $("#flightLegend").hide();
            $('#device').val("");
            for (var index = 0; index < $scope.devArr.length; index++) {
                $scope.devArr[index].primary = 0;
                $scope.devArr[index].secondary = 0;
            }
            for (var seat = 0; seat < seats.length; seat++) {
                currentSeat = angular.element(seats[seat]);
                if (! currentSeat.hasClass("visibilityHidden")) {
                    currentSeat.attr("class",(currentSeat.attr("data-rowIndex") > 600) ? "seats floatLeft overheadSvduSeat": "seats floatLeft");
                    primary = currentSeat.attr("data-primary");
                    secondary = currentSeat.attr("data-secondary");
                    $scope.devArr[primary - 1].primary = $scope.devArr[primary - 1].primary + 1;
                    if (primary != secondary) {
                        $scope.devArr[secondary - 1].secondary = $scope.devArr[secondary - 1].secondary + 1;
                    }
                }
            }
            $("#devType").removeClass("displayNone");
            $("#devType").show();
        } else {
            $("#devType").addClass("displayNone");
            $scope.heighlightSeats(e.target.value, $(this).find("option:selected").text());
            $("#flightLegend").show();
        }
    });
    
    /*
     * Display Graphical view on change of device.
     * @param {String} - Selected device from user.
     *
     */
    $('#dbDisplayGraphicalView').on('change', '#device', function (e) {
        if (e.target.value == "") {
            $("#flightLegend").hide();
            $('.overheadSvduSeat').attr("class", "seats floatLeft overheadSvduSeat blackSeat");
            $('.greenSeat, .blueSeat').attr("class", "seats floatLeft blackSeat");
        } else {
            var primaryCount = $scope.devArr[$(this).val()].primary;
            var secondaryCount = $scope.devArr[$(this).val()].secondary;
            var notAppl = $(".seats").length - ($(".visibilityHidden").length + primaryCount + secondaryCount);
            angular.element("#modeSelection").html("MEDIA SERVER");
            angular.element('#modeDetails').html('');
            $(".seats").removeClass("blackSeat greenSeat blueSeat");
            $(".seats[data-primary='" + (Number($(this).val()) + 1) + "']").addClass("greenSeat");
            $(".seats[data-secondary='" + (Number($(this).val()) + 1) + "']").addClass("blueSeat");
            angular.element('#modeDetails').append('<tr><td style="color: green">PRIMARY</td><td>' + primaryCount + '</td></tr>');
            angular.element('#modeDetails').append('<tr><td style="color: blue">SECONDARY</td><td>' + secondaryCount + '</td></tr>');
            angular.element('#modeDetails').append('<tr><td style="color: black">NOT APPLICABLE</td><td>' + notAppl + '</td></tr>');
            angular.element('#modeDetails').append('<tr><td>TOTAL</td><td>' + (primaryCount + secondaryCount + notAppl) + '</td></tr>');
            $("#flightLegend").show();
        }
    });
    
    
    /*
     * Highlight the seats based on the information for the specific mode selected by the user
     * @param {String} - Selected value from dropdown
     */
    $scope.heighlightSeats = function (currentTarget, text) {
        var seats = $('.seats'),
        currentSeat = "",
        outputArray =[],
        totalCount = 0,
        seatColors =[ "black", "green", "blue", "magenta", "red", "yellow", "cyan", "black"],
        countLabel = {
            "sac":[ {
                "0": "NO SAC", "1": "SAC"
            }], "pa":[ {
                "1": "PA Area 1", "2": "PA Area 2", "3": "PA Area 3", "4": "PA Area 4", "5": "PA Area 5", "6": "PA Area 6", "7": "PA Area 7"
            }], "va":[ {
                "1": "VA Area 1", "2": "VA Area 2", "3": "VA Area 3", "4": "VA Area 4", "5": "VA Area 5", "6": "VA Area 6", "7": "VA Area 7"
            }], "primary":[ {
                "1": "DSU 1", "2": "DSU 2", "3": "DSU 3", "4": "DSU 4", "5": "DSU 5", "6": "DSU 6"
            }], "secondary":[ {
                "1": "DSU 1", "2": "DSU 2", "3": "DSU 3", "4": "DSU 4", "5": "DSU 5", "6": "DSU 6"
            }], "DecodeVA":[ {
                "0": "NOT DECODE SVDU", "1": "Decode Audio Priority 1", "2": "Decode Audio Priority 2", "3": "Decode Audio Priority 3"
            }], "DecodePRAM":[ {
                "0": "NOT DECODE SVDU", "1": "Decode Audio Priority 1", "2": "Decode Audio Priority 2", "3": "Decode Audio Priority 3"
            }], "DecodeBGM":[ {
                "0": "NOT DECODE SVDU", "1": "Decode Audio Priority 1", "2": "Decode Audio Priority 2", "3": "Decode Audio Priority 3"
            }], "DecodeVOE":[ {
                "0": "NOT DECODE SVDU", "1": "Decode Audio Priority 1", "2": "Decode Audio Priority 2", "3": "Decode Audio Priority 3"
            }], "DecodeVOR":[ {
                "0": "NOT DECODE SVDU", "1": "Decode Audio Priority 1", "2": "Decode Audio Priority 2", "3": "Decode Audio Priority 3"
            }], "dimming":[ {
                "0": "No Dimming", "1": "Dimming"
            }], "rpo":[ {
                "0": "NO RPO PORT", "1": "RPO PORT 1", "2": "RPO PORT 2", "3": "RPO PORT 3", "4": "RPO PORT 4"
            }], "server":[ {
                "0": "Not Applicable", "1": "Primary", "2": "Secondary"
            }], "subtype":[ {
                "0": "Unknown SVDU Type", "1": "Seatback SVDU", "2": "In-Arm SVDU", "3": "OVH SVDU", "4": "OVH Retract SVDU", "5": "MMB SVDU"
            }]
        };
        
        angular.element("#modeSelection").html(text.toUpperCase());
        angular.element('#modeDetails').html('');
        
        //angular.element('#modeDetails').html('<tr><td>Element</td><td>Count</td></tr>');
        for (var seat = 0; seat < seats.length; seat++) {
            currentSeat = angular.element(seats[seat]);
            if (! currentSeat.hasClass("visibilityHidden")) {
                currentSeat.attr("class",(currentSeat.attr("data-rowIndex") > 600) ? "seats floatLeft overheadSvduSeat": "seats floatLeft");
                if (($.inArray(currentSeat.attr("data-" + currentTarget), outputArray)) == -1) {
                    outputArray.push(currentSeat.attr("data-" + currentTarget));
                }
            }
        }
        
        for (var outSeat = 0; outSeat < outputArray.sort().length; outSeat++) {
            var modes = $(".seats[data-" + currentTarget + "='" + outputArray[outSeat] + "']");
            modes.addClass(seatColors[outputArray[outSeat]] + "Seat");
            
            angular.element('#modeDetails').append('<tr><td class="legend_' + seatColors[outputArray[outSeat]] + '">' + ((currentTarget == "ClassId") ? $(modes[0]).attr("data-classname"): countLabel[currentTarget][0][outputArray[outSeat]]) + '</td><td>' + modes.length + '</td></tr>');
            totalCount += modes.length;
        }
        
        angular.element('#modeDetails').append('<tr><td>TOTAL</td><td>' + totalCount + '</td></tr>');
    };
}]);