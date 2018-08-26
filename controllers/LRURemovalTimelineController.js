app.controller('LRURemovalTimelineController', function($scope, $http, $log, $window, $timeout, $parse) {

	console.log("inside LRURemovalTimelineController");
	$('#loadingDiv').hide();
	var aircraftId = $window.aircraftId;
	var flightLegs = $window.flightLegs;
	
	$('#errorInfo').hide();
	$('#lruRemovalTimelineLoading').show();	

	var timeline;
	var startTime;
	var endTime;

	function getTimeLineData() {
		var data={
				aircraftId:aircraftId,
				maintenanceTimeline: true,
				flightLegs: flightLegs,
		};
		if(timeline != null) {
			if(timeline.body != null){
        		timeline.destroy();
        	}
	        $('#loadingTimeline').toggle();
	    }
		
	    $.ajax({
	        type: "POST",
	        dataType: "json",
	        url: "../ajax/getAircraftTimeLineData.php",
	        data: data,
	        success: function(data) {
	            createTimeline(data);
	            startTime = data.options.start;
	            endTime = data.options.end;
				getMaintenanceData();
	        },
	        error: function (err) {
	            console.log('Error', err);
	            $('#errorInfo').show();	            
	        }
	    });
	}

	function createTimeline(data) {
	    //$('#loadingTimeline').hide();

	    var container = document.getElementById('atimeline');

	    var groups = new vis.DataSet(
	        data.groups
	        );

	    var items = new vis.DataSet(
	        data.items
	        );

	    var options = {
	        start: data.options.start,
	        end: data.options.end,
	        min: data.options.min,
	        max: data.options.max,
	        clickToUse: true,
	        stack: false,
	        multiselect: true
	    };

	    timeline = new vis.Timeline(container, items,  groups, options);
	    timeline.on('contextmenu', function (props) {
	      //alert('Right click!');
	      props.event.preventDefault();
	    });
	    $('#lruRemovalTimelineLoading').hide();
	}
	
	function getMaintenanceData() {
		$.ajax({
			type: "GET",
			url: "../ajax/LRURemovalTimelineDAO.php",
			data: {
				aircraftId:aircraftId,
				startDateTime: startTime, 
				endDateTime: endTime,
				flightLegs: flightLegs
			},
			success: function(data) {
				// Need to convert from json string to json object to we can pass it to the table
				var jsonData = $.parseJSON(data);
				
				if(jsonData.length>0){
					$('#dataInfo').show();
					$('#tableInfo').show();
				}else{
					$('#errorInfo').show();	
				}
				$('#lruRemovalTimelineTable').bootstrapTable({
					data: jsonData,
					exportOptions: {
						fileName: 'LRURemovalDetails'
					}
				});
				setInterval(function(){ 
					$('#loadingTimeline').hide(); 
            	}, 1000);					
				
				$('#loadingTable').hide();
				
			},
			error: function (err) {
				console.log('Error', err);
				$('#dataInfo').hide();
	            $('#tableInfo').hide();
	            $('#errorInfo').show();	
	            $('#loadingTimeline').hide(); 
	            $('#loadingTable').hide();
			}
		});
	}
	
	getTimeLineData();
	
});