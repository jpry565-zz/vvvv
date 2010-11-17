<?php session_start();
/****************************************************************************************************************************
 *    loginLogout.php - Allows users to log into the system and voluntarily log out.
 *    --------------------------------------------------------------------------------------
 *  Allows users to log in using a log-in form, and also provides a page users can access to voluntarily log out of the system with.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - Every front-end page should check for a valid log-in, and if an invalid log-in is detected, log the user out.  This
 *        file is just to present a nice page allowing users to log in and out, but every front-end page must validate the login.
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
  
  $loggedOff=FALSE; //TRUE is the system has logged a user off.  FALSE otherwise.
  if(isset($_REQUEST["action"]) && $_REQUEST["action"]=="logout") {
    logout();
    $loggedOff=TRUE;
  }
  
  $alreadyLoggedIn=FALSE; //TRUE if the user was logged in when they came to this page, FALSE otherwise.
  if(isset($_SESSION["authenticationToken"])) { //Check if we think someone is already logged in.
    $userInformation=checkIfUserIsLoggedIn($_SESSION["authenticationToken"]);
    $alreadyLoggedIn=TRUE;
    if(count($userInformation)==0) { //If true, than the user wasn't found
      logout();
      unset($userInformation);
      $alreadyLoggedIn=FALSE;
    }
  }
  
  $triedToLogIn=FALSE; //TRUE if the system attempted to log a user in.
  $loginSuccess=FALSE; //TRUE if the system successfully logged a user in.
  //If we are not already logged in, and all needed login parameters are present, and the action is to login, try to login.
  if((isset($_REQUEST["action"]) && isset($_REQUEST["email"]) && isset($_REQUEST["password"])) && ($_REQUEST["action"]=="login") && $alreadyLoggedIn==FALSE) {
    $loginResult=logUserIn($_REQUEST["email"], $_REQUEST["password"]);
    $triedToLogIn=TRUE;
    if($loginResult!==FALSE) {
      $_SESSION["authenticationToken"]=$loginResult;
      $userInformation=checkIfUserIsLoggedIn($loginResult);
      $loginSuccess=TRUE;
    }
  }
  
?>

<html>
<head>
  <link rel="stylesheet" href="<?php echo "lib/look/".$LOOK_DIR."/main.css"; ?>"></link>
  <title><?php if(isset($userInformation)) { echo 'Log out of '.$COLLECTION_NAME; } else { echo 'Log in to '.$COLLECTION_NAME; } ?></title>
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
        //Start by making sure the back-end supports working with users in read mode.
        if(!in_array("UseUsers", $backendCapabilities["read"]) || !in_array("UseUsers", $backendCapabilities["write"])) {
          die("The backend currently in use (".$backendInformation["name"]." version ".$backendInformation["version"].") does not support working with users.");
        }
        
        if($loggedOff==TRUE) { //If true, we just (intentially, by user's asking) logged someone off
          echo '<h1>Logout Successful</h1>';
          echo "<p>You have been successfully logged out of ".$COLLECTION_NAME.".</p>";
          echo '<p>To log back in, <a href="loginLogout.php?action=login">go to the Log in page</a>.</p>';
        } elseif($alreadyLoggedIn==FALSE) {
          if($triedToLogIn==TRUE) { //If here, we tried to log in.
            if($loginSuccess==TRUE) { //Successfully logged in.
              echo '<h1>Login successful</h1>';
              echo '<p><img src="lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> You successfully logged in to '.$COLLECTION_NAME.'.</p>';
            } else { //Failed to log in.
              echo '<h1>Login to '.$COLLECTION_NAME.'</h1>';
              echo '<span class="error"><img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> ';
              echo 'Login failed.  The email/password pair entered do not match.  Please try again.</span><br>';
              echo '<form name="loginForm" method="post" action="loginLogout.php">';
              echo '<input type="hidden" name="action" value="login" readonly="readonly"></input>';
              echo 'Your email address: <input name="email" type="text"';
              if(isset($_REQUEST["email"])) {
                echo ' value="'.$_REQUEST["email"].'"';
              }
              echo '></input><br>';
              echo 'Password: <input name="password" type="password"></input><br>';
              echo '<input type="submit" name="submit" value="Login"></input><br>';
              echo '</form>';
            }
          } else { //Didn't even try to log in, display blank login page.
            echo '<h1>Login to '.$COLLECTION_NAME.'</h1>';
            echo '<p>Enter your login credientials below to log into '.$COLLECTION_NAME.'.  <a href="forgotPassword.php">Forgot your password?</a></p>';
            echo '<table style="border-collapse: collapse;">';
            echo '<form name="loginForm" method="post" action="loginLogout.php">';
            echo '<input type="hidden" name="action" value="login" readonly="readonly"></input>';
            echo '<tr><td style="font-weight: bold;">Email address:</td><td><input name="email" type="text"></input></td></tr>';
            echo '<tr><td style="font-weight: bold;">Password:</td><td><input name="password" type="password"></input></td></tr>';
            echo '<tr><td></td><td><input type="submit" name="submit" value="Login"></input></td></tr>';
            echo '</form></table>';
          }
        } else { //If we're here, we were already logged in.
          echo '<h1>You Already Logged In On This Computer</h1>';
          echo "You were already logged in.";
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