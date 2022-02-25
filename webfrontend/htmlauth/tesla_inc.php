<?php
$debugscript = true;

include_once "loxberry_system.php";
include_once "loxberry_io.php";
require_once "defines.php";
require_once "phpMQTT/phpMQTT.php";

$tesla_api_oauth2 = 'https://auth.tesla.com/oauth2/v3';
$tesla_api_redirect = 'https://auth.tesla.com/void/callback';
$tesla_api_owners = 'https://owner-api.teslamotors.com/oauth/token';
$tesla_api_code_vlc = 86;
$cid = "81527cff06843c8634fdc09e8ac0abefb46ac849f38fe1e431c2ef2106796384";
$cs = "c7257eb71a564034f9419ee651c7d0e5f7aa6bfbd18bafb5c5c033b093bb2fa3"; 
$user_agent = "Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148";

// Init
$VID = false;
$force = false;
$token = false;
$action = "noaction";
$vid_actions = array("wake_up","auto_conditioning_start","auto_conditioning_stop","door_unlock","door_lock","charge_port_door_open","charge_port_door_close","charge_start","charge_stop");

$login = tesla_refreshtoken();
	

function tesla_refreshtoken()
{
	// Function to read token from file and refresh token, if expired
	// Reads login data from disk, and checks for expiration of the token
	
	global $token;
	
	if( !file_exists(LOGINFILE) ) {
		print_debug("tesla_refreshtoken: Loginfile missing, aborting");
		return;
	}
	print_debug("tesla_refreshtoken: read loginfile");
	$logindata = file_get_contents(LOGINFILE);
	$login = json_decode($logindata);
	
	// Read token
	if( empty($login->bearer_token) ) {
		print_debug("tesla_refreshtoken: File data error, no token found. Fallback to re-login");
		return;
	}
	
	// Get date part of token
	$tokenparts = explode(".", $login->bearer_token);
	$tokenexpires = json_decode( base64_decode($tokenparts[1]) )->exp;

    $timediff = 60*120; //2h 

    print_debug("tesla_refreshtoken: Time now                  - ". time() ." ".gmdate("Y-m-d H:i:s", time()));
	print_debug("tesla_refreshtoken: Refresh Token valid until - ". ($tokenexpires) ." ".gmdate("Y-m-d H:i:s", $tokenexpires));
    print_debug("tesla_refreshtoken: Time to Refresh Token     - ". ($tokenexpires-$timediff) ." ".gmdate("Y-m-d H:i:s", $tokenexpires-$timediff));
	
	if( $tokenexpires-$timediff > time() ) {
		// Token is valid
		$token = $login->bearer_token;
	} elseif ($tokenexpires > time()) {
		// Token expired
		print_debug("tesla_refreshtoken: Token expired (" . gmdate("Y-m-d\TH:i:s\Z", $tokenexpires) . ")");

		$token = tesla_oauth2_refresh_token( $login->bearer_refresh_token );
		print_debug("tesla_refreshtoken: New bearer_token: $token");
	} else {
		// no valid token
		print_debug("No valid token, please login.");
	}
	return $login;
}


function tesla_summary()
{
	// Function to get car summary
	
	$data = json_decode(preg_replace('/("\w+"):(\d+(\.\d+)?)/', '\\1:"\\2"', tesla_curl_send( BASEURL."/vehicles", false )));

	if(isset($data->response)) {
		foreach($data->response as $value) {
			$returndata = $value;
			mqttpublish($returndata, "/$value->id");
		}
		return $data->response;
	}
} 


function tesla_checktoken()
{
	// Function to check if token is valid
	
	$data = json_decode(tesla_curl_send( BASEURL."/vehicles", false ));
	if (is_null($data)) {
		return "false";
	} else {
		return "true";
	}
} 


function tesla_set ( $VID, $COM )
{
	// Function to control car
	
	$timeout = 5;

	while($timeout > -1) {
		$data = json_decode(tesla_curl_send( BASEURL."/vehicles/$VID/$COM", false, true));

		if (preg_match("/vehicle unavailable/i", $data->error)) {
			// Wake-Up Car
			print_debug("tesla_set: vehicle unavailable, wakeup car");
			
			$data = json_decode(tesla_curl_send( BASEURL."/vehicles/$VID/wake_up", false, true));
			sleep(2);
			$timeout = $timeout-1;
			print_debug("tesla_set: timeout $timeout");
		} elseif (preg_match("/timeout/i", $data->error)) {
			print_debug("tesla_set: timeout");
			sleep(1);
			$timeout = $timeout-1;
			print_debug("tesla_set: timeout $timeout");
		} else {
			print_debug("tesla_set: success");
			break;
		}
	}
	return $data;
} 


function tesla_get ( $VID, $COM, $force=false )
{
	// Function to get car info
	
	$timeout = 10;
	
	while($timeout > -1) {

		$data = json_decode(preg_replace('/("\w+"):(\d+(\.\d+)?)/', '\\1:"\\2"', tesla_curl_send( BASEURL."/vehicles/$VID/$COM", false )));
	
		if (!empty($data->error)) {
			if (preg_match("/vehicle unavailable/i", $data->error) and $force==true) {
				//Wake-Up Car if force==true
				print_debug("tesla_get: vehicle unavailable, wakeup car");

				$data = json_decode(tesla_curl_send( BASEURL."/vehicles/$VID/wake_up", false, true));
				sleep(2);
				$timeout = $timeout-1;
				print_debug("tesla_get: timeout $timeout");
			} elseif (preg_match("/vehicle unavailable/i", $data->error)) {
				print_debug("tesla_get: vehicle unavailable");
				break;
			} elseif (preg_match("/timeout/i", $data->error)) {
				print_debug("tesla_get: timeout");
				sleep(1);
				$timeout = $timeout-1;
				print_debug("tesla_get: timeout $timeout");
			}
		} else {
			$returndata = $data->response;
			mqttpublish($returndata, "/$returndata->id");
			print_debug("tesla_get: success");
			break;
		}
	}

	return $data;
}


function mqttpublish($data, $mqttsubtopic="")
{
	// Function to send data to mqtt

	// MQTT requires a unique client id
	$client_id = uniqid(gethostname()."_client");
	$creds = mqtt_connectiondetails();

	// Be careful about the required namespace on inctancing new objects:
	$mqtt = new Bluerhinos\phpMQTT($creds['brokerhost'],  $creds['brokerport'], $client_id);

    if( $mqtt->connect(true, NULL, $creds['brokeruser'], $creds['brokerpass'] ) ) {

		if(is_object($data)){
			foreach ($data as $key => $value) {
				if(is_object($value)) {
					foreach ($value as $skey => $svalue){
						if(is_object($svalue)) {
							foreach ($svalue as $sskey => $ssvalue){
								if(!empty($ssvalue)){ 
									$mqtt->publish(MQTTTOPIC."$mqttsubtopic/$key/$skey/$sskey", $ssvalue, 0, 1);
									print_debug("mqttpublish :".MQTTTOPIC."$mqttsubtopic/$key/$skey/$sskey: $ssvalue");
								}
							}
						} else {
							if(!empty($svalue)){ 
								$mqtt->publish(MQTTTOPIC."$mqttsubtopic/$key/$skey", $svalue, 0, 1);
								print_debug("mqttpublish: ".MQTTTOPIC."$mqttsubtopic/$key/$skey: $svalue"); 
							}
						}
					}
				} else {
					if(!empty($value)){
						if(is_array($value)){
							$value = implode(",", $value);
						}
						$mqtt->publish(MQTTTOPIC."/summary$mqttsubtopic/$key", $value, 0, 1);
						print_debug("mqttpublish :".MQTTTOPIC."/summary$mqttsubtopic/$key: $value"); }
				}
			}
		}
        $mqtt->close();
    } else {
        print_debug("MQTT connection failed");
    }
}


function tesla_curl_send( $url, $payload, $post=false )
{
	// Function to send curl command
	
	global $token;
	$curl = curl_init();

	if( !empty($payload) ) {
		$payload = json_encode ( $payload );
	} else {
		$payload = "";
	}
	
	$header = [ ];
	
	if( !empty($token) ) {
		print_debug("tesla_curl_send: Token given.");
		array_push( $header, "Authorization: Bearer $token" );
	}
	
	if($post==true) {
		array_push( $header, "Content-Type: application/json;charset=UTF-8" );
		array_push( $header, "Content-Length: " . strlen($payload) );
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
	}
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header );

	print_debug("tesla_curl_send: curl_send URL: $url");
	$response = curl_exec($curl);
	print_debug("tesla_curl_send: curl_exec finished");
	// Debugging
	$crlinf = curl_getinfo($curl);
	print_debug("tesla_curl_send: Status: " . $crlinf['http_code']);
	
	return $response;
}


function delete_token()
{
	// delete file with token
	unlink(LOGINFILE);
	print_debug("delete_token: File " . LOGINFILE . "deleted");
}


function print_debug( $message, $file="" )
{
	// Function to print debug messages to commandline
	global $debugscript;
	if ($debugscript) {
		if(!empty($file)) { $file="$file "; }
		error_log("DEBUG $file- $message");
	}
}

####################################################
# Tesla Authorization fuctions
# Based on: https://github.com/timdorr/tesla-api/discussions/362
####################################################

function tesla_connect($url, $returntransfer=1, $referer="", $http_header="", $post="", $need_header=0, $cookies="", $timeout = 10)
{
    if(!empty($post)) { $cpost = 1; } else { $cpost = 0; }
    if(is_array($http_header)) { $chheader = 1; } else { $chheader = 0; }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, $returntransfer);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, $need_header);
    curl_setopt($ch, CURLOPT_POST, $cpost);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_MAX_TLSv1_2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    if(!empty($referer)) { curl_setopt($ch, CURLOPT_REFERER, $referer); }

    if($chheader == 1) { curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header); }

    if($cpost == 1) { curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    
    if(!empty($cookies)) { curl_setopt($ch, CURLOPT_COOKIE, $cookies); }

    $response = curl_exec($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);

    return array("response" => $response, "header" => $header);
}


function gen_challenge()
{
    global $tesla_api_code_vlc;

    $code_verifier = substr(hash('sha512', mt_rand()), 0, $tesla_api_code_vlc);
    $code_challenge = rtrim(strtr(base64_encode($code_verifier), '+/', '-_'), '='); 
    
    $state = rtrim(strtr(base64_encode(substr(hash('sha256', mt_rand()), 0, 12)), '+/', '-_'), '='); 

    return array("code_verifier" => $code_verifier, "code_challenge" => $code_challenge, "state" => $state);
}


function gen_url($code_challenge, $state)
{
    global $tesla_api_oauth2, $tesla_api_redirect;


    $datas = array(
          'audience' => '',
          'client_id' => 'ownerapi',
          'code_challenge' => $code_challenge,
          'code_challenge_method' => 'S256',
          'locale' => 'en-US',
          'prompt' => 'login',
          'redirect_uri' => $tesla_api_redirect,
          'response_type' => 'code',
          'scope' => 'openid email offline_access',
          'state' => $state
    );

    return $tesla_api_oauth2."/authorize?".http_build_query($datas);
}


function return_msg($code, $msg)
{
    return json_encode(array("success" => $code, "message" => $msg));
}


function login($weburl, $code_verifier, $code_challenge, $state)
{
    global $tesla_api_redirect, $user_agent, $tesla_api_oauth2, $cid, $cs, $tesla_api_owners;

    
    $code = explode('https://auth.tesla.com/void/callback?code=', $weburl);
    $code = explode("&", $code[1])[0];


    if(empty($code)) { return return_msg(0, "Something is wrong ... Code not exists"); }

    // Get the Bearer token
    $http_header = array('Content-Type: application/json', 'Accept: application/json', 'User-Agent: '.$user_agent);
    $post = json_encode(array("grant_type" => "authorization_code", "client_id" => "ownerapi", "code" => $code, "code_verifier" => $code_verifier, "redirect_uri" => $tesla_api_redirect));
    $response = tesla_connect($tesla_api_oauth2."/token", 1, "", $http_header, $post, 0);

    $token_res = json_decode($response["response"], true);
    $bearer_token = $token_res["access_token"];
    $refresh_token = $token_res["refresh_token"];

    if(empty($bearer_token)) { return return_msg(0, "Bearer Token issue"); }

    // Final Step
    unset($response);
    $http_header = array('Authorization: Bearer '.$bearer_token, 'Content-Type: application/json');
    $post = json_encode(array("grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer", "client_id" => $cid, "client_secret" => $cs));
    $response = tesla_connect($tesla_api_owners, 1, "", $http_header, $post, 0);

    $tokens = json_decode($response["response"], true);

    if(empty($tokens['access_token'])) { return return_msg(0, "Token issue"); }

    $tokens["bearer_token"] = $bearer_token;
    $tokens["bearer_refresh_token"] = $refresh_token;
    $return_message = json_encode($tokens);

    // Write data to disk
    file_put_contents(LOGINFILE, $return_message);    

    // Output
    return return_msg(1, $return_message);  
}


function tesla_oauth2_refresh_token($bearer_refresh_token)
{
    global $tesla_api_oauth2, $tesla_api_redirect, $tesla_api_owners, $tesla_api_code_vlc, $cid, $cs;

    $brt = $bearer_refresh_token;

    // Get the Bearer token
    $http_header = array('Content-Type: application/json', 'Accept: application/json');
    $post = json_encode(array("grant_type" => "refresh_token", "client_id" => "ownerapi", "refresh_token" => $brt, "scope" => "openid email offline_access"));
    $response = tesla_connect($tesla_api_oauth2."/token", 1, "https://auth.tesla.com/", $http_header, $post, 0);

    $token_res = json_decode($response["response"], true);

    $bearer_token = $token_res["access_token"];
    $refresh_token = $token_res["refresh_token"];


    if(empty($bearer_token)) { return return_msg(0, "Bearer Refresh Token is not valid"); }

    $http_header = array('Authorization: Bearer '.$bearer_token, 'Content-Type: application/json');
    $post = json_encode(array("grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer", "client_id" => $cid, "client_secret" => $cs));
    $response = tesla_connect($tesla_api_owners, 1, "", $http_header, $post, 0);

    $tokens = json_decode($response["response"], true);

    if(empty($tokens['access_token'])) { return return_msg(0, "Token issue"); }

    $tokens["bearer_token"] = $bearer_token;
    $tokens["bearer_refresh_token"] = $refresh_token;
    $return_message = json_encode($tokens);

    // Write data to disk
    file_put_contents(LOGINFILE, $return_message);

    // Output
    return $bearer_token;
}