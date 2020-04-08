<?php
setcookie($_GET['key'],$_GET['value'], 0, "/", "", false, false);
echo "cookie ".$_GET['key']." updated to ".$_GET['value'];
?>