<?php session_start();
/****************************************************************************************************************************
 *    userManageAccount.php - Allows a user to manage basic information about their account.
 *    --------------------------------------------------------------------------------------
 *  This file contains the front-end code to allow a user to manage their own account.  It can not modify any accounts except that
 *  of the currently logged in user, and only management of account details which all users, regardless of privilege level, can
 *  modify can be changed from this page.
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
  
  function displayChangePasswordForm() {
    echo '<form name="changePasswordForm" action="userManageAccount" method="post">';
    echo '<input type="hidden" readonly="readonly" name="action" value="doChangePassword"></input>';
    echo 'Current Password: <input name="currentPassword" type="password"></input><br>';
    echo 'New Password: <input name="newPassword1" type="password"></input><br>';
    echo 'New Password (again): <input name="newPassword2" type="password"></input><br>';
    echo '<input type="submit" name="submit" value="Change Password"></input>';
    echo '</form>';
  }
  
  if(isset($_SESSION["authenticationToken"])) { //Check if we think someone is already logged in.
    $userInformation=checkIfUserIsLoggedIn($_SESSION["authenticationToken"]);
    if(count($userInformation)==0) { //If true, than the user wasn't found
      logout();
      unset($userInformation);
    }
  }
  
  /* By setting the action to "display" (harmless) and then only changing it if we have confirmed that we're actually logged in, we avoid the possibility of 
    someone not logged in setting an action to change something.  We also cna then assume, later, that if the action is anything but "display" we are 
    indeed logged in. */
  $action="display";
  if(isset($userInformation)) {
    if(isset($_REQUEST["action"])) {
      $action=$_REQUEST["action"];
    }
  }
  
?>
<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php if(isset($userInformation)) { echo "Account Management for ".$userInformation["email"]." on ".$COLLECTION_NAME; } else { echo "You Must Be Logged In To View This Page"; } ?></title>
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
        if(!isset($userInformation)) {
          echo "<h1>You Must Be Logged In To Continue</h1>";
          echo "<p>You are not logged in.  You must be logged in to view this page.</p";
          echo '<p>Please <a href="loginLogout.php">log in</a> to access this page.</p';
        } else {
          echo '<h1>Account Management</h1>';
          if($action=="display") { //Just show current account information if the action is "display"
            if(!in_array("UseUsers", $backendCapabilities["read"])) {
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img>The backend in use does not support working with users in read mode, which is required to use this page.';
            } else {
              echo '<p>You current account information is displayed below.  To change it, click "Edit Information".  To change your password, choose ';
              echo '"Change Password".  You may also deleate your account from this page.</p><br>';
              echo '<span style="font-size: small;"><a href="userManageAccount.php?action=displayEdit">Edit Information</a> / <a href="userManageAccount.php?action=displayChangePassword">Change Password</a></span><br>';
              echo '<table class="userInformationView">';
              echo '<tr><td class="userInformationViewCategory">First Name</td><td>'.$userInformation["firstName"].'</td></tr>';
              echo '<tr><td class="userInformationViewCategory">Last Name</td><td>'.$userInformation["lastName"].'</td></tr>';
              echo '<tr><td class="userInformationViewCategory">Email Address</td><td>'.$userInformation["email"].'</td></tr>';
              if($userInformation["type"]=="Admin") {
                echo '<tr><td class="userInformationViewCategory">Type*</td><td>'.$userInformation["type"].'</td></tr>';
              } else {
                echo '<tr><td class="userInformationViewCategory">Type</td><td>'.$userInformation["type"].'</td></tr>';
              }
              echo '</table>';
              echo '<span style="font-size: small;"><a href="userManageAccount.php?action=displayEdit">Edit Information</a> / <a href="userManageAccount.php?action=displayChangePassword">Change Password</a></span><br>';
              if($userInformation["type"]=="Admin") {
                echo '<br>* To change account types, use the <a href="manageAccounts.php">Manage Accounts</a> tool.';
              }
              echo '<p><a href="userManageAccount.php?action=confirmAccountRemoval">Deleate This Account</a></p>';
            }
          } elseif($action=="displayEdit") { //displayEdit action shows a form allowing users to change account information.
            if(!in_array("UseUsers", $backendCapabilities["read"]) || !in_array("UseUsers", $backendCapabilities["write"])) {
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img>The backend in use does not support working with users in read and/or write mode, which is required to use this page.';
            } else {
              echo '<p>Change any information you wish to below, and select "Apply" to save your changes.</p>';
              echo '<form name="editAccountForm" method="post" action="userManageAccount.php">';
              echo '<input type="hidden" readonly="readonly" name="action" value="doEdit"></input>';
              echo '<table class="userInformationView">';
              echo '<tr><td class="userInformationViewCategory">First Name</td><td><input type="text" name="firstName" value="'.$userInformation["firstName"].'"></input></td></tr>';
              echo '<tr><td class="userInformationViewCategory">Last Name</td><td><input type="text" name="lastName" value="'.$userInformation["lastName"].'"></input></td></tr>';
              echo '<tr><td class="userInformationViewCategory">Email Address</td><td><input type="text" name="email" value="'.$userInformation["email"].'"></input></td></tr>';
              if($userInformation["type"]=="Admin") {
                echo '<tr><td class="userInformationViewCategory">Type*</td><td>'.$userInformation["type"].'</td></tr>';
              } else {
                echo '<tr><td class="userInformationViewCategory">Type</td><td>'.$userInformation["type"].'</td></tr>';
              }
              echo '</table>';
              echo '<input type="submit" name="submit" value="Apply"></input> or <input type="reset" name="reset" value="Reset all information to their initial values"></input>';
              echo '</form>';
              if($userInformation["type"]=="Admin") {
                echo '<br>* To change account types, use the <a href="manageAccounts.php">Manage Accounts</a> tool.';
              }
            }
          } elseif($action=="displayChangePassword") { //displayChangePassword action shows a form allowing users to change their password.
            if(!in_array("UseUsers", $backendCapabilities["read"]) || !in_array("UseUsers", $backendCapabilities["write"])) {
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> The backend in use does not support working with users in read and/or write mode, which is required to use this page.';
            } else {
              echo '<p>To change your password, first enter your current password to confirm your identity, and then enter your desired new password twice in ';
              echo 'the fields below.</p>';
              displayChangePasswordForm();
            }
          } elseif($action=="doEdit") { //doEdit action actually tries to make changes done in the form presented in the displayEdit action.  Also handles errors.
            if(!in_array("UseUsers", $backendCapabilities["read"]) || !in_array("UseUsers", $backendCapabilities["write"])) {
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img>The backend in use does not support working with users in read and/or write mode, which is required to use this page.';
            } else {
              if(!isset($_REQUEST["firstName"]) || !isset($_REQUEST["lastName"]) || !isset($_REQUEST["email"])) { //If true, we don't have enough information to change anything
                echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> Error.  Not enough information given to change anything.';
              } else {
                $result=editUserByID($userInformation["userID"], $_REQUEST["email"], $_REQUEST["firstName"], $_REQUEST["lastName"], "", "", TRUE, TRUE);
                if($result!==TRUE) {
                  echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> Unable to update your account.  Please check your changes below and try again.';
                  echo '<form name="editAccountForm" method="post" action="userManageAccount.php">';
                  echo '<input type="hidden" readonly="readonly" name="action" value="doEdit"></input>';
                  echo '<table class="userInformationView">';
                  echo '<tr><td class="userInformationViewCategory">First Name';
                  if($result=="BadFirstName") {
                    echo '<br><span class="error">The first name entered is invalid.</span>';
                  }
                  echo '</td><td><input type="text" name="firstName" value="'.$userInformation["firstName"].'"></input></td></tr>';
                  echo '<tr><td class="userInformationViewCategory">Last Name';
                  if($result=="BadLastName") {
                    echo '<br><span class="error">The last name entered is invalid.</span>';
                  }
                  echo '</td><td><input type="text" name="lastName" value="'.$userInformation["lastName"].'"></input></td></tr>';
                  echo '<tr><td class="userInformationViewCategory">Email Address';
                  if($result=="BadEmail") {
                    echo '<br><span class="error">The email addresses entered is invalid.</span>';
                  }
                  echo '</td><td><input type="text" name="email" value="'.$userInformation["email"].'"></input></td></tr>';
                  if($userInformation["type"]=="Admin") {
                    echo '<tr><td class="userInformationViewCategory">Type*</td><td>'.$userInformation["type"].'</td></tr>';
                  } else {
                    echo '<tr><td class="userInformationViewCategory">Type</td><td>'.$userInformation["type"].'</td></tr>';
                  }
                  echo '</table>';
                  echo '<input type="submit" name="submit" value="Apply"></input> or <input type="reset" name="reset" value="Reset all information to their initial values"></input>';
                  echo '</form>';
                  if($userInformation["type"]=="Admin") {
                    echo '<br>* To change account types, use the <a href="manageAccounts.php">Manage Accounts</a> tool.';
                  }
                } else {
                  echo '<p><img src="lib/look/'.$LOOK_DIR.'/success.png"></img> Information Successfully Updated.</p>';
                  echo '<p><a href="userManageAccount.php">Return to your account overview</a>.</p>';
                }
              }
            }
          } elseif($action=="doChangePassword") { //doChangePassword action actually tries to change the user's password.
            if(!in_array("UseUsers", $backendCapabilities["read"]) || !in_array("UseUsers", $backendCapabilities["write"])) {
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> The backend in use does not support working with users in read and/or write mode, which is required to use this page.';
            } else {
              if(!isset($_REQUEST["newPassword1"]) || !isset($_REQUEST["newPassword2"]) || !isset($_REQUEST["currentPassword"])) {
                echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> Unable to change your password.  One or more required pieces of information is missing.';
              } elseif($_REQUEST["currentPassword"]!=$userInformation["password"]) {
                echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> Unable to change your password.  The password entered as your current password is incorrect.';
                displayChangePasswordForm();
              } elseif($_REQUEST["newPassword1"]!=$_REQUEST["newPassword2"]) {
                echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> Unable to change your password.  The two passwords entered for your new password do not match.';
                displayChangePasswordForm();
              } else {
                $result=editUserByID($userInformation["userID"], $userInformation["email"], $userInformation["firstName"], $userInformation["lastName"], $_REQUEST["newPassword1"], "", FALSE, TRUE);
                if($result===TRUE) { //Password change successful?
                  echo '<img src="lib/look/'.$LOOK_DIR.'/success.png"> Your password has been successfully changed.';
                } else { //This else block runs if the password change wasn't successful.
                  if($result=="BadPassword") {
                    echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> The new password is not strong enough or otherwise invalid.';
                    displayChangePasswordForm();
                  } else {
                    echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> An unknown error occurred while trying to change your password.';
                    echo '<p>If this problem persists, please contact the collection maintainers to report the issue.</p>';
                  }
                }
              }
            }
          } elseif($action=="confirmAccountRemoval") { //confirmAccountRemoval action displays a confirmation before allowing users to delete their account.
            echo '<span class="warning">Are you sure you want to delete your account?</span>';
            echo '<p>When you delete your account, you will no longer have access to any functions of this collection beyond browsing public content.';
            echo 'Deletions can not normally be undone, and even if you create a new account after deleating this one, you will likely not be able to ';
            echo 'edit or manage any modules or materials you have uploaded or worked with under the current account, even if you create a new account ';
            echo 'with the same email address, name, and password.</p>';
            if(in_array("UsersSoftRemove", $backendCapabilities["write"])) {
              echo '<p><span class="note">Notice:  Your account will be softly deleated.</span>  A softly deleated account may continue to store certain ';
              echo 'information in it, even once it has been deleated.  Please contact this collection\'s maintainer with any questions or concerns with ';
              echo 'soft deleation.</p>';
            }
            echo '<a href="userManageAccount.php?action=doAccountRemoval">Continue with account deleation</a> or ';
            echo '<a href="userManageAccount.php">Cancel account deleation</a> and return to your account overview page.';
            echo '';
          } elseif($action=="doAccountRemoval") { //doAccountRemoval action actually removes a user's account.
            if(in_array("UsersSoftRemove", $backendCapabilities["write"])) { //If true, the back-end is advertising soft-removal, so use that method.
              $result=removeUsersByID(array($userInformation["userID"]), TRUE);
            } else { //Back-end didn't advertise soft-removal, so don't user it.
              $result=removeUsersByID(array($userInformation["userID"]), FALSE);
            }
            if($result===TRUE) { //Deleation successful
              logout(); //Once we've deleated the account, we should also log the user out.
              echo '<img src="lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> Your account has been successfully deleated.';
              echo '<p>Your account with this collection has been successfully deleated.  You have also been logged out.</p>';
            } else { //Error deleating account
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"> <span class="error">Account deleation failed.</span>';
              echo '<p>If this problem persists, please contact this collection\'s maintainer.</p>';
            }
          } else { //Catch-all for any unknown action.
            echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to process your request.  An unknown ';
            echo 'action was specified.</span>';
          }
          echo '<p></p>';
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