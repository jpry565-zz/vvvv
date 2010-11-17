<?php
/****************************************************************************************************************************
 *    validate.php - Functions for validating input or results (part of the MySQL backend).
 *    --------------------------------------------------------------------------------------
 *  A collection of functions used for validating input and/or results.  Part of the MySQL backend and not indented to be
 *  used except by the back-end.
 *
 *  Version: 1.0
 *  Author: Ethan Greer
 *
 *  Notes: - To change what is accepted and what isn't by various parts of the MySQL backend, change the functions here to only
 *        accept what you want the back-end to accept.  This centralises validation, which may be used by multiple parts of the
 *        backend.
 ******************************************************************************************************************************/

/* validateEmail($email) - Checks an email address for very basic validity.
  @parm $email - The email address to validate.
  @return - Returns TRUE if the email address is considered valid, FALSE otherwise. */
function validateEmail($email) {
  preg_match_all('/@/', $email, $matches); //Check for how many @s there are in the email address.
  if($matches===FALSE || count($matches)!=1) { //Email address may only contain one @.
    return FALSE;
  }
  return TRUE;
}

/* validateName($name) - Checks a name for very basic validity.
  @parm $name - The name to validate.
  @return - Returns TRUE if the name is considered valid, FALSE otherwise. */
function validateName($name) {
  return TRUE;
}

/* validatePassword($password) - Checks a password for very basic validity/strength.
  @parm $password - The password to check for validity/strength.
  @return - Returns TRUE is the password is considered valid and "strong enough", FALSE otherwise.  Note that this is no guaranteed to only validate 
    strong passwords.  You are free to accept as weak of passwords as you want, including no password at all.  However, if you want to enforce password 
    strength, this is the place to do it. */
function validatePassword($password) {
  return TRUE;
}

function validateFileType($type) {
  return TRUE;
}

/* validateFileSize($size) - Checks to make sure that a file size is small enough to be allowed to be uploaded as a material.  Use this function to 
    restrict file sizes.
  @parm $size - The size of the file to be validated.
  @return - Returns TRUE if the file is considered okay, FALSE otherwise. */
function validateFileSize($size) {
  return TRUE;
}
?>