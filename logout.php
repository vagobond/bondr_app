<?php
// PrivateCircle Logout
// logout.php

session_start();
session_destroy();
header('Location: login.php');
exit;
?>
