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
        font-family: monospace;
        font-size: 110%;
        font-weight: bold;
        color: green;

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
<p style="color:red">
    <b>You are not logged in.</b>
</p><br>

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

<h2>Queries for
    <?=$name . " (VID: " . $vid . ")\n"; ?></h2>

<?php
	
			if (isset($_GET['test_query'])) 
			{
				if(isset($_POST['action'])){
					$action = $_POST['action'];
					$force = $_POST['force'];
					$uri = $commands->{"$action"}->URI;
					$uri = str_replace("{vehicle_id}", "$vid", $uri);
					
					if(isset($commands->{strtoupper($action)})) {
						$command_post = [];
						$command_post_print = "";
						$command_get = "";
						$command_output = "";
						$command_error = false;

						if (strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false) {
							if(!empty($vid)) {
								LOGDEB("teslaqueries: vid: ".$vid);
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
							//echo tesla_query( $vid, $action, $force );
							if (!$command_error) {
							$commandoutput =  tesla_query( $vid, $action, $command_post, $force );
							LOGOK("teslaqueries: vid: $vid, action: $action".$command_post_print.($force ? ", force: $force" : ""));
							}

						} else {
							LOGOK("teslaqueries: action: $action".($force ? ", force: $force" : ""));
							$commandoutput =  tesla_query( $vid, $action, $command_post, $force );
						}
					} else {
						$commandoutput =  "Command not found\n";
						LOGERR("teslaqueries: Command not found");
					}
				}
			}
?>

<form method="post" name="main_form" action="?test_query">
    <div class="form-group">
        <table class="formtable" border="0" width="100%">
            <tr>
                <td width="25%">
                    <label id="labeldepth">
                        <h3>Command</h3>
                    </label>
                </td>
                <td>
                    <select name="action">

<?php
								foreach ($commands as $command => $attribut) {
?>

                        <option
                            value="<?=$command;?>"
                            <?php if($command == $action){ echo " selected"; } ?>><?=$command;?></option>

<?php
								}
			?>
                    </select>

<?php
								if(strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false and $commands->{strtoupper($action)}->TYPE == "GET") {
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

                </table>

            </div>
        </form>

<?php
			if (isset($commandoutput)){
?>

        <hr>
        <h3>Description</h3>
        <p>

<?php
				if(isset($commands->{"$action"}->DESC)){ echo $commands->{"$action"}->DESC."<br>"; }
?>

        </p>

<?php
				if(!$command_error){
					$com = "?action=".$action.$command_get;
					if(strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false) { $com = $com."&vid=$vid"; }
					if(strpos($commands->{strtoupper($action)}->URI, '{vehicle_id}') !== false and $force){ $com = $com."&force=true"; }
					if(isset($commands->{"$action"}->URI)){ echo "TeslaConnect URI: <span class=\"mono\">".strtolower($lbzeurl.$com)."</span><br>"; }
					if(isset($commands->{"$action"}->URI)){ echo "Tesla API URI: <span class=\"mono\">".BASEURL.$uri."</span><br>"; }
					if(!empty($command_post)){ echo "Tesla API PARAMETER: <span class=\"mono\">".json_encode($command_post)."</span><br>"; }
?>

        <h3>Output</h3>
        <hr>
        <div class="mono">
            <p><?php echo pretty_print($commandoutput); ?></p>
        </div>

<?php
				}

?>

        <hr>
		
<?php
			}
		// foreach vehicles
		}
	} 
}

LBWeb::lbfooter();
?>