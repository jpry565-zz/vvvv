<?php session_start();
/*********************************************************************************************************
 *      viewMaterial.php - Sends a material file stored locally to a web browser.
 *      -------------------------------------------------------------------------
 *  This is used to send a material to a browser, allowing users to download it.  It is necessary to used
 *  this file instead of downloading the file directly because filenames are likely stored as hashes, 
 *  so linking directly to a file would present a default name of a hash, which isn't pretty.
 *  In addition, it is likely that the directory actually storing the material files is not directly 
 *  readable by users (limited with .htaccess).  Using this file allows access to those files while also
 *  possibly enforcing access restrictions.
 *
 * Author: Ethan Greer
 * Version: 1
 *
 * Notes: - If you want to send a file to the browser and force the browser to offer to save it, you MUST
 *`         NOT print ANYTHING to the page before sending the file!  ONLY PRINT TO THE PAGE _AFTER_ sending
 *          the file or if there is an error preventing sending of the file.
 **********************************************************************************************************/
 
 require("lib/config/config.php"); //Load global collection configuration.
 require("lib/backends/backend.php"); //Load the storage back-end.
 
 function returnStandardHTMLHead() {
  require("lib/look/look.php"); //Load theme used.
  $head='<html><head><link rel="stylesheet" href="lib/look/'.$LOOK_DIR.'/main.css"></link></head><body>';
  return $head;
 }
 function returnStandardHTMLBottom() {
  $foot='</body></html>';
  return $foot;
 }
 function logout() {
    if(isset($_SESSION["authenticationToken"])) {
      $logOutResult=logUserOut($_SESSION["authenticationToken"]);
    }
    unset($_SESSION["authenticationToken"]);
  }
  
  /* Check if the user has a valid login.  If they do, set $userInformation, otherwise, log them off.  The log-in information will be used for 
    access restrictions for materials, which are inherited from the access restrictions of the modules the material is attached to. */
  if(isset($_SESSION["authenticationToken"])) { //Check if we think someone is already logged in.
    $userInformation=checkIfUserIsLoggedIn($_SESSION["authenticationToken"]);
    if(count($userInformation)==0) { //If true, than the user wasn't found
      logout();
      unset($userInformation);
    }
  }
  /* Done checking for valid log in. */
 
 if(!isset($_REQUEST["materialID"])) { //If no material ID is given, print an error and die.
  echo returnStandardHTMLHead();
  echo '<span class="error">No material ID specified.  You must specify a material ID to download.</span>';
  die(returnStandardHTMLBottom());
 }
 
 $materialInfo=getMaterialByID($_REQUEST["materialID"]);
 if($materialInfo===FALSE || $materialInfo=="NotImplimented" || count($materialInfo)<=0) { //If no material was found or an error occurred, print an error and die.
  echo returnStandardHTMLHead();
  echo '<span class="error">No material with the specified ID was found in this collection.</span>';
  die(returnStandardHTMLBottom());
 }
 
  /* If this far, then the user requested a valid material.  Check to make sure they can view it (based on their privelege level), and then either 
  given an error or show/redirect-to the material. */
 $canView=FALSE; //Will be set to TRUE once it is determined the person accessing this page may view the material.
 $parentModules=getAllModulesAttatchedToMaterial($materialInfo["materialID"]);
 if($parentModules===FALSE || $parentModules==="NotImplimented") {
  echo returnStandardHTMLHead();
  echo '<span class="error">Internal error while attempting to check material ownership.</span>';
  die(returnStandardHTMLBottom());
 }
 for($i=0; $i<count($parentModules); $i++) { //Loop through all modules attatched to the material.  Our goal is to find one with an access level at least as low as the currently logged in user.
  $module=getModuleByID($parentModules[$i]); //Get information about the current module being analyzed.
  if($module["minimumUserType"]=="Unregistered") { //Anyone can view materials/modules if the minimumUserType is "Unregistered".
    $canView=TRUE;
    break;
  }
  if($module["minimumUserType"]=="Viewer" && isset($userInformation)) { //Anyone logged in can view materials/modules with a minimumUserType of the lowest possible logged in type ("Viewer").
    $canView=TRUE;
    break;
  }
  if($module["minimumUserType"]=="SuperViewer" && isset($userInformation) && ($userInformation["type"]=="SuperViewer" || $userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
    $canView=TRUE;
    break;
  }
  if($module["minimumUserType"]=="Submitter" && isset($userInformation) && ($userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
    $canView=TRUE;
    break;
  }
  if($module["minimumUserType"]=="Editor" && isset($userInformation) && ($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
    $canView=TRUE;
    break;
  }
  if($module["minimumUserType"]=="Admin" && isset($userInformation) && $userInformation["type"]=="Admin") {
    $canView=TRUE;
    break;
  }
 }
 
 if($canView===TRUE) { //Send the material if we've determined the person accessing the page is allowed to view it.
  if($materialInfo["linkType"]=="ExternalURL") { //If the material URL is an external URL, just jump to the URL given.
    header('Location: '.$materialInfo["linkToMaterial"]); //Send a redirect to the material.
  } elseif($materialInfo["linkType"]=="LocalFile") {
    /* Start by getting the MIME type and local file name of the file to send. */
    $filename=$materialInfo["linkToMaterial"];
    $finfo=finfo_open(FILEINFO_MIME_TYPE); //Create a finfo resource, which will help determine the MIME type of files.
    $mimeType=finfo_file($finfo, $MATERIAL_STORAGE_DIR.$filename); //Get the MIME type
    finfo_close($finfo); //Close the finfo resource since its no longer needed.
    /* Send the file to the browser. */
    header('Content-type: '.$mimeType); //Send MIME type to browser.
    header('Content-Disposition: attachment; filename="'.$materialInfo["readableFileName"].'"'); //Tell the browser to download the file and suggest a file name.
    readfile($MATERIAL_STORAGE_DIR.$filename); //Send the file to the browser.
  } else { //Unhandled link type, given error and die.
    echo '<span class="error">Internal system error.  Unable to retrieve material due to unhandled link type.</span>';
    die();
  }
 } else { //This else block runs if the user id not a high enough privilege level to view the material.
  echo returnStandardHTMLHead();
  echo '<span class="error">You do not have permission to access this material.</span>';
  echo '<p>Access to this material is restricted.  To view this material, log in to this collection as a user with a higher privilege level and ';
  echo 'try again.  Or, contact the collection maintainer to request this material or an increase in your privilege level.</p>';
  die(returnStandardHTMLBottom());
 }