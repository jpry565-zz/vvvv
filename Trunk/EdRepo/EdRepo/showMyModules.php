<?php session_start();
/****************************************************************************************************************************
 *    showMyModules.php - Displays a user's modules.
 *    --------------------------------------------------------------------------------------
 *  Display's modules owened by a user.  This page will automatically find modules owned by the logged in user and display them,
 *  along with options to edit/continue work on modules, and searching modules owned by the user.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: (none) 
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
    if($action=="filter" && isset($_REQUEST["filterText"])) { //If we are suppose to filter the results, do so here (but only if we have enough information to filter with).  Build a list of modules owned by this user, but only with the filtered titles.
      $modules=searchModules(array("userID"=>$userInformation["userID"], "title"=>$_REQUEST["filterText"]));
      $wasFiltered=TRUE;
      $action="display"; //Tell future parts of the program to display what we just got.
    } else { //No filter was specified, so build a list of all modules owned by this user.
      $modules=searchModules(array("userID"=>$userInformation["userID"])); //Get a list of all modules which the user owns.
      $action="display"; //Tell future parts of the program to display what we just got.
    }
  }
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo $COLLECTION_NAME." - View My Modules" ?></title>
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
        } elseif(!in_array("UseModules", $backendCapabilities["read"]) || !in_array("SearchModulesByUserID", $backendCapabilities["read"])) {
          echo '<h1>This Feature Is Not Supported</h1>';
          echo '<p>The backend in use ('.$backendInformation["name"].' version '.$backendInformation["version"].') does not support the "UseModules" and/or "SearchModulesByUserID" features which are required by this page.</p>';
        } else {
          echo '<h1>My Modules</h1>';
          echo '<form name="filter" action="showMyModules.php" method="get">';
          echo '<input type="hidden" readonly="readonly" name="action" value="filter"></input>';
          if($wasFiltered===TRUE) { //The user had a filter, so be nice and automatically place that in the filter bar.
            echo '<input type="text" name="filterText" value="'.preg_replace('/"/', '&quot;', $_REQUEST["filterText"]).'" id="filterTextInput" onclick="document.getElementById(\'filterTextInput\').value=\'\';"></input>'; 
          } else { //The user didn't have a filter, so display default text in the filter view.
            echo '<input type="text" name="filterText" value="Filter this view by title..." id="filterTextInput" onclick="document.getElementById(\'filterTextInput\').value=\'\';"></input>';
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
              echo '<table class="MIEV">';
              echo '<tr><td>Module ID</td><td>Title</td><td>Version</td><td>Date Created</td><td>Status</td><td>Edit</td></tr>';
              for($i=0; $i<count($modules); $i++) {
                $module=$modules[$i];
                echo '<tr><td><a href="viewModule.php?moduleID='.preg_replace('/"/', '&quot;', $module["moduleID"]).'&forceView=true">'.$module["moduleID"].'</a></td>';
                echo '<td>'.$module["title"].'</td><td>'.$module["version"].'</td><td>'.$module["date"].'</td><td>'.$module["status"].'</td>';
                echo '<td><a href="editModule.php?moduleID='.$module["moduleID"].'"><a href="moduleWizard/welcome.php?moduleID='.$module["moduleID"].'">Edit</a></td></tr>';
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