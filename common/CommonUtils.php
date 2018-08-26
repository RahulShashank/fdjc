<?php

class CommonUtils {
    
    /**
     * Checks if the file name contains the pattern - FileName(xx).extn
     *
     * @param unknown $fileName
     * @return boolean
     */
    public static function isDuplicateFile($fileName) {
        $isDuplicate = false;
        $duplicateFileNamePattern = "/\([0-9]{1,3}\)+\.+[a-z]*$/";
        
        if (preg_match($duplicateFileNamePattern, $fileName)) {
            $isDuplicate = true;
        }
        
        return $isDuplicate;
    }
}
?>