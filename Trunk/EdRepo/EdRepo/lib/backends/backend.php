<?php

/* backend.php - Datamanager and backend selection.
                 This file is used to select the datamanager and back-end used by the system.  Datamanagers and backends are distributed togeather, and 
                 each one should have its own subdirectory.  They should all be accessed through the "datamanager.php" file, so switching backends is as
                 simple as requiring a different datamanager. */

/* Change the line below to point to the datamanager for the backend you want to use. */
require("mysql/datamanager.php");

?>