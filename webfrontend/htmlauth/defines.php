<?php

define ("BASEURLOLD", "https://owner-api.teslamotors.com/api/1");
define ("BASEURL", "https://owner-api.teslamotors.com/");
define ("LOGINFILE", "$lbpconfigdir/sessiondata.json");
define ("COMMANDFILE", "$lbpconfigdir/tesla_commands.json");
define ("MQTTTOPIC", "${lbpplugindir}");

// Template
$template_title = "TeslaConnect " . LBSystem::pluginversion();
$helplink = "https://loxwiki.atlassian.net/l/c/CEeb8Rmh";

// Command URI
//$lbzeurl ="http://&lt;user&gt;:&lt;pass&gt;@".LBSystem::get_localip()."/admin/plugins/".LBPPLUGINDIR."/tesla_command.php";
$lbzeurl ="http://&lt;user&gt;:&lt;pass&gt;@".LBSystem::get_localip()."/admin/plugins/".LBPPLUGINDIR."/tesla_command.php";

// The Navigation Bar
$navbar[1]['Name'] = "Settings";
$navbar[1]['URL'] = 'index.php';
 
$navbar[2]['Name'] = "Test queries";
$navbar[2]['URL'] = 'testqueries.php';