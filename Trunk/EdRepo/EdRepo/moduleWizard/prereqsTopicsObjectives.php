<?php session_start();
/****************************************************************************************************************************
 *    prereqsTopicsObjectives.php - The Prereqs, Topics, and Objectives component of the module submission wizard.
 *    ------------------------------------------------------------------------------------------------------------
 *  Allows creating, editing, and removing prereqs, topics, and objectives to and from a module.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - Designed only to be accessed by other pages in the module submission wizard.
 *         - The viewing of saved prereqs, topics, and objectives is handled by a PHP-JavaScript glue.
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
  <title><?php echo "Module Submission - Step 3" ?></title>
  <script type="text/javascript">
    var savedTopics=new Array();
    var savedObjectives=new Array();
    var savedPrereqs=new Array();
    var savedCategoriesIDs=new Array();
    
    function changeFormActionToNext() {
      document.getElementById("mainForm").action="materials.php";
      return true;
    }
    function changeFormActionToBack() {
      document.getElementById("mainForm").action="basicModuleInformation.php";
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
    
    var topicsArray=new Array();
    var categoriesArray=new Array();
    var objectivesArray=new Array();
    var prereqsArray=new Array();
    var categoriesArray=new Array();
    
    function add(prefix, text) {
      if(prefix=="Topics") {
        var length=topicsArray.length;
        document.getElementById("noModuleTopics").value="false";
      } else if(prefix=="Objectives") {
        var length=objectivesArray.length;
        document.getElementById("noModuleObjectives").value="false";
      } else if(prefix=="Prereqs") {
        var length=prereqsArray.length;
        document.getElementById("noModulePrereqs").value="false";
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
      var parent=document.getElementById(prefix.toLowerCase()+"List");
      var newDiv=document.createElement("div");
      var newTextarea=document.createElement("textarea");
      var newButton=document.createElement("button");
      newDiv.setAttribute("id", "module"+prefix+"Div"+length);
      newButton.setAttribute("id", "module"+prefix+"Button"+length);
      newButton.setAttribute("type", "button");
      newButton.setAttribute("onclick", "remove('"+prefix+"', '"+length+"')");
      newButton.innerHTML="Remove";
      newTextarea.setAttribute("id", "module"+prefix+"Textarea"+length);
      newTextarea.setAttribute("name", "module"+prefix+length);
      if(text!==false) {
        newTextarea.innerHTML=text;
      }
      newDiv.appendChild(newTextarea);
      newDiv.appendChild(newButton);
      parent.appendChild(newDiv);
      if(prefix=="Topics") {
        topicsArray.push("module"+prefix+length);
      } else if(prefix=="Objectives") {
        objectivesArray.push("module"+prefix+length);
      } else if(prefix=="Prereqs") {
        prereqsArray.push("module"+prefix+length);
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
    }
    
    function remove(prefix, num) {
      var i;
      var pos=0;
      var oldArray=new Array();
      if(prefix=="Topics") {
        for(i=0; i<topicsArray.length; i++) { oldArray.push(topicsArray[i]); }
      } else if(prefix=="Objectives") {
        for(i=0; i<objectivesArray.length; i++) { oldArray.push(objectivesArray[i]); }
      } else if(prefix=="Prereqs") {
        for(i=0; i<prereqsArray.length; i++) { oldArray.push(prereqsArray[i]); }
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
      var newArray=new Array();
      for(i=0; i<oldArray.length; i++) {
        //Loop through the main array and copy everything except the thing to delete into the new array, renaming properly along the way.
        if(oldArray[i]!="module"+prefix+num) {
          var divElement=document.getElementById("module"+prefix+"Div"+i);
          var textareaElement=document.getElementById("module"+prefix+"Textarea"+i);
          var buttonElement=document.getElementById("module"+prefix+"Button"+i);
          divElement.setAttribute("id", "module"+prefix+"Div"+pos);
          textareaElement.setAttribute("id", "module"+prefix+"Textarea"+pos);
          textareaElement.setAttribute("name", "module"+prefix+pos);
          buttonElement.setAttribute("id", "module"+prefix+"Button"+pos);
          buttonElement.setAttribute("onclick", "remove('"+prefix+"', '"+pos+"')");
          newArray.push("module"+prefix+pos);
        } else { //This else block will delete the element we want to get rid of.
          var delDiv=document.getElementById("module"+prefix+"Div"+i);
          var delTextarea=document.getElementById("module"+prefix+"Textarea"+i);
          var delButton=document.getElementById("module"+prefix+"Button"+i);
          var parent=document.getElementById(prefix.toLowerCase()+"List");
          delDiv.removeChild(delButton);
          delDiv.removeChild(delTextarea);
          parent.removeChild(delDiv);
          pos--; //Deleting an element will set new elements counter back one, so compensate.
        }
        pos++;
      }
      if(prefix=="Topics") {
        topicsArray.splice(0, topicsArray.length);
        for(i=0; i<newArray.length; i++) { topicsArray.push(newArray[i]); }
        if(topicsArray.length<=0) {
          document.getElementById("noModuleTopics").value="true";
        }
      } else if(prefix=="Objectives") {
        objectivesArray.splice(0, objectivesArray.length);
        for(i=0; i<newArray.length; i++) { objectivesArray.push(newArray[i]); }
        if(objectivesArray.length<=0) {
          document.getElementById("noModuleObjectives").value="true";
        }
      } else if(prefix=="Prereqs") {
        prereqsArray.splice(0, prereqsArray.length);
        for(i=0; i<newArray.length; i++) { prereqsArray.push(newArray[i]); }
        if(prereqsArray.length<=0) {
          document.getElementById("noModulePrereqs").value="true";
        }
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
    }
    
    function addCategory(id) {
      var parent=document.getElementById("categoryList");
      var newDiv=document.createElement("div");
      var newSelect=document.createElement("select");
      var newButton=document.createElement("button");
      var length=categoriesArray.length;
      newDiv.setAttribute("id", "moduleCategoryDiv"+length);
      newSelect.setAttribute("id", "moduleCategorySelect"+length);
      newSelect.setAttribute("name", "moduleCategory"+length);
      newSelect.innerHTML=printCategorySelectInnerHTML(id);
      newButton.setAttribute("id", "moduleCategoryButton"+length);
      newButton.setAttribute("onClick", "removeCategory('"+length+"')");
      newButton.innerHTML="Remove";
      newDiv.appendChild(newSelect);
      newDiv.appendChild(newButton);
      parent.appendChild(newDiv);
      categoriesArray.push("moduleCategory"+length);
      document.getElementById("noModuleCategories").value="false";
    }
    
    function removeCategory(num) {
      var i;
      var pos=0;
      var oldArray=new Array();
      for(i=0; i<categoriesArray.length; i++) {
        oldArray.push(categoriesArray[i]);
      }
      var newArray=new Array();
      for(i=0; i<oldArray.length; i++) {
        //Loop through the main array and copy everything except the thing to delete into the new array, renaming properly along the way.
        if(oldArray[i]!="moduleCategory"+num) {
          var divElement=document.getElementById("moduleCategoryDiv"+i);
          var selectElement=document.getElementById("moduleCategorySelect"+i);
          var buttonElement=document.getElementById("moduleCategoryButton"+i);
          divElement.setAttribute("id", "moduleCategoryDiv"+pos);
          selectElement.setAttribute("id", "moduleCategorySelect"+pos);
          selectElement.setAttribute("name", "moduleCategory"+pos);
          buttonElement.setAttribute("id", "moduleCategoryButton"+pos);
          buttonElement.setAttribute("onclick", "removeCategory('"+pos+"')");
          newArray.push("moduleCategory"+pos);
        } else { //This else block will delete the element we want to get rid of.
          var delDiv=document.getElementById("moduleCategoryDiv"+i);
          var delSelect=document.getElementById("moduleCategorySelect"+i);
          var delButton=document.getElementById("moduleCategoryButton"+i);
          var parent=document.getElementById("categoryList");
          delDiv.removeChild(delButton);
          delDiv.removeChild(delSelect);
          parent.removeChild(delDiv);
          pos--; //Deleting an element will set new elements counter back one, so compensate.
        }
        pos++;
      }
      categoriesArray.splice(0, categoriesArray.length);
      for(i=0; i<newArray.length; i++) {
        categoriesArray.push(newArray[i]);
      }
      if(categoriesArray.length<=0) {
        document.getElementById("noModuleCategories").value="true";
      }
    }
    
    function printCategorySelectInnerHTML(preSelectedID) {
      var tempIDs=new Array();
      var tempNames=new Array();
      var i;
      var r=""; //r will be what is returned.  It is a series of <option> tags.
      <?php //This PHP glue will add all the available categories and names to the tempIDs and tempNames arrays.
        $allCategories=getAllCategories();
        for($i=0; $i<count($allCategories); $i++) {
          echo 'tempIDs.push("'.$allCategories[$i]["ID"].'");';
          echo 'tempNames.push("'.$allCategories[$i]["name"].'");';
        }
      ?>
      for(i=0; i<tempIDs.length; i++) {
        if(preSelectedID===tempIDs[i]) {
          r=r+"<option value=\""+tempIDs[i]+"\" selected=\"selected\">"+tempNames[i]+"</option>";
        } else {
          r=r+"<option value=\""+tempIDs[i]+"\">"+tempNames[i]+"</option>";
        }
      }
      return r;
    }
    
    function initialFillArrayAndDisplay() {
      var i;
      <?php /* This PHP glue will fill the arrays which hold all the saved topics, objectives, and prereqs from the back-end.  The javascript after this 
                PHP will actually write the contents of these arrays to the page. */
        if($moduleAction=="edit") {          
          $savedObjectives=getModuleObjectives($moduleInfo["moduleID"]);
          for($i=0; $i<count($savedObjectives); $i++) {
            $safeJSString=preg_replace('/"/', '\"', $savedObjectives[$i]["text"]);
            echo 'savedObjectives.push("'.$safeJSString.'");';
          }
          $savedTopics=getModuleTopics($moduleInfo["moduleID"]);
          for($i=0; $i<count($savedTopics); $i++) {
            $safeJSString=preg_replace('/"/', '\"', $savedTopics[$i]["text"]);
            echo 'savedTopics.push("'.$safeJSString.'");';
          }
          $savedPrereqs=getModulePrereqs($moduleInfo["moduleID"]);
          for($i=0; $i<count($savedPrereqs); $i++) {
            $safeJSString=preg_replace('/"/', '\"', $savedPrereqs[$i]["text"]);
            echo 'savedPrereqs.push("'.$safeJSString.'");';
          }
          $savedCategories=getModuleCategoryIDs($moduleInfo["moduleID"]);
          for($i=0; $i<count($savedCategories); $i++) {
            $category=getCategoryByID($savedCategories[$i]);
            $safeJSString=preg_replace('/"/', '\"', $category["name"]);
            echo 'savedCategoriesIDs.push("'.$category["ID"].'");';
          }
        }
      ?>
      //Create a box for all saved objectives
      for(i=0; i<savedObjectives.length; i++) {
        add("Objectives", savedObjectives[i]);
      }
      //Create a box for all saved topics
      for(i=0; i<savedTopics.length; i++) {
        add("Topics", savedTopics[i]);
      }
      //Create a box for all saved prereqs
      for(i=0; i<savedPrereqs.length; i++) {
        add("Prereqs", savedPrereqs[i]);
      }
      //Create a box for all saved categories
      for(i=0; i<savedCategoriesIDs.length; i++) {
        addCategory(savedCategoriesIDs[i]);
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
          if($moduleAction=="edit") {
            echo '<h1>Module Topics, Categories, Objectives, and Prerequisets</h1>';
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
              echo '<p>This page allows you to assign topics, categories, and objectives to your module.</p>';
              echo '<form method="post" name="mainForm" id="mainForm" action="basicModuleInformation.php">';
              echo '<input type="hidden" name="noModuleTopics" value="true" id="noModuleTopics"></input>';
              echo '<input type="hidden" name="noModuleObjectives" value="true" id="noModuleObjectives"></input>';
              echo '<input type="hidden" name="noModulePrereqs" value="true" id="noModulePrereqs"></input>';
              echo '<input type="hidden" name="noModuleCategories" value="true" id="noModuleCategories"></input>';
              echo '<table class="MIEV">';
              if(in_array("UseCategories", $backendCapabilities["write"]) && in_array("UseCategories", $backendCapabilities["write"])) { //Only display category options if the backend supports it
                echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Categories</span></td>';
                 echo '<td><div id="categoryList"></div><button type="button" onclick="addCategory(false);">Add Category</td></tr>';
              }
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Topics</span></td>';
               echo '<td><div id="topicsList"></div><button type="button" onclick="add(\'Topics\', false);">Add Topic</button></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Objectives</span></td>';
               echo '<td><div id="objectivesList"></div><button type="button" onclick="add(\'Objectives\', false);">Add Objective</button></td></tr>';
              echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Prerequisets</span></td>';
               echo '<td><div id="prereqsList"></div><button type="button" onclick="add(\'Prereqs\', false);">Add Prerequiset</button></td></tr>';
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