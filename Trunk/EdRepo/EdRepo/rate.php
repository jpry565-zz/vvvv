<?php session_start();
/*********************************************************************************************************
 *      rate.php - Allows users to leave ratings/comments for modules and materials.
 *      ----------------------------------------------------------------------------
 *
 * Author: Ethan Greer
 * Version: 1
 *
 * Notes: - This takes the following GET/POST parameters:
 *            action : The action to take.  One of "display" (default) or "doRate"
 *            moduleID
 *              -OR-
 *            materialID
 *                  Which specifies either a moduleID or a materialID to rate.  This page will automatically
 *                  determine if it should rate a material or module based on if moduleID or materialID was given.
 *            rating : The rating the user gave the module/material (only used with the "doRate" action).
 *            comment : Text of any comment left by the user (only used with the "doRate" action).
 *            subject : Text of any comment title left by the user (only used with the "doRate" action).
 **********************************************************************************************************/
 
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
  
  function canViewModule($userType, $minType) {
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
  
  /** WARNING:  Do not call this function until you have verified that the user is actually logged in, since this function will always indicate a 
    material is viewable/ratable is it has a parent module with a minimum user type of "Viewer" or lower, regardless of the user's log=in status! */
  function canViewMaterial($userType, $materialID) {
    $canView=FALSE; //Will be set to TRUE once it is determined the person accessing this page may view the material.
    $parentModules=getAllModulesAttatchedToMaterial($materialID);
    if($parentModules===FALSE || $parentModules==="NotImplimented") {
      return FALSE;
    }
    for($i=0; $i<count($parentModules); $i++) { //Loop through all modules attatched to the material.  Our goal is to find one with an access level at least as low as the currently logged in user.
      $module=getModuleByID($parentModules[$i]); //Get information about the current module being analyzed.
      if($module["minimumUserType"]=="Unregistered") { //Anyone can view materials/modules if the minimumUserType is "Unregistered".
        return TRUE;
      }
      if($module["minimumUserType"]=="Viewer") { //Assume the user is logged in if this function is called, which means anyone could view a module with this user type.
        return TRUE;
      }
      if($module["minimumUserType"]=="SuperViewer" && ($userType=="SuperViewer" || $userType=="Submitter" || $userType=="Editor" || $userType=="Admin")) {
        return TRUE;
      }
      if($module["minimumUserType"]=="Submitter" && ($userType=="Submitter" || $userType=="Editor" || $userType=="Admin")) {
        return TRUE;
      }
      if($module["minimumUserType"]=="Editor" && ($userType=="Editor" || $userType=="Admin")) {
        return TRUE;
      }
      if($module["minimumUserType"]=="Admin" && $userType=="Admin") {
        return TRUE;
      }
    }
    return FALSE; //If nothing above was matched, the user can't view the module, so return FALSE.
  }
  
  if(isset($_SESSION["authenticationToken"])) { //Check if we think someone is already logged in.
    $userInformation=checkIfUserIsLoggedIn($_SESSION["authenticationToken"]);
    if(count($userInformation)==0) { //If true, than the user wasn't found
      logout();
      unset($userInformation);
    }
  }
  
  $action="display";
  if(isset($_REQUEST["action"])) {
    $action=$_REQUEST["action"];
  }
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Leave a Rating"; ?></title>
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
        if(!isset($userInformation)) { //Make sure the user is logged in.
          echo '<h1>You Must Be Logged In To Continue</h1>';
          echo '<p>Only registered, logged in users may rate materials or modules.  Please <a href="loginLogout.php">log in</a> to ';
          echo $COLLECTION_NAME.' to continue.</p>';
        } else { //This else block runs if the user is logged in.
          /* Check to make sure that if a moduleID was given, the backend can write module ratings, and if a materialID was given, that the backend 
            can write material ratings.*/
          if((isset($_REQUEST["moduleID"]) && in_array("RateModules", $backendCapabilities["write"])) || (isset($_REQUEST["materialID"]) && in_array("RateMaterials", $backendCapabilities["write"]))) {
            if($action=="display") {
              if(isset($_REQUEST["moduleID"])) { //Are we trying to work with a module?
                $module=getModuleByID($_REQUEST["moduleID"]);
                if($module===FALSE || $module==="NotImplimented" || count($module)<=0) { //Check to make sure the module actually exists
                  echo '<h1>Module Not Found</h1>';
                } else { //This else runs if the module exists
                  if(canViewModule($userInformation["type"], $module["minimumUserType"])) {
                    echo '<h1>Leave A Rating For Module '.$module["title"].' version '.$module["version"].'</h1>';
                    echo '<p>Rate this module on a scale of 0 to 5, with 0 being the the lowest rating and 5 being the best.</p>';
                    echo '<form name="mainForm" action="rate.php" method="post">';
                    echo '<input type="hidden" readonly="readonly" name="action" value="doRate"></input>';
                    echo '<input type="hidden" readonly="readonly" name="moduleID" value="'.htmlspecialchars($module["moduleID"]).'"></input>';
                    echo '<input type="radio" name="rating" value="0"></input>0 &nbsp;';
                    echo '<input type="radio" name="rating" value="1"></input>1 &nbsp;';
                    echo '<input type="radio" name="rating" value="2"></input>2 &nbsp;';
                    echo '<input type="radio" name="rating" value="3"></input>3 &nbsp;';
                    echo '<input type="radio" name="rating" value="4"></input>4 &nbsp;';
                    echo '<input type="radio" name="rating" value="5"></input>5';
                    echo '<br><br><input type="submit" name="submit" value="Rate This Module"></input> or ';
                    echo '<button type="button" onclick="location.href=\'viewModule.php?moduleID='.$module["moduleID"].'\';">Cancel and Return to Module View</button>';
                    echo '</form>';
                  } else { //Error, can't view module
                    echo '<h1>You May Not Rate This Module</h1>';
                    echo '<p>The module you are attempting to rate is only accessable and rateable to users with a privilege level higher than ';
                    echo 'your current privilege level.</p>';
                  }
                }
              } elseif(isset($_REQUEST["materialID"])) { //Are we trying to work with a material?
                if(canViewMaterial($userInformation["type"], $_REQUEST["materialID"])) {
                  $material=getMaterialByID($_REQUEST["materialID"]);
                  if($material===FALSE || $material==="NotImplimented" || count($material)<=0) { //Check that the material actually exists
                    echo '<h1>Material Not Found</h1>';
                  } else {
                    echo '<h1>Leave A Rating for Material '.$material["title"].'</h1>';
                    echo '<p>Rate this material on a scale of 0 to 5, with 0 being the lowest rating and 5 being the best.  You may also leave ';
                    echo 'comments about the material if you wish.</p>';
                    echo '<form name="mainForm" action="rate.php" method="post">';
                    echo '<input type="hidden" readonly="readonly" name="action" value="doRate"></input>';
                    echo '<input type="hidden" readonly="readonly" name="materialID" value="'.htmlspecialchars($material["materialID"]).'"></input>';
                    echo '<input type="radio" name="rating" value="0"></input>0 &nbsp;';
                    echo '<input type="radio" name="rating" value="1"></input>1 &nbsp;';
                    echo '<input type="radio" name="rating" value="2"></input>2 &nbsp;';
                    echo '<input type="radio" name="rating" value="3"></input>3 &nbsp;';
                    echo '<input type="radio" name="rating" value="4"></input>4 &nbsp;';
                    echo '<input type="radio" name="rating" value="5"></input>5<br><hr>';
                    echo 'Comments (optional)<br>';
                    echo 'Title: <input type="text" name="commentTitle"></input><br>';
                    echo '<textarea name="comment"></textarea>';
                    echo '<br><hr><input type="submit" name="submit" value="Rate This Material"></input> or ';
                    echo '<button type="button" onclick="history.go(-1);">Cancel and Go Back</button>';
                    echo '</form>';
                  }
                } else { //Error, can't rate material
                  echo '<h1>You May Not Rate This Material</h1>';
                  echo '<p>The material you are attempting to rate either does not exist, or it is not attatched to any modules with a privilege ';
                  echo 'level at your user\'s privilege level or lower.  You may only rate materials that are attatched to at least one module ';
                  echo 'with a privilege level equal to or lower than your user\'s privilege level.</p>';
                }
              } else { //Error:  Don't know what material/module to work with.
                echo '<h1>No Module Or Material Specified</h1>';
                echo '<p>No module or material to rate was specified.  To avoid this error in the future, you should only rate modules and ';
                echo 'materials from the links provided for leaving ratings from the "View Module" pages.</p>';
              }
            } elseif($action=="doRate") { //Trying to actually write a rating to a module.
              if(isset($_REQUEST["moduleID"])) { //Are we trying to work with a module?
                $module=getModuleByID($_REQUEST["moduleID"]);
                if($module===FALSE || $module==="NotImplimented" || count($module)<=0) { //Check to make sure the module actually exists
                  echo '<h1>Module Not Found</h1>';
                } else { //This else runs if the module exists
                  if(canViewModule($userInformation["type"], $module["minimumUserType"])) {
                    echo '<h1>Rate Module '.$module["title"].' version '.$module["version"].'</h1>';
                    if(isset($_REQUEST["rating"])) {
                      $addRatingResult=addRatingToModule($module["moduleID"], $_REQUEST["rating"]);
                    }
                    if(!isset($_REQUEST["rating"]) || (isset($addRatingResult) && $addRatingResult!==TRUE)) { //Error
                      echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to add rating to module.</span>';
                      echo '<p>The system was unable to add a rating to the specified module.</p>';
                      echo '<p><a href="viewModule.php?moduleID='.$module["moduleID"].'">Return To Module View</a>';
                    } else { //Success
                      echo '<img src="lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> Successfully added rating.';
                      echo '<p>Your rating has been successfully added to the module "'.$module["title"].'" version '.$module["version"].'</p>';
                      echo '<p><a href="viewModule.php?moduleID='.$module["moduleID"].'">Return To Module View</a>';
                    }
                  } else { //Error, can't view module
                    echo '<h1>You May Not Rate This Module</h1>';
                    echo '<p>The module you are attempting to rate is only accessable and rateable to users with a privilege level higher than ';
                    echo 'your current privilege level.</p>';
                  }
                }
              } elseif(isset($_REQUEST["materialID"])) { //Are we trying to work with a material?
                if(canViewMaterial($userInformation["type"], $_REQUEST["materialID"])) {
                  $material=getMaterialByID($_REQUEST["materialID"]);
                  if($material===FALSE || $material==="NotImplimented" || count($material)<=0) { //Check that the material actually exists
                    echo '<h1>Material Not Found</h1>';
                    echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> No material with the specified ID was found.';
                  } else {
                    echo '<h1>Rate Material '.$material["title"].'</h1>';
                    $addRatingResult=FALSE; //By default adding a rating failed.  It will be changed to TRUE if adding a rating succeeds.
                    if(isset($_REQUEST["rating"]) && isset($_REQUEST["commentTitle"]) && isset($_REQUEST["comment"])) { //Check to make sure we have enough info to rate a material.
                      $addRatingResult=addCommentAndRatingToMaterial($_REQUEST["materialID"], $userInformation["firstName"]." ".$userInformation["lastName"], $_REQUEST["commentTitle"], $_REQUEST["comment"], $_REQUEST["rating"]);
                    }
                    if($addRatingResult!==TRUE) { //Adding rating failed
                      echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to add rating to material.</span>';
                      echo '<p>An error occurred which trying to add a rating to the material.</p>';
                    } else { //Successfully added rating
                      echo '<img src="lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> Rating successfully added to material.';
                    }
                  }
                } else { //Error, can't rate material
                  echo '<h1>You May Not Rate This Material</h1>';
                  echo '<p>The material you are attempting to rate either does not exist, or it is not attatched to any modules with a privilege ';
                  echo 'level at your user\'s privilege level or lower.  You may only rate materials that are attatched to at least one module ';
                  echo 'with a privilege level equal to or lower than your user\'s privilege level.</p>';
                }
              } else { //Error:  Don't know what material/module to work with.
                echo '<h1>No Module Or Material Specified</h1>';
                echo '<p>No module or material to rate was specified.  To avoid this error in the future, you should only rate modules and ';
                echo 'materials from the links provided for leaving ratings from the "View Module" pages.</p>';
              }
            } else { //Error, unknown action.
              echo '<h1>Unknown Action Specified</h1>';
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">The action specified is unrecognized.</span>';
              echo '<p>An unknown and unhandled action was specified, and your request could not be processed.  If you are receiving this error after ';
              echo 'clicking a link or button from with this collection, please report this error to the collection maintainer.</p>';
            }          
          } else { //If true, 
            echo '<h1>General Error While Attempting To Process Your Request</h1>';
            echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img>  The system was unable to process your request for one or more ';
            echo 'of the following reasons:';
            echo '<ul><li>Neither a module ID or material ID was specified.</li>';
            echo '<li>The backend storage system currently in use does not support writing ratings for the component specified (material or module).</li></ul>';
            echo '<a href="index.php">Return to collection homepage.</a>';
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