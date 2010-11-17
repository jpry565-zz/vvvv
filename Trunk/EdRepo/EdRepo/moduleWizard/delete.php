<?php session_start();
/****************************************************************************************************************************
 *    delete.php - Deletes a module currently being edited.
 *    --------------------------------------------------------------------------------------
 *  Deletes a module which a user is editing but which has not yet been added to the collection.  Provides a way to cancel
 *  the module submission wizard and not save the module progress.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - This is only intended to delete modules not yet active or pending moderation in the collection.
 *         - Takes the following GET or POST parameters:
 *           action : The action to take.
 *           moduleID : The ID of the module to delete.
 *           forceDelete : If set to "true", than administrators and editors will be able to delete any module, regardless
 *                    of the module's status or owner.
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
    echo '<input type="submit" name="back" value="Back" disabled="disabled" onclick="return changeFormActionToBack();"></input>';
    echo '<input type="submit" name="next" value="Next" disabled="disabled" onclick="return changeFormActionToNext();"></input>';
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
  
  if(!(isset($_REQUEST["moduleID"]) && isset($_REQUEST["moduleAction"]))) {
    $moduleAction="error";
  } else {
    $moduleID=$_REQUEST["moduleID"];
    $moduleAction=$_REQUEST["moduleAction"]; //Note:  Actions "edit" and "displayDelete" are created as the same thing, to preserve compatibality with other parts of the module submission wizard which set the action to edit whenever a navigation button is pressed.
  }
  
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Delete Module" ?></title>
  <script type="text/javascript">
    var savedTopics=new Array();
    var savedObjectives=new Array();
    var savedPrereqs=new Array();
    
    function changeFormActionToNext() {
      return false;
    }
    function changeFormActionToBack() {
      return false;
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
        if(!isset($userInformation)) {
          echo '<h1>You Must Be Logged In To Continue</h1>';
          echo '<p>You must be logged in to use this page.  You can log in at the <a href="loginLogout.php">log in page</a>.</p>';
        } elseif($userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin") { //This indicates the user IS logged in and DOES have module/material creation rights.
          if($moduleAction=="edit" || $moduleAction=="displayDelete" || $moduleAction=="doDelete") { //Valid actions are "edit", "displayDelete" (same as "edit"), and "doDelete"
            echo '<h1>Delete Module</h1>';
            $moduleInfo=getModuleByID($_REQUEST["moduleID"]); //Try to get information about the module to delete.
            if($moduleInfo===FALSE || count($moduleInfo)<=0) { //If the backend reported an error getting module info, or didn't find a module with the specified ID (returned an empty string), given an error.
              echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Module to delete not found.</span>';
              echo '<p>No module with the specified ID was found.  Unable to delete a non-existant module.</p>';
              $moduleAction="customError"; //Prevent other parts of the script from trying to work with the non-existant module.
            }
            /* Make sure the back-end supports deleting modules. */
            if(!in_array("UseModules", $backendCapabilities["write"])) {
              echo '<p><span class="error">Unable to delete module.  The back-end in use does not support writing modules.</span></p>';
              $moduleAction="customError";
            }
            /* Make sure that we have the right to edit this module. */            
            if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && !($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
              echo '</p><span class="error">Error.  You may not delete this module.</span></p>';
              echo '<p>You are not the owner of this module and do not have sufficient privileges to override this restriction.  You have therefore been ';
              echo 'blocked from deleting this module.</p>';
              $moduleAction="customError";
            }
            /* If we have the right to edit this module, but are not the module's owner, print a warning. */
            if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && ($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
              echo '<p><span class="warning">WARNING:  You are deleting a module which does not belong to you!</span></p>';
              echo '<p>Deleting a module which does not belong to you is not recomended.  If you submit this module, the origional module owner will no ';
              echo 'longer be able to edit or create new versions of this module.  It is strongly suggested, therefore, that you stop editing this ';
              echo 'module.  If you choose to continue editing this module, it is STRONGLY reccomended you do not submit it for moderation or publish ';
              echo 'it to the collection.</p>';
            }
            /* If the module's action is still edit (it didn't get changed to an error of some kind), keep going. */
            if($moduleAction=="edit" || $moduleAction=="displayDelete") { //Display a conformation to delete if the action is to "edit" or "displayDelete"
              if(saveAllPossible($_REQUEST, $userInformation, $moduleInfo)===TRUE) {
                echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Module Progress Saved"> Module progress saved.';
              } else {
                echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to save module progress.</span>';
              }
              echo '<p><span class="warning">Are you sure you want to delete this module?</span></p>';
              echo '<p>Deleting this module will permently remove it, and you will loose all changes you have made to it.</p><p>';
              if(isset($_REQUEST["forceDelete"]) && $_REQUEST["forceDelete"]=="true" && ($userInformation["type"]=="Admin" || $userInformation["type"]=="Editor")) {
                echo '<a href="delete.php?moduleAction=doDelete&moduleID='.htmlspecialchars($moduleInfo["moduleID"]).'&forceDelete=true">Continue With Module Deletion</a> ';
              } else {
                echo '<a href="delete.php?moduleAction=doDelete&moduleID='.htmlspecialchars($moduleInfo["moduleID"]).'">Continue With Module Deletion</a> ';
              }
              echo 'or <a href="../showMyModules.php">Cancel Module Deletion and Return To My Modules Panel</a></p>';
            }
            if($moduleAction=="doDelete") {
              if((isset($_REQUEST["forceDelete"]) && $_REQUEST["forceDelete"]=="true" && ($userInformation["type"]=="Admin" || $userInformation["type"]=="Editor")) || $moduleInfo["status"]=="InProgress") { //Is the module status InProgress or is forceDelete=true and the logged in user an admin?
                /* Deleting a module involves removign all topics, categories, prereqs, and objectives, and materials attached to the module, and 
                   then removing the module.  Don't remove materials which are also used by other modules, however. */
                $materials=getAllMaterialsAttatchedToModule($moduleInfo["moduleID"]); //Get a list of all materials owned by this module.
                $result=setModulePrereqs($moduleInfo["moduleID"], array()); //Remove all prereqs for the module.
                $result=setModuleTopics($moduleInfo["moduleID"], array()); //Remove all topics for the module.
                $result=setModuleObjectives($moduleInfo["moduleID"], array()); //Remove all objectives for the module.
                $result=setModuleAuthors($moduleInfo["moduleID"], array()); //Remove all authors from the module.
                if(in_array("UseCategories", $backendCapabilities["write"])) { //Does the back-end support writing categories?
                  $result=setModuleCategories($moduleInfo["moduleID"], array()); //Remove all categories from the module.
                }
                for($i=0; $i<count($materials); $i++) { //Scan all materials attatched to the module.  If they are not attatched to any other modules, delete them.
                  $result=deattatchMaterialFromModule($materials[$i], $moduleInfo["moduleID"]);
                  if(count(getAllModulesAttatchedToMaterial($materials[$i])<=1)) { //If one or fewer modules are attatched to the material, than the material must not be attached to any other modules.  Delete it.
                    $result=removeMaterialsByID(array($materials[$i]), $MATERIAL_STORAGE_DIR);
                  }
                }
                $result=removeModulesByID(array($moduleInfo["moduleID"]));
                echo '<p><img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Success"> Module Deleted.</p>';
              } else { //This else block runs if the status of the module to deletes in not InProgress
                echo '<span class="error">Unable to delete this module.  Module status either active or waiting for moderation.</span>';
              }
            }
          }
          if($moduleAction=="customError") {
            /* Don't do anything for a custom error.  customError indicates an appropirate error was already displayed. */
          }
          if($moduleAction=="error") {
            echo '<p><span class="error">Unknown error.</span></p>';
          }
        } else { //This else block indicates we're logged in, but don't have upload rights.
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