<?php
/******************************************************************************************************
 *    identify.php - Handles requests to the provider with the "Identify" verb.
 *    ------------------------------------------------------------------------------
 *
 * Version: 1.0
 * Author: Ethan Greer
 *
 * Notes: (none)
 *******************************************************************************************************/

function identify() {
	include("config.php");
	require("../lib/config/config.php"); //Require the master system configuration
	echo $OAI_TOP."\n";
	echo "<responseDate>".strftime("%Y-%m-%dT%H:%M:%SZ", time())."</responseDate>\n";
	echo "<request verb=\"Identify\">".getProviderURL()."</request>\n";
	echo "<Identify>\n";
	echo "<repositoryName>".$COLLECTION_NAME."</repositoryName>\n";
	echo "<baseURL>".getProviderURL()."</baseURL>\n";
	echo "<protocolVersion>2.0</protocolVersion>\n";
	foreach($ADMIN_EMAILS as $emailAddress) {
		echo "<adminEmail>".$emailAddress."</adminEmail>\n";
	}
	echo "<earliestDatestamp>".$EARLIEST_DATESTAMP."</earliestDatestamp>\n";
	echo "<deletedRecord>".$DELETED_RECORDS_SYSTEM."</deletedRecord>\n";
	echo "<granularity>".$GRANULARITY."</granularity>\n";
	//If compression support is added to this provider, add an Identify record for it here
	
	echo "</Identify>\n";
	echo "</OAI-PMH>";
}
?>
