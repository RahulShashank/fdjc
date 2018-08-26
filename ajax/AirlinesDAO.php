<?php
// Start the session
session_start ();

require_once "../database/connecti_database.php";
require_once('../admin/checkAdminPermission.php');

class AirlinesDAO {
    
    public $dbConnection;
    public $action = '';
    public $mainDB = '';
    public $name = '';
    public $acronym = '';
    
    /**
     * Constructor which creates the database connectivity
     */
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->name = $_REQUEST['name'];
        $this->acronym = $_REQUEST['acronym'];
    }
    
    /**
     * Main method called to handle the request and start the process
     */
    public function hadleRequest() {
        if ($this->action == 'GET_AIRLINES') {
            $this->getAirlines();
        } else if ($this->action == 'ADD_AIRLINE') {
            $this->addAirline();
        }
    }

    public function getAirlines() {
        error_log("AirlinesDAO->getAirlines() entered");
        $airlines = array();
        $query = "select name, acronym from $this->mainDB.airlines order by name";
        $result = mysqli_query($this->dbConnection, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $airlines[] = $row;
            }
        }
        
        echo $json_response = json_encode($airlines);
    }
    
    public function addAirline() {
        error_log("AirlinesDAO->addAirline() entered");
        $query = "INSERT INTO $this->mainDB.airlines (name, acronym) VALUES ('$this->name','$this->acronym')";
        $result = mysqli_query ($this->dbConnection, $query);
        if($result) {
            echo "Airline $this->name added successfully";
            mysqli_commit($this->dbConnection);
        } else {
            echo "Error when adding $this->name. " . mysqli_error ($this->dbConnection) . ".";
        }
    }
}

$airlinesDAO = new AirlinesDAO($dbConnection, $mainDB);
$airlinesDAO->hadleRequest();
?>