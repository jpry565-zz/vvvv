<?php session_start();
 /****************************************************************************************************************************
 *    createAccount.php - Allows unregistered users to create an account on the system.
 *    --------------------------------------------------------------------------------------
 *  This file allows unregistered users to create an account on the system.  It is not intended for user maintence or for logged in
 *  users, but is only for unregistered users who want to register an account.
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
  
  $action="display"; //Determines what action to take.  The default is to just display a "create account" form.
  if(isset($_REQUEST["action"]) && !isset($userInformation)) {
    $action=$_REQUEST["action"];
  }
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php echo "Create a new account on ".$COLLECTION_NAME; ?></title>
  <script type="text/javascript">
    function quickValidateAllFormFields() {
      var email=document.getElementById("email").value;
      var firstName=document.getElementById("firstName").value;
      var lastName=document.getElementById("lastName").value;
      if(firstName.search("\"")!=-1 || lastName.search("\"")!=-1 || email.search("\"")!=-1) { //Don't allow quote marks.
        alert("Sorry, but first names, last, names, and email addresses may not contain quote marks.");
        return false;
      }
      return true;
    }
  </script>
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
        if(!in_array("UseUsers", $backendCapabilities["write"])) { //Make sure the back-end supports this feature.
          echo '<h1>This Feature Is Not Supported</h1>';
          echo '<p>The backend in use ('.$backendInformation["name"].' version '.$backendInformation["version"].') does not currently support this feature.</p>';
        } elseif(isset($userInformation)) { //You can't create a new account using this page if you are already logged in.
          echo '<h1>You Must Log Out To Continue</h1>';
          echo '<p>You are currently logged in.  You must log out before creating a new account.</p>';
          if($userInformation["type"]=="Admin") {
            echo '<span class="note">Admin Users:</span> To create or manage user accounts, use the "User Management" tool from the admin menu.</p>';
          }
        } else { //The back-end supports account creation and we're not logged in, so we can create an account.
          echo '<h1>Create A New Account</h1>';
          if($action=="display") {
            echo '<p>To create a new account, fill in all of the information below and select "Create Account".</p>';
            if($NEW_ACCOUNTS_REQUIRE_APPROVAL==TRUE) {
              echo '<p><span class="note">Your account will require approval before it becomes active.</span>  The maintainer of this collection requires ';
              echo 'that new accounts be approved before they can be used.  Once your account is created, it will not be accessable until it is approved.</p>';
            }
            echo '<form name="createAccountFrom" action="createAccount.php" method="post"><table class="userInformationView">';
            echo '<input type="hidden" readonly="readonly" name="action" value="doCreateAccount"></input>';
            echo '<tr><td>First Name</td><td><input type="text" name="firstName" id="firstName"></input></td></tr>';
            echo '<tr><td>Last Name</td><td><input type="text" name="lastName" id="lastName"></input></td></tr>';
            echo '<tr><td>Email Address</td><td><input type="text" name="email" id="email"></input></td></tr>';
            echo '<tr><td>Password</td><td><input type="password" name="password1"></input></td></tr>';
            echo '<tr><td>Password (again)</td><td><input type="password" name="password2"></input></td></tr>';
            echo '</table>';
            echo '<input type="submit" name="submit" value="Create Account" onclick="return quickValidateAllFormFields();"></input>';
            echo '</form>';
          } elseif($action=="doCreateAccount") { //Actually try to create the account.
            /* Do basic error checking and validation... */
            if(!isset($_REQUEST["firstName"]) || !isset($_REQUEST["lastName"]) || !isset($_REQUEST["email"]) || !isset($_REQUEST["password1"]) || !isset($_REQUEST["password2"])) {
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Failed to create account.  One or more required parameters is missing.</span>';
            } elseif($_REQUEST["password1"]!=$_REQUEST["password2"]) {
              echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Error creating your account.  The passwords entered do not match.  Please correct this problem and select "Create Account" to try again.</span>';
              echo '<form name="createAccountFrom" action="createAccount.php" method="post"><table class="userInformationView">';
              echo '<input type="hidden" readonly="readonly" name="action" value="doCreateAccount"></input>';
              echo '<tr><td>First Name</td><td><input type="text" name="firstName" id="firstName" value="'.$_REQUEST["firstName"].'"></input></td></tr>';
              echo '<tr><td>Last Name</td><td><input type="text" name="lastName" id="lastName" value="'.$_REQUEST["lastName"].'"></input></td></tr>';
              echo '<tr><td>Email Address</td><td><input type="text" name="email" id="email" value="'.$_REQUEST["email"].'"></input></td></tr>';
              echo '<tr><td>Password</td><td><input type="password" name="password1"></input></td></tr>';
              echo '<tr><td>Password (again)<br><span class="error">The password entered here must match the password above.</span></td>';
              echo '<td><input type="password" name="password2"></input></td></tr>';
              echo '</table>';
              echo '<input type="submit" name="submit" value="Create Account" onclick="return quickValidateAllFormFields();"></input>';
              echo '</form>';
            } else { //Looks like the passwords match and we have everything we need to try to create the account, so try and check for errors.
              if($NEW_ACCOUNTS_REQUIRE_APPROVAL==TRUE) {
                $result=createUser($_REQUEST["email"], $_REQUEST["firstName"], $_REQUEST["lastName"], $_REQUEST["password1"], "Pending");
              } else {
                $result=createUser($_REQUEST["email"], $_REQUEST["firstName"], $_REQUEST["lastName"], $_REQUEST["password1"], $NEW_ACCOUNTS_ACCOUNT_TYPE);
              }
              /* Check for errors creating account, and display a form allowing users to try again on error. */
              if($result===FALSE || $result=="BadEmail" || $result=="EmailAlreadyExists" || $result=="BadPassword" || $result=="BadFirstName" || $result=="BadLastName" || $result=="BadType") {
                echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Failed to create your account due to one or more errors.  Please corrent any errors and try again.</span>';
                echo '<form name="createAccountFrom" action="createAccount.php" method="post"><table class="userInformationView">';
                echo '<input type="hidden" readonly="readonly" name="action" value="doCreateAccount"></input>';
                echo '<tr><td>First Name';
                if($result=="BadFirstName") {
                  echo '<br><span class="error">The first name entered is invalid.</span>';
                }
                echo '</td><td><input type="text" name="firstName" id="firstName" value="'.$_REQUEST["firstName"].'"></input></td></tr>';
                echo '<tr><td>Last Name';
                if($result=="BadLastName") {
                  echo '<br><span class="error">The last name entered is invalid.</span>';
                }
                echo '</td><td><input type="text" name="lastName" id="lastName" value="'.$_REQUEST["lastName"].'"></input></td></tr>';
                echo '<tr><td>Email Address';
                if($result=="BadEmail") {
                  echo '<br><span class="error">The email address entered is not valid.</span>';
                }
                if($result=="EmailAlreadyExists") {
                  echo '<br><span class="error">The email address entered already exists in the system.  Please choose a different email address.</span>';
                }
                echo '</td><td><input type="text" name="email" id="email" value="'.$_REQUEST["email"].'"></input></td></tr>';
                echo '<tr><td>Password</td><td><input type="password" name="password1"></input></td></tr>';
                echo '<tr><td>Password (again)</td><td><input type="password" name="password2"></input></td></tr>';
                echo '</table>';
                echo '<input type="submit" name="submit" value="Create Account"></input>';
                echo '</form>';
              } else { //The account was created okay.
                echo '<img src="lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> Your account has been successfully created.';
                if($NEW_ACCOUNTS_REQUIRE_APPROVAL==TRUE) {
                  echo '<p><span class="note">Note:  Your account will not be active until it has been approved.</span>  Until your account is approved, ';
                  echo 'you will not be able to log in to it, and it may appear to not be created.  Contact the collection maintainer for more information.</p>';
                  if($EMAIL_MODERATORS_ON_NEW_USERS_PENDING_APPROVAL==TRUE) { //Should an email alert be send to one or more emails/user classes alerting them to this account pending approval?
                    /* Build the message to send and the subject of the email(s)*/
                    $subject="New User Account Pending Approval on ".$COLLECTION_NAME;
                    $message="A new account has been registered on the ".$COLLECTION_NAME." collection.  It is currently pending approval by you or ";
                    $message=$message."another moderator.\n\nTo approve or deny this new account, log onto the collection using your email address ";
                    $message=$message.'and password, go to the "User Management" panel, and set the type for the new user to your desired type.';
                    $message=$message."\n\nNew Account Information:\n Name: ".$_REQUEST["firstName"]." ".$_REQUEST["lastName"]."\n";
                    $message=$message." Email: ".$_REQUEST["email"]."\n\n----------------------------------------------------------\n";
                    $message=$message."This message was automatically generated.  Please do not reply.  Contact the collection maintainer if your ";
                    $message=$message."you like to stop receiving these alerts or would like to change other preferences.";
                    $message=wordwrap($message, 70);
                    /* Send the email to any users in the specified classes to send new user account alerts to. */
                    for($i=0; $i<count($EMAIL_MODERATORS_ON_NEW_USERS_PENDING_APPROVAL_CLASS); $i++) {
                      $users=searchUsers(array("type"=>$EMAIL_MODERATORS_ON_NEW_USERS_PENDING_APPROVAL_CLASS[$i])); //Get all users in the current class being checked
                      for($j=0; $j<count($users); $j++) { //Loop through found users of the current type/class.
                        mail($users[$j]["email"] ,$subject, $message);
                      }
                    }
                    /* Send the email to any additional addresses to send new user account alerts to. */
                    for($i=0; $i<count($EMAIL_MODERATORS_ON_NEW_USERS_PENDING_APPROVAL_LIST); $i++) {
                      mail($EMAIL_MODERATORS_ON_NEW_USERS_PENDING_APPROVAL_LIST[$i], $subject, $message);
                    }
                  }
                }
              }
            }
          } else { //Catch-all error for unknown/unhandled actions.
            echo '<img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> <span class="error">Unable to process your request.  An ';
            echo 'unknown action was specified</span>';
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