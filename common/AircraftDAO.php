<?php
require_once "../database/connecti_database.php";

class AircraftDAO {

    public $dbConnection;
    public $action = '';
    public $mainDB = '';
    public $airlineId = '';
    
    /**
     * Constructor which creates the database connectivity
     */
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->airlineId = $_REQUEST['airlineId'];
    }
    
    /**
     * Main method called to start the process
     */
    public function hadleRequest() {
        if ($this->action == "GET_AIRCRAFT_TYPES_ACTION") {
            $this->getAircraftTypes();
        } else if ($this->action == "GET_AIRCRAFT_PLATFORMS_ACTION") {
            $this->getAircraftPlatforms();
        }
    }
    
    public function getAircraftTypes() {
        error_log("inside getAircraftTypes()");
        
        $aircraftTypes = array();
        
        $query = "SELECT type FROM $this->mainDB.aircraft_types ORDER BY type";
        $result = mysqli_query ($this->dbConnection, $query);
        if($result){
            while($row = mysqli_fetch_assoc($result)) {
                $aircraftTypes[] = $row;
            }
        }
        
        echo $json_response = json_encode($aircraftTypes);
    }
    
    public function getAircraftPlatforms() {
        error_log("inside getAircraftPlatforms()");
        
        $aircraftPlatforms = array();
        $query = "SELECT name FROM $this->mainDB.aircraft_platforms ORDER BY name";
        $result = mysqli_query($this->dbConnection, $query);
        while($row = mysqli_fetch_assoc($result)) {
            $aircraftPlatforms[] = $row;
        }
        
        echo $json_response = json_encode($aircraftPlatforms);
    }
    
    public function getTailsign($airlineId, $platform, $configType, $software) {
        $query = "select distinct tailsign from aircrafts where 1=1";
        
        if(!$this->isNullOrEmptyString($airlineId)) {
            $query .= " and airlineID=$airlineId";
        }
        
        if(!$this->isNullOrEmptyString($platform)) {
            $query .= " and platform='$platform'";
        }
        
        if(!$this->isNullOrEmptyString($configType)) {
            $query .= " and Ac_Configuration='$configType'";
        }
        
        if(!empty($software)) {
            error_log("Software: " . $software);
            
            if(is_array($software)) {
                $query .= " and software in(";
                foreach ($software as $software) {
                    $query .= "'" . $software . "',";
                }
                $query = rtrim($query, ",");
                
                $query .= ")";
            } else {
                $query .= " and software='$software'";
            }
        }
        
        $query .= " order by tailsign";
        
        error_log("Tailsign Query: ".$query);
        
        $result = mysqli_query($this->dbConnection, $query);
        
        $tailsign = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $tailsign[] = $row['tailsign'];
            }
        }
        
        return $tailsign;
    }
    
    /**
     * 
     * @param $airlineId
     * @return array aircrafts
     */
    public function getAircraftsForAirline($airlineId) {
        $query = "select ac.tailsign,ac.airlineId,al.acronym from $this->mainDB.aircrafts ac, $this->mainDB.airlines al where ac.airlineId=$airlineId and ac.airlineId=al.id and (upper(al.name) not like '%IRVINE%' or al.acronym not like 'IRV') order by ac.airlineId, ac.tailsign";
        
        $result = mysqli_query($this->dbConnection, $query);
        error_log("Aircraft Query: " . $query);
        
        $aircrafts = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $aircrafts[] = $row;
            }
        }
        
        return $aircrafts;
    }
    
    public function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }
}

$aircraftDAO = new AircraftDAO($dbConnection, $mainDB);
$aircraftDAO->hadleRequest();

?>