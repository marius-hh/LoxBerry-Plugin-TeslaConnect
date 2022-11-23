<?php
define ("BASEURL", "https://owner-api.teslamotors.com/");
define ("LOGINFILE", "$lbpconfigdir/sessiondata.json");
define ("COMMANDFILE", "$lbpconfigdir/tesla_commands.json");
define ("MQTTTOPIC", "${lbpplugindir}");

// Template
$template_title = "TeslaConnect " . LBSystem::pluginversion();
$helplink = "https://wiki.loxberry.de/plugins/teslaconnect/start";

// Command URI
$lbzeurl ="http://&lt;user&gt;:&lt;pass&gt;@".LBSystem::get_localip()."/admin/plugins/".LBPPLUGINDIR."/tesla_command.php";

// The Navigation Bar
$navbar[1]['Name'] = "Settings";
$navbar[1]['URL'] = 'index.php';
$navbar[2]['Name'] = "Queries";
$navbar[2]['URL'] = 'queries.php';
$navbar[3]['Name'] = "Test queries";
$navbar[3]['URL'] = 'testqueries.php';
$navbar[99]['Name'] = "Logfiles";
$navbar[99]['URL'] = '/admin/system/logmanager.cgi?package='.LBPPLUGINDIR;
$navbar[99]['target'] = '_blank';