<?php
session_start();
session_regenerate_id(true);
session_destroy();
header('Location: index.php');
exit();
?>