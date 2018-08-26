<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";

/**
 * Handles all the requests from UploadWiringDataView.php
 * 
 * @author
 */
class UploadWiringDataDAO {

    public $dbConnection;
    public $action = '';
    public $airlineId = '';
    public $airlineName = '';
    public $platform = '';
    public $configType = '';
    public $aircraftId = '';
    public $software = '';
    
    public $airlineQuery = "SELECT id, acronym FROM airlines order by acronym";

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
        $this->software = $_REQUEST['software'];
        $this->aircraftId = $_REQUEST['aircraftId'];
    }
    
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
        } elseif ($this->action == 'GET_SOFTWARES') {
            $this->getSoftwaresForAirline();
        } elseif ($this->action == 'UPLOAD_FILE') {
            $this->uploadFile();
        } elseif ($this->action == 'GET_FILES') {
            $this->getFilesForAircraft();
        }
    }
    
    /**
     * Retrieves distinct platform for an airline
     */
    public function getAirlines() {
        $result = mysqli_query($this->dbConnection, $this->airlineQuery);
        
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
     * Retrieves the softwares for an aircraft, platform and configType combination.
     */
    public function getSoftwaresForAirline() {
        $query = "select distinct software from aircrafts where airlineID=$this->airlineId and platform='$this->platform' and Ac_Configuration='$this->configType'";
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
     * Process the uploaded file
     */
    public function uploadFile() {
        $acronym = "";
        
        // get the airline acronym from the airlineId
        $airlineQuery = "SELECT id, name, acronym, status, lastStatusComputed FROM airlines where id=$this->airlineId";
        $result = mysqli_query($this->dbConnection, $airlineQuery);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $acronym = $row['acronym'];
            }
        }error_log("Acronym found: $acronym");
        
        $ds = DIRECTORY_SEPARATOR;
        if (! empty($_FILES)) {
            $tempFile = $_FILES['file']['tmp_name'];
            
            $targetPath = dirname(dirname(__FILE__)) . $ds . "wiring_data" . $ds . $acronym . $ds . $this->platform . $ds . $this->configType . $ds . $this->software . $ds;
            error_log("Target path: " . $targetPath);
            
            if (! file_exists($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            
            $targetFileName = $_FILES['file']['name'];
            $targetFile = $targetPath . $_FILES['file']['name'];
            $targetFileType = pathinfo($targetFile, PATHINFO_EXTENSION);
            
            if (strpos($targetFile, '.dat') !== FALSE) {
                $moved = move_uploaded_file($tempFile, $targetFile);
                
                if($moved) {
                    $updateQuery = "INSERT INTO $mainDB.wiring_data(airline_id, file_name, airline_name, platform, config_type, software)
								  VALUES ($this->airlineId, '$targetFileName','$acronym', '$this->platform', '$this->configType', '$this->software')
								  ON DUPLICATE KEY UPDATE last_updated_time=now()";
                    
                    if (! mysqli_query($this->dbConnection,$updateQuery)) {
                        $message = mysqli_error ( $this->dbConnection );
                        echo $message;
                        exit ();
                    }
                    
                    mysqli_commit($this->dbConnection);
                    
                    echo "$targetFileName successfully uploaded to server";
                } else {
                    echo "Error: $targetFileName not correctly transfered to server";
                }
            } else {
                echo "Error: The File is not a dat file, Please upload ONLY dat file";
                exit();
            }
        } else {
            echo "Error: $tempFile not correctly transfered to server";
            
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_OK:
                    $message = false;
                    ;
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message .= ' - file too large (limit of ' . get_max_upload() . ' bytes).';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message .= ' - file upload was not completed.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $message .= ' - zero-length file uploaded.';
                    break;
                default:
                    $message .= ' - internal error #' . $_FILES['newfile']['error'];
                    break;
            }
            
            echo "Error: $message";
            exit();
        }
        
    }
    
    /**
     * Retrieves the files for an aircraft
     */
    public function getFilesForAircraft() {
        $query = "SELECT wd.id, wd.file_name as filename, wd.airline_name as acronym, wd.platform, 
                    wd.config_type as configType, wd.software
                    FROM aircrafts ac, airlines al, wiring_data wd 
                    WHERE ac.id = $this->aircraftId AND ac.airlineId= al.id AND ac.airlineId=wd.airline_id 
                    AND al.acronym=wd.airline_name AND ac.Platform=wd.platform AND ac.Ac_Configuration=wd.config_type
                    AND ac.software=wd.software";
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

$uploadWiringDataDAO = new UploadWiringDataDAO($dbConnection);
$uploadWiringDataDAO->hadleRequest();

?>