<?php
/******************************************************************************************************
 *    helpers.php - Functions which are common to all parts of the OAI-PMH provider.
 *    ------------------------------------------------------------------------------
 *  These functions are used with most or all of the OAI-PMH provider components, and are centrailized
 *  in this file to avoid having to make changes to all files whenever a single, universal change is
 *  made.
 *
 * Version: 1.0
 * Author: Ethan Greer
 *
 * Notes: (none)
 *******************************************************************************************************/

/* getProviderURL() - Retruns a string which is the URL the provider for this collection can be found at.
  @return - A string which is the URL the provider is located at. */
function getProviderURL() {
  return "http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["REQUEST_URI"])."/provider.php";
}

function getRequestURL() {
  return "http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["REQUEST_URI"])."/provider.php";
}

/* getBaseRepositoryIdentifier($level) - Gets a level of the repository identifier.  This is made in the form:
    oai:host_or_domain_name:COLLECTION_SHORTNAME */
function getBaseRepositoryIdentifier() {
  require("../lib/config/config.php"); //Require master system configuration
  return "oai:".$_SERVER["SERVER_NAME"].":".$COLLECTION_SHORTNAME;
}

?>