<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";

/**
 * Handles all the requests from lopaData.php
 * 
 * @author
 */
class LopaDataDAO {

    public $dbConnection;
    public $action = '';
    public $airlineId = '';
    public $airlineName = '';
    public $platform = '';
    public $configType = '';
    public $aircraftId = '';
    public $tailsign = '';
	public $airIds = '';
    
    

    /**
     * Constructor which creates the database connectivity
     */
    public function __construct($dbConnection) {
        $this->dbConnection = $dbConnection;
        $this->action = $_REQUEST['action'];
        $this->airlineId = $_REQUEST['airlineId'];
        $this->airlineName = $_REQUEST['airlineName'];
        $this->platform = $_REQUEST['platform'];
        $this->configType = $_REQUEST['configType'];
        $this->tailsign = $_REQUEST['tailsign'];
        $this->aircraftId = $_REQUEST['aircraftId'];
		$this->airIds = $_REQUEST['airIds'];
		
		//$this->airlineIds=rtrim(implode(",", $_SESSION['airlineIds']), ",");
		//$this->airlineIds=rtrim($this->airlineIds, ",");
    }
	//$airlineIds = rtrim(implode(",", $_SESSION['airlineIds']), ",");
	//$airlineIds = rtrim($airlineIds, ",");
	
	//public $airlineQuery = "SELECT id, acronym FROM airlines where id IN ($this->airlineIds) order by acronym";
	//public $airlineQuery = "SELECT id, acronym FROM airlines where id IN ($this->airIds) order by acronym";
    
    /**
     * Main method called to start the process
     */
    public function hadleRequest() {
        if ($this->action == 'GET_AIRLINES') {
            $this->getAirlines();
        } elseif ($this->action == 'GET_PLATFORMS') {
            $this->getPlatformsForAirline();
        } elseif ($this->action == 'GET_CONFIG_TYPE') {
            $this->getConfigTypesForAirlineAndPlatform();
        } elseif ($this->action == 'GET_TAILSIGN') {
            $this->getTailsignForAirline();
        }
    }
    
    /**
     * Retrieves distinct platform for an airline
     */
    public function getAirlines() {
		if($this->airIds==-1){
			$airlineQuery = "SELECT id, name,acronym FROM airlines order by name";
		}else{
			$airlineQuery = "SELECT id, name,acronym FROM airlines where id IN ($this->airIds) order by name";
		}
        $result = mysqli_query($this->dbConnection, $airlineQuery);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }
    
    /**
     * Retrieves distinct platform for an airline
     */
    public function getPlatformsForAirline() {
        $query = "select distinct platform from aircrafts where airlineID=$this->airlineId order by platform";
        $result = mysqli_query($this->dbConnection, $query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }
    
    /**
     * Retrieves distinct Config Type for an airline and Platform
     */
    public function getConfigTypesForAirlineAndPlatform() {
        $query = "select distinct Ac_Configuration as configType from aircrafts where airlineID=$this->airlineId and platform='$this->platform' order by Ac_Configuration";
        $result = mysqli_query($this->dbConnection, $query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }

    /**
     * Retrieves the Tailsign for an aircraft, platform and configType combination.
     */
    public function getTailsignForAirline() {
        $query = "select distinct(tailsign) from aircrafts where airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType' order by tailsign";
        $result = mysqli_query($this->dbConnection, $query);
        
        $platforms = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $platforms[] = $row;
            }
        }
        
        echo $json_response = json_encode($platforms);
    }
    
    
    
    /**
     * Checks if the input string is empty or null
     */
    function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }

    /**
     * Prints the message in a line
     */
    function echoline($msg) {
        echo "<br/>$msg<br/>";
    }
}

// echo 'Airline Ids: '. $_POST['airIds'];
$lopaDataDAO = new LopaDataDAO($dbConnection);
$lopaDataDAO->hadleRequest();

?>