<?php session_start();
 /****************************************************************************************************************************
 *    forgotPassword.php - Allows users to recover their password/get a new password.
 *    --------------------------------------------------------------------------------------
 *  Allows users who have forgotten their password to get a new one or have their old one sent to their email address.
 *
 *  Version: 1.0
 *  Author: Ethan Greer (portions by Douglas Lovell)
 *
 *  Notes: - Not very secure.  Better security would require some back-end changes.
 *         - This file accepts the following POST/GET parameters:
 *              action : One of "display" (default) to display a form to identify the user, or "recover" to actually recover/reset
 *                  the user's password.
 *              email : The email address of the user who forgot their password.
 ******************************************************************************************************************************/
  
  require("lib/backends/backend.php");
  require("lib/look/look.php");
  require("lib/config/config.php");
  require("lib/frontend-ui.php");
  $backendInformation=getBackendBasicInformation();
  $backendCapabilities=getBackendCapabilities();
?>
<?php
  /*
  The validEmail function was written by Douglas Lovell (Jun 01, 2007).
  Origional source and article at: http://www.linuxjournal.com/article/9585
  
  Validate an email address.
  Provide email address (raw input)
  Returns true if the email address has the email 
  address format and the domain exists. */
function validEmail($email)
{
  $isValid = true;
  $atIndex = strrpos($email, "@");
  if (is_bool($atIndex) && !$atIndex)
  {
      $isValid = false;
  }
  else
  {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
        // local part length exceeded
        $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
        // domain part length exceeded
        $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
        // local part starts or ends with '.'
        $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
        // local part has two consecutive dots
        $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
        // character not valid in domain part
        $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
        // domain part has two consecutive dots
        $isValid = false;
      }
      else if
(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                str_replace("\\\\","",$local)))
      {
        // character not valid in local part unless 
        // local part is quoted
        if (!preg_match('/^"(\\\\"|[^"])+"$/',
            str_replace("\\\\","",$local)))
        {
            $isValid = false;
        }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || 
↪checkdnsrr($domain,"A")))
      {
        // domain not found in DNS
        $isValid = false;
      }
  }
  return $isValid;
}


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
  <title><?php echo "Recover lost password on ".$COLLECTION_NAME; ?></title>
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
        } elseif(isset($userInformation)) { //Logged in users obviously remembered their passwords.  Tell them how to change it, but don't actually send/reset it.
          echo '<h1>Please Use The Your "My Account" Panel to Change Your Password</h1>';
          echo '<p>You are currently logged in.  To change your password, use the your "My Account" panel, available on the left-side navigation ';
          echo 'bar.</p>';
        } else { //The back-end supports account creation and we're not logged in, so we can create an account.
          echo '<h1>Recover Your Password</h1>';
          if($action=="display") {
            echo '<p>To recover your password, enter your email address below and click "Recover Password" to send your password to your email address.</p>';
            echo '<form name="passwordRecoveryForm" method="post" action="forgotPassword.php">';
            echo '<input type="hidden" readonly="readonly" name="action" value="recover"></input>';
            echo 'Email Address: <input type="text" name="email"></input>';
            echo '<input type="submit" name="sub" value="Recover Password"></input>';
            echo '</form>';
          } elseif($action=="recover") { //Actually try to recover/reset the password, and send the result to the user's email.
            if(!isset($_REQUEST["email"])) { //If true, no password was given.  Given error.
              echo '<h1>No Email Address Specified</h1>';
              echo '<p>No email address was found while attempting to recover a lost password.  Unable to continue.</p>';
            } else { //We have an email, so check to make sure its a valid email and if it is, send the user's password to the email.
              if(!validEmail($_REQUEST["email"])) {
                echo '<h1>Invalid Email Address</h1>';
                echo '<p><img src="lib/look/'.$LOOK_DIR.'/failure.png" alt="Failure"></img> The email address entered is not a valid email address.  ';
                echo 'Please re-check the email address for accuracy.</p>';
                echo '<form name="passwordRecoveryForm" method="post" action="forgotPassword.php">';
                echo '<input type="hidden" readonly="readonly" name="action" value="recover"></input>';
                echo 'Email Address: <input type="text" name="email" value="'.htmlspecialchars($_REQUEST["email"]).'"></input>';
                echo '<input type="submit" name="sub" value="Recover Password"></input>';
                echo '</form>';
              } else { //A valid email address was given, so process it.
                /* NOTE:  We _ALWAYS_ report success, even if the email address doesn't exist in the collection!  This is to make it harder
                  for attackers to abuse this password recovery system to detect emails which actually exist in the system! */
                $user=searchUsers(array("email"=>$_REQUEST["email"]));
                if($user!==FALSE && $user!="NotImplimented" && count($user)==1) { //If no error eas reported searching for the user by email, and the search was supported, and returned exactly one user was returned, send them an email with their password.
                  //Build a message to send in the $message variable.
                  $message=$user["firstName"]." ".$user["lastName"].", \n\n";
                  $message=$message."You or somebody else requested that your password for your account on the ".$COLLECTION_NAME." collection be sent ";
                  $message=$message."to you via the 'Forgot Password' tool.  Your account information is below:\n\n";
                  $message=$message."Email Address: ".$user["email"]."\n";
                  $message=$message."     Password: ".$user["password"]."\n\n\n";
                  $message=$message."You can log into your account using the log-in information about.  Once logged in, you may modify your account ";
                  $message=$message."if you wish.\n--------------------------\n";
                  $message=$message."This is an automatically generated email.  Please do not reply.\n";
                  $message=$message."For security purposes, it is reccomended that your delete this email once you are able to log into your account.";
                  $message=wordwrap($message, 70); //Word-Wrap the message at 70 lines.
                  $subject="Your password for the ".$COLLECTION_NAME." collection";
                  mail($user["email"], $subject, $message); //Send email to the user with the message and subject built above.
                }
                /* Report success, no matter if an email was actually sent.  This helps avoid attacks which use this feature to discover the email addresses
                  of users of this system based on if a password recovery email could be sent or not. */
                echo '<h1>Password Recovery Successful</h1>';
                echo '<img src="lib/look/'.$LOOK_DIR.'/success.png" alt="Success"></img> An email has been sent to the '.$_REQUEST["email"].' with ';
                echo 'directions on how to recovering your password.';
              }
            }
          } else { //Unknown/unhandled action
            echo '<h1>Unknown/Unhandled Action Specified</h1>';
            echo '<p>An unknown or unhandled action was specified.  If you are receiving this error after clicking a link or button from within ';
            echo 'this system, please report it to the collection maintaier.</p>';
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