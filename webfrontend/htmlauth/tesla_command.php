<?php
//TODO: Add more commands
//TODO: Add command to check if token valid
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
        $_REQUEST[$e[0]]=$e[1];
    else    
        $_REQUEST[$e[0]]=0;
}

// Define action
if(!empty($_REQUEST["action"])) { 
	$action = $_REQUEST["action"];
} elseif (!empty($_REQUEST["a"])) { 
	$action = $_REQUEST["a"];
}

// Define vehicle
if(!empty($_REQUEST["vehicle"])) { 
	$vid = $_REQUEST["vehicle"];
} elseif (!empty($_REQUEST["v"])) { 
	$vid = $_REQUEST["v"];
} elseif (!empty($_REQUEST["vid"])) { 
	$vid = $_REQUEST["vid"];
}

// Define force
if(!empty($_REQUEST["force"])) { 
	$force = $_REQUEST["force"];
} elseif (!empty($_REQUEST["f"])) { 
	$force = $_REQUEST["f"];
}

if(isset($commands->{strtoupper($action)})) {
	$command_post = [];
	$command_post_print = "";
	$command_output = "";
	$command_error = false;

	if (strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false) {
		if(!empty($vid)) {
			LOGDEB("tesla_command: vid: ".$vid);
		} else {
			$command_output =  $command_output."Parameter \"VID\" missing! The id of the vehicle.\n";
			LOGDEB("tesla_command: Parameter \"VID\" missing");
			$command_error = true;
		}

		if(isset($commands->{strtoupper($action)}->PARAM)) {																			
			foreach ($commands->{strtoupper($action)}->PARAM as $param => $param_desc) {
				LOGDEB("tesla_command: Parameter \"$param\": $param_desc");
				
				if(isset($_REQUEST["$param"])) {
					LOGDEB("$param: ".$_REQUEST["$param"]);
					$command_post += array("$param" => $_REQUEST["$param"]);
					$command_post_print = $command_post_print.", $param: ".$_REQUEST["$param"];
				} else {
					$command_output = $command_output."Parameter \"$param\" missing! $param_desc\n";
					LOGDEB("tesla_command: Parameter \"$param\" missing");
					$command_error = true;
				}
			}
		}
		
		if (!$command_error) {
		$command_output =  tesla_query( $vid, $action, $command_post, $force );
		LOGOK("tesla_command: vid: $vid, action: $action".$command_post_print.($force ? ", force: $force" : ""));
		}
		
	} else {
		LOGOK("tesla_command: action: $action".($force ? ", force: $force" : ""));
		$command_output =  tesla_query( $vid, $action, $command_post, $force );
	}
} else {
	$command_output =  "Command not found\n";
	LOGERR("tesla_command: Command not found");
}
echo $command_output;
?>