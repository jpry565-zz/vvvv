<?php session_start();
/****************************************************************************************************************************
 *    browse.php - Provides methods to browse the collection.
 *    -------------------------------------------------------
 *  Allows browsing the collection (searching with set criteria) by various parameters.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - This page can take the following GET/POST parameters:
 *            browseBy : What to limit browsed results by.  May be any valid parameter for searchModules, but is suggested to 
 *              use only "titleStartsWith" (for browsing by title) or "category" (for browsing by category).
 *              Default is "titleStartsWith".  Note that what is specified here will affect valid values for the parm parameter.
 *            page : If more than recordsPerPage results are found, than they are displayed in multiple pages, with each page
 *              containing up to resultsPerPage number of records.  The page parameter determines which page to display.  It will
 *              be automatically decreased to the largest page with records on it.  Default is 1 (the fitst page).  Only positive
 *              numbers should be passed to this parameter (0 or negative numbers will result in no records being displayed, regardless
 *              of the value of this parameter or how many records actually match the criteria given with browseBy and/or parm).
 *            parm : The parameter to search whatever is given in browseBy with.  Default is "A".
 *              For a browseBy of "titleStartsWith", parm may be anything (but is suggested to be a single letter/number/symbol).
 *              For a browseBy of "category", parm should be the categoryID to browse by, and should be one of the IDs returned by
 *                a call to getAllCategories()
 *            recordsPerPage : The number of results to show per page.  Default is 15.
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
  
  /* Set default and (if possible) override default values for browsing parameters, then build a list of records to 
    display on the page. */
  $browseBy="titleStartsWith"; //The field to browse by.  This must be a valid parameter to pass to searchModules() (default, "titleStartsWith")
  $parm="A"; //The parameter to search the field given in $browseBy with (default, "A")
  $page=1; //The "page" we are on (default, 1 (first)
  $recordsPerPage=15; //The number of records to display per page (default, 15)
  
  if(isset($_REQUEST["browseBy"])) {
    $browseBy=$_REQUEST["browseBy"];
  }
  if(isset($_REQUEST["parm"])) {
    $parm=$_REQUEST["parm"];
  }
  if(isset($_REQUEST["page"])) {
    $page=$_REQUESt["page"];
  }
  if(isset($_REQUEST["recordsPerPage"])) {
    $recordsPerPage=$_REQUEST["recordsPerPage"];
  }
  
  $records=searchModules(array($browseBy=>$parm, "status"=>"Active")); //Get all records matching the query, but only records which are Active in the collection.
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Browse ".$COLLECTION_NAME; ?></title>
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
        if($records===FALSE || $records=="NotImplimented") { //Did searching for records return an error or a "NotImplimented"?
          echo '<h1>Browsing Not Supported</h1>';
          echo '<p>This collection does not currently support browsing.</p>';
        } else { //This else block runs if searching for records to browse by did not return an error.
          echo '<h1>Browse '.$COLLECTION_NAME.'</h1>';
          /* Print out a strip giving browse options.  Should look similar to this:
            Browse Modules Alphabetically: A | B | C | D .... X | Y | Z  or Browser Modules By Category <Category List><Submit Button> */
          //We need a form if we're going to allow browsing by categories.  Of course, we might not allow that, depending on if the back-end supports it or not.
          //However, creating a form also creates a new line, so to keep both browse options needing a form and those not needing one on the same line, open the
          //form here.  If no browse options end up being given which require a form, than they'll be no form elements in the form, but that won't hurt anything.
          echo '<form name="browseCriteria" action="browse.php" method="get">';
          echo 'Browse Modules Alphabetically: ';
          $alphabet=array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
          for($i=0; $i<count($alphabet); $i++) {
            if($browseBy=="titleStartsWith" && $parm==$alphabet[$i]) { //If the current $browseBy id "titleStartsWith" (aka Alphabetically) and the current parm if the letter in the alphabet being printed, make it bold.
              echo '<span style="font-weight: bold;"><a href="browse.php?browseBy=titleStartsWith&parm='.$alphabet[$i].'">'.$alphabet[$i].'</a></span> | ';
            } else {
              echo '<a href="browse.php?browseBy=titleStartsWith&parm='.$alphabet[$i].'">'.$alphabet[$i].'</a> | ';
            }
          }
          if(in_array("UseCategories", $backendCapabilities["read"])) { //If the backend supports using categories, also give options to browse by category.
            $categories=getAllCategories(); //Get a list of all categories.
            echo ' &nbsp;or by category: ';
            echo '<input type="hidden" name="browseBy" value="category" readonly="readonly"></input>'; //If the form is submitted, set the browseBy action to category.  If more than one possible
                                                                            //browseBy's might be submitted by this form, use JavaScript to change the above form field's value right before submission.
            echo '<select name="parm">';
            for($i=0; $i<count($categories); $i++) { //Loop through all found categories and put them into the select list.
              echo '<option value="'.$categories[$i]["ID"].'">'.htmlspecialchars($categories[$i]["name"], ENT_NOQUOTES).'</option>';
            }
            echo '</select>';
            echo '<input type="submit" name="sub" value="Go"></input>';
          }
          echo '</form>'; //Close browseCriteria form started earlier.
          
          /* Print out matching records (or a something saying no matches were found if $records is empty.  The record(s) to print in $records are limited by $page and $recordsPerPage. */
          if(count($records)<=0) { //No records were found.
            echo 'No modules were found.';
          } else { //At least one record was found.
            $lowerLimit=$recordsPerPage*($page-1); //The lowest index in the $records array which will be printed (based on $page and $recordsPerPage
            $upperLimit=$lowerLimit+$recordsPerPage; //The highest index in the $records array which will be printed (based on $page and $recordsPePage
            /* It is possible that records were found but the page/recordsPerPage combination is beyond the number of records (meaning no records would be displayed).  If this is true,
              decrease the page until it is small enough to show some results. */
            while(count($records)<$lowerLimit) {
              $page=$page-1;
              $lowerLimit=$recordsPerPage*($page-1); //Calculate new lowerLimit based on new page.
              $upperLimit=$lowerLimit+$recordsPerPage; //Calculate new upperLimit based on new page.
            }
            
            /* Print out records matching the browse parameters which should be on the page specified... */
            echo '<table class="moduleInformationView">';
            if(in_array("UseCategories", $backendCapabilities["read"])) {
              echo '<tr><td>Module ID</td><td>Title</td><td>Author</td><td>Date</td><td>Version</td><td>Category</td>';
            } else {
              echo '<tr><td>Module ID</td><td>Title</td><td>Author</td><td>Date</td><td>Version</td>';
            }      
            for($i=$lowerLimit; ($i<$upperLimit && $i<count($records))  ; $i++) { //Loop through records, starting at the lowest index and continuing as long as $i doesn't grow beyong the length of $records and doesn't exceed the upperLimit.
              echo '<tr><td>'.$records[$i]["moduleID"].'</td><td><a href="viewModule.php?moduleID='.$records[$i]["moduleID"].'">'.$records[$i]["title"].'</a></td>';
              echo '<td>'.$records[$i]["authorFirstName"].' '.$records[$i]["authorLastName"].'</td><td>'.$records[$i]["date"].'</td>';
              echo '<td>'.$records[$i]["version"].'</td>';
              if(in_array("UseCategories", $backendCapabilities["read"])) {
                echo '<td>';
                $categories=getModuleCategoryIDs($records[$i]["moduleID"]);
                if($categories!==FALSE) {
                  for($j=0; $j<count($categories); $j++) {
                    $category=getCategoryByID($categories[$j]);
                    echo $category["name"].' ';
                  }
                } else { //Error getting categories for module.
                  echo 'Error getting module categories.  ';
                }
                echo '</td>';
              }
              echo '</tr>';
            }
            echo '</table>';
            
            /* Print any needed "Previous Page" or "Next Page" links */
            if($page>1) {
              echo '<a href="browse.php?browseBy='.$browseBy.'&page='.($page-1).'&parm='.htmlspecialchars($parm).'&recordsPerPage='.$recordsPerPage.'">Previous Page</a> ';
            }
            if(count($records)>($page*$recordsPerPage)) {
              echo '<a href="browse.php?browseBy='.$browseBy.'&page='.($page+1).'&parm='.htmlspecialchars($parm).'&recordsPerPage='.$recordsPerPage.'">Previous Page</a> ';
            }
            
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