<?php
// TODO: Create pages
// [x] Statuspage
// [x] Querypage
// [x] Testpage

require_once "loxberry_system.php";
require_once "loxberry_web.php";
require_once "tesla_inc.php";
require_once "defines.php";

$navbar[1]['active'] = True;

// Print LoxBerry header
$L = LBSystem::readlanguage("language.ini");
LBWeb::lbheader($template_title, $helplink, $helptemplate);

//Checktoken
$tokenvalid = tesla_checktoken();
$tokenparts = explode(".", $login->bearer_token);
$tokenexpires = json_decode(base64_decode($tokenparts[1]))->exp;
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
if($tokenvalid == "true") {
?>

<p style="color:green">
    <b>You are logged in, token is valid until
        <?=date("Y-m-d H:i:s", $tokenexpires)?>
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

	if($output->success == 0) {
		echo "<br><br>".$output->message."<br><br>";
		echo "Try again. <a href=index.php>Click here</a> to login.<br><br>";
	} else {
		echo "Login successful. <a href=index.php>Click here to continue</a>.";
        echo "<script> location.href='index.php'; </script>";
	}
}

if($tokenvalid == "false") {
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
<br>
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
?>

<!-- MQTT -->
<div class="wide">MQTT</div>
<p>All data is transferred via MQTT. The subscription for this is
    <span class="mono"><?=MQTTTOPIC?>/#</span>
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