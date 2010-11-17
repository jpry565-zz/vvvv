<?php
/******************************************************************************************************
 *    dateFunctions.php - Date-specific functions used throughout the provider.
 *    ------------------------------------------------------------------------------
 *
 * Version: 1.0
 * Author: Ethan Greer
 *
 * Notes: (none)
 *******************************************************************************************************/

/* datesToMySQL($mySqlDate) - Converts the date/time given into a date/time suitable for a MySQL query.
    @parm $mySqlDate: The date and time to be converted (generally a UTC YYYY-MM-DDTHH:MM:SSZ but can be any format
		      strtotime() understands.
    @return: Returns the date and time as a string suitable for MySQL queries (YYYY-MM-DD HH:MM:SS format)*/
function datesToMySQL($mySqlDate) {
  $mySqlDate = strftime("%Y-%m-%d %H:%M:%S_" ,strtotime($mySqlDate));
  $dateEnd = strpos($mySqlDate, "_");
  $mySqlDate = substr($mySqlDate, 0, $dateEnd);
  return $mySqlDate;
}

function datesToOAI($oaiDate) {
  $oaiDate = strftime("%Y-%m-%dT%H:%M:%SZ_", strtotime($oaiDate));
  $dateEnd = strpos($oaiDate, "_");
  $oaiDate = substr($oaiDate, 0, $dateEnd);
  return $oaiDate;
}

/* dateNotBeforeEarliestDatestamp($date) - Checks to make sure than $date is not before the earliest datestamp in the collection.
  @parm $date - The date to check.
  @return - Returns TRUE is the date is not before the earliest datestamp (is valid).
    Returns FALSE if the date is before the earliest datestamp (is not valid). */
function dateNotBeforeEarliestDatestamp($date) {
  if($date=="*") { //A date of "*" means "all dates" which means it can't possibly be before the earliest datestamp.  Return TRUE in this case.
    return TRUE;
  }
  require("config.php"); //Load OAI-PMH configuration
  $dateGiven=strtotime($date); //Convert the date given into an easy-to-use integer.
  $earliest=strtotime($EARLIEST_DATESTAMP); //Convert the earliest datestamp into an easy-to-use integere
  if($dateGiven<$earliest) { //If the date given is less than the earliest date (i.e. it is earlier), return FALSE.
    return FALSE;
  }
  return TRUE;
}

/* dateFormatOkay($date) - Checks to make sure that a date/time given in an OAI-PMH format is a valid format.
  @parm $date - The OAI-PMH date/time to check.
  @return - Returns TRUE if the date/time given is valid, FALSE otherwise. */
function dateFormatOkay($date) {
  $dateRegex = "'\b[0-9]{4}[- /.](0?[1-9]|1[012])[- /.](0?[1-9]|[12][0-9]|3[01])\b'";
  $dateTimeRegex = "'\b[0-9]{4}[- /.](0?[1-9]|1[012])[- /.](0?[1-9]|[12][0-9]|3[01])[T: /.](0?[0-9]{2})[: /.](0?[0-9]{2})[: /.](0?[0-9]{2})Z\b'";
  if(preg_match($dateRegex, $date)==1 || preg_match($dateTimeRegex, $date)==1) {
    return TRUE;
  }
  return FALSE;
}

/* This function will make sure $from and $until are valid dates or date/times, and that they are valid together
   (for example that they are the smae granularity).  At this time it will NOT check if $from is greater than $until
   (which would also be an invalid combination). */
function validDates($from, $until) {
  require("config.php");
  $dateRegex = "'\b[0-9]{4}[- /.](0?[1-9]|1[012])[- /.](0?[1-9]|[12][0-9]|3[01])\b'";
  $dateTimeRegex = "'\b[0-9]{4}[- /.](0?[1-9]|1[012])[- /.](0?[1-9]|[12][0-9]|3[01])[T: /.](0?[0-9]{2})[: /.](0?[0-9]{2})[: /.](0?[0-9]{2})Z\b'";
    
  if((strftime($EARLIEST_DATESTAMP) > strftime($until)) || (strftime($EARLIEST_DATESTAMP) > strftime($from))) { //Is $until and/or $from eariler than the largest datestamp?
    return false;
  } elseif((!$from && !$until) || ($from=="" && $until=="")) { //If neither $from nor $until given, the dates are okay.
    return true;
  } elseif($from && !$until) { //Its okay to only have from...
    if(preg_match($dateRegex, $from)==1 || preg_match($dateTimeRegex, $from)==1) { //...but only if it is a valid format.
      return true;
    } else {
      return false;
    }
  } elseif(!$from && $until) { //Its okay to only have until...
    if(preg_match($dateRegex, $until)==1 || preg_match($dateTimeRegex, $until)==1) { //...but only if it is a valid format.
      return true;
    } else {
      return false;
    }
  } elseif(preg_match($dateRegex, $from)==1 && preg_match($dateRegex, $until)==1) { //If both $from and $until are valid dates only, everything is okay.
    return true;
  } elseif(preg_match($dateTimeRegex, $from)==1 && preg_match($dateTimeRegex, $until)==1) { //If both $from and $until are in date/time format, everything is okay.
    return true;
  } else {
    return false;
  }
}

?>
