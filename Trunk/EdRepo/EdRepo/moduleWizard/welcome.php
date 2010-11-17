<?php session_start();
/****************************************************************************************************************************
 *    welcome.php - The first page of the module submission wizard.
 *    --------------------------------------------------------------------------------------
 *  The first page of the module submission wizard.  This is responsible for determining what action to take regarding the module
 *  (edit it, creat a new module, create a new version of a module) and collection any initial information needed (such as the title
 *  of a new module).  Generally, this is just a welcome page, introducing users to the wizard.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - When creating, creating new versions of, or editing modules, the system should ALWAYS start with this page!  This
 *        page initializes the wizard process, and all other wizard pages expect to be accessed from other pages in the wizard,
 *        beginning with this one!
 *         - This page doesn't actually do very much, except determine what the next page of the wizard (basicModuleInformation.php)
 *        should do.  However, basicModuleInformation.php expects to be pre-initialized, and this page is responsible for that.
 *         - This page can take the collowing parameters (from a GET or POST):
 *                moduleID: The ID of the module to edit/create new version of.  This is is omitted or the ID given is not found, the
 *                          page will default to create a brand new, initial version, module.
 *                moduleAction: The action to take.  Normally determined automatically.  If comming from another page in the wizard,
 *                          this must be the action specified by the previous page (most likely "edit").
 *                forceActionToEdit: If this parameter exists and is "true", this page will attempt to edit the module specified by
 *                          moduleID, even if the default action would be to create a new version of the module.  Allows forcing
 *                          a module to be edited in place.  The user must be an Editor or Admin to use this feature, and a valid
 *                          module ID must be given in the moduleID parameter for this to work.
 ******************************************************************************************************************************/
  
  require("../lib/backends/backend.php");
  require("../lib/look/look.php");
  require("../lib/config/config.php");
  require("../lib/frontend-ui.php");
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
    echo '<input type="submit" name="delete" value="Delete This Module" disabled="disabled" onclick="return changeFormActionToDelete();"></input>';
    echo '<input type="submit" name="back" value="Back" disabled="disabled" onclick="return changeFormActionToBack();"></input>';
    echo '<input type="submit" name="next" value="Next" onclick="return changeFormActionToNext();"></input>';
    echo '<input type="submit" name="save" value="Save Progress" disabled="disabled" onclick="return changeFormActionToSave();"></input>';
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
  
  /* The $moduleID variable holds the ID of the module to work on. */
  /* The $moduleAction variable holds the action to be performed on the ID.  One of "create", "edit", "createNewVersion", "error" or "notImplimented".
    These actions tell the next page what to do with the ID (if any) in $moduleID.  "create" creates an entirely new module, "edit" will continue to edit 
    a module without attempting to change its ID, and "createNewVersion" will tell the next page to create a new version of the module in $moduleID and 
    than begin editing that.  "error" indicates a back-end error, and "notImplimented" indicates the back-end can't create/edit/get module info. */
  /* NOTE:  This page won't actually do anything with the module (create, edit, etc).  It will just tell the next page what to do. */
  $moduleID=""; //We don't know if we've been given a module ID yet, so set this to blank.  If $moduleAction is anything except "create", we MUST eventually put a valid module ID here.
  $moduleAction="create"; //By default, create a new module.  If no moduleID is given, than nothing will change this action (unless the user isn't logged in), and it will remain create.
  if(isset($userInformation)) { //The user must be logged in for us to bother to collect module information.
    if(isset($_REQUEST["moduleID"]) && isset($_REQUEST["moduleAction"])) { //This would probably indicate we're here from a "Back" button.  Don't do anything, but save the given ID and action.
      $moduleID=$_REQUEST["moduleID"];
      $moduleAction=$_REQUEST["moduleAction"];
      $moduleInfo=getModuleByID($moduleID);
    } else {
      if(isset($_REQUEST["moduleID"])) { //If a module ID was given, get it.
        $moduleID=$_REQUEST["moduleID"];
        $moduleInfo=getModuleByID($moduleID);
        if($moduleInfo=="NotImplimented") { //If the back end can't doesn't support getting module information by ID, we can't do anything, so report an error.
          $moduleAction="notImplimented";
        } elseif($moduleInfo===FALSE) { //Error in back-end.
          $moduleAction="error";
        } elseif(count($moduleInfo)==0) { //An empty array from getModuleInformationByID indicates no module with the given ID was found, so we'll need to create a new module. 
          $moduleID="create";
        } else { //This would indicate a moduleID already exists.  Figure out if we should try to create a new version, or just keep editing the old one.
          /* If the module progress is InProgess OR the user is logged in as an admin or editor and has requested the moduleAction be forced to "edit",
            set the moduleAction to edit.  If neither of the above are true, set moduleAction to "createNewVersion".
             This is used because, normally, when editing a module which is not InProgress, we want to create a new version of the module if possible.
            However, Admins and Editors can override this and force any module, regardless of its status, to be edited in-place if they want to. */
          if($moduleInfo["status"]=="InProgress" || (($userInformation["type"]=="Admin" || $userInformation["type"]=="Editor") && isset($_REQUEST["forceActionToEdit"]) && $_REQUEST["forceActionToEdit"]=="true")) {
            $moduleAction="edit";
          } else {
            $moduleAction="createNewVersion";
          }
        }
      }
    }
  } else { //This else block runs is $userInformation is not set (ie the user is not logged in).
    /* Note:  The code in the body should ignore all module actions and module IDs if $userInformation is not set (not logged in).  However, 
      set $moduleAction to error just in case, since if this block runs $moduleID won't be set, so code depending on it won't be able to run, 
      and because not being logged in and trying to use this page is considered an error. */
    $moduleAction="error";
  }
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo $COLLECTION_NAME." - Home" ?></title>
  <script type="text/javascript">
    function changeFormActionToNext() {
      document.getElementById("mainForm").action="basicModuleInformation.php";
      return true;
    }
    function changeFormActionToBack() {
      return false;
    }
    function changeFormActionToSubmit() {
      return false;
    }
    function changeFormActionToSave() {
      return false;
    }
    function changeFormActionToDelete() {
      return false;
    }
  </script>
</head>
<body>
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
        if(!isset($userInformation)) { //If true, no user is logged in.
          echo '<h1>You Must Be Logged In To Continue</h1>';
          echo '<p>You must be logged in to use this page.  You can log in at the <a href="loginLogout.php">log in page</a>.</p>';
        } elseif($userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin") { //This indicates the user IS logged in and DOES have module/material creation rights.
          if($moduleAction=="create") { //Action is to create a new module.  We know this is do-able because we already checked to make sure the use is logged in and at least a Submitter.
            echo '<h1>Create A New Module</h1>';
            echo '<p>Creating a module consists of three straightforward steps: entering basic information about the module, cross-referencing the module with ';
            echo 'other related modules, documents, and sources, and attatching materials to your module.  Each material you attatch to your module, ';
            echo 'such as an image, text document, or sound file, also requires you to include additional information about the material, such as ';
            echo 'a description and title.</p>';
            echo '<p>These steps are broken down into several screens to simplify the module creation process.  Your progress is saved each time you ';
            echo 'move to a different screen or click "Save Progress".  However, your module will not be ';
            if($NEW_MODULES_REQUIRE_MODERATION==TRUE) {
              echo 'submitted for moderation ';
            } else {
              echo 'published to the collection ';
            }
            echo 'until you click the ';
            if($NEW_MODULES_REQUIRE_MODERATION==TRUE) {
              echo '"Submit For Moderation" ';
            } else {
              echo '"Publish Module"';
            }
            echo 'button on the save screen.</p>';
            echo '<form method="post" name="mainForm" id="mainForm" action="basicModuleInformation.php">';
            echo '<p>To begin, enter a title for the new module.  Titles can not be changed after creation';
            if(in_array("UseVersions", $backendCapabilities["read"]) && in_array("UseVersions", $backendCapabilities["write"])) { //Does this back-end support versions?
              echo ', and every version of this module will have the same title.';
            } else {
              echo '.';
            }
            echo '</p>';
            echo 'Module Title: <input type="text" name="moduleTitle"></input><br><br>';
            displayNavigationFooter($moduleID, $moduleAction);
          } elseif($moduleAction=="edit" || $moduleAction=="createNewVersion") { //If the action is to edit or create a new version, check for any special circumstances.
            if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && !($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
              echo '</p><span class="error">Error.  You may not edit this module.</span></p>';
              echo '<p>You are not the owner of this module and do not have sufficient privileges to override this restriction.  You have therefore been ';
              echo 'blocked from editing this module.</p>';
              $moduleAction="customError"; //Prevent continuation of editing/creating new version by changing the moduleAction.
            }
            if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && ($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
              echo '<p><span class="warning">WARNING:  You are creating a new version of a module which does not belong to you!</span></p>';
              echo '<p>Although you create a new version of this module as an '.$userInformation["type"].', doing so is not recomended because ';
              echo 'it will prevent the origional owner of the module from making any changes or new versions of it, unless ';
              echo 'they are of type "Editor" or "Admin".  It is strongly reccomended that you stop working on this module and delete it.</p>';
            }
            if($moduleAction=="edit") { //Check to verify that the action has not changed from the last check for an action of edit.  It might have changed due to an error.
              echo '<h1>Continue Work On Module "'.$moduleInfo["title"].'"</h1>';
              echo '<p>This module is not yet active in the collection.  Changes you make will not be visable in the collection until you click the ';
              if($NEW_MODULES_REQUIRE_MODERATION==TRUE) {
                echo '"Submit For Moderation" ';
              } else {
                echo '"Publish Module"';
              }
              echo 'button.</p>';
              echo '<form method="post" name="mainForm" id="mainForm" action="basicModuleInformation.php">';
              displayNavigationFooter($moduleID, $moduleAction);
            } elseif($moduleAction=="createNewVersion") { //If the action wasn't edit, check if it is createNewVersion.  Even if it was origionally, it might have changed to an error, so recheck.
              if(!(in_array("UseVersions", $backendCapabilities["read"]) && in_array("UseVersions", $backendCapabilities["write"]))) { //If true, the back-end doesn't support versions.
                echo '<h1>Edit '.$moduleInfo["title"].'</h1>';
                echo '<p>Editing this module will permently change it in the database.  Saved changes will take effect immedietly.</p>';
              } else { //If this else block runs, the back-end does support versions.
                echo '<h1>Create Modified Version Of '.$moduleInfo["title"].'</h1>';
                echo '<p>By clicking "Next", you will beging working on a new version of '.$moduleInfo["title"].'.  When this version is ';
                echo 'published to the collection, it will be treated as a new version of its parent module.  Changes you make will not affect ';
                echo 'the parent module.</p>';
              }
              echo '<form method="post" name="mainForm" id="mainForm" action="basicModuleInformation.php">';
              displayNavigationFooter($moduleID, $moduleAction);
            }
          } elseif($moduleAction=="notImplimented") {
            echo '<p><span class="error">Action not supported.</span>  The back-end in use does not support creating, editing, and/or getting module ';
            echo 'information.  A different back-end is required to perform this action.</p>';
          } elseif($moduleAction=="error") {
            echo '<p><span class="error">Back-end error.</span>  The back-end in use encountered an error while performing module processing.  It ';
            echo 'most likely is misconfigured, improperly installed, or contains a bug causing this error.</p>';
            echo '<p>Please report this error to the collection maintainer.</p>';
          } elseif($moduleAction=="customError") {
            //Don't do anything.  An action of customError indcates all error handling has already been taken care of.
          } else {
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