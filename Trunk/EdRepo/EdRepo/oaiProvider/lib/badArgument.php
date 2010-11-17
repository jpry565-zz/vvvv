<?php

function badArgument($errCode, $extraParms, $message) {
  require("config.php");
  echo $OAI_TOP."\n";
  echo "<responseDate>".strftime("%Y-%m-%dT%H:%M:%SZ", time())."</responseDate>\n";
  if($extraParms != "") {
    echo "<request ".$extraParms.">".getRequestURL()."</request>\n";
  }
  else {
    echo "<request>".getRequestURL()."</request>\n";
  }
  echo '<error code="'.htmlspecialchars($errCode).'">'.$message."</error>\n";
  echo "</OAI-PMH>\n";
  die("");
}

?>
