<?php session_start();
/****************************************************************************************************************************
 *    moduleManagement.php - Displays and manages modules.
 *    -----------------------------------------------------
 *  Displays an optionally filtered list of modules in the system, and allows the user to perform basic management of modules
 *  in addition to editing them.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - Only Editors and Admins may use this page.
 ******************************************************************************************************************************/
  
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
  
  if(isset($_SESSION["authenticationToken"])) { //Check if we think someone is already logged in.
    $userInformation=checkIfUserIsLoggedIn($_SESSION["authenticationToken"]);
    if(count($userInformation)==0) { //If true, than the user wasn't found
      logout();
      unset($userInformation);
    }
  }
  
  $action="display";
  $wasFiltered=FALSE; //This determines if the modules fetched were filtered or not, for a nicer display if nothing was found.
  if(isset($_REQUEST["action"])) {
    $action=$_REQUEST["action"];
  }
  if(isset($userInformation)) { //Only do any filtering/etc. if we're logged in,
    if($action=="filter" && isset($_REQUEST["filterTitle"]) && isset($_REQUEST["filterStatus"])) { //If we are suppose to filter the results, do so here (but only if we have enough information to filter with).
      $modules=searchModules(array("status"=>$_REQUEST["filterStatus"], "title"=>$_REQUEST["filterTitle"]));
      $wasFiltered=TRUE;
      $action="display"; //Tell future parts of the program to display what we just got.
    } else { //No filter was specified, so build a list of all modules owned by this user.
      $modules=searchModules(array()); //Get a list of all modules .
      $action="display"; //Tell future parts of the program to display what we just got.
    }
  }
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title>Module Management</title>
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
        if(!isset($userInformation)) { //If true, we aren't logged in.
          echo '<h1>You Must Be Logged In To Continue</h1>';
          echo '<p>You must be logged in to view this page.  You can do so at the <a href="loginLogout.php">log in page</a>.</p>';
        } elseif(!in_array("UseModules", $backendCapabilities["read"])) {
          echo '<h1>This Feature Is Not Supported</h1>';
          echo '<p>The backend in use ('.$backendInformation["name"].' version '.$backendInformation["version"].') does not support the "UseModules" and/or "SearchModulesByUserID" features which are required by this page.</p>';
        } elseif(!($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin")) {
          echo '<h1>Insufficient Privileges To Perform This Action</h1>';
          echo '<p>You do not have enough permissions to perform this action.  Please log out and log back in as a user with higher privileges ';
          echo 'to user this page.</p>';
        } else {
          echo '<h1>Module Management</h1>';
          echo '<form name="filter" action="moduleManagement.php" method="get">';
          echo '<input type="hidden" readonly="readonly" name="action" value="filter"></input>';
          if($wasFiltered===TRUE) { //The user had a filter, so be nice and automatically place that in the filter bar.
            echo '<input type="text" name="filterTitle" value="'.preg_replace('/"/', '&quot;', $_REQUEST["filterTitle"]).'" id="filterTextInput" onclick="document.getElementById(\'filterTextInput\').value=\'\';"></input>'; 
            echo 'Module Status: <select name="filterStatus"><option value="*">All</option><option value="InProgress">In Progress</option>';
            echo '<option value="PendingModeration">Pending Moderation</option><option value="Active">Active</option>';
            echo '<option value="Locked">Locked</option></select>';
          } else { //The user didn't have a filter, so display default text in the filter view.
            echo '<input type="text" name="filterTitle" value="Filter this view by title..." id="filterTextInput" onclick="document.getElementById(\'filterTextInput\').value=\'\';"></input>';
            echo 'Module Status: <select name="filterStatus"><option value="*">All</option><option value="InProgress">In Progress</option>';
            echo '<option value="PendingModeration">Pending Moderation</option><option value="Active">Active</option>';
            echo '<option value="Locked">Locked</option></select>';
          }
          echo '<input type="submit" name="submit" value="Filter"></input>';
          echo '</form>';
          if($action=="display") {
            //We'll use the $modules list of modules to display built earlier
            if(count($modules)==0) { //We didn't find any modules.
              if($wasFiltered===TRUE) { //The module list was filtered, so even though we didn't find anything, it doesn't mean the user doesn't have any modules (it just means they used too strict a filter).
                echo 'No modules were found matching the specified filter.';
              } else { //We didn't find anything in an unflitered list, so the user doesn't have any materials which belong to them.
                echo "No modules currently belong to you.";
              }
            } else {
              echo '<table class="ML">';
              echo '<tr class="MLHeader"><td class="MLHeader">Module ID</td><td class="MLHeader">Title</td><td class="MLHeader">Author</td>';
              echo '<td class="MLHeader">Version</td><td class="MLHeader">Date Created</td><td class="MLHeader">Status</td>';
              echo '<td class="MLHeader">Edit</td><td class="MLHeader">Delete</td></tr>';
              for($i=0; $i<count($modules); $i++) {
                $module=$modules[$i];
                echo '<tr><td><a href="viewModule.php?moduleID='.preg_replace('/"/', '&quot;', $module["moduleID"]).'&forceView=true">'.$module["moduleID"].'</a></td><td>'.$module["title"].'</td>';
                echo '<td>'.$module["authorFirstName"].' '.$module["authorLastName"].'</td>';
                echo '<td>'.$module["version"].'</td><td>'.$module["date"].'</td><td>'.$module["status"].'</td>';
                echo '<td style="white-space: nowrap;"><a href="moduleWizard/welcome.php?moduleID='.preg_replace('/"/', '&quot;', $module["moduleID"]).'&forceActionToEdit=true">Edit</a> | ';
                echo '<a href="moduleWizard/welcom e.php?moduleID='.preg_replace('/"/', '&quot;', $module["moduleID"]).'&moduleAction=createNewVersion">Create New Version</a></td>';
                echo '<td><a href="moduleWizard/delete.php?moduleID='.htmlspecialchars($module["moduleID"]).'&moduleAction=displayDelete&forceDelete=true">Delete</a></td></tr>';
              }
              echo '</table>';
            }
            
          } else { //Unknown action
            echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Error.  An Unknown action was specified.</span>';
          }
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