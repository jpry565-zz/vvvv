<?php
/****************************************************************************************************************************
 *    moduleEditUploadHelpers.php - Functions which are used frequently during the module upload/edit/submit process.
 *    ---------------------------------------------------------------------------------------------------------------
 *  This file contains functions which are used frequently throughout the module upload/submit/edit process.  These functions 
 *  assist in the submit/upload process and are used repeadetly by different stages of the process, so they are centralized in
 *  this file to avoid having to maintain the same code in seperate files.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - Make sure the file which is requireing or importing this file has requrired the data-manager!!!  These functions will not work
 *        without the data-manager, but the data-manager is not called from this file, since it is assumed the file calling this file
 *        will have already done that. 
 ******************************************************************************************************************************/
 
  /* saveAllPossible($request, $userInformation, $moduleInfo) - Saves all possible parts of a module during edit/upload.  Does not do anything with 
      materials.
    @parm $request - The $_REQUEST variable from the calling page.
    @parm $userInformation - An array of information about the logged in user from the calling page,
    @parm $moduleInfo - An array of information about the module to save.
    @return - Returns TRUE on success, FALSE on error. */
  function saveAllPossible($request, $userInformation, $moduleInfo) {
    $backendCapabilities=getBackendCapabilities(); //Get the backend's abilities
    /* Make sure the given user has the right to save this module. */
    if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && !($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
      return FALSE;
    }
    /* Get all information about the module currently stored. */
    $moduleID=$moduleInfo["moduleID"];
    $title=$moduleInfo["title"];
    $submitterUserID=$moduleInfo["submitterUserID"];
    $authorFirstName=$moduleInfo["authorFirstName"];
    $authorLastName=$moduleInfo["authorLastName"];
    $date=$moduleInfo["date"];
    $abstract=$moduleInfo["abstract"];
    $lectureSize=$moduleInfo["lectureSize"];
    $exerciseSize=$moduleInfo["exerciseSize"];
    $homeworkSize=$moduleInfo["homeworkSize"];
    $otherSize=$moduleInfo["otherSize"];
    $labSize=$moduleInfo["labSize"];
    $authorComments=$moduleInfo["authorComments"];
    $status=$moduleInfo["status"];
    $minimumUserType=$moduleInfo["minimumUserType"];
    $checkInComments=$moduleInfo["checkInComments"];
    $version=$moduleInfo["version"];
    $topics=getModuleTopics($moduleInfo["moduleID"]);
    $objectives=getModuleObjectives($moduleInfo["moduleID"]);
    $authors=getModuleAuthors($moduleInfo["moduleID"]);
    if(in_array("UseCategories", $backendCapabilities["read"])) { //Only try to read a current category if the backend supports it.
      $categoryIDs=getModuleCategoryIDs($moduleInfo["moduleID"]);
    }
    if(in_array("CrossReferenceModulesExternal", $backendCapabilities["read"])) {
      $externalReferences=getExternalReferences($moduleInfo["moduleID"]);
    }
    if(in_array("CrossReferenceModulesInternal", $backendCapabilities["write"])) {
      $internalReferences=getInternalReferences($moduleInfo["moduleID"]);
    }
    $prereqs=getModulePrereqs($moduleInfo["moduleID"]);
    
    /* Overright the current information about the module with any new module passed in through the $request variable. */
    if(isset($request["moduleAbstract"])) {
      $abstract=$request["moduleAbstract"];
    }
    if(isset($request["moduleLectureSize"])) {
      $lectureSize=$request["moduleLectureSize"];
    }
    if(isset($request["moduleExerciseSize"])) {
      $exerciseSize=$request["moduleExerciseSize"];
    }
    if(isset($request["moduleHomeworkSize"])) {
      $homeworkSize=$request["moduleHomeworkSize"];
    }
    if(isset($request["moduleOtherSize"])) {
      $otherSize=$request["moduleOtherSize"];
    }
    if(isset($request["moduleCategoryID"])) {
      $categoryID=$request["moduleCategoryID"];
    }
    if(isset($request["moduleLabSize"])) {
      $labSize=$request["moduleLabSize"];
    }
    if(isset($request["moduleMinimumUserType"])) {
      $minimumUserType=$request["moduleMinimumUserType"];
    }
    if(isset($request["moduleAuthorComments"])) {
      $authorComments=$request["moduleAuthorComments"];
    }
    
    $i=0;
    if(isset($request["moduleTopics".$i])) {
      $topics=array();
      while(isset($request["moduleTopics".$i])) {
        $topics[]=array("text"=>$request["moduleTopics".$i]);
        $i++;
      }
    }
    $i=0;
    if(isset($request["moduleObjectives".$i])) {
      $objectives=array();
      while(isset($request["moduleObjectives".$i])) {
        $objectives[]=array("text"=>$request["moduleObjectives".$i]);
        $i++;
      }
    }
    $i=0;
    if(isset($request["modulePrereqs".$i])) {
      $prereqs=array();
      while(isset($request["modulePrereqs".$i])) {
        $prereqs[]=array("text"=>$request["modulePrereqs".$i]);
        $i++;
      }
    }
    $i=0;
    if(isset($request["moduleERefs".$i]) && isset($request["moduleERefsLink".$i])) {
      $externalReferences=array();
      while(isset($request["moduleERefs".$i]) && isset($request["moduleERefsLink".$i])) {
        $externalReferences[]=array("description"=>$request["moduleERefs".$i], "link"=>$request["moduleERefsLink".$i]);
        $i++;
      }
    }
    $i=0;
    if(isset($request["moduleIRefs".$i]) && isset($request["moduleIRefsLink".$i])) {
      $internalReferences=array();
      while(isset($request["moduleIRefs".$i]) && isset($request["moduleIRefsLink".$i])) {
        $internalReferences[]=array("description"=>$request["moduleIRefs".$i], "referencedModuleID"=>$request["moduleIRefsLink".$i]);
        $i++;
      }
    }
    $i=0;
    if(isset($request["moduleCategory".$i])) {
      $categoryIDs=array();
      while(isset($request["moduleCategory".$i])) {
        $categoryIDs[]=$request["moduleCategory".$i];
        $i++;
      }
    }
    $i=0;
    if(isset($request["moduleAuthors".$i])) {
      $authors=array();
      while(isset($request["moduleAuthors".$i])) {
        $authors[]=$request["moduleAuthors".$i];
        $i++;
      }
    }
    
    /* Try to save the module, and return accordingly. */
    if(in_array("UseModules", $backendCapabilities["write"])) { //Only try to update the module if the backend supports writing modules
      $result=editModuleByID($moduleID, $abstract, $lectureSize, $labSize, $exerciseSize, $homeworkSize, $otherSize, $authorComments, $checkInComments, $submitterUserID, $status, $minimumUserType, FALSE);
      if($result=="NotImplimented" || $result===FALSE) {
        return FALSE; //Error saving module.
      }
    }
    if(in_array("UseCategories", $backendCapabilities["write"]) && $categoryIDs!==FALSE && $categoryIDs!=="NotImplimented") { //Only try to set a category if the backend supports writing categories and we actually have a category to set to.
      if(isset($request["noModuleCategories"]) && $request["noModuleCategories"]=="true") {
        $result=setModuleCategories($moduleID, array());
      } else {
        $result=setModuleCategories($moduleID, $categoryIDs);
      }
      if($result==="NotImplimented" || $result===FALSE) {
        return FALSE;
      }
    }
    if(in_array("CrossReferenceModulesExternal", $backendCapabilities["write"]) && $externalReferences!==FALSE && $externalReferences!=="NotImplimented") {
      /* The if statement below works around a bug which prevents one from deleting all external references.  This first checks for a parameter 
        "noModuleERefs" which, if set to "true", means that if no eRefs were found on the page, to remove all eRefs attatched to the module.  Normally, 
        finding no eRefs on the page would not change them, since it would be assumed they weren't found because we weren't called from the page of 
        the module wizard containing them.  This behavior is retained if the "noModuleERefs" parameter is not found or is not "true". */
      if(isset($request["noModuleERefs"]) && $request["noModuleERefs"]=="true") {
        $result=setExternalReferences($moduleID, array());
      } else {
        $result=setExternalReferences($moduleID, $externalReferences);
      }
      if($result==="NotImplimented" || $result===FALSE) {
        return FALSE;
      }
    }
    if(in_array("CrossReferenceModulesInternal", $backendCapabilities["write"]) && $internalReferences!==FALSE && $internalReferences!=="NotImplimented") {
      if(isset($request["noModuleIRefs"]) && $request["noModuleIRefs"]=="true") {
        $result=setInternalReferences($moduleID, array());
      } else {
        $result=setInternalReferences($moduleID, $internalReferences);
      }
      if($result==="NotImplimented" || $result===FALSE) {
        return FALSE;
      }
    }
    /* Set module topics... */
    if(isset($request["noModuleTopics"]) && $request["noModuleTopics"]=="true") {
      $result=setModuleTopics($moduleID, array());
    } else {
      $result=setModuleTopics($moduleID, $topics);
    }
    if($result==="NotImplimented" || $result===FALSE) {
      return FALSE;
    }
    /* Set module objectives... */
    if(isset($request["noModuleObjectives"]) && $request["noModuleObjectives"]=="true") {
      $result=setModuleObjectives($moduleID, array());
    } else {
      $result=setModuleObjectives($moduleID, $objectives);
    }
    if($result==="NotImplimented" || $result===FALSE) {
      return FALSE;
    }
    /* Set module prereqs... */
    if(isset($request["noModulePrereqs"]) && $request["noModulePrereqs"]=="true") {
      $result=setModulePrereqs($moduleID, array());
    } else {
      $result=setModulePrereqs($moduleID, $prereqs);
    }
    if($result==="NotImplimented" || $result===FALSE) {
      return FALSE;
    }
    /* Set module authors... */
    if(isset($request["noModuleAuthors"]) && $request["noModuleAuthors"]=="true") {
      $result=setModuleAuthors($moduleID, array());
    } else {
      $result=setModuleAuthors($moduleID, $authors);
    }
    if($result==="NotImplimented" || $result===FALSE) {
      return FALSE;
    }
    return TRUE;
  } //End saveAllPossible() function.
  
  
  /* submitModule($request, $userInformation, $moduleInfo, $checkInComments, $requiresModeration) - Submits a module to the back-end, changing its 
        status to either pending moderation, or active in the system, depending on if moderation is required or not.
      @parm $request - The $_REQUEST variable from the calling page.
      @parm $userInformation - Information about the user, as from the getUserInformationByID() function.
      @parm $moduleInfo - Information about the module to submit, as from the getModuleByID() function.
      @parm $checkInComments - Any "check-in comments" left by the user at check in time.
      @parm $requiresModeration - If the module should be submitted for moderation, set to TRUE.  Otherwise, the module will immedietly be made active. */
  function submitModule($request, $userInformation, $moduleInfo, $checkInComments, $requiresModeration) {
    if(!($userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) { //Is the user allowed to submit modules?
      return FALSE;
    }
    if(saveAllPossible($request, $userInformation, $moduleInfo)!==TRUE) { //Try to save anything that might have changed.
      return FALSE;
    }
    $moduleInfo=getModuleByID($moduleInfo["moduleID"]); //Reload the module information, since it might have changed after calling saveAllPossible() above.
    if($requiresModeration===TRUE) {
      $status="PendingModeration";
    } else {
      $status="Active";
    }
    /* Update the module, setting it to the proper status (as determined by the $status variable).  This shouldn't change anything except the status and
      checkInComments, but the editModuleByID() function requires all the parameters, even if most of them aren't changed. */
    $result=editModuleByID($moduleInfo["moduleID"], $moduleInfo["abstract"], $moduleInfo["lectureSize"], $moduleInfo["labSize"], $moduleInfo["exerciseSize"], $moduleInfo["homeworkSize"], $moduleInfo["otherSize"], $moduleInfo["authorComments"], $checkInComments, $moduleInfo["submitterUserID"], $status, $moduleInfo["minimumUserType"], FALSE);
    if($result===FALSE || $result==="NotImplimented") {
      return FALSE;
    }
    return TRUE;
  }

?>