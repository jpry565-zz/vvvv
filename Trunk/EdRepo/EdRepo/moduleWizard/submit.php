<?php session_start();
  /**************************************************************************************************************************
   *    submit.php - Handles final submission of a module.
   *    --------------------------------------------------
   *  This is intended to be the final page of the "Module Submission" wizard, and is not designed to work properly if accessed
   *  directly (meaning, not from the previous page in the wizard).  This file takes care of gathering final check-in comments
   *  for modules and then either publishing the module to the database (if moderation is not used) or submitting a module for
   *  moderation.
   *
   * Author: Ethan Greer
   * Version: 1
   * Notes:  - This will also take care of sending email alerts of modules pending moderation, if this behavior is configured 
   *            in the system-wide configuration settings.
   ************************************************************************************************************************/
  
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
  
  if(!(isset($_REQUEST["moduleID"]) && isset($_REQUEST["moduleAction"]))) {
    $moduleAction="error";
  } else {
    $moduleID=$_REQUEST["moduleID"];
    $moduleAction=$_REQUEST["moduleAction"];
  }
  if($moduleAction=="edit" || $moduleAction=="doSubmit") {
    $moduleInfo=getModuleByID($moduleID);
  } else { //This page only supports editing modules.
    $moduleAction="error";
  }
  
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Module Submission - Submit" ?></title>
  <script type="text/javascript"> 
    function changeFormActionToNext() {
      return false;
    }
    function changeFormActionToBack() {
      document.getElementById("mainForm").action="final.php";
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
            echo '<h1>Sumbit Module</h1>';
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
              echo '<p>Your module is ready to be ';
              if($NEW_MODULES_REQUIRE_MODERATION==TRUE) {
                echo 'submitted for moderation.  ';
              } else {
                echo 'published to the collection.  ';
              }
              echo 'Enter any comments you have regarding the module submission process or would like moderators to read, and select the "Submit ';
              echo 'Module "button to complete the submission process.</p>';
              echo '<form method="post" name="mainForm" id="submitModuleForm" action="submit.php">';
              echo '<input type="hidden" readonly="readonly" name="moduleAction" value="doSubmit"></input>';
              echo '<input type="hidden" readonly="readonly" name="moduleID" value="'.$moduleInfo["moduleID"].'"></input>';
              echo '<table class="MIEV">';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Check-In Comments</span><br>';
               echo '<span class="MIEVDescriptiveText">Any final comments you have regarding this module, the submission process, or any ';
               echo 'information you wish to share with any moderators.  These comments are only visable to moderators.</span></td>';
              echo '<td><textarea name="moduleCheckInComments"></textarea></td></tr>';
              echo '</table>';
              echo '<input type="submit" name="submit" value="Submit Module"></input></form><br>';
              echo '<form name="navForm" id="mainForm" action="submit.php">';
              displayNavigationFooter($moduleID, $moduleAction);
            }
          }
          if($moduleAction=="doSubmit") {
            if(!isset($_REQUEST["moduleCheckInComments"])) {
              echo '<h1>Error Submitting Module</h1>';
              echo 'Sorry, no check-in comments were found.  Please only submit your module from the module submission wizard.';
            } else {
              if(submitModule($_REQUEST, $userInformation, $moduleInfo, $_REQUEST["moduleCheckInComments"], $NEW_MODULES_REQUIRE_MODERATION)===TRUE) {
                echo '<h1>Module Submission Successful</h1>';
                echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> ';
                if($NEW_MODULES_REQUIRE_MODERATION==TRUE) {
                  echo 'Your module has been successfully submitted for moderation.';
                  /* Since submitting the module for moderation succeeded, we now need to check if we're suppose to send email alerts to moderator(s)
                     alerting them to this new module pending moderation.  If we are, send the email(s). */
                  if($EMAIL_MODERATORS_ON_NEW_MODULE_PENDING_MODERATION==TRUE) {
                    /* Build the message to send and the subject of the email(s)*/
                    $subject="New Module Pending Moderation on ".$COLLECTION_NAME;
                    $message="A new module has been submitted to the ".$COLLECTION_NAME." collection and is currently pending moderation by your or ";
                    $message=$message."another moderator.\n\nTo approve or deny this new module, log onto the collection using your email address ";
                    $message=$message.'and password, go to the "Pending Moderation Requests" panel, and either approve or deny this new module.';
                    $message=$message."\n\nNew Module Information:\n Title: ".$moduleInfo["title"]."\n";
                    $message=$message." Version: ".$moduleInfo["version"]."\n Author: ".$moduleInfo["authorFirstName"]." ".$moduleInfo["moduleLastName"]."\n";
                    $message=$message." Abstract: ".$moduleInfo["abstract"]."\n Date: ".$moduleInfo["date"]."\n";
                    $message=$message." Check In Comments: ".$moduleInfo["checkInComments"]."\n\n";
                    $message=$message."----------------------------------------------------------\n";
                    $message=$message."This message was automatically generated.  Please do not reply.  Contact the collection maintainer if your ";
                    $message=$message."you like to stop receiving these alerts or would like to change other preferences.";
                    $message=wordwrap($message, 70);
                    /* Send the email to any users in the specified classes to send new user account alerts to. */
                    for($i=0; $i<count($EMAIL_MODERATORS_ON_NEW_MODULE_PENDING_MODERATION_CLASS); $i++) {
                      $users=searchUsers(array("type"=>$EMAIL_MODERATORS_ON_NEW_MODULE_PENDING_MODERATION_CLASS[$i])); //Get all users in the current class being checked
                      for($j=0; $j<count($users); $j++) { //Loop through found users of the current type/class.
                        mail($users[$j]["email"] ,$subject, $message);
                      }
                    }
                    /* Send the email to any additional addresses to send new user account alerts to. */
                    for($i=0; $i<count($EMAIL_MODERATORS_ON_NEW_MODULE_PENDING_MODERATION_LIST); $i++) {
                      mail($EMAIL_MODERATORS_ON_NEW_MODULE_PENDING_MODERATION_LIST[$i], $subject, $message);
                    }
                  }
                } else {
                  echo 'Your module has been successfully published to the collection.';
                }
              } else { //Module submission failed :(
                echo '<h1>Module Submission Failed</h1>';
                echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> ';
                echo '<span class="error">Module submission failed.</span>';
                echo '<p>The system was unable to submit your module.  Please restart the wizard and try again (your changes have not been lost).  ';
                echo 'If this problem persists, please contact the collection maintainer.</p>';
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