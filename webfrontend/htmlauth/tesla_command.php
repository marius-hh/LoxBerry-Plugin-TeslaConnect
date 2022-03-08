<?php
/*
php ./tesla_command.php a=summary
php ./tesla_command.php action=summary
php ./tesla_command.php action=vehicle_data vid=123
php ./tesla_command.php a=vehicle_data v=123
/tesla_command.php?a=vehicle_data&v=123
*/
require_once "loxberry_web.php";
require_once "defines.php";
require_once "tesla_inc.php";

//
// Query parameter 
//

// Convert commandline parameters to query parameter
foreach ($argv as $arg) {
    $e=explode("=",$arg);
    if(count($e)==2)
        $_GET[$e[0]]=$e[1];
    else    
        $_GET[$e[0]]=0;
}

// Define action
if(!empty($_GET["action"])) { 
	$action = $_GET["action"];
} elseif (!empty($_GET["a"])) { 
	$action = $_GET["a"];
}

// Define vehicle
if(!empty($_GET["vehicle"])) { 
	$VID = $_GET["vehicle"];
} elseif (!empty($_GET["v"])) { 
	$VID = $_GET["v"];
} elseif (!empty($_GET["vid"])) { 
	$VID = $_GET["vid"];
}

// Define force
if(!empty($_GET["force"])) { 
	$force = $_GET["force"];
} elseif (!empty($_GET["f"])) { 
	$force = $_GET["f"];
}

if(isset($commands->{strtoupper($action)})) {
	if (strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false) {
		if(!empty($VID)) {
			echo tesla_query( $VID, $action, $force );
		} else {
			echo "VID missing.\n";
		}
	} else {
		echo tesla_query( $VID, $action, $force );
	}
} else {
	echo "Command not found.\n"; 
}
?>
