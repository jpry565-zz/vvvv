<?php session_start();
/****************************************************************************************************************************
 *    crossReferences.php - Handles viewing, creating, and managing cross-references during module submission.
 *    --------------------------------------------------------------------------------------
 *  Handles the cross-reference creation and editing process during module submission.  It is a component of the module submission
 *  wizard and is designed only to work when accessed either from itself or a link/button from another page in the module
 *  submission wizard.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - This file is not intended to be accessed except from the module submission wizard.
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
  if(!((in_array("CrossReferenceModulesExternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesExternal", $backendCapabilities["write"])) || (in_array("CrossReferenceModulesInternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesInternal", $backendCapabilities["write"])))) {
    
    $moduleAction="notImplimented";
  }
  
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Module Submission - Cross References" ?></title>
  <script type="text/javascript">
    function changeFormActionToNext() {
      document.getElementById("mainForm").action="final.php";
      return true;
    }
    function changeFormActionToBack() {
      document.getElementById("mainForm").action="materials.php";
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
    
    var iReferencesArray=new Array();
    var eReferencesArray=new Array();
    
    function add(prefix, text, link) {
      if(prefix=="ERefs") {
        var length=eReferencesArray.length;
        document.getElementById("noModuleERefs").value="false"; //We're adding an eRef, so there must be at least one.
      } else if(prefix=="IRefs") {
        var length=iReferencesArray.length;
        document.getElementById("noModuleIRefs").value="false"; //We're adding an iRef, so there must be at least one.
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
      var parent=document.getElementById(prefix.toLowerCase()+"List");
      var newDiv=document.createElement("div");
      var newTextarea=document.createElement("textarea");
      var newInput=document.createElement("input");
      var newButton=document.createElement("button");
      var span1=document.createElement("span");
      var span2=document.createElement("span");
      var hr=document.createElement("hr");
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
      newInput.setAttribute("id", "module"+prefix+"Input"+length);
      newInput.setAttribute("name", "module"+prefix+"Link"+length);
      newInput.setAttribute("type", "text");
      if(prefix=="IRefs") {
        newInput.setAttribute("size", "10");
      }
      if(link!==false) {
        newInput.setAttribute("value", link);
      }
      hr.setAttribute("id", "module"+prefix+"Hr"+length);
      span1.setAttribute("id", "module"+prefix+"Span1"+length);
      span1.setAttribute("style", "font-weight: bold;");
      span1.innerHTML="Description of resource:<br>";
      span2.setAttribute("id", "module"+prefix+"Span2"+length);
      span2.setAttribute("style", "font-weight: bold;");
      if(prefix=="ERefs") {
        span2.innerHTML="Citation for external reference:<br>";
      } else if(prefix=="IRefs") {
        span2.innerHTML="Module ID of reference:<br>";
      }
      newDiv.appendChild(span1);
      newDiv.appendChild(newTextarea);
      newDiv.appendChild(span2);
      newDiv.appendChild(newInput);
      newDiv.appendChild(newButton);
      newDiv.appendChild(hr);
      parent.appendChild(newDiv);
      if(prefix=="ERefs") {
        eReferencesArray.push("module"+prefix+length);
      } else if(prefix=="IRefs") {
        iReferencesArray.push("module"+prefix+length);
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
    }
    
    function remove(prefix, num) {
      var i;
      var pos=0;
      var oldArray=new Array();
      if(prefix=="ERefs") {
        for(i=0; i<eReferencesArray.length; i++) { oldArray.push(eReferencesArray[i]); }
      } else if(prefix=="IRefs") {
        for(i=0; i<iReferencesArray.length; i++) { oldArray.push(iReferencesArray[i]); }
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
          var inputElement=document.getElementById("module"+prefix+"Input"+i);
          var span1=document.getElementById("module"+prefix+"Span1"+i);
          var span2=document.getElementById("module"+prefix+"Span2"+i);
          var hr=document.getElementById("module"+prefix+"Hr"+i);
          divElement.setAttribute("id", "module"+prefix+"Div"+pos);
          textareaElement.setAttribute("id", "module"+prefix+"Textarea"+pos);
          textareaElement.setAttribute("name", "module"+prefix+pos);
          buttonElement.setAttribute("id", "module"+prefix+"Button"+pos);
          buttonElement.setAttribute("onclick", "remove('"+prefix+"', '"+pos+"')");
          inputElement.setAttribute("id", "module"+prefix+"Input"+pos);
          inputElement.setAttribute("name", "module"+prefix+"Link"+pos);
          hr.setAttribute("id", "module"+prefix+"Hr"+pos);
          span1.setAttribute("id", "module"+prefix+"Span1"+pos);
          span2.setAttribute("id", "module"+prefix+"Span2"+pos);
          newArray.push("module"+prefix+pos);
        } else { //This else block will delete the element we want to get rid of.
          var delDiv=document.getElementById("module"+prefix+"Div"+i);
          var delTextarea=document.getElementById("module"+prefix+"Textarea"+i);
          var delButton=document.getElementById("module"+prefix+"Button"+i);
          var parent=document.getElementById(prefix.toLowerCase()+"List");
          var delInput=document.getElementById("module"+prefix+"Input"+i);
          var span1=document.getElementById("module"+prefix+"Span1"+i);
          var span2=document.getElementById("module"+prefix+"Span2"+i);
          var hr=document.getElementById("module"+prefix+"Hr"+i);
          delDiv.removeChild(delButton);
          delDiv.removeChild(span1);
          delDiv.removeChild(delTextarea);
          delDiv.removeChild(span2);
          delDiv.removeChild(delInput);
          delDiv.removeChild(hr);
          parent.removeChild(delDiv);
          pos--; //Deleting an element will set new elements counter back one, so compensate.
        }
        pos++;
      }
      if(prefix=="ERefs") {
        eReferencesArray.splice(0, eReferencesArray.length);
        for(i=0; i<newArray.length; i++) { eReferencesArray.push(newArray[i]); }
        if(eReferencesArray.length<=0) { //If true, there are no eRefs left.
          document.getElementById("noModuleERefs").value="true";
        }
      } else if(prefix=="IRefs") {
        iReferencesArray.splice(0, iReferencesArray.length);
        for(i=0; i<newArray.length; i++) { iReferencesArray.push(newArray[i]); }
        if(iReferencesArray.length<=0) { //If true, there are no iRefs left.
          document.getElementById("noModuleIRefs").value="true";
        }
      } else {
        alert("Error:  Unknown prefix '"+prefix+"' passed.");
      }
    }
    
    function initialFillArrayAndDisplay() {
      var i;
      var savedErefsDescriptions=new Array();
      var savedErefsLinks=new Array();
      var savedIrefsDescriptions=new Array();
      var savedIrefsLinks=new Array();
      <?php /* This PHP glue will fill the arrays which hold all the saved topics, objectives, and prereqs from the back-end.  The javascript after this 
                PHP will actually write the contents of these arrays to the page. */
        if($moduleAction=="edit") {          
          if(in_array("CrossReferenceModulesInternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesInternal", $backendCapabilities["write"])) {
            $savedIReferences=getInternalReferences($moduleInfo["moduleID"]);
            for($i=0; $i<count($savedIReferences); $i++) {
              $safeJSString=preg_replace('/"/', '\"', $savedIReferences[$i]["description"]);
              echo 'savedIrefsDescriptions.push("'.$safeJSString.'");';
              $safeJSString=preg_replace('/"/', '\"', $savedIReferences[$i]["referencedModuleID"]);
              echo 'savedIrefsLinks.push("'.$safeJSString.'");';
            }
          }
          if(in_array("CrossReferenceModulesExternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesExternal", $backendCapabilities["write"])) {
            $savedEReferences=getExternalReferences($moduleInfo["moduleID"]);
            for($i=0; $i<count($savedEReferences); $i++) {
              $safeJSString=preg_replace('/"/', '\"', $savedEReferences[$i]["description"]);
              echo 'savedErefsDescriptions.push("'.$safeJSString.'");';
              $safeJSString=preg_replace('/"/', '\"', $savedEReferences[$i]["link"]);
              echo 'savedErefsLinks.push("'.$safeJSString.'");';
            }
          }
        }
      ?>
      //Create a box for all saved external references
      for(i=0; i<savedErefsDescriptions.length; i++) {
        add("ERefs", savedErefsDescriptions[i], savedErefsLinks[i]);
      }
      //Create a box for all saved internal references
      for(i=0; i<savedIrefsDescriptions.length; i++) {
        add("IRefs", savedIrefsDescriptions[i], savedIrefsLinks[i]);
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
            echo '<h1>Cross References</h1>';
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
              echo '<p>Cross references allow you to create links between your module and other resources.  ';
              if(in_array("CrossReferenceModulesInternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesInternal", $backendCapabilities["write"])) {
                echo 'Cross references to other modules allow you to create a two-way link to other modules, so that those who view either this ';
                echo 'module or the referenced module can view the cross reference between them.  ';
              }
              if(in_array("CrossReferenceModulesExternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesExternal", $backendCapabilities["write"])) {
                echo 'External references allow you to point those viewing this module to other relivent sources of information, such as books, ';
                echo 'web pages, and journal articles relating to this module.  It is suggested you provide these references as a citation.';
              }
              echo '</p>';
              echo '<form method="post" name="mainForm" id="mainForm" action="crossReferences.php">';
              echo '<input type="hidden" name="noModuleERefs" value="true" id="noModuleERefs"></input>'; //Is set to "false" by JavaScript when at least one eRef has been entered, and "true" if all have been removed. 
              echo '<input type="hidden" name="noModuleIRefs" value="true" id="noModuleIRefs"></input>'; //Is set to "false" by JavaScript when at least one iRef has been entered, and "true" if all have been removed.
              echo '<table class="MIEV">';
              //Check for support for internal references.  If it exists, print elements for it.
              if(in_array("CrossReferenceModulesInternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesInternal", $backendCapabilities["write"])) {
                echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">Related Modules</span><br>';
                echo '<span class="MIEVDescriptiveText">If other modules in this collection relate to this module, add them here, along with ';
                echo 'a brief description of the relation.</span></td>';
                echo '<td><div id="irefsList"></div><button type="button" onclick="add(\'IRefs\', false, false)">Add Related Module</button></td></tr>';
              }
              //Check for support for external references.  If it exists, print for elements for it.
              if(in_array("CrossReferenceModulesExternal", $backendCapabilities["read"]) && in_array("CrossReferenceModulesExternal", $backendCapabilities["write"])) {
                echo '<tr><td class="MIEVCategoryCell"><span class="MIEVCategoryText">External References</span><br>';
                echo '<span class="MIEVDescriptiveText">External references are references to sources outside this collection that viwers of your ';
                echo 'module may be interested in.  It is reccomended you provide these references in the form of a citation (for example, in ';
                echo 'APA or MLA style).</span></td>';
                echo '<td><div id="erefsList"></div><button type="button" onclick="add(\'ERefs\', false, false)">Add External Reference</button></td></tr>';
              }
              
              echo '</table>';
              displayNavigationFooter($moduleID, $moduleAction);
            }
          }
          if($moduleAction=="notImplimented") {
            echo '<h1>Cross References</h1>';
            if(saveAllPossible($_REQUEST, $userInformation, $moduleInfo)===TRUE) {
              echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Module Progress Saved"> Module saved.';
            } else {
              echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to save module progress.</span>';
            }
            echo '<p>The storage back-end currently running on this collection does not support editing either internal or external cross-references.  ';
            echo 'This is not an error, and will not affect the rest other aspects of your module.  However, it does mean you can not create ';
            echo 'cross-references with other resources.</p>';
            echo '<form method="post" name="mainForm" id="mainForm" action="basicModuleInformation.php">';
            displayNavigationFooter($moduleID, "edit");
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