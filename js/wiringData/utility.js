app.factory("utilityFactory", function ($location) {
    var utilityFactory = {};
    
    
    $("#contentWrapper").on("keydown", "input", function (event) {
        if (event.target.dataset.allowspacing == "false") {
            if (event.which === 32)
            return false;
        }
    });
    
    $("#contentWrapper").on("change", "input", function (event) {
        if (event.target.dataset.allowspacing == "false") {
            this.value = this.value.replace(/\s/g, "");
        }
    });
    
    $("#contentWrapper").on("change", "textarea", function () {
        this.value = $.trim(this.value);
    });
    
	/*based on privilege object enable and disable required buttons(create New and Delete)*/
	
	utilityFactory.privilegeSetting = function(privilege, scope){
		if(Object.keys(privilege).length > 0){
			scope.createbtn = "buttonDisabled";
			//$(".createbtn").addClass("buttonDisabled");
			$(".createColumnbtn").addClass("buttonDisabled");
			scope.deleteRows = "deleteRowDisabled";
		} else {
			scope.deleteRows = "deleteRow";
		}
	};
	
    utilityFactory.highlightErrors = function (page) {
        
        var pageErrorData = JSON.parse(localStorage.errorFlow)[$location.path().replace("/", "")];
        
        //remove existing errors
        $("td[data-errors='true']").removeAttr("style data-toggle data-placement data-container data-original-title data-errors");
        $("tr").removeClass("outLine");
        
        //highlight current errors
        for (var error = 0; error < pageErrorData.length; error++) {
            if ((pageErrorData[error].id !== '') && (pageErrorData[error].id !== undefined) && (pageErrorData[error].id !== "0") && (pageErrorData[error].id !== "null") && (pageErrorData[error].id !== null)) {
                angular.element("#row_" + pageErrorData[error].id).addClass("outLine");
                angular.element("#row_" + pageErrorData[error].id + " ." + pageErrorData[error].parameter).css({
                    "background": "#FF6464", "color": "#fff", "font-size": "12px;", "font-family": "arial regular"
                }).attr({
                    "data-original-title": pageErrorData[error].reason, "data-errors": true, "data-toggle": "tooltip", "data-placement": "right", "data-container": "body"
                });
            } else if ((pageErrorData[error].orderId !== '') && (pageErrorData[error].orderId !== undefined)) {
                if (page == "headendconfig") {
                    $($("#rowCount tr")[pageErrorData[error].orderId - 1]).addClass("outLine");
                    $($("#rowCount tr")[pageErrorData[error].orderId - 1]).find("." + pageErrorData[error].parameter).css({
                        "background": "#FF6464", "color": "#fff", "font-size": "12px;", "font-family": "arial regular"
                    }).attr({
                        "data-original-title": pageErrorData[error].reason, "data-errors": true, "data-toggle": "tooltip", "data-placement": "right", "data-container": "body"
                    });
                } else if (page == "columnconfig") {
                    $($(".columnConfig tr")[pageErrorData[error].orderId - 1]).addClass("outLine");
                    $($(".columnConfig tr")[pageErrorData[error].orderId - 1]).find("." + pageErrorData[error].parameter).css({
                        "background": "#FF6464", "color": "#fff", "font-size": "12px;", "font-family": "arial regular"
                    }).attr({
                        "data-original-title": pageErrorData[error].reason, "data-errors": true, "data-toggle": "tooltip", "data-placement": "right", "data-container": "body"
                    });
                } else {
                    $("#rowCount tr:nth-child(" + pageErrorData[error].orderId + ")").addClass("outLine");
                    angular.element($("#rowCount tr:nth-child(" + pageErrorData[error].orderId + ") ." + pageErrorData[error].parameter)).css({
                        "background": "#FF6464", "color": "#fff", "font-size": "12px;", "font-family": "arial regular"
                    }).attr({
                        "data-original-title": pageErrorData[error].reason, "data-errors": true, "data-toggle": "tooltip", "data-placement": "right", "data-container": "body"
                    });
                }
            }
        }
		/* Move Table Scroll to current Error Position */
		
		for(var scroll=0;scroll < $(".scrollBody").length ;scroll++){
			$($(".scrollBody")[scroll]).scrollTop(0);
			var currentError = $($(".scrollBody")[scroll]).find("td[data-errors='true']:first"),
				top = 0;
				
			if(currentError.length > 0){
				top = currentError.offset().top - $($(".scrollBody")[scroll]).offset().top ;			
				$($(".scrollBody")[scroll]).scrollTop(top);
			}
		}		
    };
    
    utilityFactory.order = function (predicate, e, scope) {
        scope.reverse = (scope.predicate === predicate) ? ! scope.reverse: false;
        angular.element(".sorting").removeClass("asc").removeClass("desc");
        e.target.className = (scope.reverse == false) ? e.target.className + " asc": e.target.className + " desc";
        scope.predicate = predicate;
    };
    
    utilityFactory.showLoader = function () {    	
        var loaderTemplate = '<div class="modal fade" id="loaderModal" tabindex="-1" data-backdrop="static" data-keyboard="false" role="dialog" aria-labelledby="myModalLabel">' +
        '<div id="dataLoaderWrapper">' +
        'Loading....' +
        '</div>' +
        '</div>';
        
        $(".page-wrapper").append(loaderTemplate);
        
        angular.element("#loaderModal").modal("show");
        $(".modal-backdrop").css("z-index", "1015");
    };
    
    utilityFactory.hideLoader = function () {
        $(".modal-backdrop").remove();
        angular.element("#loaderModal").modal("hide").remove();
    };
    
    /*validation pattern matching*/
    utilityFactory.alfa = function (currentForm) {
        var alfaLayout = currentForm.find("#airlineName").val(),
        alfaExist = alfaLayout.match(/(^[A-Za-z]*$)/),
        bool = true;
        if (alfaExist !== null) {
            bool = false;
        }
        
        return bool;
    };
    
    utilityFactory.alfaNum = function (currentForm) {
        var alfaNumLayout = currentForm.find("#airlineName").val(),
        alfaNumExist = alfaNumLayout.match(/(^[A-Za-z0-9]*$)/),
        bool = true;
        if (alfaNumExist !== null) {
            bool = false;
        }
        
        return bool;
    };
    
    
    utilityFactory.alfaNumWithHash = function (currentForm) {
        var alfaNumWithHashLayout = currentForm.find("#airlineName").val(),
        alfaNumWithHashExist = alfaNumWithHashLayout.match(/(^[A-Za-z0-9#_-]*$)/),
        bool = true;
        if (alfaNumWithHashExist !== null) {
            bool = false;
        }
        
        return bool;
    };
    
    
    utilityFactory.scrollBody = function () {
        var isIE = /*@cc_on!@*/ false || ! ! document.documentMode,
        isFirefox = typeof InstallTrigger !== 'undefined';
        
        $(".scrollBody").on("scroll", function (e) {
            if (isIE || isFirefox) {
                angular.element(".formEditRow #formWrapper").css("margin-left",(e.target.scrollLeft -5) + "px");
            } else {
                angular.element(".formEditRow #formWrapper").css("margin-left", e.target.scrollLeft + "px");
            }
            angular.element(".form-control-fixed").css("right",(e.target.scrollLeft * -1) + "px");
            angular.element(".scrollHead table").css("margin-left",(e.target.scrollLeft * -1) + "px");
        });
    };
    return utilityFactory; // Return public API.
});