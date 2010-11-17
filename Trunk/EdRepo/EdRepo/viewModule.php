<?php session_start();
/****************************************************************************************************************************
 *    viewModule.php - Displays the contents of a module.
 *    --------------------------------------------------------------------------------------
 *  Displays the contents of a module (metadata, materials, material metadata, etc).
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - This page can take the following GET or POST parameters:
 *          forceView: If set to "true", will attempt to view modules, even if they are not active in the collection.  Only Editors,
 *                     Admins, and the module's creator may force view modules.
 *          moduleID: The ID of the module to view.  Required, or the page will prompt for an ID.
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
  
  function userCanViewModule($userType, $minType) {
    if($minType=="Unregistered" || $userType=="Admin") { //Everyone can view modules if the minimum level is Unregistered, and Admins can view every module.
      return TRUE;
    }
    if($userType=="Disabled" || $userType=="Deleted" || $userType=="Pending") { //Disabled, deleted, and pending users can not view any modules.
      return FALSE;
    }
    if($userType=="Viewer" && $minType=="Viewer") {
      return TRUE;
    }
    if($userType=="SuperViewer" && ($minType=="Viewer" || $minType=="SuperViewer")) {
      return TRUE;
    }
    if($userType=="Submitter" && ($minType=="Viewer" || $minType=="SuperViewer" || $minType=="Submitter")) {
      return TRUE;
    }
    if($userType=="Editor" && ($minType=="Viewer" || $minType=="SuperViewer" || $minType="Submitter" || $minType=="Editor")) {
      return TRUE;
    }
    return FALSE;
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
  <title><?php echo "View Module"; ?></title>
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
        if(isset($_REQUEST["moduleID"])) { //Did we get a module ID?
          /* Gather information about this module (including materials, categories, prereqs, etc). */
          $module=getModuleByID($_REQUEST["moduleID"]);
          if($module===FALSE || count($module)<=0) { //If the backend reported an error getting the module, or returned an empty array, assume the module doesn't exist.
            echo '<h1>Module Not Found</h1>';
            echo '<p>No module with the specified ID was found.  Please try again with a different ID.</p>';
            echo '<form name="moduleIDSubmission" action="viewModule.php" method="get">';
            echo '<input type="text" name="moduleID"></input>';
            echo '<input type="submit" name="sub" value="View Module"></input>';
            echo '</form>';
          } else { //This else block runs is a module with the specified ID was found.  It determines if the user may view the module, and if they can, displays it.
            if(in_array("UseMaterials", $backendCapabilities["read"])) { //Does the back-end support reading modules?
              $materials=getAllMaterialsAttatchedToModule($module["moduleID"]);
            } else { //This else blcok runs if the back-end doesn't support reading materials.
              $materials=FALSE;
            }
            $prereqs=getModulePrereqs($module["moduleID"]);
            $topics=getModuleTopics($module["moduleID"]);
            $objectives=getModuleObjectives($module["moduleID"]);
            $authors=getModuleAuthors($module["moduleID"]);
            if(in_array("UseCategories", $backendCapabilities["read"])) { //Only get categories if the back-end supports reading module categories.
              $categories=getModuleCategoryIDs($module["moduleID"]); //Grab all category IDs which are attatched to the module.
            } else { //This else block runs if the back-end doesn't support categories.
              $categories=FALSE; //Set the category to FALSE to alert later code not to display any category.
            }
            /* Done gathering module/meterial/etc information. */
            
            /* To determine if the user can view the module, first create a variable $canViewModule which will be FALSE if the user can't view 
              the module, and TRUE if they can.  Then, perform two checks to see if the user can view the module:  (1) check if the user
              is not logged in, and if they aren't, then check if the module can be viewed by unregistered users.  (2) If the user is
              logged in, check to see if they can view the module, based on their pirvilege level.
            The reason for this cumbersome two-part check (with the first check actually being two parts) is because if the user is not
              logged in, that the $userInformation variable will not exist.  However, this variable is needed to check if a user has sufficient
              privileges to view the module, so to avoid a "variable does nto exist" error, we must check if $userInformation does NOT exist and
              handle that, and only interact with the variable if we determine it really does exist. */
            $canViewModule=FALSE; //Set to true if we determine the user can view the module.
            if(!isset($userInformation)) {
              if($module["minimumUserType"]=="Unregistered") {
                $canViewModule=TRUE;
              }
            } elseif(userCanViewModule($userInformation["type"], $module["minimumUserType"]===TRUE)) {
              $canViewModule=TRUE;
            }
            if($module["status"]=="InProgress" || $module["status"]=="PendingModeration") { //If the module exists, check to see if its status is "InProgress" or "PendingModeration".  These modules can't normally be viewed, unless overridden.
              /*Check to see if the user is logged in and if they have requested a force override to view InProgress modules.  If they have, 
                check to make sure the user is an Editor or Admin or is the owner of the module and if they are one of these, allow the force
                over-ride.  Otherwise, deny it. */
              if(isset($userInformation) && isset($_REQUEST["forceView"]) && $_REQUEST["forceView"]=="true" && ($userInformation["type"]=="Editor" || $userInformation["type"]=="Admin" || $module["submitterUserID"]==$userInformation["userID"])) {
                $canViewModule=TRUE;
              } else {
                $canViewModule=FALSE;
              }
            }
            if($canViewModule===TRUE) { //Did we determine that the user can view the module?
              echo '<h1>View Module "'.$module["title"].'"</h1>';
              echo '<p><span class="MIVSectionHeader">General Information</span><p>';
              if($module["status"]=="InProgress") { //If the module's status is InProgress, print a warning that the module is not active in the collection.
                echo '<p><span class="note">Notice:  This module is not yet active in this collection.</span>  This module has not yet been published ';
                echo 'to this collection, and can not be searched for or viewed by most users.  To activate this module, it must be submitted to ';
                echo 'the collection via the module submission wizard';
                if($NEW_MODULES_REQUIRE_MODERATION==TRUE) {
                  echo ' and approved by a moderator';
                }
                echo '.</p>';
              }
              echo '<table class="MIV">';
              echo '<tr><td class="MIVCategory">Title</td><td>'.$module["title"].'</td></tr>';
              echo '<tr><td class="MIVCategory">Abstract</td><td>'.$module["abstract"].'</td></tr>';
              if($authors!==FALSE && $authors!=="NotImplimented" && count($authors)>=1) { //display found authors, but only if we successfulyl found some.
                echo '<tr><td class="MIVCategory" rowspan="'.count($authors).'">Authors</td>';
                echo '<td>'.$authors[0].'</td></tr>'; //display the first author
                for($i=1; $i<count($authors); $i++) { //Loop through any additional authors and display them.
                  echo '<tr><td>'.$authors[$i].'</td></tr>';
                }
              }
              echo '<tr><td class="MIVCategory">Last Modified</td><td>'.$module["date"].'</td></tr>';
              echo '<tr><td class="MIVCategory">Version</td><td>'.$module["version"].'</td></tr>';
              if($categories!=="NotImplimented" && $categories!==FALSE && count($categories)>=1) { //Only display a category if we earlier determined the back-end actually supported categories.
                $category=getCategoryByID($categories[0]); //Get the first category for the module.
                echo '<tr><td rowspan="'.count($categories).'" class="MIVCategory">Categories</td><td>'.$category["name"].'</td></tr>';
                for($i=1; $i<count($categories); $i++) {
                  $category=getCategoryByID($categories[$i]);
                  echo '<tr><td>'.$category["name"].'</td></tr>';
                }
              }
              echo '<tr><td class="MIVCategory">Comments</td><td>'.$module["authorComments"].'</td></tr>';
              if(in_array("RateModules", $backendCapabilities["read"])) { //Show the module's rating, if the backend supports reading module ratings.
                echo '<tr><td class="MIVCategory">Rating</td><td>';
                $ratings=getModuleRatings($module["moduleID"]);
                if($ratings["numberOfRatings"]<=0) { //If there are no ratings, indicate that (don't try to determine a numerical rating)
                  echo 'This module as not yet been rated.';
                } else { //This else runs if there is at least one rating for the module.
                  echo ($ratings["rating"]/$ratings["numberOfRatings"]).' of 5 (out of '.$ratings["numberOfRatings"].' total ratings).';
                }
                if(in_array("RateModules", $backendCapabilities["write"])) { //Does the backend support writing module ratings?  If so, display a link to do so.
                  echo ' &nbsp;<a href="rate.php?moduleID='.$module["moduleID"].'">Leave a Rating</a>';
                }
                echo '</td></tr>'; //End rating cells and row
              }
              echo '</table>';
              
              echo '<p><span class="MIVSectionHeader">Module Size</span><br>';
              echo 'A module\'s size refers to either how long each component of a module is expected to take, and/or how many people each ';
              echo 'component is designed for.</p>';
              echo '<table class="MIV">';
              echo '<tr><td class="MIVCategory">Lecture</td><td>'.$module["lectureSize"].'</td></tr>';
              echo '<tr><td class="MIVCategory">Exercise</td><td>'.$module["exerciseSize"].'</td></tr>';
              echo '<tr><td class="MIVCategory">Lab</td><td>'.$module["labSize"].'</td></tr>';
              echo '<tr><td class="MIVCategory">Homework</td><td>'.$module["homeworkSize"].'</td></tr>';
              echo '<tr><td class="MIVCategory">Other</td><td>'.$module["otherSize"].'</td></tr>';
              echo '</table>';
              
              echo '<p><span class="MIVSectionHeader">Topics, Objectives, and Prerequisets</span></p>';
              echo '<table class="MIV">';
              /* Displaying the cells/rows for topics, objectives, and prereqs works like this:
                (1) Make sure there is at least one topic/objective/prereq to display.
                (2) If (1) is true, than create a row with the proper lable (Topics/Objectives/Prerequisets) and make it span n number of rows, 
                  where n is the total number of topics/objectives/prereqs.  Also, on the same line, print the cell for the first
                  topic/objective/prereq and end the row.
                (3) Print new rows and cells with the rest of the topics/objectives/prereqs, starting with the 2nd one (index [1]), since the 
                  first was already printed. */
              if(count($topics)>=1) { //Only display a topics entry if there is at least one topic.
                echo '<tr><td class="MIVCategory" rowspan="'.count($topics).'">Topics</td><td>'.$topics[0]["text"].'</td>';
                for($i=1; $i<count($topics); $i++) {
                  echo '<tr><td>'.$topics[$i]["text"].'</td></tr>';
                }
              }
              if(count($objectives)>=1) { //Only display objectives entry if there is at least one objectives.
                echo '<tr><td class="MIVCategory" rowspan="'.(count($objectives)).'">Objectives</td><td>'.$objectives[0]["text"].'</td>';
                for($i=1; $i<count($objectives); $i++) {
                  echo '<tr><td>'.$objectives[$i]["text"].'</td></tr>';
                }
              }
              if(count($prereqs)>=1) { //Only display prereqs entry if there is at least one prereq.
                echo '<tr><td class="MIVCategory" rowspan="'.count($prereqs).'">Prerequisets</td><td>'.$prereqs[0]["text"].'</td>';
                for($i=1; $i<count($prereqs); $i++) {
                  echo '<tr><td>'.$prereqs[$i]["text"].'</td></tr>';
                }
              }
              echo '</table>';
              
              if($materials!==FALSE && $materials!=="NotImplimented") { //Only show materials if we previously determined the back-end actually supports materials.
                echo '<p><span class="MIVSectionHeader">Materials</span></p>';
                if(count($materials)<=0) { //Check if the module does not contain any materials.
                  echo '<p>This module contains no materials.</p>';
                } else { //This else block runs if the module contains at least one material.
                  echo '<table class="MIV">';
                  for($i=0; $i<count($materials); $i++) { //Loop through every material ID found, fetch the actual material, and display its metadata.
                    $material=getMaterialByID($materials[$i]); //Get the actual information about the material.
                    //The number of rows the left column of the material details table must span is dependent on if we can show ratings or not.  So, 
                    //set the rowspan ($rowspan) to initially be the number to display if we can show ratings, and then decrease if if we can't 
                    //show ratings (becuase of the backend) and hence won't show the "ratings" row.
                    $rowspan=9;
                    if(!in_array("RateMaterials", $backendCapabilities["read"])) {
                      $rowspan--;
                    }
                    echo '<tr><td class="MIVCategory" rowspan="'.$rowspan.'">'.$material["title"].'</td>';
                    echo '<td><span class="MIVSubSection">Title:</span>  '.$material["title"].'</td></tr>';
                    echo '<tr><td><span class="MIVSubSection">Creator:</span>  '.$material["creator"].'</td></tr>';
                    echo '<tr><td><span class="MIVSubSection">Type:</span>  '.$material["type"].'</td></tr>';
                    echo '<tr><td><span class="MIVSubSection">Description:</span>  '.$material["description"].'</td></tr>';
                    echo '<tr><td><span class="MIVSubSection">Publisher:</span>  '.$material["publisher"].'</td></tr>';
                    echo '<tr><td><span class="MIVSubSection">Language:</span>  '.$material["language"].'</td></tr>';
                    echo '<tr><td><span class="MIVSubSection">Rights/Liscense:</span>  '.$material["rights"].'</td></tr>';
                    if(in_array("RateMaterials", $backendCapabilities["read"])) { //Display the material's rating if the backend supports reading material ratings.
                      echo '<tr><td><span class="MIVSubSection">Rating:</span>  ';
                      $ratings=getMaterialRatingsAndComments($material["materialID"]);
                      if(count($ratings)<=0) { //If there are 0 or fewer ratings, don't don't display a rating.  Instead, say there are no ratings (avoids a division by 0 error if there are numberOfRatings==0)
                        echo 'This material has not been rated yet.';
                      } else {
                        $totalRating=0;
                        $numRatings=0;
                        for($j=0; $j<count($ratings); $j++) {
                          $totalRating=$totalRating+$ratings[$j]["rating"];
                          $numRatings++;
                        }
                        echo ($totalRating/$numRatings).' out of 5 (out of '.$numRatings.' total ratings).';
                      }
                      if(in_array("RateMaterials", $backendCapabilities["write"])) { //If the backend supports writing ratings, give a link to rate the material.
                        echo ' &nbsp;<a href="rate.php?materialID='.$material["materialID"].'">Leave a Rating</a>';
                      }
                      echo '</td></tr>';
                    }
                    if($material["linkType"]=="LocalFile") { //Links to materials always go to the same place.  However, materials which are external URLS are labeled differently than materials which are local files which will be downloaded, hence this if.
                      echo '<tr><td><a href="viewMaterial.php?materialID='.$material["materialID"].'">Download This Material</a></td></tr>';
                    } else { //This else block runs if the link type is not a local file (ie the link is to an external URL).
                      echo '<tr><td><a href="viewMaterial.php?materialID='.$material["materialID"].'">View This Material</a></td></tr>';
                    }
                  }
                  echo '</table>';
                }
              }
            } else { //This else block runs if the module exists, but the user isn't allowed to view it.
              echo '<h1>Insufficient Privileges To View This Module</h1>';
              echo '<p>You do not have enough permissions to view this module.  Log out and log back in at a higher privilege level to view this ';
              echo 'module.</p>';
            }
          }
        } else { //This else block runs if no moduleID was given
          echo '<h1>View Module</h1>';
          echo '<p>No module ID was given.  Enter the ID of the module you wish to view below and click "View Module" to view the module, or ';
          echo 'use the search feature to search for modules.</p>';
          echo '<form name="moduleIDSubmission" action="viewModule.php" method="get">';
          echo '<input type="text" name="moduleID"></input>';
          echo '<input type="submit" name="sub" value="View Module"></input>';
          echo '</form>';
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