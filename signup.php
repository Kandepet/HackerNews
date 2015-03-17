<?php
require 'vendor/autoload.php';

$config = new HackerNews\Config('config.ini');
$base_url = $config['site.base_url'] . $config['site.base_path'] . '/';

$db = HackerNews\Common::database($config);

// Start Session
session_start();

// Check if this is an availability check from signup page using ajax
if(isset($_GET['avail'])) {
   // Check if anyone has this username
   $db->where("username", HackerNews\Common::validate_input($_GET['avail']));
   $availcheck = $db->getOne("users");

   if($availcheck) {
      // Someone already has this username
      echo $_GET['avail'].'|0';
   } else {
      // That username is available
      echo $_GET['avail'].'|1';
   }

   exit;
}

// Check if they have submitted the signup page
if(isset($_POST['username'])) {
   // Initiate error messages
   $errors = 0;
   $username_error = '';
   $password_error = '';
   $email_error = '';
   $agree_error = '';
   $security_error = '';

   $_POST['username'] = strip_tags($_POST['username']);

   if(ereg('[^A-Za-z0-9]',$_POST['username'])) {
      $errors++;
      $username_error = "Username can only contain alphanumerics";
   } elseif( (strlen($_POST['username']) < 4) OR (strlen($_POST['username']) > 16) ) {
      $errors++;
      $username_error = "Username should be between 4 to 16 characters";
   } else {
      //$avail = mysql_num_rows(mysql_query("SELECT 1 FROM ".$config['db']['pre']."users WHERE username='".validate_input($_POST['username'])."' LIMIT 1"));
      $db->where("username", HackerNews\Common::validate_input($_POST['username']));
      $avail = $db->getOne("users");


      if($avail) {
         $errors++;
         $username_error = "Username is not available. Please choose another username";
      }
   }

   if( (strlen($_POST['password']) < 4) OR (strlen($_POST['password']) > 16) ) {
      $errors++;
      $password_error = "Password should be between 4 to 16 characters";
   } elseif($_POST['password'] != $_POST['password2']) {
      $errors++;
      $password_error = "Passwords do not match";
   }

   if(trim($_POST['email']) == '') {
      $errors++;
      $email_error = "Please enter an email address";
   } elseif(!eregi("^[[:alnum:]][a-z0-9_.-]*@[a-z0-9.-]+\.[a-z]{2,4}$", $_POST['email'])) {
      $errors++;
      $email_error = "Invalid email address";
   } else {
      $db->where("email", HackerNews\Common::validate_input($_POST['email']));
      $avail = $db->getOne("users");

      if($avail) {
         $errors++;
         $email_error = "Email address already used. Please select another";
      }
   }

   if($errors == 0) {
      $rem = md5(mt_rand(0,56)*time());

      $data = Array(
         "user_id" => '',
         "username" => HackerNews\Common::validate_input($_POST['username']),
         "password" => HackerNews\Common::validate_input(md5($_POST['password'])),
         "email" => HackerNews\Common::validate_input($_POST['email']),
         "remember" => HackerNews\Common::validate_input($rem),
         "created_on" => time(),
         "status" => '1'
      );

      $user_id = $db->insert("users", $data);

      $_SESSION['hn_login']['id'] = $user_id;
      $_SESSION['hn_login']['name'] = $_POST['username'];

      header('Location: index.php');
      exit;
   }
}

$loggedin = 0;
if(isset($_SESSION['hn_login']['id'])) $loggedin = 1;

$twig = new HackerNews\TwigEngine($config);
echo $twig->setVars(array(
   'loggedin' => $loggedin,
   'username' => $_SESSION['hn_login']['name'],
   'userid' => $_SESSION['hn_login']['id'],
   'username_field' => $_POST['username'],
   'email_field' => $_POST['email'],
   'username_error' => $username_error,
   'password_error' => $password_error,
   'email_error' => $email_error,
   'agree_error' => $agree_error,
   'security_error' => $security_error,
))->render('signup');

?>
