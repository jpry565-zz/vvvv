<?php session_start();
/****************************************************************************************************************************
 *    final.php - The final page of the module submission wizard before submitting a module to the collection.
 *    --------------------------------------------------------------------------------------------------------
 *  Handles all final details regarding module submission, except those related directly to actually submitting the module.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - Designed only to be accessed from other pages in the module submission wizard.
 *         - Does not actually submit modules to the collection.  All submission-related tasks are handled by submit.php
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
      echo '<input type="submit" name="submitForModeration" value="Submit For Moderation" onclick="return changeFormActionToSubmit();"></input>';
    } else {
      echo '<input type="submit" name="submit" value="Publish To Collection" onclick="return changeFormActionToSubmit();"></input>';
    }
    echo '</form>';
  }
  
  function displayMaterialEscapeNav($moduleID, $label) {
    echo '<form name="addMaterialNav" action="materials.php" method="post">';
    echo '<input type="hidden" name="moduleAction" value="edit"></input>';
    echo '<input type="hidden" name="moduleID" value="'.$moduleID.'"></input>';
    echo '<input type="submit" name="submit" value="'.$label.'"></input></form>';
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
    $moduleAction=$_REQUEST["moduleAction"];
  }
  
  if($moduleAction=="edit" || $moduleAction=="showAddMaterial" || $moduleAction=="doAddMaterial" || $moduleAction=="doRemoveMaterial") {
    $moduleInfo=getModuleByID($moduleID);
  } else { //this else block handles module actions which aren't handled.  Just set the action to "error" to take care of these.
    $moduleAction="error";
  }
  
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Module Submission: Comments and Access Restrictions" ?></title>
  <script type="text/javascript">
    function changeFormActionToNext() {
      document.getElementById("mainForm").action="submit.php";
      return true;
    }
    function changeFormActionToBack() {
      document.getElementById("mainForm").action="crossReferences.php";
      return true;
    }
    function changeFormActionToSubmit() {
      document.getElementById("mainForm").action="submit.php";
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
          if($moduleAction=="edit") {
            echo '<h1>Module Comments and Access Restrictions</h1>';
            /* Make sure that we have the right to edit this module. */
            if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && !($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
              $moduleAction="customError";
              echo '</p><span class="error">Error.  You may not edit this module.</span></p>';
              echo '<p>You are not the owner of this module and do not have sufficient privileges to override this restriction.  You have therefore been ';
              echo 'blocked from editing this module.</p>';
            }
            /* If we have the right to edit this module, but are not the module's owner, print a warning. */
            if($moduleInfo["submitterUserID"]!=$userInformation["userID"] && ($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
              echo '<p><span class="warning">WARNING:  You are editing a module which does not belong to you!</span></p>';
              echo '<p>Editing a module which does not belong to you is not recomended.  If you submit this module, the origional module owner will no ';
              echo 'longer be able to edit or create new versions of this module.  It is strongly suggested, therefore, that you stop editing this ';
              echo 'module.  If you choose to continue editing this module, it is STRONGLY reccomended you do not submit it for moderation or publish ';
              echo 'it to the collection.</p>';
            }
            /* If the module's action is still edit (it didn't get changed to an error of some kind), keep going. */
            if($moduleAction=="edit") {
              if(saveAllPossible($_REQUEST, $userInformation, $moduleInfo)===TRUE) {
                echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Module Progress Saved"> Module saved.';
              } else {
                echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to save module progress.</span>';
              }
              echo '<p>You may leave comments and additional information about your module on this page.  In addition, you may restrict viewing of ';
              echo 'your module to a specific minimum user level.  This will prevent users of a lower level from viewing your module or its materials, ';
              echo 'and can be used to restrict access to your module from certain privilege levels.</p>';
              echo '<form name="mainForm" id="mainForm" method="post" action="materials.php">';
              echo '<table class="MIEV">';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Minimum User Level To View Module</span><br>';
              echo '<span class="MIEVDescriptiveText">Specifies the minimum level a user must be to view your module.  The lowest level is "No ';
              echo 'Restrictions", which will allow everyone, including unregistered users, to view your module.  Other possible values coorespond ';
              echo 'to privilege levels of registered users.  It is reccomended you set this as low as possible, to prevent unintended blocking ';
              echo 'of your module.  In addition, please note that everyone can search for and see basic information about your module (such as title, ';
              echo 'author, etc).  Restricting access here will only prevent restricted users from viewing details about your module or the module\'s ';
              echo 'materials.</span></td>';
               echo '<td><select name="moduleMinimumUserType">';
                if($moduleInfo["minimumUserType"]=="Unregistered") {
                  echo '<option value="Unregistered" selected="selected">Unregistered Users (do not restrict access to anyone) [Reccomended]</option>';
                } else {
                  echo '<option value="Unregistered">Unregistered Users (do not restrict access to anyone) [Reccomended]</option>';
                }
                if($moduleInfo["minimumUserType"]=="Viewer") {
                  echo '<option value="Viewer" selected="selected">Viewers or higher</option>';
                } else {
                  echo '<option value="Viewer">Viewers or higher</option>';
                }
                if($moduleInfo["minimumUserType"]=="SuperViewer") {
                  echo '<option value="SuperViewer" selected="selected">SuperViewers or higher</option>';
                } else {
                  echo '<option value="SuperViewer">SuperViewers or higher</option>';
                }
                if($moduleInfo["minimumUserType"]=="Submitter") {
                  echo '<option value="Submitter" selected="selected">Submitters or higher</option>';
                } else {
                  echo '<option value="Submitter">Submitters or higher</option>';
                }
                if($moduleInfo["minimumUserType"]=="Editor") {
                  echo '<option value="Editor" selected="selected">Editors or higher</option>';
                } else {
                  echo '<option value="Editor">Editors or higher</option>';
                }
                if($moduleInfo["minimumUserType"]=="Admin") {
                  echo '<option value="Admin" selected="selected">Administrators Only</option>';
                } else {
                  echo '<option value="Admin">Administrators Only</echo>';
                }
                
               echo '</select></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Comments</span><br>';
               echo '<span class="MIEVDescriptiveText">Comments about this module.  These comments are viewable by anyone who can view details about ';
               echo 'this module.</span></td>';
              echo '<td><textarea name="moduleAuthorComments">'.$moduleInfo["authorComments"].'</textarea></td></tr>';
              echo '</table>';
              displayNavigationFooter($moduleID, $moduleAction);
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