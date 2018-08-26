app.controller("Lrumap",[ 'dbdisplayFactory', '$scope', '$http', '$location', '$compile', '$routeParams', '$rootScope', '$timeout', 'utilityFactory', function (dbdisplayFactory, $scope, $http, $location, $compile, $routeParams, $rootScope, $timeout, utilityFactory) {
    
    $("body").tooltip({
        selector: '[data-toggle="tooltip"]'
    });
    
    $scope.seatJSON = {};
    $scope.flightDecks = {};
    $scope.lrus =[];
    $scope.legendCabinDetails = {};
    $scope.defaultDeck = "";   
    $scope.traversedItemInPath = {
    };   
    
    /*
     * Called on click of 'NeighbourLink.dat' option. Initialises the connections of
     * 'headend' of seat layout on the UI.
     * @param {JSON} -  connection data
     */
    $scope.initlizeComponent = function (data) {
        angular.element("#lruMap").show();
    };
}]);