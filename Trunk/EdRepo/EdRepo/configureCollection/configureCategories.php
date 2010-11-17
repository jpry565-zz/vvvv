<?php session_start();
/****************************************************************************************************************************
 *    configureCategories.php - Allows editing of categories used in the collection.
 *    ---------------------------------------------------------------------------------------------------------
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - Only Admins may use this page.
 *         - This page uses the following GET/POST parameters:
 *            action : One of "display" (default) which will display all categories, with links to remove a category/add a new one, 
 *              "doRemove" to remove a category, or "doAdd" to add a category.
 *            categoryID : Used with the "doRemove" action to specify which category to delete.
 *            categoryName : Used with the "doAdd" action to specify the name of the category to add.
 *            categoryDescription : Used with the "doAdd" action to specify a description for the category to add.
 ******************************************************************************************************************************/
  
  require("../lib/backends/backend.php");
  require("../lib/look/look.php");
  require("../lib/config/config.php");
  require("../lib/frontend-ui.php");
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
  
  $action="display"; //Default action is to display categories.
  if(isset($_REQUEST["action"])) {
    $action=$_REQUEST["action"];
  }
?>
<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title>Configure Categories</title>
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
            echo "You are logged in as ".$userInformation["firstName"]." ".$userInformation["lastName"].'. &nbsp;<a href="userManageAccount.php">Manage Your Account</a> ';
            echo 'or <a href="../loginLogout.php?action=logout">log out</a>.';
          } else {
            echo 'Welcome. &nbsp;Please <a href="../loginLogout.php?action=login">login</a> to your account, or <a href="../createAccount.php">create a new account</a>.';
          }
        ?>
      </div>
      <?php
        if(!isset($userInformation)) {
          echo "<h1>You Must Be Logged In To Continue</h1>";
          echo "<p>You are not logged in.  You must be logged in to view this page.</p";
          echo '<p>Please <a href="../loginLogout.php">log in</a> to access this page.</p';
        } elseif($userInformation["type"]!="Admin") { //We are not an admin...
          echo '<h1>Insufficient Privileges To Perform This Action</h1>';
          echo '<p>You do not have sufficient privileges to view or use this page.  Please log out and log back in as a user with greater privileges to use this page.</p>';
        } elseif(!(in_array("UseCategories", $backendCapabilities["read"]) && in_array("UseCategories", $backendCapabilities["write"]))) { //Check for the ability of the backend to use categories.
          echo '<h1>This Feature Is Not Supported</h1>';
          echo '<p>The back-end storage system currently running on this collection does not support using categories in read and/or write mode.  ';
          echo 'You can not manage catagories using this configuration panel.</p>';
        } else {
          if($action=="display") {
            echo '<h1>Manage Collection Categories</h1>';
            echo '<p>This configuration panel allows you to view, add, and remove categories from this collection.  Categories are used to organize ';
            echo 'modules.</p>';
            
            /* Show an "Add Category" box/form to allow the addition of a new category. */
            echo '<div class="subblock">';
            echo '<span class="subbblockHeader">Add A New Category</span>';
            echo 'To add a new category, enter a title and a short description for the new category and select "Add".';
            echo '<form name="addCategoryForm" action="configureCategories.php" method="post">';
            echo '<input type="hidden" readonly="readonly" name="action" value="doAdd"></input>';
            echo 'Category Name: <input type="text" name="categoryName"></input><br>';
            echo 'Description: <input type="text" name="categoryDescription"></input<br>';
            echo '<input type="submit" name="sub" value="Add"></input>';
            echo '</form>';
            echo '</div>';
            echo 'Current categores in this collection:';
            $categories=getAllCategories(); //Get all categories currently in this collection.
            if($categories===FALSE || $categories=="NotImplimented" || count($categories)<=0) { //Make sure there wasn't an error getting categores and that at least one was found.
              echo '<span class="note">This collection currently contains no categories.</span><br>';
              echo 'To add a category, use the "Add Category" box at the top of this page.';
            } else { //This else block runs if we got at least one category.
              echo '<table>';
              for($i=0; $i<count($categories); $i++) { //List all found categories.
                echo '<tr><td>'.$categories[$i]["name"].'</td><td>'.$categories[$i]["description"].'</td><td><a href="configureCategories.php?action=doRemove&categoryID='.htmlspecialchars($categories[$i]["ID"]).'">Remove</a></td></tr>';
              }
              echo '</table>';
            }
          } elseif($action=="doRemove" && isset($_REQUEST["categoryID"])) { //If the action is doRemove and a categoryID was given, try to remove it.
            echo '<h1>Remove Category</h1>';
            $result=removeCategory($_REQUEST["categoryID"]); //Remove specified category.  This function is also suppose to automatically remove any modules from the category as well, so no special action is needed here to keep the storage back-end consistant.
            if($result===FALSE || $result==="NotImplimented") { //Error
              echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unabled to remove category</span>.';
            } else { //Successfully removed category
              echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> Category successfully removed.';
            }
            echo '<p><a href="configureCategories.php?action=display">Return to Category Configuration Panel</a></p>';
          } elseif($action=="doAdd" && isset($_REQUEST["categoryName"]) && isset($_REQUEST["categoryDescription"])) {
            echo '<h1>Add New Category</h1>';
            $result=createCategory($_REQUEST["categoryName"], $_REQUEST["categoryDescription"]);
            if($result===FALSE || $result=="NotImplimented") { //Failed to create new category
              echo '<img src="../lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Failed to create to category.</span>';
            } else { //Success
              echo '<img src="../lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> Category successfully created.';
            }
            echo '<p><a href="configureCategories.php?action=display">Return to Category Configuration Panel</a></p>';
          } else { //Unknown/unhandled action specified.
            echo '<h1>Unknown or Unhandled Action Specified</h1>';
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