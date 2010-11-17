<?php session_start();
/****************************************************************************************************************************
 *    index.php - The collection configuration main panel.
 *    -------------------------------------------------------------
 *  The main configuration panel for the collection.  Serves as a jumping point to specific configuration settings.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - Only Admins may use this page.
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
  
?>
<html>
<head>
  <link rel="stylesheet" href="<?php echo "../lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php if(isset($userInformation)) { echo "Configure ".$COLLECTION_NAME; } else { echo "You Must Be Logged In To View This Page"; } ?></title>
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
        } else { //This else block is if we're logged in as an admin.
          echo '<h1>Configure '.$COLLECTION_NAME.'</h1>';
          /* Display block allowing us to configure the categories used in this collection (in the back-end support it) */
          if(in_array("UseCategories", $backendCapabilities["read"]) && in_array("UseCategories", $backendCapabilities["write"])) {
            echo '<div class="subblock">';
            echo '<span class="subblockHeader">Collection Categories</span>';
            echo '<p>Categories provide a quick and easy way to organize modules.  You can configure which categories are available in your ';
            echo 'collection</p>';
            echo '<button type="button" onclick="location.href=\'configureCategories.php\';">Configure Categories</button>';
            echo '</div><br>';
          }
          
          /* Display block allowing us to configure static pages (about page, home page, etc) */
           echo '<div class="subblock">';
           echo '<span class="subblockHeader">Information Pages</span>';
           echo "<p>Change the content of this collection's home page, about page, and other static content pages.</p>";
           echo '<p>To edit the content of a page, select the page from the drop-down menu and click "Edit Page"</p>';
           echo '<form name="infoPagesForm" action="editStaticPages.php" method="get">';
           echo '<input type="hidden" readonly="readonly" name="action" value="displayEdit">';
           echo '<select name="page">';
            echo '<option value="home.html">Homepage</option>';
            echo '<option value="about.html">About Page</option>';
           echo '</select>';
           echo '<input type="submit" name="sub" value="Edit Page"></input>';
           echo '</div>';
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