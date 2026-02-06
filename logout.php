<?php
session_start();
session_destroy();
header("Location: indexView.php");
exit;
?>
