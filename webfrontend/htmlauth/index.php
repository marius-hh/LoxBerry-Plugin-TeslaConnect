<?php
require_once "loxberry_system.php";
require_once "loxberry_web.php";
require_once "defines.php";
require_once "tesla_inc.php";

$navbar[1]['active'] = True;

// Print LoxBerry header
$L = LBSystem::readlanguage("language.ini");
LBWeb::lbheader($template_title, $helplink, $helptemplate);
?>

<style>
    .mono {
        font-family: monospace;
        font-size: 110%;
        font-weight: bold;
        color: green;

    }
</style>

<!-- Status -->
<div class="wide">Status</div>

<?php
//Checktoken
$tokenvalid = tesla_checktoken();
$tokenparts = explode(".", $login->bearer_token);
$tokenexpires = json_decode( base64_decode($tokenparts[1]) )->exp;

if($tokenvalid == "true") {
?>

<p style="color:green">
    <b>You are logged in, token is valid until
        <?=gmdate("Y-m-d H:i:s", $tokenexpires)?>
        (<a href="?delete_token">delete token</a>).</b>
</p><br>

<?php
} else {
?>

<p style="color:red">
    <b>You are not logged in.</b>
</p><br>

<?php
}

if (isset($_GET['delete_token'])) {
	delete_token();
	echo "<script> location.href='index.php'; </script>";
} else if(isset($_POST["login"])) {
	$output = json_decode(login($_POST["weburl"], $_POST["code_verifier"], $_POST["code_challenge"], $_POST["state"]));
	echo $output;
	if($output->success == 0) {
		echo "<br><br>".$output->message."<br><br>";
		echo "Try again. <a href=index.php>Click here</a> to login.";
	} else {
		echo "<script> location.href='index.php'; </script>";
	}
} else {
?>

<!-- Queries -->
<?php
	$vehicles = tesla_summary();

	if(isset($vehicles)) {
?>

<div class="wide">Queries</div>
<p>
    <i>
        <span class="mono">&lt;user&gt;:&lt;pass&gt;
        </span>must be replaced with your
        <b>LoxBerry's</b>
        username and password.</i>
</p>
<h2>General queries</h2>

<?php
		foreach ($commands as $command => $attribut) {
			if($attribut->TYPE == "GET"){
				// General
				if (strpos($attribut->URI, '{vehicle_id}') == false) {				
?>

<div style="display:flex; align-items: center; justify-content: center;">
    <div style="flex: 0 0 95%;padding:5px" data-role="fieldcontain">
        <label for="summarylink">
            <strong><?=strtolower($command)?></strong><br>
            <span class="hint"><?= "$attribut->DESC" ?></span></label>
        <input
            type="text"
            id="summarylink"
            name="summarylink"
            data-mini="true"
            value="<?=$lbzeurl."?action=".strtolower($command); ?>"
            readonly="readonly">
    </div>
</div>

<?php
				}
			}
		}
?>

<hr>

<?php
		// foreach vehicles
		foreach ($vehicles as $vehicle) {
			$name = $vehicle->display_name;
			$vid = strval($vehicle->id);
			$state = $vehicle->state;
			$vehicle_id = "&vid=$vid";
?>

<h2>Queries for
    <?=$name . " (VID: " . $vid . ")\n"; ?></h2>
<h3>Get informations</h3>
<p>
    <i>If you add the parameter
        <span class="mono">&force=true</span>, the vehicle will be woken up if the request is not possible.</i>
</p>

<?php
		foreach ($commands as $command => $attribut) {

			if($attribut->TYPE == "GET"){
				//Vehicle GET
				if (strpos($attribut->URI, '{vehicle_id}') == true) {
?>

<div style="display:flex; align-items: center; justify-content: center;">
    <div style="flex: 0 0 95%;padding:5px" data-role="fieldcontain">
        <label for="summarylink">
            <strong><?=strtolower($command)?></strong><br>
            <span class="hint"><?= "$attribut->DESC" ?></span></label>
        <input
            type="text"
            id="summarylink"
            name="summarylink"
            data-mini="true"
            value="<?=$lbzeurl."?action=".strtolower($command).$vehicle_id; ?>"
            readonly="readonly">
    </div>
</div>

<?php
				}
			}
		}
?>

<h3>Send commands</h3>

<?php
			foreach ($commands as $command => $attribut) {
				if($attribut->TYPE == "POST"){
					//Vehicle POST
					if (strpos($attribut->URI, '{vehicle_id}') == true) {
						$command_get = "";
						if(isset($commands->{strtoupper($command)}->PARAM)) {
							foreach ($commands->{strtoupper($command)}->PARAM as $param => $param_desc) {
									$command_get = $command_get."&$param=<value>";
							}
						}

?>

<div style="display:flex; align-items: center; justify-content: center;">
    <div style="flex: 0 0 95%;padding:5px" data-role="fieldcontain">
        <label for="summarylink">
            <strong><?=strtolower($command)?></strong><br>
            <span class="hint"><?= "$attribut->DESC" ?></span></label>
        <input
            type="text"
            id="summarylink"
            name="summarylink"
            data-mini="true"
            value="<?=$lbzeurl."?action=".strtolower($command).$vehicle_id.$command_get; ?>"
            readonly="readonly">
    </div>
</div>

<?php
					}
				}
			}
		// foreach vehicles
		}
	} else {
		$challenge = gen_challenge();
		$code_verifier = $challenge["code_verifier"];
		$code_challenge = $challenge["code_challenge"];
		$state = $challenge["state"];
		$timestamp = time();
?>
<div class="wide">Login</div>
<form method="post">
    <input type="hidden" name="login" value="">
    <input type="hidden" name="code_verifier" value="<?php echo $code_verifier; ?>">
    <input
        type="hidden"
        name="code_challenge"
        value="<?php echo $code_challenge; ?>">
    <input type="hidden" name="state" value="<?php echo $state; ?>">
    <p>Please follow the steps below to log in:</p>

    Step 1: Please
    <strong>
        <a href="#<?php echo $timestamp; ?>" onclick="teslaLogin();return false();">click here</a>
    </strong>
    to log in to Tesla (A popup window will open, please allow popups).<br>
    Step 2: Please enter your Tesla login data on the Tesla website.<br>
    Step 3: If the login was successful, you will receive a
    <strong>Page not found</strong>
    information on the Tesla website. Copy the complete web address (e.g.
    <strong><?php echo $tesla_api_redirect; ?>?code=.....&state=...&issuer=....</strong>)<br>
    Step 4: Paste the copied web address here and press the
    <strong>Get Token</strong>-Button:<br>
    <input type="text" name="weburl" size="100" required="required"><input type="submit" value="Get Token">
</form>

<script>
    function teslaLogin() {
        teslaLogin = window.open(
            "<?php echo gen_url($code_challenge, $state);?>",
            "TeslaLogin",
            "width=600,height=400,status=yes,scrollbars=yes,resizable=yes"
        );
        teslaLogin.focus();
    }
</script>
<?php
	}
}
?>

<!-- MQTT -->
<div class="wide">MQTT</div>
<p>All data is transferred via MQTT. The subscription for this is
    <span class="mono">teslaconnect/#</span>
    and is automatically registered in the MQTT gateway plugin.</p>

<?php
	// Query MQTT Settings
	$mqttcred = mqtt_connectiondetails();
	if ( !isset($mqttcred) ) {
?>

<p style="color:red">
    <b>MQTT gateway not installed!</b>
</p>

<?php
	} else {		
?>

<p style="color:green">
    <b>MQTT gateway found and it will be used.</b>
</p>

<?php
	}
	
LBWeb::lbfooter();
?>