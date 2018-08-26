app.controller('aircraftFlightsCtrl', function($scope, $compile, $timeout, $http, $window) {
    $scope.events = [];
    $scope.eventSources = [$scope.events];

    function getData(startDay, startMonth, startYear, endDay, endMonth, endYear) {
    	$http({
        	url: "../ajax/getAircraftFlights.php",
       		method: "POST",
        	data: {
			'aircraftId': aircraftId,
			'startDate': startYear + '-' + startMonth + '-' + startDay,
			'endDate': endYear + '-' + endMonth + '-' + endDay,
		},
        	headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function (data, status, headers, config) {
	    //console.table(data);
            $scope.events.splice(0, $scope.events.length);// clear events array
            for(var i = 0; i < data.length; i++)
            {
                // console.log(data);
                // $scope.events[i] = {id:data[i].id, title: data[i].title,start: new Date(data[i].start), end: new Date(data[i].end),allDay: false};
                $scope.events.push({
                    id: data[i].id,
                    title: data[i].title,
                    start: new Date(data[i].start), 
                    end: new Date(data[i].end),
                    status: data[i].status,
                    allDay: false,
                    stick: true
                });
            }

    	}).error(function (data, status, headers, config) {});
    }

    $scope.eventRender = function(event, element, view ){
        if(event.status == "-1") {
            $(element).css("backgroundColor", "grey");
            $(element).css("border-color", "grey");
        } else if(event.status == "0") {
            $(element).css("backgroundColor", "#D1CF95");
            $(element).css("border-color", "#637B83");
            $(element).css("color", "#879700");
        } else if(event.status == "1") {
            $(element).css("backgroundColor", "orange");
            $(element).css("border-color", "orange");
        } else if(event.status == "2") {
            $(element).css("backgroundColor", "#fe5757");
            $(element).css("border-color", "#fe2e2e");
            $(element).css("color", "#8a3c3e");
        }
    };
    

    /* message on eventClick */
    $scope.alertOnEventClick = function( event, allDay, jsEvent, view ){
        console.log(event);
        var url = "flightSummary.php?aircraftId=" + aircraftId + "&flightLegs=" + event.id;
        console.log("url: " + url);
        $window.open(url);
    };

    $scope.viewRender = function(view, element) {
        $scope.events.splice(0, $scope.events.length);// clear events array
        getData(view.start.date(), view.start.month() + 1, view.start.year(), view.end.date(), view.end.month() + 1, view.end.year());
    }


    /* config object */
    $scope.uiConfig = {
        calendar:{
            height: 'auto',
            // height: 600,
            editable: false,
            allDaySlot: false,
            // slotDuration:"00:15:00",
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaTwoDay,agendaWeek,month'
            },
            eventRender: $scope.eventRender,
            eventClick: $scope.alertOnEventClick,
	    viewRender: $scope.viewRender
        }
    };
});

