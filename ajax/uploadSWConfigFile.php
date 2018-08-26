<?php

try {
	//$customer = $_REQUEST['customer'];
	
	// if(file_exists('../uploads/'.$customer)===FALSE){
		// mkdir('../uploads/'.$customer, 0666);
	// }
    // Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.
    if (
        !isset($_FILES['xlfile']['error']) ||
        is_array($_FILES['xlfile']['error'])
    ) {
        echo json_encode(array("status"=>"fail", "message"=>"Invalid parameters."));
		exit;
		//throw new RuntimeException('Invalid parameters.');
    }

    // Check $_FILES['xlfile']['error'] value.
    switch ($_FILES['xlfile']['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            echo json_encode(array("status"=>"fail", "message"=>"No file sent."));
			exit;
			//throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            echo json_encode(array("status"=>"fail", "message"=>"Exceeded filesize limit."));
			exit;
			//throw new RuntimeException('Exceeded filesize limit.');
        default:
            echo json_encode(array("status"=>"fail", "message"=>"Unknown errors."));
			exit;
			//throw new RuntimeException('Unknown errors.');
    }

    // You should also check filesize here. 
    if ($_FILES['xlfile']['size'] > 1000000) {
		echo json_encode(array("status"=>"fail", "message"=>"Exceeded filesize limit."));
		exit;
        //throw new RuntimeException('Exceeded filesize limit.');
    }

    // DO NOT TRUST $_FILES['xlfile']['mime'] VALUE !!
    // Check MIME Type by yourself.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if (strpos($_FILES['xlfile']['name'], ".xls")===false and strpos($_FILES['xlfile']['name'], ".xlsx")===false) {
        echo json_encode(array("status"=>"fail", "message"=>"Invalid file format."));
		exit;
		//throw new RuntimeException('Invalid file format.');
    }
	
	if(file_exists('../uploads/'.$_FILES['xlfile']['name'])){
		echo json_encode(array("status"=>"fail", "message"=>"File already uploaded."));
		exit;
	}

    // You should name it uniquely.
    // DO NOT USE $_FILES['xlfile']['name'] WITHOUT ANY VALIDATION !!
    // On this example, obtain safe unique name from its binary data.
    if (!move_uploaded_file(
        $_FILES['xlfile']['tmp_name'],'../uploads/'.$_FILES['xlfile']['name'])) {
        echo json_encode(array("status"=>"fail", "message"=>"Failed to move uploaded file."));
		exit;
		//throw new RuntimeException('Failed to move uploaded file.');
    }

    echo json_encode(array("status"=>"success", "message"=>"File uploaded successfully."));
	exit;

} catch (RuntimeException $e) {
	echo json_encode(array("status"=>"success", "message"=>$e->getMessage()));
}
?>