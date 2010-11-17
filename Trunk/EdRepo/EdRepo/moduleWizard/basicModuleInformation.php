<?php session_start();
/****************************************************************************************************************************
 *    basicModuleInformation.php - Step 2 of the module submission wizard.
 *    --------------------------------------------------------------------------------------
 *  Allows users to enter basic information about their module.  Also, actually creates new modules or new versions of modules
 *  if directed to by step of the wizard (welcome.php).
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - This page should only be accessed via other pages in the module submission wizard.  Normally, that is either step 1
 *        (welcome.php) or step 3 (prereqsTopicsObjectives.php).
 ******************************************************************************************************************************/
  
  require("../lib/backends/backend.php");
  require("../lib/look/look.php");
  require("../lib/config/config.php");
  require("../lib/frontend-ui.php");
  require("../lib/moduleEditUploadHelpers.php");
  $backendInformation=getBackendBasicInformation();
  $backendCapabilities=getBackendCapabilities();
?>
<?php
  function logout() {
    if(isset($_SESSION["authenticationToken"])) {
      $logOutResult=logUserOut($_SESSION["authenticationToken"]);
    }
    unset($_SESSION["authenticationToken"]);
  }
  
  function displayNavigationFooter($moduleID, $moduleAction) {
    require("../lib/config/config.php");
    echo '<input type="hidden" readonly="readonly" name="moduleID" value="'.$moduleID.'"></input>';
    echo '<input type="hidden" readonly="readonly" name="moduleAction" value="'.$moduleAction.'"></input>';
    echo '<input type="submit" name="delete" value="Delete This Module" onclick="return changeFormActionToDelete();"></input>';
    echo '<input type="submit" name="back" value="Back" onclick="return changeFormActionToBack();"></input>';
    echo '<input type="submit" name="next" value="Next" onclick="return changeFormActionToNext();"></input>';
    echo '<input type="submit" name="save" value="Save Progress" onclick="return changeFormActionToSave();"></input>';
    if($NEW_MODULES_REQUIRE_MODERATION==TRUE) {
      echo '<input type="submit" name="submitForModeration" value="Submit For Moderation" disabled="disabled" onclick="return changeFormActionToSubmit();"></input>';
    } else {
      echo '<input type="submit" name="submit" value="Publish To Collection" disabled="disabled" onclick="return changeFormActionToSubmit();"></input>';
    }
    echo '</form>';
  }
  
  if(isset($_SESSION["authenticationToken"])) { //Check if we think someone is already logged in.
    $userInformation=checkIfUserIsLoggedIn($_SESSION["authenticationToken"]);
    if(count($userInformation)==0) { //If true, than the user wasn't found
      logout();
      unset($userInformation);
    }
  }
  
  $customError=""; //If setting the action to a custom error, set $customError to the error HTML to print.
  $customWarning=""; //$customWarning is printed in the heading of this page.  If you need to display a warning (but not an error), set this to the warning to display.
  
  if(!isset($_REQUEST["moduleAction"])) { //Check that a moduleAction is present.  Its required.
    $moduleAction="error";
  } else {
    $moduleAction=$_REQUEST["moduleAction"];
    if($moduleAction!="create") {
      if(!isset($_REQUEST["moduleID"])) { //If the moduleAction is not create, we need a moduleID
        $moduleAction="error";
      } else {
        $moduleID=$_REQUEST["moduleID"];
      }
    } else { //This else block runs if the moduleAction is create.
      if(!isset($_REQUEST["moduleTitle"])) { //To create a module, we must have a moduleTitle
        $moduleAction="error";
      }
    }
  }
  
  if($moduleAction=="edit" || $moduleAction=="createNewVersion") {
    $moduleInfo=getModuleByID($moduleID);
  }
  if($moduleAction=="createNewVersion" && !(in_array("UseVersions", $backendCapabilities["read"]) && in_array("UseVersions", $backendCapabilities["write"]))) { //If true, the back-end doesn't support versions, but we tried to create a new version.  Just silently fall back to basic versionless-edit in this case.
    $moduleAction="edit";
  }
  if(!(in_array("UseModules", $backendCapabilities["read"]) && in_array("UseModules", $backendCapabilities["write"]) && in_array("UseMaterials", $backendCapabilities["read"]) && in_array("UseMaterials", $backendCapabilities["write"]))) { //If true, the back-end doesn't support modules and materials in read and write mode, so editing is impossible.
    $moduleAction="notImplimented";
  }
  
  /* Take care of creating a new module or version up here... this is needed because doing so will set the module authors, which are read below 
    (but before printing any text to the browser window).  Basically, take care of any backend work needed for the actions "create" and 
    "createNewVersion" here. */
  //Make sure the user is logged in and has creation rights.
  if(isset($userInformation) && ($userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
    if($moduleAction=="create") {
      if(!isset($_REQUEST["moduleTitle"])) {
        $moduleAction="customError";
        $customError='<p><span class="error">Error: No module title given.</span> &nbsp;The module could not be created because its name was not specified.</p>';
      } else {
        $moduleID=createModule($_REQUEST["moduleTitle"], "", "", "", "", "", "", "", "InProgress", "", $userInformation["userID"], "");
        if($moduleID===FALSE) {
          $moduleAction="customError";
          $customError='An unknown error occurred while attempting to create the module.  This is most likely a back-end problem.';
        } else {
          $moduleInfo=getModuleByID($moduleID);
          $userAuthor=$userInformation["firstName"]." ".$userInformation["lastName"];
          $result=setModuleAuthors($moduleInfo["moduleID"], array($userAuthor)); //By default, set the module author to the currently logged in user (the creator).
          $customWarning=$result;
          $moduleAction="edit";
        }
      }
    } 
    if($moduleAction=="createNewVersion") {
      if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && !($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
        $moduleAction="customError";
        $customError='</p><span class="error">Error.  You may not edit this module.</span></p>';
        $customError.='<p>You are not the owner of this module and do not have sufficient privileges to override this restriction.  You have therefore been ';
        $customError.='blocked from editing this module.</p>';
      }
      if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && ($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
        $customWarning='<p><span class="warning">WARNING:  You are creating a new version of a module which does not belong to you!</span></p>';
        $customWarning.='<p>Creating a new version of a module which does not belong to you.  Although you may do so as an '.$userInformation["type"].', doing ';
        $customWarning.='so is not reccomended because it will prevent the origional owner of the module from making any changes or new versions of it, unless ';
        $customWarning.='they are of type "Editor" or "Admin".  It is strongly reccomended that you stop working on this module and delete it.</p>';
      }
    }
    if($moduleAction=="createNewVersion") { //If the action is still to create a new version (ie it didn't change because we don't have the right to do so), actually create the new version.  Yes, this is very very ugly.
      $origionalModuleID=$moduleInfo["moduleID"];
      $moduleID=editModuleByID($moduleID, $moduleInfo["abstract"], $moduleInfo["lectureSize"], $moduleInfo["labSize"], $moduleInfo["exerciseSize"], $moduleInfo["homeworkSize"], $moduleInfo["otherSize"], $moduleInfo["authorComments"], $moduleInfo["checkInComments"], $userInformation["userID"], "InProgress", $moduleInfo["minimumUserType"], TRUE);
      $moduleInfo=getModuleByID($moduleID); //Refresh module information with the newly created version (probably only the version changed).
      /* Copy the authors from the old module version into the new module version. */
      $old=getModuleAuthors($origionalModuleID);
      $result=setModuleAuthors($moduleInfo["moduleID"], $old);
      /* The next several lines copy topics, prereqs, objectives, categories, materials, and internal/external references from the old module 
        to the new version.  However, they'll only be copied if the backend says it supports the feature being copied. */
      $old=getModuleTopics($origionalModuleID);
      $result=setModuleTopics($moduleInfo["moduleID"], $old);
      $old=getModulePrereqs($origionalModuleID);
      $result=setModulePrereqs($moduleInfo["moduleID"], $old);
      $old=getModuleObjectives($origionalModuleID);
      $result=setModuleObjectives($moduleInfo["moduleID"], $old);
      if(in_array("UseCategories", $backendCapabilities["read"]) && in_array("UseCategories", $backendCapabilities["write"])) {
        $old=getModuleCategoryIDs($origionalModuleID);
        $result=setModuleCategories($moduleInfo["moduleID"], $old);
      }
      if(in_array("UseMaterials", $backendCapabilities["read"]) && in_array("UseMaterials", $backendCapabilities["write"])) {
        $old=getAllMaterialsAttatchedToModule($moduleInfo["moduleID"]);
        for($i=0; $i<count($old); $i++) {
          $m=getMaterialByID($old[$i]);
          $materialID=createMaterial($m["linkToMaterial"], $m["linkType"], $m["readableFileName"], $m["type"], $m["title"], $m["rights"], $m["language"], $m["publisher"], $m["description"], $m["creator"]);
          if($materialID!==FALSE && $materialID!=="NotImplimented") {
            $result=attatchMaterialToModule($materialID, $moduleInfo["moduleID"]);
          }
        }
      }
      if(in_array("CrossReferenceModulesInternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesInternal", $backendCapabilities["write"])) {
        $old=getInternalReferences($moduleInfo["moduleID"]);
        $result=setInternalReferences($moduleInfo["moduleID"], $old);
      }
      if(in_array("CrossReferenceModulesExternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesExternal", $backendCapabilities["write"])) {
        $old=getExternalReferences($moduleInfo["moduleID"]);
        $result=setExternalReferences($moduleInfo["moduleID"], $old);
      }
      /* End copying topics/prereqs/categories/objectives/materials/internal refs/external refs into new version. */
      $moduleAction="edit"; //Set the moduleAction to edit, since we want to edit the newly created version.
    }
  }
  
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo $COLLECTION_NAME." - Home" ?></title>
  <script type="text/javascript">
    function changeFormActionToNext() {
      document.getElementById("mainForm").action="prereqsTopicsObjectives.php";
      return true;
    }
    function changeFormActionToBack() {
      document.getElementById("mainForm").action="welcome.php";
      return true;
    }
    function changeFormActionToSubmit() {
      return false;
    }
    function changeFormActionToSave() {
      document.getElementById("mainForm").action="save.php";
      return true;
    }
    function changeFormActionToDelete() {
      document.getElementById("mainForm").action="delete.php";
      return true;
    }
  </script>
  <script type="text/javascript">
  var authorsArray=new Array();
  
  function add(prefix, text) {
      if(prefix=="Authors") {
        var length=authorsArray.length;
        document.getElementById("noModuleAuthors").value="false";
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
      var parent=document.getElementById(prefix.toLowerCase()+"List");
      var newDiv=document.createElement("div");
      var newInput=document.createElement("input");
      var newButton=document.createElement("button");
      newDiv.setAttribute("id", "module"+prefix+"Div"+length);
      newButton.setAttribute("id", "module"+prefix+"Button"+length);
      newButton.setAttribute("type", "button");
      newButton.setAttribute("onclick", "remove('"+prefix+"', '"+length+"')");
      newButton.innerHTML="Remove";
      newInput.setAttribute("type", "text");
      newInput.setAttribute("id", "module"+prefix+"Input"+length);
      newInput.setAttribute("name", "module"+prefix+length);
      if(text!==false) {
        newInput.value=text;
      }
      newDiv.appendChild(newInput);
      newDiv.appendChild(newButton);
      parent.appendChild(newDiv);
      if(prefix=="Authors") {
        authorsArray.push("module"+prefix+length);
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
    }
    
    function remove(prefix, num) {
      var i;
      var pos=0;
      var oldArray=new Array();
      if(prefix=="Authors") {
        for(i=0; i<authorsArray.length; i++) { oldArray.push(authorsArray[i]); }
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
      var newArray=new Array();
      for(i=0; i<oldArray.length; i++) {
        //Loop through the main array and copy everything except the thing to delete into the new array, renaming properly along the way.
        if(oldArray[i]!="module"+prefix+num) {
          var divElement=document.getElementById("module"+prefix+"Div"+i);
          var inputElement=document.getElementById("module"+prefix+"Input"+i);
          var buttonElement=document.getElementById("module"+prefix+"Button"+i);
          divElement.setAttribute("id", "module"+prefix+"Div"+pos);
          inputElement.setAttribute("id", "module"+prefix+"Input"+pos);
          inputElement.setAttribute("name", "module"+prefix+pos);
          buttonElement.setAttribute("id", "module"+prefix+"Button"+pos);
          buttonElement.setAttribute("onclick", "remove('"+prefix+"', '"+pos+"')");
          newArray.push("module"+prefix+pos);
        } else { //This else block will delete the element we want to get rid of.
          var delDiv=document.getElementById("module"+prefix+"Div"+i);
          var delInput=document.getElementById("module"+prefix+"Input"+i);
          var delButton=document.getElementById("module"+prefix+"Button"+i);
          var parent=document.getElementById(prefix.toLowerCase()+"List");
          delDiv.removeChild(delButton);
          delDiv.removeChild(delInput);
          parent.removeChild(delDiv);
          pos--; //Deleting an element will set new elements counter back one, so compensate.
        }
        pos++;
      }
      if(prefix=="Authors") {
        authorsArray.splice(0, authorsArray.length);
        for(i=0; i<newArray.length; i++) { authorsArray.push(newArray[i]); }
        if(authorsArray.length<=0) {
          document.getElementById("noModuleAuthors").value="true";
        }
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
    }
    
    function initialFillAndDisplay() {
      var savedAuthors=new Array();
      var i;
      <?php
        if($moduleAction=="edit") {
          $savedAuthors=getModuleAuthors($moduleInfo["moduleID"]);
          for($i=0; $i<count($savedAuthors); $i++) {
            $safeJSString=preg_replace('/"/', '\"', $savedAuthors[$i]);
            echo 'savedAuthors.push("'.$safeJSString.'");';
          }
        }
      ?>
      for(i=0; i<savedAuthors.length; i++) {
        add("Authors", savedAuthors[i]);
      }
    }
  </script>
</head>
<body onload="initialFillAndDisplay();">
<div id="header">
  <?php
    echo file_get_contents("../lib/look/".$LOOK_DIR."/header.html");
  ?>
  <div id="top-nav-bar">
    <?php showTopNavMenu(); ?>
  </div>
</div>
<div id="content-body-wrapper">
  <div id="content-body">
    <div id="left-sidebar">
      <?php
        if(isset($userInformation)) {
          if($userInformation["type"]=="Viewer") {
            showViewerMenu();
          } elseif($userInformation["type"]=="SuperViewer") {
            showSuperViewerMenu();
          } elseif($userInformation["type"]=="Submitter") {
            showSubmitterMenu();
          } elseif($userInformation["type"]=="Editor") {
            showEditorMenu();
          } elseif($userInformation["type"]=="Admin") { //We are logged in as an admin.
            showAdminMenu();
          }
        } else { //We aren't logged in.
          showGuestMenu();
        }
      ?>
    </div> <!-- End left-sidebar div -->
    <div id="mainContentArea">
      <div id="mainContentAreaTopInfoBar">
        <?php
          if(isset($userInformation)) {
            echo "You are logged in as ".$userInformation["firstName"]." ".$userInformation["lastName"].'. &nbsp;<a href="../userManageAccount.php">Manage Your Account</a> ';
            echo 'or <a href="../loginLogout.php?action=logout">log out</a>.';
          } else {
            echo 'Welcome. &nbsp;Please <a href="../loginLogout.php?action=login">login</a> to your account, or <a href="../createAccount.php">create a new account</a>.';
          }
        ?>
      </div>
      <?php
        if(!isset($userInformation)) {
          echo '<h1>You Must Be Logged In To Continue</h1>';
          echo '<p>You must be logged in to use this page.  You can log in at the <a href="loginLogout.php">log in page</a>.</p>';
        } elseif($userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin") { //This indicates the user IS logged in and DOES have module/material creation rights.
          /* The $moduleAction should never be "create" or "createNewVersion" at this point, since those cases were suppose to be handled above. 
            If the action is one of these, display and error. */
          if($moduleAction=="create" || $moduleAction=="createNewVersion") {
            $moduleAction="customError";
            $customError=$customError+"  Internal error.  Action not handled at proper point.  Please report this error to the collection maintainer.";
          }
          if($moduleAction=="notImplimented") {
            echo '<p><span class="error">Action not supported.</span>  The back-end in use does not support creating, editing, and/or getting module ';
            echo 'information.  A different back-end is required to perform this action.</p>';
          }
          if($moduleAction=="error") {
            echo '<p><span class="error">Back-end error.</span>  The back-end in use encountered an error while performing module processing.  It ';
            echo 'most likely is misconfigured, improperly installed, or contains a bug causing this error.</p>';
            echo '<p>Please report this error to the collection maintainer.</p>';
          }
          if($moduleAction=="edit") {
            echo '<h1>Basic Module Information</h1>';
            if($moduleInfo===FALSE) {
              $moduleAction="customError";
              echo '<span class="error">Error getting information about the current module to be edited.</span>';
            }
            if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && !($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
              $moduleAction="customError";
              echo '</p><span class="error">Error.  You may not edit this module.</span></p>';
              echo '<p>You are not the owner of this module and do not have sufficient privileges to override this restriction.  You have therefore been ';
              echo 'blocked from editing this module.</p>';
            }
            if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && ($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
              echo '<p><span class="warning">WARNING:  You are editing a module which does not belong to you!</span></p>';
              echo '<p>Editing a module which does not belong to you is not recomended.  If you submit this module, the origional module owner will no ';
              echo 'longer be able to edit or create new versions of this module.  It is strongly suggested, therefore, that you stop editing this ';
              echo 'module.  If you choose to continue editing this module, it is STRONGLY reccomended you do not submit it for moderation or publish ';
              echo 'it to the collection.</p>';
            }
            echo $customWarning; //Print any non-fatal warning encountered previously but not printed.
            if($moduleAction=="edit") { //If the action is still edit (ie, it wasn't changed because we were denied editing rights), continue editing.  Yes, I know this is very UGLY!!
              if(saveAllPossible($_REQUEST, $userInformation, $moduleInfo)===TRUE) {
                echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> Module progress saved.';
              } else {
                echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to save module progress.</span>';
              }
              echo '<p>This page contains basic information about your module, such as its title, a description, and the module\'s intended size.  ';
              echo 'You can edit all information except for the module\'s title, which can not be changed after creation.</p>';
              echo '<form method="post" name="mainForm" id="mainForm" action="basicModuleInformation.php">';
              echo '<input type="hidden" readonly="readonly" name="noModuleAuthors" id="noModuleAuthors" value="true"></input>';
              echo '<table class="MIEV">';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Module Title</span></td><td>'.$moduleInfo["title"].'</td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Abstract</span><br>';
               echo '<span class="MIEVDescriptiveText">A description of this module.</span></td>';
               echo '<td><textarea name="moduleAbstract" style="width: 100%;">'.$moduleInfo["abstract"].'</textarea></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Authors</span><br>';
               echo '<span class="MIEVDescriptiveText">You may add as many authors to this module as you wish.  By default, you are the only author.</span></td>';
               echo '<td><div id="authorsList"></div>';
               echo '<button type="button" onclick="add(\'Authors\', false);">Add Author</button></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Module Size</span><br>';
               echo '<span class="MIEVDescriptiveText">You may specify up to four different sizes for a module.  Each size should indicate how long each ';
               echo 'portion of the module is intended to take, and how many people it can accomidate.<br><br>Modules may include sizes for as many ';
               echo 'or as few components as it makes sense to include.</span></td>';
               echo '<td>Lecture Size<br><input type="text" maxlength="150" name="moduleLectureSize" value="'.$moduleInfo["lectureSize"].'"></input><br><br>';
               echo 'Exercise Size<br><input type="text" maxlength="150" name="moduleExerciseSize" value="'.$moduleInfo["exerciseSize"].'"></input><br><br>';
               echo 'Lab Size<br><input type="text" maxlength="150" name="moduleLabSize" value="'.$moduleInfo["labSize"].'"></input><br><br>';
               echo 'Homework Size<br><input type="text" maxlength="150" name="moduleHomeworkSize" value="'.$moduleInfo["homeworkSize"].'"></input><br><br>';
               echo 'Other Size<br><input type="text" maxlength="150" name="moduleOtherSize" value="'.$moduleInfo["otherSize"].'"></input></td></tr>';
              echo '</table>';
              displayNavigationFooter($moduleID, $moduleAction);
            }
          }
          if($moduleAction=="customError") {
            echo $customError;
          }
          if(!($moduleAction=="edit" || $moduleAction=="error" || $moduleAction=="notImplimented" || $moduleAction=="customError")) {
            echo '<p><span class="error">Internal Error.</span>  An internal error was detected, and no action could be performed.</p>';
            echo '<p>Please report this error to the collection maintainer.</p>';
          }
        } else { //This block runs if the user is logged in, but doesn't have permission to create/edit modules.
          echo '<h1>Insufficient Permissions To Perform This Action</h1>';
          echo '<p><img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"> <span class="error">You do not have sufficient rights to create or edit ';
          echo 'modules on this collection.</span></p>';
          echo '<p>To create or edit modules, you must have a higher privilege level.  Contact the collection maintainer for more information.</p>';
        }
      ?>
    </div> <!-- End mainContentArea div -->
    <div id="right-sidebar"></div>
  </div>
</div>
<div id="footer">
  <?php showFooter(); ?>
</div>
</body>
</html>