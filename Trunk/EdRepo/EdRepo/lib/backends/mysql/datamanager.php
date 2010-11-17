<?php
/****************************************************************************************************************************
 *    datamanager.php - The datamanager for the MySQL backend.
 *    ---------------------------------------------------------
 *  This file is the datamanager component of the MySQL backend.  Contains all datamanager functions.  In the MySQL backend, much
 *  of the datamanager and backend code are combined into one file, so this file also contains most of the backend code.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - All files in the directory containing this file are parts of the MySQL backend.
 *         - This datamanager also contains lots of backend code.
 ******************************************************************************************************************************/

require("validate.php");

/* Data-managers must have all of these functions.  If the back-end does not support a function, it must return "NotImplimented".  
The getBackendCapabilities() function is REQUIRED!  Back-ends are free to impliment every function here however they want, either 
within the data-manager or through a collection of other files/functions. */


/* getBackendCapabilities() - Returns all capabilities a back-end supports. 
  @return - Returns a 2D associative array of all capabilities a back-end supports.  The first dimension if "read" and "write", which 
    allows back-ends to indicate if they support a feature in read-only or read-write mode.  The second dimension is the list of 
    capabilities a back-end supports for each mode. 
      POSSIBLE CAPABILITIES (possible modes): meaning of capability:
        UseModules (read, write): Support for working with (displaying, creating, editing, removing) modules.
        ModuleVersions (read, write): Support for multiple versions of modules.  Back-ends which support reading must also support writing.
        UseMaterials (read, write): Support for working with (displaying, creating, editing, removing) materials.
        UseUsers (read, write): Support for working with (displaying, creating, editing, removing) users.
        UsersSoftRemove(read, write): Support for deleating users "softly" (ie, not actually deleating them but instead just changing their state to deleated).
          *Note: Any back-end which supports UsersSoftRemove in write mode is expected to properly handle softly-removed users in normal user mode (they will 
          act as though softly-removed users are deleted).  If a back-end supports UsersSoftRemove ONLY in write mode, this indicates the back-end can not
          manage softly-removed users (since that would require reading them back).  If back-ends choose to support UsersSoftRemove, they should therefore
          make every effort to support this capability in both modes, or they will likely create lots of garbage from left-over softly-deleated users.
          *Note2: The purpose of softly-deleating users is usually to preserve information used by modules a user has uploaded even after they deleate
            their account.  If users can be hard-deleated without damaging references in modules, it is better to hard-deleate them.  Therefore, DO NOT
            SUPPORT THIS CAPABILITY UNLESS IT IS ABSOLUTELY NEEDED!  The front-end will default to automatically use this capability if it is available, 
            assuming it is required to maintain database consistancy.  If it isn't, don't advertise this capability which will cause the front-end to 
            always default to hard-remove users, which is better is possible and safe.
        UseCategories (read, write): Support for working with categories both stand-alone (displaying, editing, creating, removing), and with 
            modules (if the UseModules capability is also present).
        SearchModulesByTitle (read): Support for searching modules by title.
        SearchModulesByDate (read): Support for searching modules by date (absolute, min, and max).
        SearchModulesByID (read): Support for searching modules by ID.
        SearchModulesByCategory (read): Support for searching modules by category (does not automatically imply UseCategories support).
        SearchModulesByAbstract (read): Support for searching modules by abstract/description.
        SearchModulesByStatus (read): Support for searching modules by status.
        SearchModulesByUserID (read): Support for searching modules by the ID of the user who created the module (User ID).
        SearchModulesByAuthor (read): Support for searching modules by author (note: the author will come in as one string, back-ends which seperate first name from last name must be smart enough to deal with this!).
        SearchMaterialsByID (read): Support for searching materials by ID.
        SearchMaterialsByDate (read): Support for searching materials by date.
        SearchMaterialsByAuthor (read): Support for searching materials by author.
        HandleOrphanMaterials (read, write): Support for automatic orphan material detection and deletion.
        HandleModeration(read, write): Support for a moderation system for modules and materials.
        CrossReferenceModulesInternal (read, write): Support for cross-referencing modules to internal (within collection) sources.
        CrossReferenceModulesExternal (read, write): Support for cross-referencing modules to external (outside collection) sources.
        RateModules (read, write): Indicates support for rating modules.
        RateMaterials (read, write): Indicates support for rating materials.  Implies the ability to read/write comments on the material as well.
*/
function getBackendCapabilities() {
  $read=array("UseUsers", "UsersSoftRemove", "UseModules", "SearchModulesByUserID", "SearchModulesByStatus", "SearchModulesByDate", "UseVersions", "UseCategories", "UseMaterials", "SearchModulesByCategory", "SearchModulesByTitle", "CrossReferenceModulesInternal", "CrossReferenceModulesExternal", "RateModules", "RateMaterials");
  $write=array("UseUsers", "UsersSoftRemove", "UseModules", "UseVersions", "UseCategories", "UseMaterials", "CrossReferenceModulesExternal", "CrossReferenceModulesInternal", "RateModules", "RateMaterials");
  return array("read"=>$read, "write"=>$write);
}

/* getBackendBasicInformation() - Gets basic information about a backend, such as name, liscense, version, etc.
  @return - Returns an associative array with the following keys and meanings:
    name: The name of the backend.
    version: The version of the backend.
    author: The backend author (or group which made the backend).  May be left blank, but not recomended.
    email: A contact email for the backend.
    license: The name of the liscense the backend is released under, or the text of the liscense.  Public Domain is also acceptable, obviously. */
function getBackendBasicInformation() {
  return array("name"=>"MySQL Backend", "version"=>"0.02", "author"=>"Ethan Greer", "email"=>"elg42@drexel.edu", "license"=>"");
}


/* getModuleByID($moduleID) - Gets information about a single module given by a unique ID.
  @parm $moduleID - The ID of the module to fetch.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error (not finding a module with the ID is not an error).
    Returns an array of the module parameters on success, or an empty array if no module was found. */
/** WARNING:
  The return keys "authorFirstName" and "authorLastName" are depricated!  Use the getModuleAuthors() and setModuleAuthors() functions to work with 
  module authors instead!
*/
function getModuleByID($moduleID) {
  require("settings.php");
  $getModuleDbConnection=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$getModuleDbConnection) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE, $getModuleDbConnection);
  $result=mysql_query("SELECT * FROM module WHERE ModuleID='".mysql_real_escape_string($moduleID)."';", $getModuleDbConnection);
  if($result===FALSE) {
    mysql_close();
    echo "Result=FALSE";
    return FALSE;
  } elseif(mysql_num_rows($result)<=0) {
    return array();
  }
  $row=mysql_fetch_assoc($result);
  $date=$row["Date"];
  $abstract=$row["Abstract"];
  $lectureSize=$row["LectureSize"];
  $labSize=$row["LabSize"];
  $exerciseSize=$row["ExerciseSize"];
  $homeworkSize=$row["HomeworkSize"];
  $otherSize=$row["OtherSize"];
  $authorComments=$row["AuthorComments"];
  $status=$row["Status"];
  $minimumUserType=$row["MinimumUserType"];
  $checkInComments=$row["CheckInComments"];
  $version=$row["Version"];
  $submitterUserID=$row["SubmitterUserID"];
  $result=mysql_query("SELECT Title FROM modulebases INNER JOIN module ON modulebases.BaseID=module.BaseID WHERE module.ModuleID='".mysql_real_escape_string($moduleID)."';", $getModuleDbConnection);
  if($result===FALSE) {
    mysql_close($getModuleDbConnection);
    return FALSE;
  }
  $row=mysql_fetch_assoc($result);
  $title=$row["Title"];
  $result=mysql_query("SELECT FirstName, LastName FROM users INNER JOIN module ON users.UserID=module.SubmitterUserID WHERE module.ModuleID='".mysql_real_escape_string($moduleID)."';", $getModuleDbConnection);
  if($result===FALSE) {
    mysql_close($getModuleDbConnection);
    return FALSE;
  }
  $row=mysql_fetch_assoc($result);
  $authorFirstName=$row["FirstName"];
  $authorLastName=$row["LastName"];
  mysql_close($getModuleDbConnection);
  return array("moduleID"=>$moduleID, "title"=>$title, "submitterUserID"=>$submitterUserID, "authorFirstName"=>$authorFirstName, "authorLastName"=>$authorLastName, "date"=>$date, "abstract"=>$abstract, "lectureSize"=>$lectureSize, "exerciseSize"=>$exerciseSize, "homeworkSize"=>$homeworkSize, "labSize"=>$labSize, "otherSize"=>$otherSize, "authorComments"=>$authorComments, "status"=>$status, "minimumUserType"=>$minimumUserType, "checkInComments"=>$checkInComments, "version"=>$version);
}

/* getMaterialByID($materialID) - Gets information about a single material given by a unique ID.
  @parm $materialID - The ID of the material to fetch.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error (not finding a material with the ID is not an error).
    Returns an array of the material parameters on success, or an empty array if no material was found. */
function getMaterialByID($materialID) {
  require("settings.php");
  $gmbidb=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$gmbidb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE, $gmbidb);
  $result=mysql_query("SELECT * FROM materials WHERE MaterialID=".$materialID.";", $gmbidb);
  if($result===FALSE) {
    mysql_close($gmbidb);
    return FALSE;
  }
  if(mysql_num_rows($result)<=0) { //If true, no matching material was found.
    mysql_close($gmbidb);
    return array();
  }
  $row=mysql_fetch_assoc($result);
  $material=array("materialID"=>$row["MaterialID"], "linkToMaterial"=>$row["LinkToMaterial"], "linkType"=>$row["LinkType"], "readableFileName"=>$row["ReadableFileName"], "type"=>$row["Type"], "title"=>$row["Title"], "rights"=>$row["Rights"], "language"=>$row["Language"], "publisher"=>$row["Publisher"], "description"=>$row["Description"], "creator"=>$row["Creator"], "date"=>$row["Date"]);
  mysql_close($gmbidb);
  return $material;
}

/* searchModules($searchParameters) - Searches modules and returns results based on certain parameters.
  @parm $searchParameters - An associative array listing all search parameters.  The keys are the parameter type, and the values are the 
    text of the parameter.  For example, array("category"=>"Turtles", "author"=>"Jasper") would search for modules in the "Turtles" category 
    by author Jasper.  Note that unknown parameters (keys) are expected to be ignored silently.
  @return - Returns "NotImplimented" if no searching of any kind is supported.
    Returns FALSE on any error.
    Returns a 2D array on success.  The first dimension is numerically indexed, and the second dimension is an associative array with the keys 
      being the attributes of the matching modules, and the values being the text of the attributes (the structure being the same as getModulesByID() ).  
      Returns an empty array if no matching results were found. */
function searchModules($searchParameters) {
  //var_dump($searchParameters);
  require("settings.php");
  $moduleSearchDbConnection=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$moduleSearchDbConnection) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE, $moduleSearchDbConnection);
  if(count($searchParameters)>=1) { //Do we have have search parameters?  If so, build a query with them.  Note that its up to the front-end to avoid passing unhandled parameters (based on this back-ends capabilities)
    $and="";
    $where="WHERE ";
    $query="SELECT ModuleID FROM module ";
    if(isset($searchParameters["title"]) && isset($searchParameters["titleStartsWith"])) { //One may not search by both "title" and "titleStartsWith".
      mysql_close($moduleSearchDbConnection);
      return FALSE;
    }
    if(isset($searchParameters["title"])) {
      /* Searching by title is special, because we have to look in a different table.  So, search for it first to do the inner join. */
      $query=$query."INNER JOIN modulebases ON module.BaseID=modulebases.BaseID WHERE modulebases.Title LIKE '%".mysql_real_escape_string($searchParameters["title"])."%' ";
      $where="";
      $and="AND ";
    }
    if(isset($searchParameters["titleStartsWith"])) { //This parameter is like searching by title, but instead of looking for the parameter given anywhere in the title, it is looked for only in the beginning of the title.  Useful for browsing.
      $query=$query."INNER JOIN modulebases ON module.BaseID=modulebases.BaseID WHERE modulebases.Title LIKE '".mysql_real_escape_string($searchParameters["titleStartsWith"])."%' ";
      $where="";
      $and="AND ";
    }
    if(isset($searchParameters["userID"])) {
      $query=$query.$where.$and."module.SubmitterUserID='".$searchParameters["userID"]."' ";
      $and="AND ";
      $where="";
    }
    if(isset($searchParameters["status"]) && $searchParameters["status"]!=="*") { //Limit results by status if a status is given and is isn't "*" (which would indicate we're to match all statuses, so no need to limit by them).
      $query=$query.$where.$and."module.Status='".mysql_real_escape_string($searchParameters["status"])."' ";
      $and="AND ";
      $where="";
    }
    if(isset($searchParameters["absoluteDate"]) && $searchParameters["absoluteDate"]!=="*") {
      $query=$query.$where.$and."module.Date == '".$searchParameters["absoluteDate"]."' ";
      $and="AND ";
      $where="";
    } else {
      if(isset($searchParameters["minDate"]) && $searchParameters["minDate"]!=="*") {
        /* Start by converting whatever date that came in into a format suitable for MySQL. */
        $date = strftime("%Y-%m-%d %H:%M:%S_" ,strtotime($searchParameters["minDate"]));
        $dateEnd = strpos($date, "_");
        $date = substr($date, 0, $dateEnd);
        $query=$query.$where.$and."module.Date >= '".$date."' ";
        $and="AND ";
        $where="";
      }
      if(isset($searchParameters["maxDate"]) && $searchParameters["maxDate"]!=="*") {
        $date = strftime("%Y-%m-%d %H:%M:%S_" ,strtotime($searchParameters["maxDate"]));
        $dateEnd = strpos($date, "_");
        $date = substr($date, 0, $dateEnd);
        $query=$query.$where.$and."module.Date <= '".$date."' ";
        $and="AND ";
        $where="";
      }
    }
  } else { //If this else block runs, we didn't have any search parameters for phase 1, so search for everything.
    $query="SELECT ModuleID FROM module;";
  }
  $result=mysql_query($query, $moduleSearchDbConnection);
  //echo "<br><br>".$query.'<br><br>';
  if($result===FALSE) {
    mysql_close($moduleSearchDbConnection);
    return FALSE;
  }
  if(mysql_num_rows($result)<=0) {
    mysql_close($moduleSearchDbConnection);
    return array();
  }
  $matches=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $matches[]=getModuleByID($row["ModuleID"]);
    $row=mysql_fetch_assoc($result);
  }
  /* Search by categories works like this:  First, search by other criteria (or, if no other search parameters were given, get all modules) and build 
    a list of modules.  Then, check every module in that list to see if it is in the category specified.  If it is, keep it (or actually, copy it 
    back into a new $matches variable), otherwise, throw it out.  The end result is all matches found which are not in the specified category are 
    discarded, leaving only modules in the specified category remaining. */
  if(isset($searchParameters["category"]) && $searchParameters["category"]!="" && $searchParameters["category"]!="*") {
    $searchParameters["category"]=preg_replace('/\*/', '%', $searchParameters["category"]); //Swap * wildcard for MySQL wildcard.
    $matchesPhase1=$matches; //Copy all the previous matches into a new variable so we don't loose them.
    $matches=array(); //Reset all matches to nothing.
    for($i=0; $i<count($matchesPhase1); $i++) {
      $result=mysql_query("SELECT ModuleID FROM modulecatagories WHERE CategoryID=".$searchParameters["category"]." AND ModuleID=".$matchesPhase1[$i]["moduleID"].";", $moduleSearchDbConnection);
      if(mysql_num_rows($result)>=1) { //Did the above query checking for a matching in the category find a match?
        $matches[]=$matchesPhase1[$i]; //Copy the module back into matches, since it was in the right category.  No need to call getModuleByID again, since we have all the information about the module anyway preserved from the phase 1 search.
      }
    }
  }
  mysql_close($moduleSearchDbConnection);
  //var_dump($matches);
  return $matches;
}

/* searchMaterials($spearchParameters) - Searches all materials for those matching the specified search parameters.
  @parm $searchParameters - An associative array of parameters to search by.  Keys are the parameter, values are the value of the parameter.  
    Unknown or unsupported search parameters are silently ignored.
  @return - Returns "NotImplimented" if the back-end does not support search materials.
    Returns FALSE on any error (not finding any matching materials or unsupported parameters are not considered errors).
    Returns an associative array of results matching the parameters given on success, or an empty array if no results are found. */
function searchMaterials($searchParameters) {
  //var_dump($searchParameters);
  require("settings.php");
  $smaterialsdb=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$smaterialsdb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE, $smaterialsdb);
  $and="";
  $where="WHERE ";
  $query="SELECT MaterialID FROM materials ";
  if(isset($searchParameters["absoluteDate"]) && $searchParameters["absoluteDate"]!=="*") {
    $query=$query.$where.$and."materials.Date == '".$searchParameters["absoluteDate"]."' ";
    $and="AND ";
    $where="";
  } else {
    if(isset($searchParameters["minDate"]) && $searchParameters["minDate"]!=="*") {
      /* Start by converting whatever date that came in into a format suitable for MySQL. */
      $date = strftime("%Y-%m-%d %H:%M:%S_" ,strtotime($searchParameters["minDate"]));
      $dateEnd = strpos($date, "_");
      $date = substr($date, 0, $dateEnd);
      $query=$query.$where.$and."materials.Date >= '".$date."' ";
      $and="AND ";
      $where="";
    }
    if(isset($searchParameters["maxDate"]) && $searchParameters["maxDate"]!=="*") {
      $date = strftime("%Y-%m-%d %H:%M:%S_" ,strtotime($searchParameters["maxDate"]));
      $dateEnd = strpos($date, "_");
      $date = substr($date, 0, $dateEnd);
      $query=$query.$where.$and."materials.Date <= '".$date."' ";
      $and="AND ";
      $where="";
    }
  }
  
  $result=mysql_query($query.";", $smaterialsdb);
  if($result===FALSE) {
    mysql_close($smaterialsdb);
    return FALSE;
  }
  
  /* Create an array of all matching material IDs. */
  $materialIDs=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $materialIDs[]=$row["MaterialID"];
    $row=mysql_fetch_assoc($result);
  }
  
  /* Loop through all the material IDs and get the info for the matching material and put it in the $results array. */
  $results=array();
  for($i=0; $i<count($materialIDs); $i++) {
    $results[]=getMaterialByID($materialIDs[$i]);
  }
  
  mysql_close($smaterialsdb);
  return $results;
}

/* createModule() - Creates a new module, starting at the inital version.  To create a new VERSION of a module, use editModuleByID() instead.
  @parm $abstract - A description or adbstract of the module.
  @parm $lectureSize - The size or amount of time the lecutre component of the module is expected to be.
  @parm $labSize - The size or amount of time the lab component of the module is expected to be.
  @parm $exerciseSize - The size or amount of time the exercise component of the module is expected to be.
  @parm $homeworkSize - The size or amount of time the homework component of the module is expected to be.
  @parm otherSize - The size or amount of time any other component of the module is expected to be.
  @authorComments - Comments on the module by the author.
  @status - The module status.  One of the following values: "InProgress", "PendingModeration", "Active", "Locked"
  @minimumUserType - The minimum user type which may view this module once active.  One of "Viewer", "SuperViewer", "Submitter", "Editor", "Admin".
  @submittingUserID - The ID of the user who submitted the module.
  @checkInComments - Comments left by the submitter.
  @return - Returns FALSE on any error.
    Returns the moduleID of the new module on success. */
function createModule($title, $abstract, $lectureSize, $labSize, $exerciseSize, $homeworkSize, $otherSize, $authorComments, $status, $minimumUserType, $submittingUserID, $checkInComments) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  /* Start by creating a new BaseID */
  $result=mysql_query("INSERT INTO modulebases (BaseID, Title, ModuleIdentifier) VALUES (NULL, '".$title."', '');");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $baseID=mysql_insert_id(); //Grab the auto-generated BaseID from the last query.
  $result=mysql_query("INSERT INTO module (ModuleID, Date, Abstract, LectureSize, LabSize, ExerciseSize, HomeworkSize, OtherSize, AuthorComments, Status, MinimumUserType, BaseID, Version, SubmitterUserID, CheckInComments) VALUES (NULL, NULL, '".mysql_real_escape_string($abstract)."', '".mysql_real_escape_string($lectureSize)."', '".mysql_real_escape_string($labSize)."', '".mysql_real_escape_string($exerciseSize)."', '".mysql_real_escape_string($homeworkSize)."', '".mysql_real_escape_string($otherSize)."', '".mysql_real_escape_string($authorComments)."', '".mysql_real_escape_string($status)."', '".mysql_real_escape_string($minimumUserType)."', ".$baseID.", 1, ".$submittingUserID.", '".mysql_real_escape_string($checkInComments)."');");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $moduleID=mysql_insert_id();
  mysql_close();
  return $moduleID;
}

/* editMaterialByID($moduleID, $createNewVersion) - Update's a module's information in the system.  On back-ends which support writing multiple 
    versions of a module, may optionally also create a new version of the module with the new information while preserving the origional module.
      *NOTE:  Moderation is done primarily through this function.  To approve a module held for moderation, run this function but do not change any 
        values from their origional values except change $status to indicate the module has been approved.  Also, set $createNewVersion to FALSE to 
        prevent a new version from being created (since approving a module simply changes the status, it doesn't create a new version).
  @parm $moduleID - The ID of the module to edit.
  @parm $abstract - A description or adbstract of the module.
  @parm $lectureSize - The size or amount of time the lecutre component of the module is expected to be.
  @parm $labSize - The size or amount of time the lab component of the module is expected to be.
  @parm $exerciseSize - The size or amount of time the exercise component of the module is expected to be.
  @parm $homeworkSize - The size or amount of time the homework component of the module is expected to be.
  @parm otherSize - The size or amount of time any other component of the module is expected to be.
  @authorComments - Comments on the module by the author.
  @checkInComments - Comments left by the submitter.
  @status - The status of the module.  One of "InProgress", "PendingModeration", "Active", "Locked"
  @minimumUserType - The minimum user type which may view this module once active.  One of "Viewer", "SuperViewer", "Submitter", "Editor", "Admin".
  @parm $createNewVersion - Ignored on back-ends which do not support writing new versions of modules.  On other back-ends, specifies if a 
    new version of a module should be created.  Set to TRUE to create a new version of the module with the new information and preserve the 
    old version of the module as it was, or set to FALSE to instead just overwrite the module without changing the version.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns the module ID of the resulting module on success.  On back-ends which do not supprot writing multiple versions, or if the 
      $createNewVersion parameter was set to FALSE, this should be the same as the module given in $moduleID.  If a new version of a 
      module was created, the return value will be the ID of the new version. */
function editModuleByID($moduleID, $abstract, $lectureSize, $labSize, $exerciseSize, $homeworkSize, $otherSize, $authorComments, $checkInComments, $submittingUserID, $status, $minimumUserType, $createNewVersion) {
  require("settings.php");
  $embidb=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$embidb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT ModuleID FROM module WHERE ModuleID=".$moduleID.";", $embidb); //Check that the module ID given exists.
  if($result===FALSE || mysql_num_rows($result)<=0) {
    mysql_close($embidb);
    return FALSE;
  }
  if($createNewVersion===FALSE) {
    $newModuleID=$moduleID; //We won't change the module ID, so set the "new" module ID the same as the old one.
    $result=mysql_query("UPDATE module SET Date=NOW(), Abstract='".mysql_real_escape_string($abstract)."', LectureSize='".mysql_real_escape_string($lectureSize)."', LabSize='".mysql_real_escape_string($labSize)."', ExerciseSize='".mysql_real_escape_string($exerciseSize)."', HomeworkSize='".mysql_real_escape_string($homeworkSize)."', OtherSize='".mysql_real_escape_string($otherSize)."', AuthorComments='".$authorComments."', CheckInComments='".mysql_real_escape_string($checkInComments)."', SubmitterUserID=".$submittingUserID.", Status='".mysql_real_escape_string($status)."', MinimumUserType='".mysql_real_escape_string($minimumUserType)."' WHERE ModuleID=".$moduleID.";", $embidb);
    if($result===FALSE) {
      echo "<br>Failed here because: ".mysql_error($embidb)."....<br>";
      mysql_close($embidb);
      return FALSE;
    }
  } else { //This else block handles editing a module by creating a new version.
    $result=mysql_query("SELECT Version FROM module WHERE ModuleID=".$moduleID.";", $embidb); //Get the version of the module being edited.
    if($result===FALSE) {
      mysql_close($embidb);
      return FALSE;
    }
    $row=mysql_fetch_assoc($result);
    $currentModuleVersion=$row["Version"]; //Store the version of the module to edit.
    $result=mysql_query("SELECT BaseID FROM module WHERE ModuleID=".$moduleID.";", $embidb); //Get the base ID of the module to edit.
    if($result===FALSE) {
      mysql_close($embidb);
      return FALSE;
    }
    $row=mysql_fetch_assoc($result);
    $baseID=$row["BaseID"];
    $result=mysql_query("INSERT INTO module (ModuleID, Date, Abstract, LectureSize, LabSize, ExerciseSize, HomeworkSize, OtherSize, AuthorComments, Status, MinimumUserType, BaseID, Version, SubmitterUserID, CheckInComments) VALUES (NULL, NULL, '".mysql_real_escape_string($abstract)."', '".mysql_real_escape_string($lectureSize)."', '".mysql_real_escape_string($labSize)."', '".mysql_real_escape_string($exerciseSize)."', '".mysql_real_escape_string($homeworkSize)."', '".mysql_real_escape_string($otherSize)."', '".mysql_real_escape_string($authorComments)."', '".mysql_real_escape_string($status)."', '".mysql_real_escape_string($minimumUserType)."', ".$baseID.", ".($currentModuleVersion+1).", ".$submittingUserID.", '".mysql_real_escape_string($checkInComments)."');", $embidb); //Create a new version of the module.
    if($result===FALSE) {
      mysql_close($embidb);
      return FALSE;
    }
    $newModuleID=mysql_insert_id($embidb); //Get the auto-incriment value (new module ID) from the last operation and store it as the new module ID.
  }
  mysql_close($embidb);
  return $newModuleID;
}

/* setModuleAuthors($moduleID, $authors) - Sets a module's authors to the given authros.
  @parm $moduleID - The ID of the module to set authors for.
  @parm $authors - An array of author names to set for the module.  An empty array clears all authors from the module.
  @return - Returns "NotImplimented" if this feature is not supported by the backend.
    Returns FALSE on any error.
    Returns TRUE on success. */
function setModuleAuthors($moduleID, $authors) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  //Start by clearing all authors from the module.
  $result=mysql_query("DELETE FROM moduleauthors WHERE ModuleID='".mysql_real_escape_string($moduleID)."';");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  //Now, loop through any names given and add them.
  for($i=0; $i<count($authors); $i++) {
    $result=mysql_query("INSERT INTO moduleauthors (ModuleID, AuthorName) VALUES ('".mysql_real_escape_string($moduleID)."', '".mysql_real_escape_string($authors[$i])."');");
  }
  mysql_close();
  return TRUE;
}

/*  getModuleAuthors($moduleID) - Returns a list of all authors attatched to a module.
  @parm $moduleID - The ID of the module to check for authors.
  @return - Returns "NotImplimented" if this feature is not supported by the backend.
    Returns FALSE on any error.
    Returns an array of names on success.  An empty array indicates no authors were found. */
function getModuleAuthors($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT AuthorName FROM moduleauthors WHERE ModuleID='".mysql_real_escape_string($moduleID)."';");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $authors=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $authors[]=$row["AuthorName"];
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $authors;
}

/* createCategory($name, $description) - Creates a new category, which modules may add themselves to.
  @parm $name - The category name.
  @parm $description - A description for the category.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns a CategoryID on success, which uniquely identifies the category. */
function createCategory($name, $description) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("INSERT INTO categories (CategoryID, Name, Description) VALUES (NULL, '".mysql_real_escape_string($name)."', '".mysql_real_escape_string($description)."');");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $categoryID=mysql_insert_id($db);
  mysql_close();
  return $categoryID;
}

/* removeCategory($categoryID) - Removes the category with the specified ID.  This function should also remove any modules from the deleted category.
  @parm $categoryID - The ID of the category to remove.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on success. */
function removeCategory($categoryID) {
  //echo 'removeCategory() called with: '.$categoryID;
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("DELETE FROM categories WHERE CategoryID=".$categoryID.";"); //Delete the category.
  if($result===FALSE) {
    mysql_close($db);
    return FALSE;
  }
  $result=mysql_query("DELETE FROM modulecatagories WHERE CategoryID=".$categoryID.";"); //Remove any modules from the deleted category.s
  if($result===FALSE) {
    mysql_close($db);
    return FALSE;
  }
  mysql_close();
  return TRUE;
}

/* getCategoryById() - Returns information about the category with the specified ID.
  @parm $categoryID - The ID of the category to look up.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding the specified category IS considered an error.
    On success, returns an associative array with information about the category, with keys "name", "ID", and "description". */
function getCategoryById($categoryID) {
  require("settings.php");
  $gcbidb=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$gcbidb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM categories WHERE CategoryID=".$categoryID.";", $gcbidb);
  if($result===FALSE || mysql_num_rows($result)<=0) {
    mysql_close($gcbidb);
    return FALSE;
  }
  $row=mysql_fetch_assoc($result);
  $name=$row["Name"];
  $description=$row["Description"];
  mysql_close($gcbidb);
  return array("ID"=>$categoryID, "name"=>$name, "description"=>$description);
}

/* getAllCategories() - Returns all categories.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    On success, returns a 2D array, with the first element numerically indexed and indicating a category, and the second dimension the same as that 
      returned by getCategoryByID(). */
function getAllCategories() {
  require("settings.php");
  $gacdb=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$gacdb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT CategoryID FROM categories", $gacdb);
  if($result===FALSE) {
    mysql_close($gacdb);
    return FALSE;
  }
  if(mysql_num_rows($result)<=0) {
    mysql_close($gacdb);
    return array();
  }
  $categories=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $categories[]=getCategoryByID($row["CategoryID"]);
    $row=mysql_fetch_assoc($result);
  }
  mysql_close($gacdb);
  return $categories;
}

/* setModuleCategories($moduleID, $categoryIDs) - Sets a module's category(a) to the category(a) with the specified ID(a).  This function will also 
    remove a module from any categories not identified by the specified category ID.
    Note:  It is advised that the back-end have some sort of default "other" or dummy category for modules which do not specify a category, since there 
      is no method to remove a module from a category.
  @parm $moduleID - The ID of the module to put into a category.  Passing an empty string ("") will remove all categories associated with the module.
  @parm $categoryIDs - An array of category IDs to put the module into.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
      Back-ends are encouraged to ensure the given module ID and category ID are valid, but this is not required, espcially for back-ends which do not 
        actually have category IDs, but instead treat each category IDs as category names.
    Returns TRUE on success. */
function setModuleCategories($moduleID, $categoryIDs) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  //Make sure all the category IDs given actually exist.
  for($i=0; $i<count($categoryIDs); $i++) {
    $result=mysql_query("SELECT * FROM categories WHERE CategoryID=".$categoryIDs[$i].";"); //Make sure the category ID specified actually exists.
    if($result===FALSE || mysql_num_rows($result)<=0) {
      mysql_close();
      return FALSE;
    }
  }
  $result=mysql_query("DELETE FROM modulecatagories WHERE ModuleID=".$moduleID.";"); //Remove any references to the module in any categories.
  if(count($categoryIDs)>=0) { //Only set new category(s) for the module if a category was actually given.
    for($i=0; $i<count($categoryIDs); $i++) {
      $result=mysql_query("INSERT INTO modulecatagories (ModuleID, CategoryID) VALUES (".$moduleID.", ".$categoryIDs[$i].");"); //Add the module to the category}
      if($result===FALSE) {
        mysql_close();
        return FALSE;
      }
    }
  }
  mysql_close();
  return TRUE;
}

/* getModuleCategoryIDs($moduleID) - Returns the ID(s) of the category(s) the specified module is in.
  @parm $moduleID - The ID of the module to look for the category of.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on error.
    Returns an array category IDs on success.  An empty array indicates the module is not in any categories. */
function getModuleCategoryIDs($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM modulecatagories WHERE ModuleID=".$moduleID.";");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $row=mysql_fetch_assoc($result);
  $categoryIDs=array();
  while($row) {
    $categoryIDs[]=$row["CategoryID"];
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $categoryIDs;
}

/* setModuleTopics($moduleID, $topics) - Sets ALL topics for a module, and erases all topics for a module not specified.
  @parm $moduleID - The ID of the module to set topics for.
  @parm $topics - A 2D array of topics to set for the module.  The first dimension should be numerically indexed, with each index referring to a topic, 
    and the second dimensions should be an associative array with key "text", referring to the text of the topic.  If supported, this function 
    will store topics in a sensible order which is preserved when read back.  The order is the same as the order of the topics in the array: the 
    first topic in the array is the first topic in order, etc.  The front-end will, if possible, attempt to display topics in the same order they 
    are saved.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on success. */
function setModuleTopics($moduleID, $topics) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("DELETE FROM topics WHERE ModuleID=".$moduleID.";"); //Clear any topics for this module.
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  for($i=0; $i<count($topics); $i++) { //Loop through every topic given and save it.
    $result=mysql_query("INSERT INTO topics (ModuleID, TopicText, OrderID) VALUES (".$moduleID.", '".mysql_real_escape_string($topics[$i]["text"])."', ".$i.");");
    if($result===FALSE) {
      mysql_close();
      return FALSE;
    }
  }
  mysql_close();
  return TRUE;
}

/* getModuleTopics($moduleID) - Gets all topics associated with a module.  This function should attempt to order topics in a sensible way if possible.
  @parm $moduleID - The ID of the module to get topics of.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding any topics is not considered an error.
    Returns a 2D array on success, with the first dimension numerically indexed, with each index being a topic, and the second dimension being an 
      associative array of information about the topic (keys "orderID" and "text").  Returns an empty array if no topics are found for the module. */
function getModuleTopics($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM topics WHERE ModuleID='".mysql_real_escape_string($moduleID)."' ORDER BY OrderID;");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $foundTopics=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $topic=array("orderID"=>$row["OrderID"], "text"=>$row["TopicText"]);
    $foundTopics[]=$topic;
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $foundTopics;
}

/* setModuleObjectives($moduleID, $objectives) - Sets ALL objectives for a module, and erases all objectives for a module not specified.
  @parm $moduleID - The ID of the module to set objectives for.
  @parm $objectives - A 2D array of objectives to set for the module.  The first dimension should be numerically indexed, with each index referring to an 
    objective, and the second dimensions should be an associative array with key "text", referring to the text of the objective.  If supported, this 
    function will store objectives in a sensible order which is preserved when read back.  The order is the same as the order of the objectives in the 
    array: the first objective in the array is the first orbjective in order, etc.  The front-end will, if possible, attempt to display objectives in 
    the same order they are saved.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on success. */
function setModuleObjectives($moduleID, $objectives) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("DELETE FROM objectives WHERE ModuleID=".$moduleID.";"); //Clear any topics for this module.
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  for($i=0; $i<count($objectives); $i++) { //Loop through every objective given and save it.
    $result=mysql_query("INSERT INTO objectives (ModuleID, ObjectiveText, OrderID) VALUES (".$moduleID.", '".mysql_real_escape_string($objectives[$i]["text"])."', ".$i.");");
    if($result===FALSE) {
      mysql_close();
      return FALSE;
    }
  }
  mysql_close();
  return TRUE;
}

/* getModuleObjectives($moduleID) - Gets all objectives associated with a module.  This function should attempt to order objectives in a sensible way if 
    possible.
  @parm $moduleID - The ID of the module to get objectives of.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding any objectives is not considered an error.
    Returns a 2D array on success, with the first dimension numerically indexed, with each index being an objective, and the second dimension being an 
      associative array of information about the objective (keys "orderID" and "text").  Returns an empty array if no objectives are found for the 
      module. */
function getModuleObjectives($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM objectives WHERE ModuleID='".mysql_real_escape_string($moduleID)."' ORDER BY OrderID;");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $foundObjectives=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $objective=array("orderID"=>$row["OrderID"], "text"=>$row["ObjectiveText"]);
    $foundObjectives[]=$objective;
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $foundObjectives;
}

/* setModuleObjectives($moduleID, $prereqs) - Sets ALL prereqs for a module, and erases all prereqs for a module not specified.
  @parm $moduleID - The ID of the module to set prereqs for.
  @parm $objectives - A 2D array of prereqs to set for the module.  The first dimension should be numerically indexed, with each index referring to a 
    prereq, and the second dimensions should be an associative array with key "text", referring to the text of the prereq.  If supported, this 
    function will store prereqs in a sensible order which is preserved when read back.  The order is the same as the order of the prereqs in the 
    array: the first prereq in the array is the first prereq in order, etc.  The front-end will, if possible, attempt to display prereqs in 
    the same order they are saved.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on success. */
function setModulePrereqs($moduleID, $prereqs) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("DELETE FROM prereqs WHERE ModuleID=".$moduleID.";"); //Clear any topics for this module.
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  for($i=0; $i<count($prereqs); $i++) { //Loop through every topic given and save it.
    $result=mysql_query("INSERT INTO prereqs (ModuleID, PrerequisiteText, OrderID) VALUES (".$moduleID.", '".mysql_real_escape_string($prereqs[$i]["text"])."', ".$i.");");
    if($result===FALSE) {
      mysql_close();
      return FALSE;
    }
  }
  mysql_close();
  return TRUE;
}

/* getModulePrereqs($moduleID) - Gets all prereqs associated with a module.  This function should attempt to order prereqs in a sensible way if possible.
  @parm $moduleID - The ID of the module to get prereqs of.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding any prereqs is not considered an error.
    Returns a 2D array on success, with the first dimension numerically indexed, with each index being an prereqs, and the second dimension being an 
      associative array of information about the prereqs (keys "orderID" and "text").  Returns an empty array if no prereqs are found for the 
      module. */
function getModulePrereqs($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM prereqs WHERE ModuleID='".mysql_real_escape_string($moduleID)."' ORDER BY OrderID;");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $foundPrereqs=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $prereq=array("orderID"=>$row["OrderID"], "text"=>$row["PrerequisiteText"]);
    $foundPrereqs[]=$prereq;
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $foundPrereqs;
}

/* removeModulesByID($moduleIDs) - Removes one or more modules based on their unique IDs.
  @parm $moduelIDs - A numerically indexed array of module IDs to remove.  The value of each index should correspond to the ID of a material 
    to remove.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding a specified module ID in the system is not considered an error.
    Returns TRUE on success. */
function removeModulesByID($moduleIDs) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  for($i=0; $i<count($moduleIDs); $i++) {
    $result=mysql_query("DELETE FROM module WHERE ModuleID='".mysql_real_escape_string($moduleIDs[$i])."';");
    if($result===FALSE) {
      mysql_close();
      return FALSE;
    }
  }
  mysql_close();
  return FALSE;
}

/* createMaterial($linkToMaterial, $type, $title, $rights, $language, $publisher, $description, $creator) - Adds a material with the specified information 
    to the system.  This will only add material metadata.  If the actual material resides within the system (a local file, not a URL), than the 
    material must also be uploaded to the system with storeMaterialLocally().
      NOTE: When storing materials locally, it is _STROGNLY_ advised to use storeMaterialLocally() BEfORE createMaterial, otherwise it is difficult to 
        impossible to know the proper $linkToMaterial!!
  @parm $linkToMaterial - A link which indicates how to actually retrieve that material.  If the material is to be stored locally, this should be a 
    suitable path name to a file as returned by storeMaterialLocally().  If the material resides outside the system (for example, on a video sharing site, 
    or web site), this should be the URL to the material.  Links to externally-hosted materials MUST begin with the protocal (http://, ftp:// etc).
  @parm linkType - The type of link to make.  Either "LocalFile" (the source is stored within the system in a file), or "ExternalURL" (the source is stored
    somewhere remotely, accessed by a URL).
  @parm $readableFileName - Used only if $linkType is "LocalFile".  If it is, than this parameter specifies a human readable file name for the file 
    being uploaded.  Ignored if the link type is not "LocalFile".
  @parm $type - The type of the material.  Types must come from the DCMI types published on dublincore.org
  @parm $title - A descriptive title for the material.
  @parm $rights - A rights statement for the material.  It is suggested to either include a link to a rights statement or liscense, or include the 
    statement or liscense here.
  @language - The language of the material.
  @description - A description of the material.
  @creator - The creator of the material.  This is the material's author, and not necessarily the uploader of the material.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns a material ID on success.  This material ID uniquely identifies the material in the system. */
function createMaterial($linkToMaterial, $linkType, $readableFileName, $type, $title, $rights, $language, $publisher, $description, $creator) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("INSERT INTO materials (MaterialID, LinkToMaterial, LinkType, Title, Type, Rights, Language, Publisher, Description, Creator) VALUES (NULL, '".mysql_real_escape_string($linkToMaterial)."', '".mysql_real_escape_string($linkType)."', '".mysql_real_escape_string($title)."', '".mysql_real_escape_string($type)."', '".mysql_real_escape_string($rights)."', '".mysql_real_escape_string($language)."', '".mysql_real_escape_string($publisher)."', '".mysql_real_escape_string($description)."', '".mysql_real_escape_string($creator)."');");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $materialID=mysql_insert_id(); //Get the auto-incriment value of the last INSERT.  This should be the ID given to the material by the database.
  if($linkType=="LocalFile") { //If the link type is a file stored locally, we need to provide a human readable file name for it (since it's likely stored as a hash).
    $result=mysql_query("UPDATE materials SET ReadableFileName='".mysql_real_escape_string($readableFileName)."' WHERE MaterialID='".mysql_real_escape_string($materialID)."';");
  }
  mysql_close();
  return $materialID;
}

/* attatchMaterialToModule($materialID, $moduleID) - Links a material to a module.  This will make a material appear when viewing a module.
  @parm $materialID - The ID of the material to attatch to a module.
  @parm $moduleID - The module to attatch the material to.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE if the material was successfully attatched to the module. */
function attatchMaterialToModule($materialID, $moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("INSERT INTO modulematerialslink (ModuleID, MaterialID) VALUES (".$moduleID.", ".$materialID.");");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  mysql_close();
  return TRUE;
}

/* deattatchMaterialFromModule($materialID, $moduleID) - Removes a material attatchment from a module without actually deleting the material.  In most 
    cases, it is probably preferable to instead just delete the material.
  @parm $materialID - The ID of the material to de-attatch from a module.
  @parm $moduleID - The ID of the module to de-attatch the material from.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on error.
    Returns TRUE on success. */
function deattatchMaterialFromModule($materialID, $moduleID) {
  require("settings.php");
  $dmfmdb=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$dmfmdb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE, $dmfmdb);
  $result=mysql_query("DELETE FROM modulematerialslink WHERE MaterialID=".$materialID." AND ModuleID=".$moduleID.";", $dmfmdb);
  if($result===FALSE) {
    mysql_close($dmfmdb);
    return FALSE;
  }
  mysql_close($dmfmdb);
  return TRUE;
}

/* getAllMaterialsAttatchedToModule($moduleID) - Gets a list of all materials attatched to a module.
  @parm $moduleID - The ID of the module to get attatched modules of.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding a module with the specified ID _IS_ considered an error.
    Returns a numerically indexed array of material IDs on success.  An empty array indicates no materials are attached to the module. */
function getAllMaterialsAttatchedToModule($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT MaterialID FROM modulematerialslink WHERE ModuleID='".mysql_real_escape_string($moduleID)."';");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $results=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $results[]=$row["MaterialID"];
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $results;
}

/* getAllModulesAttatchedToMaterial($materialID) - Returns a list of all modules attatched to a material.
  @parm $materialID - The ID of the material to check for attached modules.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding a material with the specified ID _IS_ considered an error.
    Returns a numerically indexed array of modules attatched to the material on success.  An empty array indicates no modules are attached to the 
      material (in other words, this is an orphan material).*/
function getAllModulesAttatchedToMaterial($materialID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT ModuleID FROM modulematerialslink WHERE MaterialID='".mysql_real_escape_string($materialID)."';");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $results=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $results[]=$row["ModuleID"];
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $results;
}

/* storeMaterialLocally($uploadedFileReference, $storageDir) - Stores a material file locally, returning a link to the laterial suitable for use with the back-end.
  @parm $uploadedFileTempReference - A reference to the FILE structure containing information about the file.  This can be found with $_FILES['name'] 
    where 'name' is the name of the HTML form element which stored the file.
  @parm $storageDir - The directory to store materials in.  This should probably be the $MATERIAL_STORAGE_DIR variable from the master collection
    configuration, and needs to end with a slash.
  @return - Returns "NotImplimented" if this feature is not implimented by the back-end.
    Returns FALSE on any error storing the material.
    Returns a link to the material on success.  This link may be used with createMaterial and is suitable for discovering the material in the system. */
function storeMaterialLocally($uploadedFileReference, $storageDir) {
  require("settings.php");
  if($uploadedFileReference["error"]>0) { //This indicates a file upload error from PHP.
    return FALSE;
  }
  if(!is_uploaded_file($uploadedFileReference["tmp_name"])) {
    return FALSE;
  }
  if(!validateFileType($uploadedFileReference["type"]) || !validateFileSize($uploadedFileReference["size"])) { //Make sure the file type and size is okay.
    return FALSE;
  }
  $filenameToStore=sha1_file($uploadedFileReference["tmp_name"]); //Make a hash of the file, which will become its filename.
  if(file_exists($storageDir.$filenameToStore)) { //If a file with the same hash already exists, assume an identical file as already been uploaded, and do nothing expect return the proper link.
    return $filenameToStore;
  }
  $result=move_uploaded_file($uploadedFileReference["tmp_name"], $storageDir.$filenameToStore);
  if($result===FALSE) {
    return FALSE;
  }
  return $filenameToStore;
}


/** 
  WARNING:  THIS FUNCTION IS DEPRICATED!  Material-download has been re-worked so that it no longer depends on this function.
    Do not use this function in new code, since it will be removed.  Back-ends should not have to include this function.
*/
/* getLocalMaterialFileURL($materialID) - Gets a an actual material file URL (not metadata) stored locally.
  @parm $materialID - The ID of the material who's file it is to retrieve.
  @parm $server - The $_SERVER variable from the calling page.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    On success, returns the actual URL to download the material which can be linked to from a web page.  It is an absolute URL. */
function getLocalMaterialFileURL($materialID, $server) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT LinkToMaterial, LinkType FROM materials WHERE MaterialID='".mysql_real_escape_string($materialID)."';");
  if($result===FALSE || mysql_num_rows<=0) {
    mysql_close();
    return FALSE;
  }
  $row=mysql_fetch_assoc();
  if($row["LinkType"]!=="LocalFile") { //Check to make sure the link type is a local file.  This function only works on locally stored files.
    mysql_close();
    return FALSE;
  }
  $url=$_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
  $url=$url.$MATERIAL_STORAGE_URL.$row["LinkToMaterial"];
  return $url;
}

/* editMaterialByID($moduleID) - Update's a material's information in the system.
  @parm $materialID - The ID of the material to edit.
  @parm $linkToMaterial - Links the material to an actual file containing the actual material.  Either a URL to an external reference or a local path as 
    returned by storeMaterialLocally().
  @linkType - Either "LocalFile" if the material is a file stored locally on the system, or "ExternalURL" if the material is a file stored outside the
    system (ex. on YouTube) and accessable through a URL.
  @readableFileName - A human-readable file name for the material.  This name will be suggested when users download the material.
  @parm $type - The type of the material.  Must be one of the DCMI type found on dublincore.org
  @parm $title - The title of the material.
  @parm $rights - A rights statement, liscnese, or link to a rights statement or liscense for the material.
  @parm $language - The language of the material.
  @parm $publisher - The publisher of the material.
  @parm $description - A description of the material.
  $creator - The creator of the material.  Not necessairly the person who uploaded the material.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns the module ID of the resulting module on success.  This is should be the same as the materialID initially given. */
function editMaterialByID($materialID, $linkToMaterial, $linkType, $readableFileName, $type, $title, $rights, $language, $publisher, $description, $creator) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$embidb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE. $embidb);
  $result=mysql_query("UPDATE materials SET LinkToMaterial='".mysql_real_escape_string($linkToMaterial)."', LinkType='".mysql_real_escape_string($linkType)."', ReadableFileName='".mysql_real_escape_string($readableFileName)."', Type='".mysql_real_escape_string($type)."', Title='".mysql_real_escape_string($title)."', Rights='".mysql_real_escape_string($rights)."', Language='".mysql_real_escape_string($language)."', Publisher='".mysql_real_escape_string($publisher)."', Description='".mysql_real_escape_string($description)."', Creator='".mysql_real_escape_string($creator)."', WHERE MaterialID='".mysql_real_escape_string($materialID)."';", $embidb);
  if($result===FALSE || mysql_affected_rows($embidb)<=0) { //If true, either MySQL gave an error, or nothing was affected (which would inciate the materialID given probably doesn't exist).  Either way, it's an error.
    mysql_close($embidb);
    return FALSE;
  }
  mysql_close($embidb);
  return $materialID;
}

/* removeMaterialsByID($materialIDs) - Removes one or more materials based on their unique material ID.  This removes both the metadata and, if the 
    material is stored locally, the actual material as well (unless it is in use by another material/module).
  @parm $materialIDs - A numerically indexed array of the materials to remove.  The value of each index should correspond to a material ID 
    which uniquely identifies a material to remove.
  @parm $storageDir - The directory containing the sotred materials.  This should probably be the $MATERIAL_STORAGE_DIR from the 
    collection's master configuration, and needs to end with a slash.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding a material ID is not considered an error.
    Returns TRUE on success. */
function removeMaterialsByID($materialIDs, $storageDir) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  for($i=0; $i<count($materialIDs); $i++) {
    /* Get the link type of the material to delete. */
    $result=mysql_query("SELECT LinkToMaterial, LinkType FROM materials WHERE MaterialID='".mysql_real_escape_string($materialIDs[$i])."';");
    $row=mysql_fetch_assoc($result);
    /* If the link type is a local file, check to see if the file is used by any other materials.  If it isn't, delete the file. */
    if($row["LinkType"]=="LocalFile") {
      $result=mysql_query("SELECT MaterialID FROM materials WHERE LinkToMaterial='".mysql_real_escape_string($row["LinkToMaterial"])."' AND MaterialID!='".mysql_real_escape_string($materialIDs[$i])."';");
      $foundLinks=mysql_fetch_assoc($result);
      $foundLinks=$foundLinks["MaterialID"];
      if(count($foundLinks<=0)) { //If true, the material file isn't used by any other modules.  Delete it.
        $result=unlink($storageDir.$row["LinkToMaterial"]);
      }
    }
    /* Delete the material metadata. */
    $result=mysql_query("DELETE FROM materials WHERE MaterialID='".mysql_real_escape_string($materialIDs[$i])."';");
  }
  mysql_close();
  return TRUE;
}

/* addCommentAndRatingToMaterial($materialID, $subject, $comment, $rating) - Adds a comment and a rating to a material.
  @parm $materialID - The ID of the material which is being commented and rated on.
  @parm $author - The name of the person leaving the rating.
  @parm $subject - The subject/title of the comment.
  @parm $rating - The rating of the material.
  @return - Returns "NotImplimented" if this feature is not implimented by the back-end.
    Returns FALSE on any error.
    Returns TRUE on successful addition of comment and rating.*/
function addCommentAndRatingToMaterial($materialID, $author, $subject, $comment, $rating) {
  /* Check if the materialID given exists.  If it doesn't, return an error. */
  $matTest=getMaterialByID($materialID);
  if($matTest===FALSE || $matTest=="NotImplimented" || count($matTest)<=0) {
    return FALSE;
  }
  $result=settype($rating, "integer"); //Convert the rating given to an integer type.
  if($result===FALSE) { //Indicates an error converting to an integer.
    return FALSE;
  }
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  /* Add the rating and comments for the material to the database. */
  $result=mysql_query("INSERT INTO materialcomments (MaterialID, Comments, Subject, Date, Rating, Author, NumberOfRatings) VALUES ('".mysql_real_escape_string($materialID)."', '".mysql_real_escape_string($comment)."', '".mysql_real_escape_string($subject)."', NULL, ".$rating.", '".mysql_real_escape_string($author)."', NULL);");
  if($result===FALSE) { //Check for errors on updating rating
    mysql_close();
    return FALSE;
  }
  mysql_close();
  return TRUE;
}

/* addRatingToModule($moduleID, $rating) - Adds a rating to a material.
  @parm $moduleID - The ID of the module to add a rating to.
  @parm $rating - The rating of the module.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on successful addition of the rating. */
function addRatingToModule($moduleID, $rating) {
  /* Check if the moduleID given exists.  If it doesn't, return an error. */
  $modTest=getModuleByID($moduleID);
  if($modTest===FALSE || $modTest=="NotImplimented" || count($modTest)<=0) {
    return FALSE;
  }
  $result=settype($rating, "integer"); //Convert the rating given to an integer type.
  if($result===FALSE) { //Indicates an error converting to an integer.
    return FALSE;
  }
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  /* Check to see if the module being rated already has any ratings.  If it doesn't, create a rating entry for it in the database. */
  $result=mysql_query("SELECT Rating FROM moduleratings WHERE ModuleID='".mysql_real_escape_string($moduleID)."';");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  if(mysql_num_rows($result)<=0) { //If true, there's no row for ratings for this module.  We need to create it.
    $result=mysql_query("INSERT INTO moduleratings (ModuleID, Rating, NumRatings) VALUES ('".mysql_real_escape_string($moduleID)."', 0, 0);"); 
  }
  /* MySQL allows you to incriment a value within a MySQL query without having to read the value and write it back.  This feature is used here to 
    avoid having to read the current rating or number of ratings for a module stored in the database and then write them back, which might 
    introduce a race condition.  This also means we don't have to worry about table locking. */
  $result=mysql_query("UPDATE moduleratings SET Rating=Rating+".$rating.", NumRatings=NumRatings+1 WHERE ModuleID='".mysql_real_escape_string($moduleID)."';");
  if($result===FALSE) { //Check for errors on updating rating
    mysql_close();
    return FALSE;
  }
  mysql_close();
  return TRUE;
}

/* getModuleRatings($moduleID) - Gets all ratings attatched to a module.
  @parm $moduleID - The ID of the module to get ratings of.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns an array with keys "rating" and "numberOfRatings" on success.  The overall rating can be detering by "rating"/"numberOfRatings".  If no 
      ratings are found, "numberOfRatings" will be zero.  Be sure to check for this when using results to avoid trying to divide by zero. */
function getModuleRatings($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM moduleratings WHERE ModuleID='".mysql_real_escape_string($moduleID)."'");
  if($result==FALSE) {
    mysql_close();
    return FALSE;
  }
  $ratings=0;
  $numberOfRatings=0;
  if(mysql_num_rows($result)>=1) { //This indicates there is at least one row in the database with ratings for this module.
    $row=mysql_fetch_assoc($result);
    while($row) {
      $ratings=$ratings+$row["Rating"];
      $numberOfRatings=$numberOfRatings+$row["NumRatings"];
      $row=mysql_fetch_assoc($result);
    }
  }
  mysql_close();
  return array("rating"=>$ratings, "numberOfRatings"=>$numberOfRatings);
}

/* getMaterialRatingsAndComments($materialID) - Gets all ratings and comments attatched to a material.
  @parm $materialID - The ID of the material to get ratings/comments of.
  @return - Returns "NotImplimented" if this feature is not supported by the backend.
    Returns FALSE on any error.
    Returns a 2D array on success, with the first dimension numerically indexed, with each index referring to a rating, and the second dimenstion 
      an associative array with keys "subject", "comment", "date", "rating", and "author". */
function getMaterialRatingsAndComments($materialID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM materialcomments WHERE MaterialID='".mysql_real_escape_string($materialID)."';");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $materials=array(); //Will hold details of all material ratings/comments found.
  $row=mysql_fetch_assoc($result);
  while($row) {
    $materials[]=array("subject"=>$row["Subject"], "comment"=>$row["Comments"], "date"=>$row["Date"], "rating"=>$row["Rating"], "author"=>$row["Author"]);
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $materials;
}

/* getOrphinMaterials() - Returns a list of all materials that are considered orphaned.  Orphaned materials are materials which are not 
    referenced by any module.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error (detecting no orphan materials is not considered an error).
    Returns an array of material IDs considered orphaned on success.  An empty array indicates no orphaned materials were found. */
function getOrphinMaterials() {
  return "NotImplimented";
}

/* purgeOrphinMaterials() - Automatically deletes all materials which are orphaned, meaning all materials which are not referenced by any 
    module.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error (detecting no orphan materials and hence removing none is not considered an error).
    Returns TRUE on success. */
function purgeOrphinMaterials() {
  return "NotImplimented";
}

/* getExternalReferences($moduleID) - Returns a list of all references a module as made to external sources.
  @parm $moduleID - The ID of the module to check for external references.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding any external references is not considered an error.
    On success, returns a 2D array of references, with the first dimenstion numerically indexed and referring to a reference, and the second 
      dimension an associative array with keys "description", "order", and "link".  Returns an empty array if no references were found. */
function getExternalReferences($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM otherresources WHERE ModuleID='".mysql_real_escape_string($moduleID)."' ORDER BY OrderID;");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $references=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $references[]=array("description"=>$row["Description"], "order"=>$row["OrderID"], "link"=>$row["ResourceLink"]);
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $references;
}

/* setExternalReferences($moduleID, $references) - Sets all the external references for a module, and clears any external references not specified.
  @parm $moduleID - The ID of the module to set external references for.
  @parm $references - A 2D array with external references.  The first dimenstion is numerically indexed, with each index indicating a new external 
    reference.  The second dimension is an associative array with keys "description" and "link".  The back-end will attempt to store the external 
    references in the same order they are given (based on the first dimension of the $references parameter), so that setExternalReferences() will
    return the references in the same order they were passed.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on successful setting of external references. */
function setExternalReferences($moduleID, $references) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("DELETE FROM otherresources WHERE ModuleID=".$moduleID.";"); //Clear any external references for this module.
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  for($i=0; $i<count($references); $i++) { //Loop through every topic given and save it.
    $result=mysql_query("INSERT INTO otherresources (ModuleID, Description, ResourceLink, OrderID) VALUES (".$moduleID.", '".mysql_real_escape_string($references[$i]["description"])."', '".$references[$i]["link"]."', ".$i.");");
    if($result===FALSE) {
      mysql_close();
      return FALSE;
    }
  }
  mysql_close();
  return TRUE;
}

/* getInternalReferences($moduleID) - Returns a list of all references the specified module makes to other modules.
  @parm $moduleID - The ID of the module to check for references to other modules.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.  Not finding any references is not considered an error.
    On success, returns a 2D array, with the first dimension numerically indexed and referring to a reference, and the second dimension an
      associative array with keys "description", "referencedModuleID", and "order".  Returns an empty array if no references were found. */
function getInternalReferences($moduleID) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT * FROM seealso WHERE ModuleID='".mysql_real_escape_string($moduleID)."' ORDER BY OrderID;");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  $references=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $references[]=array("description"=>$row["Description"], "order"=>$row["OrderID"], "referencedModuleID"=>$row["ReferencedModuleID"]);
    $row=mysql_fetch_assoc($result);
  }
  mysql_close();
  return $references;
}

/* setInternalReferences($moduleID, $references) - Sets all internal references for a module (other modules referenced by the module) and removes 
    any references made which are not specified.
  @parm $moduleID - The ID of the module making the reference.
  @parm $references - A 2D array of references, with the first dimension being numerically indexed, with each index referring to a reference, and 
    the second dimenstion being an associative array with keys "description" and "referencedModuleID" (with the value for the key 
    "referencedModuleID" containing a valid moduleID for the module being referenced).  This function will attempt to store the references in the 
    same order they are passed in the first dimension of this parameter, so that they will be read back by getInternalReferences() in the same
    order they are passed to this function.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on successful setting of references to other modules.
  NOTE:  This function does not directly check to make sure module IDs given in $references are actually valid.  If invalid IDs are given, this 
    function may return either TRUE or FALSE, and if it returns TRUE than the invalid reference will have been saved to the storage back-end.
    Therefore, it is suggested front-ends check that IDs to be referenced are valid before passing them to this function. */
function setInternalReferences($moduleID, $references) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("DELETE FROM seealso WHERE ModuleID='".mysql_real_escape_string($moduleID)."';"); //Clear any internal references made by this module.
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  for($i=0; $i<count($references); $i++) { //Loop through every topic given and save it.
    $result=mysql_query("INSERT INTO seealso (ModuleID, Description, ReferencedModuleID, OrderID) VALUES ('".mysql_real_escape_string($moduleID)."', '".mysql_real_escape_string($references[$i]["description"])."', '".$references[$i]["referencedModuleID"]."', ".$i.");");
    if($result===FALSE) {
      mysql_close();
      return FALSE;
    }
  }
  mysql_close();
  return TRUE;
}

/* searchUser($searchParameters) - Searches users based on specified parameters.  Unkown search parameters are silently ignored.
    @parm $searchParameters - An associative array of the search parameters to search for.  Keys are the parameter name and key values are 
      the text of the parameter.  Valid keys are: "UserID", "Email", "FirstName", "LastName", "Type".  Note that searching by password is 
      not supported.  Unkown keys are silently ignored.
    @return - Returns "NotImplimented" if this feature is not supported by the back-end.
      Returns FALSE on any error.  Unknown/invalid keys are not considered errors, nor is finding no matching users.
      Returns a 2D array on success.  The first dimension is numerically indexed, with each index referring to a matching user.  The 
        second array is an associative array with the information about each found user.  The structure of this array is the same as 
        that returned by getUserInformationByID().
        An empty array indicates no matches were found. */
function searchUsers($searchParameters) {
  require("settings.php");
  $sudb=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$sudb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE, $sudb);
  $and="";
  $where="WHERE ";
  $query="SELECT UserID FROM users ";
  if(isset($searchParameters["name"]) && $searchParameters["name"]!="" && $searchParameters["name"]!=" " && $searchParameters["name"]!="*") { //Add filter-by-name criteria to the query, but only if something to search for was actually given.
    $names=preg_split('/ /',$searchParameters["name"] , -1, PREG_SPLIT_NO_EMPTY); //Since the back-end stores names as two seperate fields (first and last), split the names given into all whole words, to search for a matching in either FirstName or LastName for any part of the name given.
    $subQuery="";
    for($i=0; $i<count($names); $i++) {
      if($i!=0) {
        $subQuery=$subQuery."OR ";
      }
      $subQuery=$subQuery."FirstName LIKE '%".mysql_real_escape_string($names[$i])."%' OR LastName LIKE '%".mysql_real_escape_string($names[$i])."%' ";
    }
    $query=$query.$where.$and.$subQuery;
    $and="AND ";
    $where="";
  }
  if(isset($searchParameters["email"]) && $searchParameters["email"]!="" && $searchParameters["email"]!="*") {
    $query=$query.$where.$and."Email='".mysql_real_escape_string($searchParameters["email"])."' ";
    $and="AND ";
    $where="";
  }
  if(isset($searchParameters["type"]) && $searchParameters["type"]!="" && $searchParameters["type"]!="*") {
    $query=$query.$where.$and."Type='".mysql_real_escape_string($searchParameters["type"])."' ";
    $and="AND ";
    $where="";
  }
  //echo "<br><br>".$query."<br><br>";
  $result=mysql_query($query.";", $sudb);
  if($result===FALSE) {
    mysql_close($sudb);
    return FALSE;
  }
  $users=array();
  $row=mysql_fetch_assoc($result);
  while($row) {
    $users[]=getUserInformationByID($row["UserID"]);
    $row=mysql_fetch_assoc($result);
  }
  mysql_close($sudb);
  return $users;
}

/* checIfUserIsLoggedIn($authenticationToken) - Checks to see if a user with the given authentication token is properly logged into the system.  Also, 
    purges all expired or otherwise invalid authentication tokens.
  @parm $authenticationToken - A unique number from -9223372036854775808 to 9223372036854775807 which identifies a login.
  @return - Returns "NotImplimented" if this feature is not implimented by the back-end.
    Returns FALSE on any error.  A user with the authentication token not being logged in is not an error.
    Returns an array of information about the logged in user if the authentication token properly points to a logged in user.  The format of this array is 
      the same as that returned by getUserInformationByID().  An empty array indicates the user was not logged in. */
function checkIfUserIsLoggedIn($authenticationToken) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  
  /* Start by purging all authentication tokens which have expired... */
  $result=mysql_query("DELETE FROM currentlogins WHERE DATEDIFF(NOW(), Expires)>0;");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  /* Done purging expired authentication tokens. */
  $result=mysql_query("SELECT UserID FROM currentlogins WHERE AuthenticationToken=".$authenticationToken.""); //Try to find the authentication token given
  if($result===FALSE || mysql_num_rows($result)<=0) { //If true, we didn't find the authentication token...
    mysql_close();
    return array(); //Return an empty array, since we didn't find anything.
  }
  $userID=mysql_fetch_assoc($result);
  mysql_close();
  return getUserInformationByID($userID["UserID"]);
}

/* logUserIn($email, $password) - Attempts to log a user into the system.
  @parm $email - The email address of the user attempting to log in.
  @parm $password - The password of the user attempting to log in, in plain text.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error, of this the email/password combination is invalid.
    On successful log in, returns an authentication token which can be used to verify the user. */
function logUserIn($email, $password) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  /*Start by making sure the email/password pair is valid. */
  $result=mysql_query("SELECT UserID FROM users WHERE email='".$email."' AND password='".$password."' AND Type!='Disabled' AND Type!='Deleted' AND Type!='Pending';");
  if($result===FALSE || mysql_num_rows($result)!==1) {
    mysql_close();
    return FALSE;
  }
  /* Email/password pair should be valid if we're this far... */
  $userID=mysql_fetch_assoc($result);
  if($userID===FALSE) {
    mysql_close();
    return FALSE;
  }
  srand((double)(microtime()*1000000)); //Seed the random number generator.  This seed from the discussion on PHP.net of the mt_srand function.  It isn't a great seed, but should work okay.
  $max=9223372036854775807; //By default, make the maximum number possibly to generate the maximum number the MySQL table can hold.
  if($max>getrandmax()) { //If the default maximum number to possibly generate is larger than the max PHP can handle, set the max to generate the max PHP can handle.
    $max=getrandmax();
  }
  $authenticationToken=rand(-$max, $max);
  /* Make sure the authentication token is not currently being used... */
  $result=mysql_query("SELECT AuthenticationToken FROM currentlogins WHERE AuthenticationToken=".$authenticationToken.";");
  if(!$result) {
    mysql_close();
    return FALSE;
  }
  while(mysql_num_rows($result)!==0) { //If the authentication token we picked is being used, keep generating new ones until an unused one is found...
    srand((double)(microtime()*1000000));  //Seed the random number generator.  This seed from the discussion on PHP.net of the mt_srand function.  It isn't a great seed, but should work okay.
    $authenticationToken=rand(-$max, $max);
    $result=mysql_query("SELECT AuthenticationToken FROM currentlogins WHERE AuthenticationToken='".mysql_real_escape_string($authenticationToken)."';");
    if(!$result) {
      mysql_close();
      return FALSE;
    }
  }
  $result=mysql_query("INSERT INTO ".$DB_DATABASE.".currentlogins (CurrentLoginID, UserID, AuthenticationToken, Expires) VALUES (NULL, '".$userID["UserID"]."', '".$authenticationToken."', DATE_ADD(NOW(), INTERVAL 2 DAY))");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  mysql_close();
  return $authenticationToken;
}

/* logUserOut($authenticationToken) - Logs a user out of the system.
  @parm $authenticationToken - The authentication token of the user to log out.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on successful log out. */
function logUserOut($authenticationToken) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("DELETE FROM currentlogins WHERE AuthenticationToken=".$authenticationToken.";");
  mysql_close();
  return FALSE;
}

/* getUserInformationByID($userID) - Gets information about a user with the specified ID.
  @parm $userID - The ID of the user to get information about, which uniquely identifies them.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error (not finding a user with the specified user ID is not considered an error).
    Returns an associative array on success with the information about the user.  An empty array indicates no user with the specified ID 
      was found.
      The array contains the following keys:  "userID", "email", "firstName", "lastName", "password", "type" */
function getUserInformationByID($userID) {
  require("settings.php");
  $guibidb=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, TRUE);
  if(!$guibidb) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE, $guibidb);
  $result=mysql_query("SELECT * FROM users WHERE UserID=".$userID.";", $guibidb);
  if($result===FALSE) { //If true, there was an error in MySQL.
    mysql_close($guibidb);
    return FALSE;
  }
  if(mysql_num_rows($result)<=0) { //If true, we didn't find anything
    mysql_close($guibidb);
    return array(); //Return an empty array to show that we didn't find anything.
  }
  $userInfoFromDB=mysql_fetch_assoc($result);
  $userInformation=array("userID"=>$userInfoFromDB["UserID"], "email"=>$userInfoFromDB["Email"], "firstName"=>$userInfoFromDB["FirstName"], "lastName"=>$userInfoFromDB["LastName"], "password"=>$userInfoFromDB["Password"], "type"=>$userInfoFromDB["Type"]);
  mysql_close($guibidb);
  return $userInformation;
}

/* createUser($email, $firstName, $lastName, $password, $type) - Creates a new user with the specified parameters.
  @parm $email - The email address of the new user.
  @parm $firstName - The first name of the user.
  @parm $lastName - The last name of the user.
  @parm $password - The desired password of the new user, in plain text.
  @parm $type - The type of the desired user.  Types correspond directly to the rights of the user.  Valid types are:
    
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    On error, returns either an error code or FALSE.  Possible error codes are:
      "EmailAlreadyExists" - The email address already exists and can not be re-used.
      "BadEmail" - The email address given is invalid in some way.
      "BadPassword" - The password given is invalid in some way.
      "BadType" - The type specified is invalid in some way.
      "BadFirstName" - The first name given is invalid in some way.
      "BadLastName" - The last name given is invalid in some way.
    On success, returns the user ID of the new user, which uniquely identifies them in the system. */
function createUser($email, $firstName, $lastName, $password, $type) {
  /* Start by doing some simple validation checks on the input */
  if(validateEmail($email)!==TRUE) {
    return "BadEmail";
  }
  if(validateName($firstName)!==TRUE) {
    return "BadFirstName";
  }
  if(validateName($lastName)!==TRUE) {
    return "BadLastName";
  }
  if(validatePassword($password)!==TRUE) {
    return "BadPassword";
  }
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  $result=mysql_query("SELECT UserID FROM users WHERE Email='".mysql_real_escape_string($email)."';");
  if($result===FALSE || mysql_num_rows($result)>=1) {
    mysql_close();
    return "EmailAlreadyExists";
  }
  
  $result=mysql_query("INSERT INTO users (UserID, Email, FirstName, LastName, Password, Type) VALUES (NULL, '".mysql_real_escape_string($email)."', '".mysql_real_escape_string($firstName)."', '".mysql_real_escape_string($lastName)."', '".mysql_real_escape_string($password)."', '".mysql_real_escape_string($type)."');");
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  /* If we're here, we've created the user.  Now, get their user ID and return that. */
  $result=mysql_query("SELECT UserID FROM users WHERE Email='".mysql_real_escape_string($email)."';");
  if($result===FALSE || mysql_num_rows($result)<=0) {
    mysql_close();
    return FALSE;
  }
  $userID=mysql_fetch_assoc($result);
  $userID=$userID["UserID"];
  mysql_close();
  return $userID;
}

/* editUserByID($userID, $email, $firstName, $lastName, $password, $type) - Updates a user specified by $userID to have the charastics specified.
  @parm $userID - The user ID of the user to edit.  Note that changing user IDs is not supported.
  @parm $email - The email address the user should have after applying the update.
  @parm $firstName - The first name the user should have after applying the update.
  @parm $lastName - The last name the user should have after applying the update.
  @parm $password - The password the user should have after applying the update, in plain text.
  @parm $type - The type of the user after the update.  Types correspond to rights of the user.  See the createUser for a list of valid types.
  @parm $ignoreChangePassword - If set to TRUE, the password given will be completely ignored and not changed.
  @parm $ignoreChangeType - If set to TRUE, the type given will be completely ignored and not changed. 
  @return - Returns "NotImplimented" if this feature is not implimented.
    Returns an error code or FALSE on any failure.  Possible error codes are:
      "BadEmail" - The email address given is invalid in some way.
      "BadPassword" - The password given is invalid in some way.
      "BadType" - The type specified is invalid in some way.
      "BadFirstName" - The first name given is invalid in some way.
      "BadLastName" - The last name given is invalid in some way.
    Returns TRUE on successful edit/update. */
function editUserByID($userID, $email, $firstName, $lastName, $password, $type, $ignoreChangePassword, $ignoreChangeType) {
  /* Start by doing some very basic input checking, to make sure we don't have completely */
  if(validateEmail($email)!==TRUE) {
    return "BadEmail";
  }
  if(validateName($firstName)!==TRUE) {
    return "BadFirstName";
  }
  if(validateName($lastName)!==TRUE) {
    return "BadLastName";
  }
  if(validatePassword($password)!==TRUE) {
    return "BadPassword";
  }
  /* Now, actually commit the changes, since we've verified the changes should be okay. */
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  /* Build the query which will make the changes... */
  $query="UPDATE users SET Email='".mysql_real_escape_string($email)."', FirstName='".mysql_real_escape_string($firstName)."', LastName='".mysql_real_escape_string($lastName)."'";
  if($ignoreChangePassword!==TRUE) {
    $query=$query.", Password='".mysql_real_escape_string($password)."'";
  }
  if($ignoreChangeType!==TRUE) {
    $query=$query.", Type='".mysql_real_escape_string($type)."'";
  }
  $query=$query." WHERE UserID=".$userID.";";
  /* Run the query, check for errors, and return the appropriate thing. */
  $result=mysql_query($query);
  if($result===FALSE) {
    mysql_close();
    return FALSE;
  }
  mysql_close();
  return TRUE;
}

/* removeUsersByID($userID) - Removes one or more users based on their user IDs.  Note that it is up to the back-end to determine exactly how 
    to remove the user(s) (i.e., actually delete them from the system, set them as deleted, etc).
  @parm $userIDs - A numerically indexed array, with each index value containing an ID of a user to remove.
  @parm $softRemove - If TRUE, this will set a user's status to deleated/disabled, instead of actually removing them.  Back-ends do not have to support 
    soft-removes.  If they do not, they should not advertise the "UsersSoftRemove" capability.  In this case, they will simply ignore this parameter.
  @return - Returns "NotImplimented" if this feature is not supported by the back-end.
    Returns FALSE on any error.
    Returns TRUE on success. */
function removeUsersByID($userIDs, $softRemove) {
  require("settings.php");
  $db=mysql_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD);
  if(!$db) {
    return FALSE;
  }
  mysql_select_db($DB_DATABASE);
  for($i=0; $i<count($userIDs); $i++) {
    if($softRemove===TRUE) {
      $query=mysql_query("UPDATE users SET Type='Deleted' WHERE UserID=".$userIDs[$i].";");
    } else {
      $query=mysql_query("DELETE FROM users WHERE UserID=".$userIDs[$i].";");
    }
    if($query===FALSE) {
      mysql_close();
      return FALSE;
    }
  }
  mysql_close();
  return TRUE;
}

?>