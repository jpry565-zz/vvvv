<?php
 /****************************************************************************************************************************
 *    frontend-ui.php - Functions used throughout the front-end to display the user interface.
 *    --------------------------------------------------------------------------------------
 *  Contains functions used by most or all front-end files to assist with displaying the user interface.  This allows parts
 *  of the interface which may change across every page to be centralized in one location, eliminating the need to change every
 *  front-end file should part of the interface change.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: (none)
 ******************************************************************************************************************************/

function getBaseDir() {
  $BASE_DIR="./";
  $currentBaseDir=basename(getcwd());
  /* You should now test to see if the current base directory is an known subdirectory of the actual base install directory.  If it is, 
    change $BASE_DIR to the relative path component needed to get from the subdirectory to the base install directory (probably just "../"). */
  if($currentBaseDir=="moduleWizard") { //Match against the moduleWizard subdirectory.
    $BASE_DIR="../";
  }
  if($currentBaseDir=="oaiProvider") { //Match against the oaiProvider subdirectory.
    $BASE_DIR="../";
  }
  if($currentBaseDir=="configureCollection") { //Match against the configureCollection subdirectory.
    $BASE_DIR="../";
  }
  return $BASE_DIR;
}

require(getBaseDir()."lib/config/config.php");

  /* showGuestMenu() - Prints a menu suitable for a guest (someone not logged in). */
  function showGuestMenu() {
  
  }
  
  function showViewerMenu() {
    echo '<div class="viewerMenu">';
    echo '<a href="'.getBaseDir().'userManageAccount.php">My Account</a><br>';
    echo '</div><br>';
  }
  
  function showSuperViewerMenu() {
    //Currently, this is the same as the viewer menu.  The only difference is a Superviewer can possibly see more modules.
    showViewerMenu();
  }
  
  function showSubmitterMenu() {
    showSuperViewerMenu();
    echo '<div class="submitterMenu">';
    echo '<a href="'.getBaseDir().'showMyModules.php">My Modules</a><br>';
    echo '<a href="'.getBaseDir().'moduleWizard/welcome.php">Submit A Module</a>';
    echo '</div><br>';
  }
  
  function showEditorMenu() {
    showSubmitterMenu();
    echo '<div class="editorMenu">';
    echo '<a href="'.getBaseDir().'moduleManagement.php">Module Management<a/><br>';
    echo '<a href="'.getBaseDir().'moderate.php">Pending Moderation Requests</a><br>';
    echo '</div><br>';
  }
  
  /* showAdminMenu() - Displays a sidebar menu for an admin user. */
  function showAdminMenu() {
    showEditorMenu();
    echo '<div class="adminMenu">';
    echo 'Admin Menu<br>';
    echo '<a href="'.getBaseDir().'userManagement.php">User Management</a><br>';
    echo '<a href="'.getBaseDir().'configureCollection/index.php">Configure this Collection</a>';
    echo '</div>';
  }
  
  function showTopNavMenu() {
    require(getBaseDir()."lib/config/config.php");
    echo '<form name="searchForm" action="'.getBaseDir().'search.php" method="get">';
    echo '<a href="'.getBaseDir().'index.php">Home</a> &nbsp;|&nbsp; ';
    echo '<a href="'.getBaseDir().'about.php">About</a> &nbsp;|&nbsp; ';
    echo '<a href="'.getBaseDir().'browse.php">Browse</a> &nbsp;|&nbsp; ';
    echo '<input name="title" type="text"></input><input type="submit" name="sub" value="Search"></input>';
    echo '<span style="font-size: x-small;"><a href="'.getBaseDir().'search.php">Advnaced Search</a></span></form>';
  }
  
  function showFooter() {
    require(getBaseDir()."lib/config/config.php");
    require(getBaseDir()."lib/look/look.php");
    echo '<a href="'.getBaseDir().'index.php">Home</a> &nbsp;|&nbsp; ';
    echo '<a href="'.getBaseDir().'search.php">Search</a> &nbsp;|&nbsp; Browse &nbsp;|&nbsp; ';
    echo '<a href="'.getBaseDir().'about.php">About '.$COLLECTION_NAME.'</a> &nbsp;';
    echo '|&nbsp; <a href="'.getBaseDir().'oaiProvider/index.php">Harvest with OAI-PMH</a><br>';
    echo "";
    echo file_get_contents(getBaseDir()."lib/look/".$LOOK_DIR."/footer.html");
  }
?>