<?php

/* Lists metadata formats available.  Since this only supports oai_dc, this will be the only format returned. */
function listMetadataFormats() {
  //include("config/config.php");
  
  if(isset($_REQUEST["identifier"])) {
    $len = strlen(getBaseRepositoryIdentifier());
    $requestedRepository = substr($_REQUEST["identifier"], 0, $len);
    if($requestedRepository != getBaseRepositoryIdentifier()) {
      badArgument("badArgument", "identifier=\"".$_REQUEST["identifier"]."\"", "The repository requested repository (".$requestedRepository.") does not match this repository (".getBaseRepositoryIdentifier().").");
    }
    
    /*If we got to here, we know they requested a vaild record.  We only support oai_dc, though.*/
    listOaiDC($_REQUEST["identifier"]);
  } /* End of if($_REQUEST["identifier"]) */
  else { /* Run is no identifier is given. */
    listOaiDC("");
  }
}
  
function listOaiDC($identifier) {
  require("config.php");
  echo $OAI_TOP."\n";
  echo "<responseDate>".strftime("%Y-%m-%dT%H:%M:%SZ", time())."</responseDate>\n";
  if($identifier!="") {
    echo "<request verb=\"ListMetadataFormats\" identifier=\"".$identifier."\">".getRequestURL()."</request>\n"; /* Its safe to hardcore "listMetadataFormats" because this function
													  * wouldn't be called unless this was the verb. */
  }
  else {
    echo "<request verb=\"ListMetadataFormats\">".getRequestURL()."</request>\n"; /* Its safe to hardcode "listMetadataFormats becuase this function
									     * wouldn't be called if this wasn't the verb. */
  }
  echo "<ListMetadataFormats>\n";
  echo " <metadataFormat>\n";
  echo "  <metadataPrefix>oai_dc</metadataPrefix>\n";
  echo "  <schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>\n";
  echo "  <metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/</metadataNamespace>\n";
  echo " </metadataFormat>\n";
  echo "</ListMetadataFormats>\n";
  echo "</OAI-PMH>\n";
}

?>
