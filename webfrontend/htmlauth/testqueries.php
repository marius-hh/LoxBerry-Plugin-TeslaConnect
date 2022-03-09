<?php
require_once "loxberry_system.php";
require_once "loxberry_web.php";
require_once "defines.php";
require_once "tesla_inc.php";

$navbar[2]['active'] = True;

// Print LoxBerry header
$L = LBSystem::readlanguage("language.ini");
LBWeb::lbheader($template_title, $helplink, $helptemplate);

?>

<style>
.mono {
        font-family:monospace;
        font-size:110%;
        font-weight:bold;
        color:green;

}
</style>

<?php
//Checktoken
$tokenvalid = tesla_checktoken();
$tokenparts = explode(".", $login->bearer_token);
$tokenexpires = json_decode( base64_decode($tokenparts[1]) )->exp;

if($tokenvalid == "false") {
	?>
	<!-- Status -->
	<div class="wide">Status</div>
	<p style="color:red"><b>You are not logged in.</b></p><br>
	
	<?php

} else {
?>

<!-- Queries -->
<?php
	$vehicles = tesla_summary();

	if(isset($vehicles)) {
?>

		<div class="wide">Test Queries</div>

<?php
		// foreach vehicles
		foreach ($vehicles as $vehicle) {
			$name = $vehicle->display_name;
			$vid = strval($vehicle->id);
			$state = $vehicle->state;
?>

		<h2>Queries for <?=$name . " (VID: " . $vid . ")\n"; ?></h2>

<?php
if (isset($_GET['test_query'])) 
{

	if(isset($_POST['action'])){
		$action = $_POST['action'];
		$force = $_POST['force'];
		$uri = $commands->{"$action"}->URI;
		$uri = str_replace("{vehicle_id}", "$vid", $uri);
		
		if(isset($commands->{strtoupper($action)})) {
			if (strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false) {
				if(!empty($vid)) {
					$commandoutput = tesla_query( $vid, $action, $force );
				} else {
					$commandoutput = "VID missing.\n";
				}
			} else {
				$commandoutput = tesla_query( $vid, $action, $force );
			}
		} else {
			$commandoutput = "Command not found.\n"; 
		}
	}
}
?>
<form method="post" name="main_form" action="?test_query">
<div class="form-group">
	<table class="formtable" border="0" width="100%">
		<tr>
			<td width="25%">
				<label id="labeldepth">Command:</label>
			</td>
			<td>
					<select name="action">
<?php
					foreach ($commands as $command => $attribut) {
?>
						<option value="<?=$command;?>" <?php if($command == $action){ echo " selected"; } ?>><?=$command;?></option>
<?php
					}
?>
					</select>

					<fieldset data-role="controlgroup">
						<input type="checkbox" name="force" id="force" <?php if($force){ echo " checked"; } ?> class="refreshdisplay">
						<label for="force">Wake up, if vehicle unavailable (force)</label>
						<p class="hint">If you check this box, the vehicle will be waked up, if it is unavailable.</p>
					</fieldset>
				</td>
				<td width="5%">&nbsp;</td>
				<td width="20%"><input type="submit" value="Submit"></td>
				<td width="15%">&nbsp;</td>
			</tr>
	</table>
	
</div>
</form>
<h2>Output</h2>
<?php
if (isset($commandoutput)){
	$com = "?action=".$action;
	if(strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false and $force){ $com = $com."&force=true"; }
	if(isset($commands->{"$action"}->URI)){ echo "TeslaConnect URI: <span class=\"mono\">".strtolower($lbzeurl.$com)."</span><br>"; }
	if(isset($commands->{"$action"}->URI)){ echo "Tesla API URI: <span class=\"mono\">".BASEURL.$uri."</span><br>"; }
	if(isset($commands->{"$action"}->DESC)){ echo "Description: <span class=\"mono\">".$commands->{"$action"}->DESC."</span><br>"; }
?>
<hr>
<div class="mono">
<p><?php echo pretty_print($commandoutput); ?></p>
</div>
<?php
}
?>
<hr>
<?php

		// foreach vehicles
		}
	} 
}

LBWeb::lbfooter();
?>