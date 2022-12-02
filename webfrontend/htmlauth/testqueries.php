<?php
require_once "loxberry_system.php";
require_once "loxberry_web.php";
require_once "tesla_inc.php";
require_once "defines.php";

$navbar[3]['active'] = True;

// Print LoxBerry header
$L = LBSystem::readlanguage("language.ini");
LBWeb::lbheader($template_title, $helplink, $helptemplate);

// Define action
if(!empty($_REQUEST["type"])) { 
	$type = strtoupper($_REQUEST["type"]);
	$vid = $type;
} else {
	$type = "GENERAL";
}

// Define action
if(!empty($_REQUEST["action"])) { 
	$action = strtoupper($_REQUEST["action"]);
} elseif (!empty($_REQUEST["a"])) { 
	$action = strtoupper($_REQUEST["a"]);
}

//Checktoken
$tokenvalid = tesla_checktoken();
$tokenparts = explode(".", $login->bearer_token);
$tokenexpires = json_decode( base64_decode($tokenparts[1]) )->exp;
?>

<style>
    .mono {
        font-family: monospace;
        font-size: 110%;
        font-weight: bold;
        color: green;

    }
</style>

<?php
// if($tokenvalid == "false")
if($tokenvalid == "false") {
?>

<!-- Status -->
<div class="wide">Status</div>
<p style="color:red">
    <b>You are not logged in.</b>
</p>
<br>

<?php
// if($tokenvalid == "false")
} else {
?>

<!-- Queries -->
<?php
	$vehicles = tesla_summary();

		if (isset($_GET['test_query'])) {
			if(isset($_POST['action'])){
				$action = $_POST['action'];
				$force = $_POST['force'];
				$uri = $commands->{"$action"}->URI;
				
				if(isset($commands->{strtoupper($action)})) {
					$command_post = [];
					$command_post_print = "";
					$command_get = "";
					$command_output = "";
					$command_error = false;

					if (strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') == true || strpos($commands->{strtoupper($action)}->URI, '{energy_site_id}') == true) {
						if(!empty($vid)) {
							LOGDEB("teslaqueries: vid: ".$vid);
							$uri = str_replace("{vehicle_id}", "$vid", $uri);
							$uri = str_replace("{energy_site_id}", "$vid", $uri);
						} else {
							$command_output =  $command_output."Parameter \"VID\" missing! The id of the vehicle.\n";
							LOGINF("Parameter \"VID\" missing");
							$command_error = true;
						}

						if(isset($commands->{strtoupper($action)}->PARAM)) {
							foreach ($commands->{strtoupper($action)}->PARAM as $param => $param_desc) {
								
								if(!empty($_REQUEST["$param"])) {
									LOGDEB("teslaqueries: $param: ".$_REQUEST["$param"]);
									$command_post += array("$param" => $_REQUEST["$param"]);
									$command_post_print = $command_post_print.", $param: ".$_REQUEST["$param"];
									$command_get = $command_get."&$param=".$_REQUEST["$param"];
								} else {
									$commandoutput = $commandoutput."Parameter \"$param\" missing! $param_desc\n";
									LOGINF("Parameter \"$param\" missing");
									$command_error = true;
								}
							}
						}
						
						if (!$command_error) {
						$commandoutput = tesla_query( $vid, $action, $command_post, $force );
						LOGOK("teslaqueries: vid: $vid, action: $action".$command_post_print.($force ? ", force: $force" : ""));
						}

					} else {
						LOGOK("teslaqueries: action: $action".($force ? ", force: $force" : ""));
						//[x] removed $vid
						$commandoutput = tesla_query( "", $action, $command_post, $force );
					}
				} else {
					$commandoutput =  "Command not found\n";
					LOGERR("teslaqueries: Command not found");
				}
			}
		}
?>

<div class="wide">Test Queries</div>

<form method="post" name="main_form" action="?test_query">
    <div class="form-group">
        <table class="formtable" border="0" width="100%">
		<tr>
                <td width="25%">
                    <label id="labeldepth">
                        <h3>Type</h3>
                    </label>
                </td>
                <td>
                    <select name="type" onchange="self.location='?type='+this.options[this.selectedIndex].value;">
                        <option value="General" <?php if($type == "GENERAL"){ echo " selected"; } ?>>
							General
						</option>

<?php
	// foreach vehicle
	foreach ($vehicles as $vehicle) {
		if(isset($vehicle->energy_site_id)){
			$name = $vehicle->site_name;
			$vid = strval($vehicle->energy_site_id);
		} else {
			$name = $vehicle->display_name;
			$vid = strval($vehicle->id);
			$state = $vehicle->state;
		}
?>

						<option value="<?=$vid;?>" <?php if($type == $vid){ echo " selected"; }?>>
							<?=$name." (ID ".$vid.")";?>
						</option>

<?php
		// foreach vehicle	
		}
?>

                    </select>
                </td>
                <td width="5%">&nbsp;</td>
                <td width="20%"></td>
                <td width="15%">&nbsp;</td>
            </tr>
            <tr>
                <td width="25%">
                    <label id="labeldepth">
                        <h3>Command</h3>
                    </label>
                </td>
                <td>
                    <select name="action" onchange="self.location='?type=<?=$type?>&action='+this.options[this.selectedIndex].value;">
					<option disabled selected>
							Please select
					</option>
					
<?php
		foreach ($vehicles as $vehicle) {
			if(isset($vehicle->energy_site_id)){
				$name = $vehicle->site_name;
				$vid = strval($vehicle->energy_site_id);
			} else {
				$name = $vehicle->display_name;
				$vid = strval($vehicle->id);
				$state = $vehicle->state;
			}

			if ($type == $vid) {
				foreach ($commands as $command => $attribut) {
					if ((isset($vehicle->vin) && strpos($attribut->URI, '{vehicle_id}') == true)) {
?>

						<option value="<?=$command;?>" <?php if($command == $action){ echo " selected"; } ?>>
						<?=$command;?>
						</option>

<?php
					} elseif ((isset($vehicle->energy_site_id) && strpos($attribut->URI, '{energy_site_id}') == true)) {
?>

						<option value="<?=$command;?>" <?php if($command == $action){ echo " selected"; } ?>>
						<?=$command;?>
						</option>

<?php
					} 
				}
			}
		// foreach vehicle	
		}

		foreach ($commands as $command => $attribut) {
			if ($type == "GENERAL" && strpos($attribut->URI, '{energy_site_id}') == false && strpos($attribut->URI, '{vehicle_id}') == false) {
?>

		<option value="<?=$command;?>" <?php if($command == $action){ echo " selected"; } ?>>
		<?=$command;?>
		</option>

<?php
			}
		}
?>

</select>
<p class="hint">
	<?=$commands->{"$action"}->DESC;?>
</p>
<?php
		if(strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') == true and $commands->{strtoupper($action)}->TYPE == "GET") {
?>				

                    <fieldset data-role="controlgroup">
                        <input
                            type="checkbox"
                            name="force"
                            id="force"
                            <?php if($force){ echo " checked"; } ?>
                            class="refreshdisplay">
                        <label for="force">Wake up, if vehicle unavailable (force)</label>
                        <p class="hint">If you check this box, the vehicle will be waked up, if it is unavailable.</p>
                    </fieldset>

<?php
		}
?>

                </td>
                <td width="5%">&nbsp;</td>
                <td width="20%"><input type="submit" value="Submit"></td>
                <td width="15%">&nbsp;</td>
            </tr>

<?php
		if(isset($commands->{strtoupper($action)}->PARAM)) {
?>

            <tr>
                <td >
                    <h4>Parameter</h4>
                </td>
                <td>

<?php
			foreach ($commands->{strtoupper($action)}->PARAM as $param => $param_desc) {
?>

                    <tr>
                        <td width="25%">
                            <label id="labeldepth"><?=$param;?></label>
                        </td>
                        <td>
                            <input type="text" name="<?=$param;?>" value="<?=$_REQUEST["$param"];?>">
                            <p class="hint"><?=$param_desc;?></p>
                        </td>
                    </tr>

<?php
			}
		}
?>

				</td>
			</tr>
        </table>
    </div>
</form>
<hr>

<!-- Output -->
	<h2>Output</h2>

	<?php
		$com = "?action=".$action.$command_get;
		if(strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false) { $com = $com."&vid=$type"; }
		if(strpos($commands->{strtoupper($action)}->URI, '{energy_site_id}') !== false) { $com = $com."&vid=$type"; }
		if(strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false and $force){ $com = $com."&force=true"; }
		if(isset($commands->{"$action"}->URI)){ echo "TeslaConnect URI: <span class=\"mono\">".strtolower($lbzeurl.$com)."</span><br>"; }

		if(isset($commandoutput)) {
			if(!$command_error) {
				if(isset($commands->{"$action"}->URI)){ echo "Tesla API URI: <span class=\"mono\">".BASEURL.$uri."</span><br>"; }
				if(!empty($command_post)){ echo "Tesla API PARAMETER: <span class=\"mono\">".json_encode($command_post)."</span><br>"; }
?>

<hr>
<div class="mono">
	<p><?php echo pretty_print($commandoutput);?></p>
</div>
<hr>
	
<?php
			}
		}
// if($tokenvalid == "false")
}

LBWeb::lbfooter();
?>