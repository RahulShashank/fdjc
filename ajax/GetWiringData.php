<?php
require_once "../database/connecti_database.php";

/**
 * Fetches the Host Name and Serial Number data from the individual databases and 
 * updates the serialnumber_info table along with Tail Sign and Airline Id.
 * 
 * Before we run this file, we need to update the database related details 
 * specific to the environment we urn.
 * 
 * @author 
 */
class WiringDataDAO {

    public $dbConnection;
    public $action = '';
    public $airlineId = '';
    public $platform = '';

    /**
    * Constructor which creates the database connectivity
    */
    public function __construct($dbConnection) {
        $this->dbConnection = $dbConnection;
        $this->action = $_REQUEST['action'];
        $this->airlineId = $_REQUEST['airlineId'];
        $this->platform = $_REQUEST['platform'];
    }// end __consruct

    /**
    * Main method called to start the process
    */
    public function getData() {
        
        if ($this->action == 'GET_PLATFORMS') {
//             echo "inside GET Platforms - $this->airlineId";
            $this->getPlatformsForAirline();
        } elseif ($this->action == 'GET_CONFIG_TYPE') {
            $this->getConfigTypesForAirlineAndPlatform();
        }
    }// end main
    
    /**
     * Retrieves distinct platform for an airline
     */
    public function getPlatformsForAirline() {
        $query = "select distinct platform from aircrafts where airlineID=$this->airlineId";
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
     * Retrieves distinct platform for an airline
     */
    public function getConfigTypesForAirlineAndPlatform() {
        $query = "select distinct Ac_Configuration as configType from aircrafts where airlineID=$this->airlineId and platform='$this->platform'";
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

// $airlineId = $_REQUEST['airlineId'];
// $action = $_REQUEST['action'];
// echo "Inside GetWiringData.php => $airlineId = $action";

$wiringDataDAO = new WiringDataDAO($dbConnection);
$wiringDataDAO->getData();

?>