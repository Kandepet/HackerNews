<?php

// Start session
session_start();

require 'vendor/autoload.php';

$config = new HackerNews\Config('config.ini');

$_SESSION['hn_login'] = array();
$_SESSION['voted'] = array();
$_SESSION['bury'] = array();

// Remove the session
unset($_SESSION['hn_login']);
unset($_SESSION['voted']);
unset($_SESSION['bury']);

// Remove rememeber me cookie
if(isset($_COOKIE[$config['cookies.name']]))
{
	setcookie($config['cookies.name'],'',time()-3600);
}

// Redirect to front page
header("Location: index.php");
?>
