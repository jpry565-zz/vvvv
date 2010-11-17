<?php
/****************************************************************************************************************************
 *    provider.php - The main component of Edrepo's OAI-PMH provider.
 *    ---------------------------------------------------------------
 *  Handles all requests to the OAI-PMH provider, handing off requests to the appropriate sub-components of the provider if 
 *  necessary.  This is the only part of the provider harvisters interact with directly.  All subcomponents are stored in 
 *  files in the "lib" subdirectory.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: (none)
 ******************************************************************************************************************************/

require("../lib/backends/backend.php"); //Get datamanager functions.
require("lib/identify.php");
require("lib/getRecord.php");
require("lib/badArgument.php");
require("lib/listMetadataFormats.php");
require("lib/listRecords.php");
require("lib/listIdentifiers.php");
require("lib/dateFunctions.php");
require("lib/helpers.php");
require("lib/config.php"); //Get repository configuration and constants

/* Start by making sure no illegal parameters were given. */
$allowedParms = array("verb", "from", "until", "metadataPrefix", "set", "resumptionToken", "identifier");

/* This next block will check to make sure only valid parameters are passed and no parameter is used more than once. */
$query_string=$_SERVER["QUERY_STRING"];
//echo $query_string."<br><br>";
$query  = explode('&', $query_string);
$params = array();
foreach( $query as $param ) //Create a double array $params which has keys of each parameter and values of an array containing the value of each istance of the parameter.
{
  list($name, $value)=explode('=', $param);
  $params[urldecode($name)][] = urldecode($value);
}
foreach(array_keys($params) as $arg) {
  if(!in_array($arg, $allowedParms)) { //If the parameter isn't in $allowedParms, than its an invalid parameter.
     badArgument("badArgument", "", "Some invalid parameter was passed.");
  }
  //print_r(array_keys($arg));
  if(count($params[$arg]) > 1) { //If a parameter has multiple values, it must have been given more than once.
      badArgument("badArgument", "", "The perameter ".$arg." was found more than once.");
  }
}
/* If here, than all parameters given have valid names and there are no duplicates.  Its safe to
   return to using $_REQUEST */


/* Now collect the verbs from either GET or POST methods */
if(isset($_REQUEST["verb"])) {
  if($_REQUEST["verb"]=="GetRecord") {  //Is the verb GetRecord?
    if(isset($_REQUEST["identifier"]) && isset($_REQUEST["metadataPrefix"])) { //Make sure all required parameters for the GetRecord verb are present.
      getRecord($_REQUEST["identifier"], $_REQUEST["metadataPrefix"], TRUE, TRUE);
    } else { //One or more required parameters missing.  Give error.
      badArgument("badArgument", "", "Either the \"identifier\" or \"metadataPrefix\" arguments are missing.  Both are required.");
    }
  } elseif($_REQUEST["verb"]=="Identify") { //Is the verb Identify?
    identify();
  } elseif($_REQUEST["verb"]=="ListMetadataFormats") { //Is the verb ListMetadataFormats?
    listMetadataFormats();
  } elseif($_REQUEST["verb"]=="ListRecords") { //Is the verb ListRecords?
    if(isset($_REQUEST["resumptionToken"])) { /* Did they give a resumption token? */
      /* We actually don't support resumption tokens (we'll never give them).  Just cheat here any say any resumption
      * token is bad, since we never give them out. */
      badArgument("badResumptionToken", "resumptionToken=\"".$_REQUEST["resumptionToken"]."\"", "Actually, this repository doesn't even support resumption tokens.");
    }
    if(isset($_REQUEST["metadataPrefix"])) { //Check to make sure all required parameters for the ListRecords verb are present
      listRecords($_REQUEST["metadataPrefix"]);
    } else {
      badArgument("badArgument", "", "The metadataPrefix argument is missing.");
    }
  } elseif($_REQUEST["verb"]=="ListSets") { //Is the verb ListSets?
    //For now, we only support two built-in sets: modules and materials.  So just print this.
    echo $OAI_TOP;
    echo "<responseDate>".strftime("%Y-%m-%dT%H:%M:%SZ", time())."</responseDate>\n";
    echo '<request verb="ListIdentifiers">'.getRequestURL()."</request>\n";
    echo "<ListSets>\n";
    echo "<set>\n";
    echo "<setSpec>modules</setSpec>\n";
    echo "<setName>All modules in this collection.  The preferred set to harvest.</setName>\n";
    echo "</set>\n<set>\n";
    echo "<setSpec>materials</setSpec>\n";
    echo "<setName>All materials in this collection.  It is generably preferrable to harvest the 'modules' set.</setName>\n";
    echo "</set></ListSets>";
    echo '</OAI-PMH>';
  } elseif($_REQUEST["verb"]=="ListIdentifiers") { //Is the verb ListIdentifiers?
    if(isset($_REQUEST["resumptionToken"])) { /* Did they give a resumption token? */
      /* We actually don't support resumption tokens (we'll never give them).  Just cheat here any say any resumption
      * token is bad, since we never give them out. */
      badArgument("badResumptionToken", "resumptionToken=\"".htmlspecialchars($_REQUEST["resumptionToken"])."\"", "Actually, this repository doesn't even support resumption tokens.");
    }
    if(isset($_REQUEST["metadataPrefix"])) { //Check to make sure all required parameters for the ListIdentifiers verb are present.
      listIdentifiers($_REQUEST["metadataPrefix"]);
    } else {
      badArgument("badArgument", "", "The metadataPrefix argument is missing.");
    }
  } else { /* If here, an unknown verb was given. */
    badArgument("badVerb", "", "The verb given is unrecognized and can not be processed.");
  }
 } else { /*If here, no verb was given. */
  badArgument("badVerb", "", "No verb was given.  The request can not be processed without a valid verb.");
}

?>
