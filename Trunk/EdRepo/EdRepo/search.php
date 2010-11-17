<?php session_start();
/****************************************************************************************************************************
 *    search.php - Provides methods to search the collection.
 *    -------------------------------------------------------
 *  Handles all front-end searching of the collection, including collecting search parameters, interfacing with the backend, and
 *  displaying search results.
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
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Search ".$COLLECTION_NAME; ?></title>
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
        if(!(isset($_REQUEST["title"]) || isset($_REQUEST["author"]) || isset($_REQUEST["version"]) || isset($_REQUEST["category"])) || isset($_REQUEST["showAdvancedSearch"])) { //If no search parameters were specified, OR showAdvancedSearch was specified, show advnaced search options.
          $atLeastOneSearchOption=FALSE; //This gets set to true if the back-end supports at least one search option.
          echo '<h1>Advanced Search</h1>';
          echo '<p>You can search by all criteria shown.  Leaving a field blank or, if applicable, setting it to "All" will not limit search results ';
          echo 'by that criteria.</p>';
          echo '<form name="advancedSearchForm" action="search.php" method="get">';
          if(in_array("SearchModulesByTitle", $backendCapabilities["read"])) {
            echo 'Title: <input type="text" name="title"></input><br>';
            $atLeastOneSearchOption=TRUE;
          }
          if(in_array("SearchModulesByAuthor", $backendCapabilities["read"])) {
            echo 'Author: <input type="text" name="author"></input><br>';
            $atLeastOneSearchOption=TRUE;
          }
          if(in_array("SearchModulesByCategory", $backendCapabilities["read"])) {
            $categories=getAllCategories();
            echo 'Category: <select name="category">';
            for($i=0; $i<count($categories); $i++) {
              echo '<option value="*" selected="selected">All</option>';
              echo '<option value="'.$categories[$i]["ID"].'">'.$categories[$i]["name"].'</option>';
            }
            echo '</select><br>';
            $atLeastOneSearchOption=TRUE;
          }
          if($atLeastOneSearchOption==TRUE) {
            echo '<input type="submit" name="sub" value="Search"></input>';
          } else { //This else block indicates we couldn't find any way to search
            echo 'Sorry, the back-end in use does not support searching for modules.';
          }
          echo '</form>';
        } else { //This else block runs if we have enough info to actually search.  So, search and display the results.
          $title="";
          $category="*";
          $author="";
          if(isset($_REQUEST["title"])) {
            $title=$_REQUEST["title"];
          }
          if(isset($_REQUEST["category"])) {
            $category=$_REQUEST["category"];
          }
          if(isset($_REQUEST["author"])) {
            $author=$_REQUEST["author"];
          }
          $results=searchModules(array("title"=>$title, "author"=>$author, "category"=>$category, "status"=>"Active"));
          echo '<h1>Search Results</h1>';
          if(count($results)<=0) { //No results were found.
            echo '<p>Your search returned no results.</p>';
          } else {
            echo '<table class="moduleInformationView">';
            if(in_array("UseCategories", $backendCapabilities["read"])) {
              echo '<tr><td>Module ID</td><td>Title</td><td>Author</td><td>Date</td><td>Version</td><td>Category</td>';
            } else {
              echo '<tr><td>Module ID</td><td>Title</td><td>Author</td><td>Date</td><td>Version</td>';
            }
            for($i=0; $i<count($results); $i++) {
              echo '<tr><td>'.$results[$i]["moduleID"].'</td><td><a href="viewModule.php?moduleID='.$results[$i]["moduleID"].'">'.$results[$i]["title"].'</a></td>';
              echo '<td>'.$results[$i]["authorFirstName"].' '.$results[$i]["authorLastName"].'</td><td>'.$results[$i]["date"].'</td>';
              echo '<td>'.$results[$i]["version"].'</td>';
              if(in_array("UseCategories", $backendCapabilities["read"])) {
                echo '<td>';
                $categories=getModuleCategoryIDs($results[$i]["moduleID"]);
                if($categories!==FALSE) { //No error when checking for categories
                  for($j=0; $j<count($categories); $j++) {
                    $category=getCategoryByID($categories[$j]);
                    echo $category["name"].' ';
                  }
                } else { //Error while checking for categories.
                  echo 'Error while checking module categories. ';
                }
                echo '</td>';
              }
              echo '</tr>';
            }
            echo '</table>';
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