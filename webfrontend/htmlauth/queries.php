<?php
require_once "loxberry_system.php";
require_once "loxberry_web.php";
require_once "tesla_inc.php";
require_once "defines.php";

$navbar[2]['active'] = True;

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

<?php
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
				if ((strpos($attribut->URI, '{vehicle_id}') || strpos($attribut->URI, '{energy_site_id}')) == false) {				
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
            if(isset($vehicle->energy_site_id)){
                $name = $vehicle->site_name;
                $vid = strval($vehicle->energy_site_id);
                $vehicle_id = "&vid=$vid";
            } else {
                $name = $vehicle->display_name;
                $vid = strval($vehicle->id);
                $state = $vehicle->state;
                $vehicle_id = "&vid=$vid";                
            }
?>

<h2>Queries for
    <?=$name . " (VID: " . $vid . ")\n"; ?></h2>
<h3>Get informations</h3>

<?php
if (isset($vehicle->vin)){
?>

<p>
    <i>If you add the parameter
        <span class="mono">&force=true</span>, the vehicle will be woken up if the request is not possible.</i>
</p>

<?php
}
		foreach ($commands as $command => $attribut) {

			if($attribut->TYPE == "GET"){
				//Vehicle GET
				if (isset($vehicle->vin) && strpos($attribut->URI, '{vehicle_id}') == true) {
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
				} elseif (isset($vehicle->energy_site_id) && strpos($attribut->URI, '{energy_site_id}') == true) {
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
        if (isset($vehicle->vin)){
?>

<h3>Send commands</h3>

<?php
        }
			foreach ($commands as $command => $attribut) {
				if($attribut->TYPE == "POST"){
					//Vehicle POST
					if (isset($vehicle->vin) && strpos($attribut->URI, '{vehicle_id}') == true) {
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
	} 
}
LBWeb::lbfooter();
?>