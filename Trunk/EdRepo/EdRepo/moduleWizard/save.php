<?php session_start();
/****************************************************************************************************************************
 *    save.php - Saves all progress of the module submission wizard, and ends the wizard.
 *    -----------------------------------------------------------------------------------
 *  Allows users to save all progress of their module without publishing the module.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: (none)
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
    $moduleAction=$_REQUEST["moduleAction"];
  }
  if($moduleAction=="edit") {
    $moduleInfo=getModuleByID($moduleID);
  } else { //This page only supports editing modules.
    $moduleAction="error";
  }
  
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Save Module" ?></title>
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
          if($moduleAction=="edit") {
            echo '<h1>Save Module Progress</h1>';
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
                echo '<p>Your module has been saved.</p>';
                if($moduleInfo["status"]=="InProgress") { //If true, the module still isn't active.
                  echo '<p>Your module has not yet been made active in this collection.  When you are ready, please edit this module and click the ';
                  if($NEW_MODULES_REQUIRE_MODERATION==TRUE) {
                    echo '"Submit For Moderation" ';
                  } else {
                    echo '"Publish Module"';
                  }
                  echo 'button to make this module active in this collection.</p>';
                }
              } else {
                echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to save module progress.</span>';
                echo '<p>There was an error saving your module.  Please contact the collection maintainer to report this issue.</p>';
              }
              echo '<form method="post" name="mainForm" id="mainForm" action="basicModuleInformation.php">';
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