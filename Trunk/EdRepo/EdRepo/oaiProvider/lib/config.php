<?php

/* RESPOSITORY_IDENTIFIER is the identifer of your OAI-PMH repository.  Usually oai:yourdomain.org
              requests to the provider would be given as IDENTIFIER:record_name
              for example, oai:yourdomain.org:53 */
$REPOSITORY_IDENTIFIER = "oai:www.xcitegroup.org";

/* ADMIN_EMAILS contains all email addresses for admins of the repository.  It should contain at least one email address. */
$ADMIN_EMAILS = array("elg42@drexel.edu");

/* GRANULARITY specifies how the repository keeps tract of dates and times, either at the day level or hour, minute, second
    level.  If the repository can keep track at day level only, set this to "YYYY-MM-DD".  If the repository supports
    dates at the day, hour, minute, second level, set this to "YYYY-MM-DDThh:mm:ssZ".*/
$GRANULARITY = "YYYY-MM-DDThh:mm:ssZ";

/* EARLIEST_DATESTAMP should be the earliest date in the repository.  Nothing should be older than this date and the 
   oldest thing should have this date.  If we only support day granularity, this should be in YYYY-MM-DD 
   format, if we support day and hour granularity, this should be in YYYY-MM-DDThh:mm:ssZ format.  The format of this 
   MUST match the format specified in $GRANULARITY (above). */
$EARLIEST_DATESTAMP = "2010-07-21T18:05:12Z";

/* DELETED_RECORDS_SYSTEM says how the repository handles deleted records.  Since this system doesn't, set to "no". */
$DELETED_RECORDS_SYSTEM = "no";

/* Don't bother to edit anything below this.
   ----------------------------------------- */

$OAI_TOP = <<<END
<?xml version="1.0" encoding="UTF-8"?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
	http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
END;

?>
