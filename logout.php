<?php
require_once 'config/config.php';
$userObj = new User();
$userObj->logout();
redirect('/login.php');