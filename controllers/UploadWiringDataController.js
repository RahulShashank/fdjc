var app = angular.module("myApp",[]);

app.controller('UploadWiringDataController', function($scope, $http) {
	hideAlert();
	init();

    function init() {
        loadAirlines();
    }

    function loadAirlines() {
    	$http.get("../common/AirlineDAO.php?action=GET_AIRLINES_BY_IDS")
		.success(function (data) {
			for (var i = 0; i < data.length; i++) {
				var airline = data[i];
				$('#airline').append('<option value=' + airline.id + '>' + airline.name + " (" + airline.acronym + ")" + '</option>');
		    }
			
			$('#airline').selectpicker('refresh');
		});
    }
    
    $('#airline').change(function(){
    	emptySelect('platform');
    	emptySelect('configType');
    	emptySelect('software');
    	hideAlert();
    	
    	loadPlatform();
    });
    
    function loadPlatform() {
    	var airlineId = $('#airline').val();
        
        var data = $.param({
            airlineId: airlineId,
            action: 'GET_PLATFORMS'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/UploadWiringDataDAO.php', data, config)
        .success(function (data, status, headers, config) {
			for (var i = 0; i < data.length; i++) {
				var platform = data[i];
				$('#platform').append('<option value="' + platform.platform + '">' + platform.platform + '</option>');
		    }
			
			$('#platform').selectpicker('refresh');
        });
    }
    
    $('#platform').change(function(){
    	emptySelect('configType');
    	emptySelect('software');
    	hideAlert();
    	
    	loadConfigTypes();
    });
    
    function loadConfigTypes() {
    	var airlineId = $('#airline').val();
    	var platform = $('#platform').val();
    	
        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            action: 'GET_CONFIG_TYPE'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/UploadWiringDataDAO.php', data, config)
        .success(function (data, status, headers, config) {
            $("#platform").css("border-color","");
            $("#error").empty();
            
			for (var i = 0; i < data.length; i++) {
				var configType = data[i];
				$('#configType').append('<option value="' + configType.configType + '">' + configType.configType + '</option>');
		    }
			
			$('#configType').selectpicker('refresh');
        });
    }
    
    $('#configType').change(function(){
    	emptySelect('software');
    	hideAlert();

    	loadSoftwares();
    });
    
    function loadSoftwares() {
    	var airlineId = $('#airline').val();
    	var platform = $('#platform').val();
        var configType = $('#configType').val();
        
        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            configType: configType,
            action: 'GET_SOFTWARES'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/UploadWiringDataDAO.php', data, config)
        .success(function (data, status, headers, config) {
            $("#configType").css("border-color","");
            $("#error").empty();
            
			for (var i = 0; i < data.length; i++) {
				var software = data[i];
				$('#software').append('<option value="' + software.software + '">' + software.software + '</option>');
		    }
			
			$('#software').selectpicker('refresh');
        });
    }

    $('#software').change(function(){
    	hideAlert();
    });
    
    $scope.validate = function() {
    	$("#software").css("border-color","");
    	$("#error").empty();
    };
    
	function emptySelect(selId) {
		$('#'+selId).empty();
		$('#'+selId).selectpicker('refresh');
	}
        
	function hideAlert() {
	    $('#alertDiv').hide();
	}

});

$(window).load(function(){
      $(".dz-hidden-input").prop("disabled",true);
});

function showAlert(message){
    $('#alertDiv').text(message);
    $('#alertDiv').show();
}

function showMessage(msg){
    noty({
        text: msg,
        layout: 'topCenter',
        type: "success", 
        timeout: 4000
    });
}                                            

$(document).ready(function(){
    $('.navbar-nav li').removeClass('active');
    $("#uploadWiringData").addClass("active");
    
    $('#wiringDataDropzone').click(function(e) {
        e.preventDefault();
        e.stopPropagation();

        if($("#airline").val() == ''){
        	showAlert('Please select Airline');
        } else if($("#platform").val() == ''){
        	showAlert('Please select Platform');
        } else if($("#configType").val() == ''){
        	showAlert('Please select Configuration');
        } else if($("#software").val() == ''){
        	showAlert('Please select Software');
        } else{
        	$('#wiringDataDropzone').dropzone({
        		clickable: true});
        }
        return false;
    });
});

Dropzone.options.wiringDataDropzone = {
	    maxFiles: 1,
	    acceptedFiles: ".dat",
	    init: function() {
    	    this.on("sending", function(file, xhr, formData){
    	    	var airlineId = $('#airline').val();
    	    	var platform = $('#platform').val();
    	        var configType = $('#configType').val();
    	        var software = $('#software').val();
    	    	
    	    	formData.append('action', 'UPLOAD_FILE');
        	    formData.append('airlineId',airlineId);
        	    formData.append('platform',platform);
        	    formData.append('configType',configType);
        	    formData.append('software',software);
    	    });

            this.on("success", function(file, responseText) {
                var message = '';

                if(responseText.indexOf("Error") > -1) {
                	showAlert(responseText);
                } else {
                	showMessage(responseText);
                }
	    	});

	    	this.on("error", function(file, responseText) {
		    	message = responseText;
		    	showAlert(responseText);
      		});

      		this.on("addedfile", function(file) {
	      		console.log("added file: " + file);
			});
		}
};
