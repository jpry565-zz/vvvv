<?php session_start();
/****************************************************************************************************************************
 *    materials.php - Allows the editing of materials attatched to a module.
 *    -----------------------------------------------------------------------
 *  Allows users to create, remove and (possibly) edit materials attatched to a module.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - This file is designed to be accessed only from other files of the module submission wizard.
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
  <title><?php echo "Module Submission: Materials" ?></title>
  <script type="text/javascript">
    function changeFormActionToNext() {
      document.getElementById("mainForm").action="crossReferences.php";
      return true;
    }
    function changeFormActionToBack() {
      document.getElementById("mainForm").action="prereqsTopicsObjectives.php";
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
    function checkTitle() {
      var title=document.getElementById("materialTitle").value;
      if(title=="" || title==" ") { //Check for an empty title
        alert("You must enter a title for this material.");
        return false;
      }
      return true;
    }
  
    function setProperMaterialSourceInput() {
      var div=document.getElementById("materialSourceInputDiv");
      var input=document.getElementById("materialSourceInput");
      var selectObj=document.getElementById("materialSourceType");
      var fileOrURL=selectObj.options;
      if(fileOrURL[selectObj.selectedIndex].value=="LocalFile") {
        div.removeChild(input);
        var input2=document.createElement("input");
        input2.setAttribute("name", "materialFile");
        input2.setAttribute("type", "file");
        input2.setAttribute("id", "materialSourceInput");
        div.appendChild(input2);
      } else if(fileOrURL[selectObj.selectedIndex].value=="ExternalURL") {
        div.removeChild(input);
        var input2=document.createElement("input");
        input2.setAttribute("name", "materialURL");
        input2.setAttribute("type", "text");
        input2.setAttribute("id", "materialSourceInput");
        input2.setAttribute("value", "http://");
        div.appendChild(input2);
      } else {
        alert("Internal error: Unknown index selected.");
      }
    }
    
    function toggleRightsExamples() {
      if(document.getElementById("rightsExamples").style.display=="none") {
        document.getElementById("rightsExamples").style.display="block";
      } else {
        document.getElementById("rightsExamples").style.display="none";
      }
    }
  </script>
  <script type="text/javascript">
    var savedMaterialsTitles=new Array();
    var savedMaterialsIDs=new Array();
    
    function initialFillArrayAndDisplay() {
      <?php //This PHP glue fills the JavaScript savedMaterials* arrays with information about saved materials from the database.
        $allModuleMaterials=getAllMaterialsAttatchedToModule($moduleInfo["moduleID"]);
        if($allModuleMaterials===FALSE) {
          echo 'alert("Warning:  Internal system error in JS:internalFillArrayAndDisplay() and PHP:getAllMaterialsAttatchedToModule() !")';
        } else {
          for($i=0; $i<count($allModuleMaterials); $i++) {
            $material=getMaterialByID($allModuleMaterials[$i]);
            $title=$material["title"];
            $title=preg_replace('/"/', '\"', $title); //Make safe for a JavaScript string
            echo 'savedMaterialsTitles.push("'.$title.'");';
            echo 'savedMaterialsIDs.push("'.$material["materialID"].'");';
          }
        }
        echo "var moduleID=".$moduleInfo["moduleID"].";";
      ?>
      var i;
      var parent=document.getElementById("materialsList");
      var newSubmit;
      var newForm;
      var newInput1; //A hidden form field with the moduleAction
      var newInput2; //A hidden form field with the ID of the module to remove
      var newInput3; //A hidden form field with the current ModuleID
      var newDiv;
      for(i=0; i<savedMaterialsTitles.length; i++) {
        newform=document.createElement("form");
        newInput1=document.createElement("input");
        newInput2=document.createElement("input");
        newInput3=document.createElement("input");
        newSubmit=document.createElement("input");
        newForm=document.createElement("form");
        newDiv=document.createElement("div");
        newDiv.setAttribute("name", "materialDiv"+i);
        newDiv.innerHTML=savedMaterialsTitles[i];
        newForm.setAttribute("name", "materialForm"+i);
        newForm.setAttribute("action", "materials.php");
        newForm.setAttribute("method", "post");
        newInput1.setAttribute("name", "moduleAction");
        newInput1.setAttribute("value", "doRemoveMaterial");
        newInput1.setAttribute("type", "hidden");
        newInput1.setAttribute("readonly", "readonly");
        newInput2.setAttribute("name", "materialIDToRemove");
        newInput2.setAttribute("type", "hidden")
        newInput2.setAttribute("readonly", "readonly");
        newInput2.setAttribute("value", savedMaterialsIDs[i]);
        newInput3.setAttribute("name", "moduleID");
        newInput3.setAttribute("type", "hidden");
        newInput3.setAttribute("readonly", "readonly");
        newInput3.setAttribute("value", moduleID);
        newSubmit.setAttribute("name", "submit");
        newSubmit.setAttribute("type", "submit");
        newSubmit.setAttribute("value", "Remove This Material");
        
        newForm.appendChild(newInput1)
        newForm.appendChild(newInput2);
        newForm.appendChild(newInput3);
        newForm.appendChild(newSubmit);
        newDiv.appendChild(newForm);
        parent.appendChild(newDiv);
      }
    }
  </script>
</head>
<body onload="initialFillArrayAndDisplay();">
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
          if($moduleAction=="edit" || $moduleAction=="showAddMaterial" || $moduleAction=="doAddMaterial" || $moduleAction=="doRemoveMaterial") {
            if($moduleAction=="edit") {
              echo '<h1>Manage Module Materials</h1>';
            } elseif($moduleAction=="showAddMaterial") {
              echo '<h1>Add a Material to this Module</h1>';
            } elseif($moduleAction=="doAddMaterial") {
              echo '<h1>Add a Material to this Module</h1>';
            } elseif($moduleAction=="doRemoveMaterial") {
              echo '<h1>Remove a Material from this Module</h1>';
            }
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
              echo '<p>Materials are the actual media which make up your module, such as pictures, word processing files, and videos.  </p>';
              echo '<table class="MIEV">';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Materials</span></td>';
               echo '<td><div id="materialsList"></div>';
               echo '<form method="post" name="materialManagementForm" id="materialManagementForm" action="materials.php">';
               echo '<input type="hidden" name="moduleID" readonly="readonly" value="'.$moduleInfo["moduleID"].'"></input>';
               echo '<input type="hidden" name="moduleAction" readonly="readonly" value="showAddMaterial"></input>';
               echo '<input type="submit" name="addMaterial" value="Add A Material"></input></td></tr>';
              echo '</table></form>';
              echo '<form name="mainForm" id="mainForm" action="materials.php">';
              displayNavigationFooter($moduleID, $moduleAction);
            }
            if($moduleAction=="showAddMaterial") { //Show a form to add a material.
              echo '<p>Enter as much information about his material as you know.</p>';
              echo '<form enctype="multipart/form-data" name="addMaterialForm" action="materials.php" method="post">';
              echo '<input type="hidden" name="moduleAction" value="doAddMaterial" readonly="readonly"></input>';
              echo '<input type="hidden" name="moduleID" value="'.$moduleInfo["moduleID"].'" readonly="readonly"></input>';
              echo '<table class="MIEV">';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Material Title</span><br>';
               echo '<span class="MIEVDescriptiveText">A descriptive title for the material.</span></td>';
               echo '<td><input type="text" name="materialTitle" id="materialTitle"></input></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Author</span><br>';
               echo '<span class="MIEVDescriptiveText">The author of the material.  If this material is not your own work, this is the name of the ';
               echo 'person(s) or orginization(s) who created the material.  If you created the material yourself, put your name here.</span></td>';
               echo '<td><input type="text" name="materialCreator"></input></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Material Type</span><br>';
               echo '<span class="MIEVDescriptiveText">Describes the type of the material.</span></td><td>';
                echo '<select name="materialType">';
                 echo '<option value="text">Text</option>';
                 echo '<option value="StillImage">Image/Picture</option>';
                 echo '<option value="Software">Software</option>';
                 echo '<option value="Service">Service</option>';
                 echo '<option value="PhysicalObject">Physical Object</option>';
                 echo '<option value="MovingImage">Video, Animation, Moving Image</option>';
                 echo '<option value="InteractiveResource">Interactive Resource</option>';
                 //echo '<option value="Image">Image (2)</option>';
                 echo '<option value="Event">Event</option>';
                 echo '<option value="Dataset">Dataset</option>';
                 echo '<option value="Collection">Collection</option>';
                 echo '<option value="NotSpecified">Unknown/Other</option>';
               echo '</td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Description</span><br>';
               echo '<span class="MIEVDescriptiveText">A brief description of the material.</span></td>';
               echo '<td><textarea name="materialDescription"></textarea></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Language</span><br>';
               echo '<span class="MIEVDescriptiveText">Specifies the language of the material (for example, English).</span></td>';
               echo '<td><input type="text" name="materialLanguage"></input></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Material Publisher</span><br>';
               echo '<span class="MIEVDescriptiveText">The publisher of the material.</span></td>';
               echo '<td><input type="text" name="materialPublisher"></input></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Rights</span><br>';
               echo '<span class="MIEVDescriptiveText">If this material is covered by a specific liscense or limitation on its use, specify so here.  ';
               echo 'Either the text of a liscense/rights statement, or a link to such a statement is acceptable.  Note that the system will display ';
               echo 'this rights statement/liscense with this material, but can not itself enforce it.<br>';
               echo '<a href="javascript:toggleRightsExamples();">Toggle Examples</a></span>';
               echo '<div id="rightsExamples" style="display: none;" class="MIEVDescriptiveText">';
               echo '<ul><li>GNU General Public Liscense V3 (http://www.gnu.org/licenses/gpl-3.0.html)</li>';
               echo '<li>Public Domain</li>';
               echo '<li>Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License (http://creativecommons.org/licenses/by-nc-sa/3.0/)</li>';
               echo '<li>Copyright 2009 John Doe.  All rights reserved.  Modifications phrohibited without written permission from John Doe, 0 JD Lane, Somewhere, NY 00000</li></ul></div></td>';
               echo '<td><textarea name="materialRights"></textarea></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Material Source</span></td>';
                echo '<td><select name="materialSourceType" id="materialSourceType" onChange="setProperMaterialSourceInput();">';
                 echo '<option value="LocalFile" selected="selected">Upload File</option>';
                 echo '<option value="ExternalURL">Internet URL</option>';
                echo '</select><br>';
                echo '<div id="materialSourceInputDiv">';
                 echo '<input type="file" name="materialFile" id="materialSourceInput"></input>';
                echo '</div>';
              echo '</table>';
              echo '<input type="submit" name="submit" value="Add Material" onclick="return checkTitle();"></input></form>';
              displayMaterialEscapeNav($moduleInfo["moduleID"], "Cancel and Return To Material List");
            }
            if($moduleAction=="doAddMaterial") { //Actually try to add a material.
              if(!(isset($_REQUEST["moduleID"]) && isset($_REQUEST["materialType"]) && isset($_REQUEST["materialTitle"]) && isset($_REQUEST["materialRights"]) && isset($_REQUEST["materialLanguage"]) && isset($_REQUEST["materialPublisher"]) && isset($_REQUEST["materialDescription"]) && isset($_REQUEST["materialCreator"]) && isset($_REQUEST["materialSourceType"]) && (($_REQUEST["materialSourceType"]=="LocalFile" && isset($_FILES["materialFile"])) || ($_REQUEST["materialSourceType"]=="ExternalURL" && isset($_REQUEST["materialURL"]))))) {
                //Not enough information was given to add the material.
                var_dump($_REQUEST);
                vaR_dump($_FILES);
                echo '<p><img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span style="error">Unable to add a material to this ';
                echo 'module.  Some information necessary to add the material was not present.</span></p>';
                displayMaterialEscapeNav($moduleInfo["moduleID"], "Return To Material List");
              } else { //This else block runs if enough information to add the material was present.
                if($_REQUEST["materialSourceType"]=="LocalFile") { //Is the material type a file to store?
                  $materialLink=storeMaterialLocally($_FILES["materialFile"], $MATERIAL_STORAGE_DIR); //Try to store the material file, and get a link to it.
                  $readableFileName=$_FILES["materialFile"]["name"]; //Set the "human-readable" file name to save to be the name of the file uploaded.
                } else { //Run this block if the material source type isn't a file to upload (ie its a URL)
                  $materialLink=$_REQUEST["materialURL"]; //Get the link (URL) from what was submitted.
                  $readableFileName=""; //There is no "human-readable" file name for URLs.
                }
                if($materialLink===FALSE) { //Error storing material life?
                  echo '<p><img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span style="error">Unable to upload material file.</span></p>';
                  echo '<p>Check to ensure the file fits the minimum upload requirements (size, type, and virus-free) and try again.  If this problem persists, ';
                  echo 'please contact the collection maintainer.</p>';
                  displayMaterialEscapeNav($moduleInfo["moduleID"], "Return To Material List");
                } else {
                  $materialID=createMaterial($materialLink, $_REQUEST["materialSourceType"], $readableFileName, $_REQUEST["materialType"], $_REQUEST["materialTitle"], $_REQUEST["materialRights"], $_REQUEST["materialLanguage"], $_REQUEST["materialPublisher"], $_REQUEST["materialDescription"], $_REQUEST["materialCreator"]); //Add the material to the database
                  if($materialID===FALSE) { //Error adding material?
                    echo '<p><img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span style="error">Unable to create material.</span></p>';
                    echo '<p>Please contact the collection maintainer to report this error.</p>';
                    displayMaterialEscapeNav($moduleInfo["moduleID"], "Return To Material List");
                  } else {
                    $result=attatchMaterialToModule($materialID, $moduleInfo["moduleID"]); //Attatch the material to the module
                    if($result===FALSE) { //Error attatching material to module?
                      echo '<p><img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span style="error">Unable to attatch material to module.</span></p>';
                      echo '<p>Please contact the collection maintaier to report this error.</p>';
                      displayMaterialEscapeNav($moduleInfo["moduleID"], "Return To Material List");
                    } else { //Material successfully uploaded, added to database, and attatched to module!
                      echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img>  Material successfully added.';
                      displayMaterialEscapeNav($moduleInfo["moduleID"], "Return To Material List");
                    }
                  }
                }
              }
            }
            if($moduleAction=="doRemoveMaterial") { //Try to remove a material.
              if(!isset($_REQUEST["materialIDToRemove"])) { //Can't remove a material if we don't know the ID.
                echo '<span class="error">The ID of the material to delete was not specified.  Unable to remove unknown material.</span>';
              } else {
                $result=deattatchMaterialFromModule($_REQUEST["materialIDToRemove"], $moduleInfo["moduleID"]); //Deattatch the material from the module.
                if($result!==TRUE) {
                  echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Error removing material (at deattatchMaterialFromModule).</span>';
                } else {
                  $result=removeMaterialsByID(array($_REQUEST["materialIDToRemove"]), $MATERIAL_STORAGE_DIR); //Actually remove the material.
                  if($result!==TRUE) {
                    echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Error removing material (at removeMaterialsByID).</span>';
                  } else {
                    echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> Successfully removed material.';
                  }
                }
              }
              echo '<p><a href="materials.php?moduleAction=edit&moduleID='.preg_replace('/"/', '/\"/', $moduleInfo["moduleID"]).'">Return to Material List</a></p>';
            } //End if($moduleAction=="doRemoveMaterial"
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