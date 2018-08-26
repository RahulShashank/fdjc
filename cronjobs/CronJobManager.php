<?php

class CronJobManager {
//     public $action;
    
    public function __construct() {
//         $this->action = $_REQUEST['action'];
    }
    
    public function getCronJobStatus() {
        $command = "service cron status";
        $output = shell_exec($command);
        echo $output;
    }
    
    public function startCronJob() {
        
    }
    
    public function stopCronJob() {
        
    }
}

$cronJobManager = new CronJobManager();
$cronJobManager-> getCronJobStatus();
?>