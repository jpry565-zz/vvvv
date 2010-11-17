<?php session_start();
  /***********************************************************************************************************
  ************************************************************************************************************
  ****                    NOT USED!                                                                       ****
  ****                      NOT USED!           This file is not used.  It will be removed from final     ****
  ****                        NOT USED!         distributions of this system.  It is an old, no longer    ****
  ****                          NOT USED!       used component of the module submission system.           ****
  ****                            NOT USED!                                                               ****
  ************************************************************************************************************
  ************************************************************************************************************/
  
  require("lib/backends/backend.php");
  require("lib/look/look.php");
  require("lib/config/config.php");
  require("lib/frontend-ui.php");
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
  
  /* moduleInProgress() - Checks to see if a module is in progress.
    @return - Returns TRUE is a module is in progress.
      Returns FALSE if a module is not in progress.
      Returns "Error" on any error.
      Returns "InvalidModule" if a module is in progress, but if can't be worked on for some reason. */
  function moduleInProgress() {
    if(isset($_REQUEST["moduleID"])) {
      
    } else {
      return FALSE;
    }
  }
  
  if(isset($_SESSION["authenticationToken"])) { //Check if we think someone is already logged in.
    $userInformation=checkIfUserIsLoggedIn($_SESSION["authenticationToken"]);
    if(count($userInformation)==0) { //If true, than the user wasn't found
      logout();
      unset($userInformation);
    }
  }
  
  $stage="introduction";
  $triedToUploadMaterial=FALSE; //Indicates if we tried to upload a material or not.
  $materialUploadSuccess=FALSE; //Indicates the success of uploading a meterial.  Meaningless is $triedToUploadMaterial=FALSE;
  $savedPreviousScreen=FALSE; //If true, indicates we've saved everything from the previous screen properly.
  if(isset($userInformation) && ($userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) { //Only continue if the user is logged in and at least a submitter.
    if(isset($_REQUEST["stage"]) && isset($_REQUEST["stageNext"]) && isset($_REQUEST["stagePrevious"])) {
      if($_REQUEST["stage"]=="Next") { //If the user clicked a "Next" submit button, move to the page specified by the stageNext field.
        $stage=$_REQUEST["stageNext"];
      } elseif($_REQUEST["stage"]=="Previous") { //If the user clicked a "Previous" submit button, move to the page specified by the stagePrevious button.
        $stage=$_REQUEST["stagePrevious"];
      } elseif($_REQUEST["stage"]=="Upload") { //If the user click an "Upload" submit button, trigger handling a material upload.
        $stage="uploadMaterial";
      } elseif($_REQUEST["stage"]=="Add Material") { //If the user clicked an "Add Material" submit button, move to the page to gather information abou the material.
        $stage="showAddMaterial";
      } else {
        $stage="introduction";
      }
    }
    if($stage=="uploadMaterial") { //We are suppose to try to upload a meterial, so do so.
      $triedToUploadMaterial=TRUE;
      if(!isset($_REQUEST["moduleID"]) || !isset($_REQUEST["materialType"]) || !isset($_REQUEST["materialTitle"]) || !isset($_REQUEST["materialRights"]) || !isset($_REQUEST["materialLanguage"]) || !isset($_REQUEST["materialPublisher"]) || !isset($_REQUEST["materialDescription"]) || !isset($_REQUEST["materialCreator"]) || !isset($_FILES["materialFile"])) {
        $materialUploadSuccess=FALSE;
      } else { //If we run this else block, we have everything needed to try to upload the material.
        $materialLink=storeMaterialLocally($_FILES["materialFile"]);
        if($materialLink===FALSE) {
          $materialUploadSuccess=FALSE;
        } else {
          /* If we got this far, we've managed to store the file locally and have a reference to it.  Now, enter the metadata and link the file to the moduleID. */
          $materialID=createMaterial($materialLink, $_REQUEST["materialType"], $_REQUEST["materialTitle"], $_REQUEST["materialRights"], $_REQUEST["materialLanguage"], $_REQUEST["materialPublisher"], $_REQUEST["materialDescription"], $_REQUEST["materialCreator"]);
          if($materialID=="NotImplimented" || $materialID===FALSE) {
            $materialUploadSuccess=FALSE;
          } else {
            $result=attatchMaterialToModule($materialID, $_REQUEST["moduleID"]);
            if($result!==TRUE) {
              $materialUploadSuccess=FALSE;
            } else {
              $materialUploadSuccess=TRUE;
            }
          }
        }
      }
    }
  }
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo $COLLECTION_NAME." - Home" ?></title>
</head>
<body>
<div id="header">
  <?php
    echo file_get_contents("lib/look/".$LOOK_DIR."/header.html");
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
            echo "You are logged in as ".$userInformation["firstName"]." ".$userInformation["lastName"].'. &nbsp;<a href="userManageAccount.php">Manage Your Account</a> ';
            echo 'or <a href="loginLogout.php?action=logout">log out</a>.';
          } else {
            echo 'Welcome. &nbsp;Please <a href="loginLogout.php?action=login">login</a> to your account, or <a href="createAccount.php">create a new account</a>.';
          }
        ?>
      </div>
      <?php
        if(!isset($userInformation)) {
          echo '<h1>You Must Be Logged In To Continue</h1>';
          echo '<p>You must be logged in to use this page.  You can log in at the <a href="loginLogout.php">log in page</a>.</p>';
        } elseif($userInformation["type"]=="Submitter" || $userInformation["type"]=="Editor" || $userInformation["type"]=="Admin") { //This indicates the user IS logged in and DOES have module/material creation rights.
          echo '<h1>Create A Module</h1>';
          if($stage=="introduction") {
            echo '<h2>Introduction</h2>';
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
            echo '<p>To continue, click the "Next" button below.</p>';
            echo '<form name="navigation" method="post" action="createModule.php">';
            echo '<input type="hidden" readonly="readonly" name="stageNext" value="basicModuleInformation"></input>';
            echo '<input type="hidden" readonly="readonly" name="stagePrevious" value="introduction"></input>';
            echo '<input type="submit" name="stage" value="Next"></input></form>';
          } elseif($stage="basicModuleInformation") {
            echo '<h2>Basic Module Information</h2>';
            if(moduleInProgress()===TRUE) { //If true, a module was in progress, so save any progress.
              $moduleID=saveModuleProgress();
              echo 'Your progress has been saved.';
            } elseif(moduleInProgress()===FALSE) { //If true, no module was in progress, so create a new one, but fill it with all blanks.
              $moduleID=createModule("", "", "", "", "", "", "", "InProgress", "", "", "");
              echo 'A new module with ID of '.$moduleID.' has been created.';
            } else { //Error creating/saving module
              echo 'Error creating or saving module.';
            }
            echo '<p>';
            
            echo '<input type="hidden" readonly="readonly" name="stageNext" value=""></input>';
            echo '<input type="hidden" readonly="readonly" name="stagePrevious" value="introduction"></input>';
          } else { //Unknown module submission stage, give error.
            echo '<p><img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"> <span class="error">Unknown stage specified.  Unable to continue with ';
            echo 'module submission.</span></p>';
            echo '<p>If you reached this page after clicking a link within this collection, please report it to the collection maintainer.</p>';
          }
        } else { //User is logged in but doesn't have the right to create modules/materials.
          echo '<h1>Insufficient Permissions To Perform This Action</h1>';
          echo '<p><img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"> <span class="error">You do not have sufficient rights to create or edit ';
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