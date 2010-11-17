<?php
/*****************************************************************************************************
 *    listRecords.php - Handles ListRecords requests to the OAI-PMH provider.
 *    -----------------------------------------------------------------------
 *  Handles ListRecords requests to the OAI-PMH provider via the listRecords() function.  Designed
 *  to be called from the main provider file (provider.php).
 *
 * Author: Ethan Greer
 * Version: 1.0 (based off SWEnet OAI-PMH provider, also by Ethan Greer).
 *
 * Notes: (none)
 ******************************************************************************************************/


/* listRecords($metadataPrefix) - Lists all the records in a database, using the metadataPrefix given.
	This function will look for "from", "until", "set", and "resumptionToken" parameters from GET
	or POST inputs.  "set" and "resumptionToken" aren't supported and will generate errors.  "from"
	and "until" will be used to limit the returned results to the time period given by "from" and "until"
	as described by the OAI-PMH documentation for the parameters.
    @parm $metadataPrefix: The metadataPrefix to return results in.  Currently only "oai_dc" is supported.*/
function listRecords($metadataPrefix) {
  require("config.php");
  
  if($metadataPrefix != "oai_dc") { /* We only support oai_dc */
    badArgument("cannotDisseminateFormat", "metadataPrefix=\"".$metadataPrefix."\"", "This repository only supports the \"oai_dc\" metadataPrefix.");
  }
  
  $set=FALSE; //Default to assume no set was asked for.
  if(isset($_REQUEST["set"])) { /* Did they ask for a set? */
    $set=$_REQUEST["set"];
  }
  
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
  
  if(isset($from) && isset($until)) {
    if(validDates($from, $until)===FALSE) {
      badArgument("badArgument", "", "The from and/or until parameters are invalid.");
    }
  }
  
  /* If either $from and/or $until have not been set by POST/GET parameters, set them to defaults of "everything". */
  if(!isset($from)) {
    $from="*";
  }
  if(!isset($until)) {
    $until="*";
  }
  
  if(dateNotBeforeEarliestDatestamp($until)!==TRUE) { //Check to make sure that "until" is less than the earliest date in the repository.
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
  
  /* If we're this far, than at least one record was found.  So print it. */
  echo $OAI_TOP."\n";
  echo "<responseDate>".strftime("%Y-%m-%dT%H:%M:%SZ", time())."</responseDate>\n";
  echo '<request verb="ListRecords">'.getRequestURL()."</request>\n";
  echo "<ListRecords>\n";
  if($set===FALSE || $set=="modules") { //Print modules if no set was given or the set to list was modules.
    /* NOTE:  Everything inside this for loop should be EXACTLY the same as as would be output by a GetRecord query.  The reason it is copied here instead
        of just re-using the getRecord() function is because we already have all the information we need to print the output, and calling getRecord() again
        for every record would needlessly add twice the amount of work (or more) to the back-end.  If performance is no object, and you prefer the absolutely
        easiest-to-maintain code, convert the contents of the for loop to just keep making calls to getRecord with the approperate parameters. */
    for($i=0; $i<count($moduleResults); $i++) {
      echo "<record>\n";
      echo "<header>\n<identifier>".urlencode(getBaseRepositoryIdentifier()."/module-".$moduleResults[$i]["moduleID"])."</identifier>\n";
      echo "<datestamp>".datesToOai($moduleResults[$i]["date"])."</datestamp>\n";
      echo "<setSpec>modules</setSpec>\n</header>\n";
      echo "<metadata>\n";
      echo "<oai_dc:dc xmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n";
      echo "<dc:title>".htmlspecialchars($moduleResults[$i]["title"], ENT_NOQUOTES)."</dc:title>\n";
      echo "<dc:description>".htmlspecialchars($moduleResults[$i]["abstract"], ENT_NOQUOTES)."</dc:description>\n";
      echo "<dc:date>".datesToOAI($moduleResults[$i]["date"], ENT_NOQUOTES)."</dc:date>\n";
      echo "<dc:creator>".htmlspecialchars($moduleResults[$i]["authorFirstName"].' '.$moduleResults[$i]["authorLastName"], ENT_NOQUOTES)."</dc:creator>";
      $topics=getModuleTopics($moduleResults[$i]["moduleID"]);
      foreach($topics as $topic) {
        echo "<dc:subject>".htmlspecialchars($topic["text"], ENT_NOQUOTES)."</dc:subject>\n";
      }
      echo "<dc:source>".returnModuleSource($moduleResults[$i]["moduleID"])."</dc:source>\n";
      /* If the minimum user type for the module requested is not "Unregistered" (everyone has access), than print the restriction as 
        part of the dc:rights element.  This provides a way for haresters to see if records might not be accessable, even if they are 
        harvestable.  If sending requests based on materials, the dc:rights field would instead be filled with whatever liscense/rights 
        statement the material uploader input. */
      if($moduleResults[$i]["minimumUserType"]!="Unregistered") {
        echo '<dc:rights>Access to this resource is only available to users who have registered an account with this collection and who are of type "'.$moduleResults[$i]["minimumUserType"].'" or higher.</dc:rights>';
      }
      echo "</oai_dc:dc>\n";
      echo "</metadata>\n";
      echo "</record>\n";
    }
  }
  if($set===FALSE || $set=="materials") { //Print materials if no set was given or the set was materials.
    /* NOTE: The contents of this loop should be the same thing printed as by a GetRecord request. */
    for($i=0; $i<count($materialResults); $i++) {
      echo "<record>\n";
      echo "<header>\n<identifier>".urlencode(getBaseRepositoryIdentifier()."/material-".$materialResults[$i]["materialID"])."</identifier>\n";
      echo "<datestamp>".datesToOai($materialResults[$i]["date"])."</datestamp>\n";
      echo "<setSpec>materials</setSpec>\n</header>\n";
      echo "<metadata>\n";
      echo "<oai_dc:dc xmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n";
      echo "<dc:title>".htmlspecialchars($materialResults[$i]["title"], ENT_NOQUOTES)."</dc:title>\n";
      echo "<dc:creator>".htmlspecialchars($materialResults[$i]["creator"], ENT_NOQUOTES)."</dc:creator>\n";
      echo "<dc:publisher>".htmlspecialchars($materialResults[$i]["publisher"], ENT_NOQUOTES)."</dc:publisher>\n";
      echo "<dc:rights>".htmlspecialchars($materialResults[$i]["rights"], ENT_NOQUOTES)."</dc:rights>\n";
      echo "<dc:description>".htmlspecialchars($materialResults[$i]["description"], ENT_NOQUOTES)."</dc:description>\n";
      /* Unless the type is NotSpecific, print what the type is (the DCMI type definitions don't include an "unknown" or "not specified") */
      if($materialResults[$i]["type"]!="NotSpecified") {
        echo "<dc:type>".$materialResults[$i]["type"]."</dc:type>\n";
      }
      echo "<dc:source>".returnMaterialSource($materialResults[$i]["materialID"])."</dc:source>\n";
      echo "</oai_dc:dc>\n";
      echo "</metadata>\n";
      echo "</record>\n";
    }
  }
  
  echo "</ListRecords>\n";
  echo "</OAI-PMH>";  
}

?>
