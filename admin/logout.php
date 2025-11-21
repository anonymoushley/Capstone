<?php
session_start();
session_destroy();
header('Location: chair_login.php');
exit();
?> 