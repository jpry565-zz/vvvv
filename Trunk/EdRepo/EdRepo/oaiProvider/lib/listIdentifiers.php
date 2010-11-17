<?php
/******************************************************************************************************
 *    listIdentifiers.php - Functions to handle "ListIdentifiers" requests to the provider.
 *    ------------------------------------------------------------------------------
 *
 * Version: 1.0
 * Author: Ethan Greer
 *
 * Notes: (none)
 *******************************************************************************************************/

/* listIdentifiers($metadataPrefix) - Lists all the records in a database, using the metadataPrefix given.
	This function will look for "from", "until", "set", and "resumptionToken" parameters from GET
	or POST inputs.  "set" and "resumptionToken" aren't supported and will generate errors.  "from"
	and "until" will be used to limit the returned results to the time period given by "from" and "until"
	as described by the OAI-PMH documentation for the parameters.
    @parm $metadataPrefix: The metadataPrefix to return results in.  Currently only "oai_dc" is supported.*/
function listIdentifiers($metadataPrefix) {
  require("config.php"); //Load provider configuration
  $backendCapabilities=getBackendCapabilities(); //Get all backend capabilities, since they will be needed to fetch identifiers.
  
  if(!in_array("UseModules", $backendCapabilities["read"]) || !in_array("SearchModulesByDate", $backendCapabilities["read"])) { //The backend must support both working with modules and searching modules by date in read more to use this.
    badArgument("noRecordsMatch", "", "This collection's backend storage system is not capable of serving ListIdentifiers requests.");
  }
  
  if($metadataPrefix != "oai_dc") { /* We only support oai_dc */
    badArgument("cannotDisseminateFormat", "metadataPrefix=\"".$metadataPrefix."\"", "This repository only supports the \"oai_dc\" metadataPrefix.");
  }
  
  $set=FALSE; //Default to assume no set was asked for.
  if(isset($_REQUEST["set"])) { /* Did they ask for a set? */
    $set=$_REQUEST["set"];
  }
  
  /* The following block will ensure the times in from and until are the same granularity, if both from and until are
     given.  It works by making sure that if from ends in "Z" (indicating a time), until also ends in "Z" and vice-versa.*/
  /*if($_GET["from"] && $_GET["until"]) {
    if($_GET["from"][strlen($_GET["from"])-1] == "Z" && $_GET["until"][strlen($_GET["until"])-1] != "Z") {
      badArgument("badArgument", "from=\"".datesToOAI($from)."\" until=\"".datesToOAI($until)."\"", "from and until must have the same granularity.");
    } elseif($_GET["until"][strlen($_GET["until"])-1] == "Z" && $_GET["from"][strlen($_GET["from"])-1] != "Z") {
      badArgument("badArgument", "from=\"".datesToOAI($from)."\" until=\"".datesToOAI($until)."\"", "from and until must have the same granularity.");
    }
  }
  if($_POST["from"] && $_POST["until"]) {
    if($_GET["from"][strlen($_GET["from"])-1] == "Z" && $_GET["until"][strlen($_GET["until"])-1] != "Z") {
      badArgument("badArgument", "from=\"".datesToOAI($from)."\" until=\"".datesToOAI($until)."\"", "from and until must have the same granularity.");
    } elseif($_GET["until"][strlen($_GET["until"])-1] == "Z" && $_GET["from"][strlen($_GET["from"])-1] != "Z") {
      badArgument("badArgument", "from=\"".datesToOAI($from)."\" until=\"".datesToOAI($until)."\"", "from and until must have the same granularity.");
    }
  }*/
  
  
  /* If either the from or until parameters were passed, get them. */
  if(isset($_REQUEST["from"])) {
    $from=$_REQUEST["from"];
    if(dateFormatOkay($from)!==TRUE) {
      badArgument("badArgument", "", "The date/time given as a 'from' is invalid.");
    }
  }
  if(isset($_REQUEST["until"])) {
    $until=$_REQUEST["until"];
    if(dateFormatOkay($until)!==TRUE) {
      badArgument("badArgument", "", "The date/time given as an 'until' is invalid.");
    }
  }
  
  /* If both from and until were passed, verify that they are valid. */
  if(isset($from) && isset($until)) {
    /* Make sure the dates/times in $from and $until are valid. */
    if(validDates($from, $until)==FALSE) {
      badArgument("badArgument", "", "The from and/or until parameters are invalid.");
    }
    if(strtotime($from) > strtotime($until)) {
      badArgument("badArgument", "from=\"".datesToOAI($from)."\" until=\"".datesToOAI($until)."\"", "from can not be larger than until.");
    }
    if(strtotime($until) < strtotime($from)) {
      badArgument("badArgument", "from=\"".datesToOAI($from)."\" until=\"".datesToOAI($until)."\"", "until cann not be smaller than from.");
    }
  }
  
  /* If either from and/or until was not specified, fill it with a wildcard (*) which will prevent filtering results by that parameter. */
  if(!isset($from)) {
    $from="*";
  }
  if(!isset($until)) {
    $until="*";
  }
  
  if(dateNotBeforeEarliestDatestamp($until)!==TRUE) { //Check to make sure "until" is less than the earliest date in the repository.
    badArgument("badArgument", "", "You have requested a record from a date/time earlier than the earliest record in this repository.");
  }
  
  if($set===FALSE || $set=="modules") { //If no set was specified or the set specified was "modules", get all modules matching $from and $until.
    $moduleResults=searchModules(array("minDate"=>$from, "maxDate"=>$until));
    if($moduleResults===FALSE || $moduleResults=="NotImplimented") { //Just handle these errors as if no results were found.
      $moduleResults=array();
    }
  }
  if($set===FALSE || $set=="materials") { //If no set was specified or the set specified was "materials", get all materials matching $from and $until
    $materialResults=searchMaterials(array("minDate"=>$from, "maxDate"=>$until));
    if($materialResults===FALSE || $materialResults=="NotImplimented") { //Just handle these errors as if no results were found.
      $materialResults=array();
    }
  }
  
  /* Explination:
     ( If neither $moduleResults or $materialResults is )    ( If no set was specified, but no modules or materials were found, )    ( If the set specified was modules, but no  )    ( If the set specified was materials, but no    )
     ( set than an unkown set was specified, which means) OR ( than everything was searched but nothing was found.              ) OR ( results were found when searching         ) OR ( materials were found when searching materials )
     ( no records were found.                           )    (                                                                  )    ( modules, than nothing was found.          )    ( than nothing was found.                       ) */
  if((!isset($moduleResults) && !isset($materialResults)) || ($set===FALSE && (count($moduleResults)+count($materialResults))<=0) || ($set=="modules" && count($moduleResults)<=0) || ($set=="materials" && count($materialResults)<=0)) { //If true, than no records mathed queryAdd
    if($from && !$until) {
      badArgument("noRecordsMatch", "from=\"".datesToOAI($from)."\"", "Sorry, but your query returned no results.");
    }
    if($until && !$from) {
      badArgument("noRecordsMatch", "until=\"".datesToOAI($until)."\"", "Sorry, but your query returned no results.");
    }
    if($until && $from) {
      badArgument("noRecordsMatch", "from=\"".datesToOAI($from)."\" until=\"".datesToOAI($until)."\"", "Sorry, but your query returned no results.");
    }
    if(!$until && !$from) {
      badArgument("noRecordsMatch", "", "Sorry, but your query returned no results.  Either the repository isn't working right or it is empty.");
    }
  }
  
  echo $OAI_TOP."\n";
  echo "<responseDate>".strftime("%Y-%m-%dT%H:%M:%SZ", time())."</responseDate>\n";
  echo "<request verb=\"ListIdentifiers\" metadataPrefix=\"$metadataPrefix\">".getRequestURL()."</request>\n";
  echo "<ListIdentifiers>\n";
  if($set===FALSE || $set=="modules") { //If no set was specified or the set specified was modules, show module identifiers.
    for($i=0; $i<count($moduleResults); $i++) { //Print all the modules found.
      echo "<header>\n";
      echo "<identifier>".urlencode(getBaseRepositoryIdentifier()."/module-".$moduleResults[$i]["moduleID"])."</identifier>\n";
      echo "<datestamp>".datesToOAI($moduleResults[$i]["date"])."</datestamp>\n";
      echo "<setSpec>modules</setSpec>\n";
      echo "</header>\n";
    }
  }
  if($set===FALSE || $set=="materials") { //If no set was specified or the set specified was materials, show material identifiers.
    for($i=0; $i<count($materialResults); $i++) { //Print all the materials found.
      echo "<header>\n";
      echo "<identifier>".urlencode(getBaseRepositoryIdentifier()."/material-".$materialResults[$i]["materialID"])."</identifier>\n";
      echo "<datestamp>".datesToOAI($materialResults[$i]["date"])."</datestamp>\n";
      echo "<setSpec>materials</setSpec>\n";
      echo "</header>\n";
    }
  }
  echo "</ListIdentifiers>\n";
  echo "</OAI-PMH>";  
}

?>
