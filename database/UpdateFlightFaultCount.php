<?php
/*===========================================================================
This variable is used to run the script for multiple airlines.

1. To run the script for specific airlines, add the Ids to the array separated comma.
   for example - array(1,2,3)

2. Leave it blank if the script has to be run for all the airlines */

$airlineIdArray= array(1);

//===========================================================================

require_once "connecti_database.php";
require_once "../common/functions.php";
require_once "../common/seatAnalyticsData.php";
require_once "../common/FlightFaultFunctions.php";

class UpdateFlightFaultCount {
    var $aircraftDB;
    var $flightLegId;
    var $aircraftId;
    var $cruiseTime;
    var $dateFlightLeg;
    
    public function process() {
        $this->echoLine('Process started - '. $this->getTime());
        global $dbConnection;
        global $mainDB;
        global $airlineIdArray;
        $aircraftArray = array();
        
        $this->dbConnection = $dbConnection;
        $airlineIdString = "";
        
        if(!empty($airlineIdArray)) {
            foreach ($airlineIdArray as $airlineId) {
                $airlineIdString .= "$airlineId,";
            }
            $airlineIdString = rtrim($airlineIdString,',');
            $airlineIdString = "where airlineId in ($airlineIdString)";
        }
        
        $query = "select airlineId, id as aircraftId, databaseName from $mainDB.aircrafts $airlineIdString order by airlineId, id";
//         $this->echoLine("Query: $query");
        $result = mysqli_query($this->dbConnection, $query);
        
        if($result) {
            while ($row = mysqli_fetch_assoc($result)) {
//                 $this->echoLine('Airline Id: ' . $row['airlineId']);
//                 $this->echoLine('Aricraft Id: ' . $row['aircraftId']);
//                 $this->echoLine('Database Name: ' . $row['databaseName']);
                
                $aircraftArray[$row['aircraftId']] = $row['databaseName'];
            }
            
            foreach ($aircraftArray as $aircraftId => $aircraftDB) {
//                 $this->echoLine('Aricraft Id: ' . $aircraftId);
//                 $this->echoLine('Database Name: ' . $aircraftDB);
                
                $flightLegQuery = "select idFlightLeg from $aircraftDB.sys_flight order by idFlightLeg";
                $flightLegResult = mysqli_query($this->dbConnection, $flightLegQuery);
                if($flightLegResult) {
                    while ($flightLegRow = mysqli_fetch_assoc($flightLegResult)) {
                        $flightLegId = $flightLegRow['idFlightLeg'];
                        
                        $resetsQuery = "select flightDate, totalCruise from $this->mainDB.resets_report where flightLegId=".$flightLegRow['idFlightLeg']." and acid=$aircraftId";
                        $resetsResult = mysqli_query($this->dbConnection, $resetsQuery);
                        
                        if($resetsResult) {
                            while ($resetsRow = mysqli_fetch_assoc($resetsResult)) {
                                $dateFlightLeg = $resetsRow['flightDate'];
                                $cruiseTime = $resetsRow['totalCruise'];
//                                 $this->echoLine('===============================');
//                                 $this->echoLine('Aricraft Id: ' . $aircraftId);
//                                 $this->echoLine('Database Name: ' . $aircraftDB);
//                                 $this->echoLine('Flight Leg Id: ' . $flightLegId);
//                                 $this->echoLine('Flight Date: ' . $dateFlightLeg);
//                                 $this->echoLine('Total Cruise Time: ' . $cruiseTime);
                                
                                $flightFaultFunctions = new FlightFaultFunctions();
                                $flightFaultFunctions->init($dbConnection, $mainDB, $aircraftDB, $flightLegId, $aircraftId, $dateFlightLeg, $cruiseTime);
//                                 $flightFaultFunctions->processFlightFaultCountForFlightLeg();

//                                 $this->echoLine('===============================');
                            }
                        }
                    }
                }
            }
                        
        } else {
            $this->echoLine('No result');
            $this->echoline(mysqli_error($dbConnection));
        }
        
        $this->echoLine('Process completed - '. $this->getTime());
    }
    
    function echoLine($msg) {
        echo $msg . "<br/>";
    }
    
    function getTime() {
        $now = new DateTime(null, new DateTimeZone('Asia/Kolkata'));
        return $now->format('Y-m-d H:i:s');
    }
}

$updateFlightFaultCount = new UpdateFlightFaultCount();
$updateFlightFaultCount->process();
?>