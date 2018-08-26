<?php
require_once "../database/connecti_database.php";

class AircraftDAO {

    public $dbConnection;
    public $action = '';
    public $mainDB = '';
    public $airlineId = '';
    public $tailsign = '';
    public $noseNumber = '';
    public $msn = '';
    public $type = '';
    public $aircraftSeatConfiguration = '';
    public $aircraftConfiguration = '';
    public $platform = '';
    public $isp = '';
    public $eis = '';
    public $swBaseLine = '';
    public $customerSw = '';
    public $swinstalled = '';
    
    /**
     * Constructor which creates the database connectivity
     */
    public function __construct($dbConnection, $mainDB) {
        $this->dbConnection = $dbConnection;
        $this->mainDB = $mainDB;
        $this->action = $_REQUEST['action'];
        $this->airlineId = $_REQUEST['airlineId'];
        $this->tailsign = $_REQUEST['tailsign'];
        $this->noseNumber = $_REQUEST['noseNumber'];
        $this->msn = $_REQUEST['msn'];
        $this->type = $_REQUEST['type'];
        $this->aircraftSeatConfiguration = $_REQUEST['aircraftSeatConfiguration'];
        $this->aircraftConfiguration = $_REQUEST['aircraftConfiguration'];
        $this->platform = $_REQUEST['platform'];
        $this->isp = $_REQUEST['isp'];
        $this->eis = $_REQUEST['eis'];
        $this->swBaseLine = $_REQUEST['swBaseLine'];
        $this->software = $_REQUEST['customerSw'];
        $this->swinstalled = $_REQUEST['swinstalled'];
    }
    
    /**
     * Main method called to start the process
     */
    public function hadleRequest() {
        if ($this->action == "GET_AIRCRAFT_TYPES_ACTION") {
            $this->getAircraftTypes();
        } else if ($this->action == "GET_AIRCRAFT_PLATFORMS_ACTION") {
            $this->getAircraftPlatforms();
        } else if ($this->action == "GET_AIRCRAFTS") {
            $this->getAircrafts();
        } else if ($this->action == "GET_AIRCRAFT_SEAT_CONFIG") {
            $this->getAircraftSeatConfiguration();
        } else if ($this->action == "GET_AIRCRAFT_CONFIG") {
            $this->getAircraftConfiguration();
        } else if ($this->action == "ADD_AIRCRAFT") {
            $this->addAircraft();
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
    
    /**
     * Returns all the aircrafts
     */
    public function getAircrafts() {
        $query = "SELECT a.id, a.tailsign, b.name, a.type, a.msn, a.platform, a.software FROM $this->mainDB.aircrafts a, $this->mainDB.airlines b WHERE a.airlineId = b.id order by b.name";
        $result = mysqli_query($this->dbConnection, $query);
        error_log("Aircraft Query: " . $query);
        
        $aircrafts = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $aircrafts[] = $row;
            }
        }
        
        echo $json_response = json_encode($aircrafts);
    }
    
    /**
     * Returns all the Aircraft Seat Configuration
     */
    public function getAircraftSeatConfiguration() {
        $aircraftSeatConfig = array();
        $query = "SELECT id, configurationName FROM $this->mainDB.aircraft_seatinfo ORDER BY configurationName";
        $result = mysqli_query ($this->dbConnection, $query);
        if($result){
            while($row = mysqli_fetch_assoc($result)) {
                $aircraftSeatConfig[] = $row;
            }
        }
        
        echo $json_response = json_encode($aircraftSeatConfig);
    }
    
    /**
     * Returns all the Aircraft Configuration
     */
    public function getAircraftConfiguration() {
        $aircraftConfig = array();
        $query = "SELECT DISTINCT Ac_Configuration as configuration FROM $this->mainDB.aircrafts WHERE airlineId =$this->airlineId AND Ac_Configuration<>'' ORDER BY Ac_Configuration";
        error_log($query);
        $result = mysqli_query ($this->dbConnection, $query);
        if($result){
            while($row = mysqli_fetch_assoc($result)) {
                $aircraftConfig[] = $row;
            }
        }
        
        echo $json_response = json_encode($aircraftConfig);
    }
    
    /**
     * Adds an aircraft
     */
    public function addAircraft() {
        $status = $this->validateAddAircraftInputData();
        
        if($status['state'] == 0) {
            $airlineId = mysqli_real_escape_string($this->dbConnection, $this->airlineId);	//Escape the user input before using in mysql query.
            $query = "SELECT a.acronym FROM $this->mainDB.airlines a WHERE id = $airlineId";
            $result = mysqli_query ( $this->dbConnection, $query );
            
            $airlineAcronymName = '';
            
            //	In Case of errors in MySQL query execution.
            if(!$result){
                $status['state'] = -1;
                $status['message'] = "Error: Finding Airline";
                echo json_encode($status);
                exit ();
            }
            
            if (mysqli_num_rows ( $result ) > 0) {
                $row = mysqli_fetch_assoc( $result );
                $airlineAcronymName = $row ['acronym'];
            } else {
                //	If Airline not found.
                $status['state'] = -1;
                $status['message'] = "Error: Airline Not found ";
                echo json_encode($status);
                exit ();
            }
            
            $tailsign = mysqli_real_escape_string($this->dbConnection, $this->tailsign);	//Escape the user input before using in mysql query.
            $queryForTail = "SELECT * FROM $this->mainDB.aircrafts WHERE tailsign = '$tailsign'";
            $resultForTail = mysqli_query ( $this->dbConnection,$queryForTail );
            
            //	In Case of errors in MySQL query execution.
            if(!$resultForTail){
                $status['state'] = -1;
                $status['message'] = "Error in Tailsign ";
                echo json_encode($status);
                exit ();
            }
            
            // Case where Aircraft matching tailsign already exists.
            if(mysqli_num_rows($resultForTail) > 0){
                $status['state'] = -1;
                $status['message'] = "Error: Aircraft with matching Tailsign already exists";
                echo json_encode($status);
                exit ();
            }
            
            $aircraftDbName = $this->createDbNameFrmTail ( $tailsign, $airlineAcronymName );
            
            //Escape the user input before using in mysql query.
            $msn = mysqli_real_escape_string($this->dbConnection, $this->msn);
            $type = mysqli_real_escape_string($this->dbConnection, $this->type);
            if(isset($this->config) && !is_null($this->config) && $this->config != '' ){
                $config = mysqli_real_escape_string($this->dbConnection, $this->config);
                $config = intval($config);
            }else{
                $config = "NULL";
            }
            $configId=$this->getConfigIdForAircraft($this->airlineId,$this->aircraftConfiguration);
            $configId = mysqli_real_escape_string($this->dbConnection, $this->configId);
            $configId = intval($configId);
            error_log("configId after calling the method : $configId");
            $platform = mysqli_real_escape_string($this->dbConnection,$this->platform);
            $isp = mysqli_real_escape_string($this->dbConnection, $this->isp);
            $software = mysqli_real_escape_string($this->dbConnection, $this->software);
            $aircraftDbName = mysqli_real_escape_string($this->dbConnection, $aircraftDbName);
            $repairTailName = $this->getRepairTailNameForAircraftType($this->type, $this->msn);
            error_log("Repair Tail Name after calling the method : $repairTailName");
            
            // not allowing second entry for same aircraft Using TailSign.
            if (mysqli_num_rows ( $resultForTail ) == 0 and $tailsign != '') {
                $sql = "INSERT INTO aircrafts (tailsign,repair_TailName,noseNumber,msn,type,aircraftConfigId,platform,software,airlineId,flightLegIdCount,databaseName, isp,Ac_Configuration,SW_installed,EIS,SW_Baseline)
	       	          VALUES
	       	         ('$tailsign','$repairTailName','$this->noseNumber','$msn','$type', '$configId' ,'$platform','$software','$this->airlineId','1','$aircraftDbName','$isp','$this->aircraftConfiguration','$this->swinstalled','$this->eis','$this->swBaseLine')";
                
                if (! mysqli_query ( $this->dbConnection,$sql )) {
                    $status['state'] = -1;
                    $status['message'] = "Error: $sql " . mysqli_error ( $this->dbConnection );
                    echo json_encode($status);
                    exit ();
                }
                // 	mysqli_commit( $dbConnection );
            }
            
            // Create Database. TODO: handle the case if DB creation is unsuccessfull.
            $createDB = $this->createDataBaseForAircraft ( $aircraftDbName, $tailsign );
            
            
            /*	TODO: Idea of using commit & rollback was to fail the transaction, if new db creation is failure or any of the mysql_queries fail. But because of
             *	DDL statment in between (CREATE DB), transaction gets committed implicitly. Need to find a proper handling of this case.
             */
            if($createDB){
                error_log("Going to commit the DB changes");
                // 	mysqli_select_db ( $dbConnection, $mainDB);
                mysqli_commit($this->dbConnection);
            }else{
                mysqli_rollback( $this->dbConnection );
            }
            
            echo json_encode($status);
            exit();
            
        }
        
        echo json_encode($status);
    }
    
    function getConfigIdForAircraft($airlineId,$config) {
        
        global $dbConnection, $mainDB;
        $query = "select id,Ac_Configuration from $mainDB.aircrafts WHERE airlineId =$airlineId AND Ac_Configuration='$config' limit 1";
        error_log("ConfigId Query: $query");
        $result = mysqli_query($this->dbConnection, $query);        
        if($result) {
            $row = mysqli_fetch_assoc($result);
            $configId = $row['id'];
            error_log("Config Id : $configId");
        }
        
        return $configId;
    }
    private function createDbNameFrmTail($tailsign, $airlineAcronymName) {
        $dbName = "";
        $tail = str_replace ( "-", "_", $tailsign );
        
        if (is_null ( $tail )) {
            $dbName = $airlineAcronymName . "_" . $tailsign;
        } else {
            $dbName = $airlineAcronymName . "_" . $tail;
        }
        
        return $dbName;
    }
    
    private function getRepairTailNameForAircraftType($acType, $msn) {
        
        global $dbConnection, $mainDB;
        $repairTailName = "";
        
        $paddedmsn = str_pad($msn, 4, '0', STR_PAD_LEFT);
        
        $query = "select concat_ws('',repair_prefix, '$paddedmsn') as repairTailName from $mainDB.aircraft_types where type='$acType'";
        error_log("Repair Tail Name Query: $query");
        $result = mysqli_query($dbConnection, $query);
        
        if($result) {
            $row = mysqli_fetch_assoc($result);
            $repairTailName = $row['repairTailName'];
            error_log("Repair Tail Name : $repairTailName");
        }
        
        return $repairTailName;
    }
    
    // Generate New DataBase for new Aircraft.
    private function createDataBaseForAircraft($aircraftDbName, $tailsign) {
        global $status, $dbConnection;
        $dbCreationStatus = TRUE;
        
        error_log("addAircrafts.php -> going to create database with name $aircraftDbName");
        
        // not allowing database creationg if TailSign is wrong.
        if (! mysqli_select_db ( $dbConnection, $aircraftDbName ) and $tailsign != '') {
            //echo ("creating database!\n");
            $sql = "CREATE DATABASE $aircraftDbName";
            if (! mysqli_query ( $dbConnection,$sql )) {
                // TODO : Handle case whether to EXIT with note or IGNORE if db creation failed. right now echoing Error, which gets displayed to user.
                $status['state'] = -1;
                $status['message'] = 'Error creating database: ' . mysqli_error ($dbConnection);
                $dbCreationStatus = FALSE;
                return $dbCreationStatus;
            }
            // Select Database
            mysqli_select_db ( $dbConnection, $aircraftDbName );
            
            // Generate tables from "db_schema.txt"
            $filepath = pathinfo ( $_SERVER ['SCRIPT_FILENAME'], PATHINFO_DIRNAME );
            $fileschemapath = $filepath . "/db_schema.txt";
            
            $templine = '';
            $lines = file ( $fileschemapath );
            foreach ( $lines as $line ) {
                if (substr ( $line, 0, 2 ) == '--' || $line == '')
                    continue;
                    
                    $templine .= $line;
                    if (substr ( trim ( $line ), - 1, 1 ) == ';') {
                        if (! mysqli_query ( $dbConnection, $templine )) {
                            // TODO : Handle case whether to EXIT with note or IGNORE if db creation failed. right now echoing Error, which gets displayed to user.
                            $status['state'] = -1;
                            $status['message'] = 'Error Loading Schema: ' . mysqli_error ($dbConnection);
                            $dbCreationStatus = FALSE;
                            break;
                        }
                        $templine = '';
                    }
            } //End of Foreach Loop
            //echo "Database created successfully with ".$tailsign;
            mysqli_commit( $dbConnection );
        } // End of Mysql DB Selection.
        
        error_log("addAircrafts.php -> database $aircraftDbName created and the creation status is $dbCreationStatus");
        
        return $dbCreationStatus;
    } // End of create database
    
    private function validateAddAircraftInputData() {
        //	Status Return Format
        $status = array('state' => 0,
            'message' => "Successfully Added Aircraft"
        );
        
        // Server Side Validation of User Input.
        if(!preg_match('/^\d+$/',$this->airlineId)){
            $status['state'] = -1;
            $status['message'] = "Invalid Airline Id";
            return $status;
        }else if(!preg_match('/^[0-9]{1,5}$/',$this->msn)){
            $status['state'] = -1;
            $status['message'] = "Invalid MSN";
            return $status;
        }else if(!preg_match('/^[A-Za-z0-9]{1,10}$/',$this->type)){
            $status['state'] = -1;
            $status['message'] = "Invalid Aircraft Type";
            return $status;
        }else if(!preg_match('/^[A-Za-z0-9]{1,10}$/',$this->platform)){
            $status['state'] = -1;
            $status['message'] = "Invalid Platform";
            return $status;
        }else if(!preg_match('/^[A-Za-z0-9]+$/',$this->isp)){
            $status['state'] = -1;
            $status['message'] = "Invalid Internet Service Provider";
            return $status;
        }else if(!preg_match('/^.{1,10}$/',$this->software)){
            $status['state'] = -1;
            $status['message'] = "Invalid Customer Software";
            return $status;
        }
        
        return $status;
    }
    
    public function isNullOrEmptyString($str) {
        return (! isset($str) || trim($str) === '');
    }
}

$aircraftDAO = new AircraftDAO($dbConnection, $mainDB);
$aircraftDAO->hadleRequest();

?>