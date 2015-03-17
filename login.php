<?php
//require 'core/common.php';
require 'vendor/autoload.php';

// Start session
session_start();

function send_forgot_email($email,$id,$config, $db) {
   $time = time();
   $rand = getrandnum(10);
   $forgot = md5($time.'_:_'.$rand.'_:_'.$email);

   $db->where("user_id", addslashes($id));
   $db->update("users", Array("forgot" => addslashes($forgot)));
   echo $db->getLastQuery();

   $mail = new \PHPMailer();

   if($config['email.type'] == 'smtp') {
      $mail->IsSMTP();
      $mail->SMTPAuth = true;
      $mail->Username = $config['email.smtp.user'];
      $mail->Password = $config['email.smtp.pass'];
      $mail->Host = $config['email.smtp.host'];
   } else if ($config['email.type'] == 'sendmail') {
      $mail->IsSendmail();
   } else {
      $mail->IsMail();
   }

   $mail->FromName = $config['site.title'];
   $mail->From = $config['site.admin_email'];
   $mail->AddAddress($email);

   $mail->Subject = $config['site.title'] . ": Forgot Password";
   $mail->Body = "To reset your password please click the link below:\n\n".$config['site.base_url'].$config['site.base_path']."/login.php?forgot=".$forgot."&r=".$rand."&e=".$email."&t=".$time;
   $mail->IsHTML(false);
   $mail->Send();
}



$config = new HackerNews\Config('config.ini');
$base_url = $config['site.base_url'] . $config['site.base_path'] . '/';
$db = HackerNews\Common::database($config);

// Set number of errors to 0
$errors = 0;

// Set default error message
$login_error = '';

if(isset($_POST['forgot'])) $_GET['forgot'] = $_POST['forgot'];
if(isset($_POST['r'])) $_GET['r'] = $_POST['r'];
if(isset($_POST['e'])) $_GET['e'] = $_POST['e'];
if(isset($_POST['t'])) $_GET['t'] = $_POST['t'];

// Check if they are using a forgot password link
if(isset($_GET['forgot'])) {
   $db->where("email", HackerNews\Common::validate_input($_GET['e']));
   $forgot = $db->getOne("users", Array("user_id", "forgot", "username"));

   if($_GET['forgot'] == $forgot['forgot']) {
      if($_GET['forgot'] == md5($_GET['t'].'_:_'.$_GET['r'].'_:_'.$_GET['e'])) {
         // Check that the link hasn't timed out (30 minutes old)
         if($_GET['t'] > (time()-108000)) {
            $forgot_error = '';

            if(isset($_POST['password'])) {
               if( (strlen($_POST['password']) < 4) OR (strlen($_POST['password']) > 16) ) {
                  $forgot_error = "Password should be between 4 to 16 characters";
               } else {
                  if($_POST['password'] == $_POST['password2']) {
                     $db->where("user_id", HackerNews\Common::validate_input($forgot["user_id"]));
                     $db->update("users", Array("forgot" => ''));

                     $db->where("user_id", HackerNews\Common::validate_input($forgot["user_id"]));
                     $db->update("users", Array("password" => md5($_POST['password'])));

                     $twig = new HackerNews\TwigEngine($config);
                     echo $twig->setVars(array('username' => $forgot['username'], 'mode' => 'password_reset_success'))->render('login');

                     exit;
                  } else {
                     $twig = new HackerNews\TwigEngine($config);
                     echo $twig->setVars(array('error_message' => 'Passwords do not match. Try again', 'mode' => 'error'))->render('login');
                  }
               }
            }

            $twig = new HackerNews\TwigEngine($config);
            echo $twig->setVars(array(
               'username' => $forgot['username'],
               'forgot' => $_GET['forgot'],
               'r' => $_GET['r'],
               'e' => $_GET['e'],
               't' => $_GET['t'],
               'mode' => 'password_reset'
            ))->render('login');

            exit;
         } else {
            $twig = new HackerNews\TwigEngine($config);
            echo $twig->setVars(array('error_message' => 'Passwords code expired. Please try again', 'mode' => 'error'))->render('login');
         }
      } else {
         $twig = new HackerNews\TwigEngine($config);
         echo $twig->setVars(array('error_message' => 'Invalid passwords code. Please try again', 'mode' => 'error'))->render('login');
      }
   } else {
      $twig = new HackerNews\TwigEngine($config);
      echo $twig->setVars(array('error_message' => 'Invalid passwords code. Please try again', 'mode' => 'error'))->render('login');
   }

   exit;
}


// Check if they are trying to retrieve their email
if(isset($_POST['email']) && ($_POST['email'] != ''))
{

   $db->where("email", HackerNews\Common::validate_input($_POST['email']));
   $user_id = $db->getValue("users", "user_id");
   print_r($user_id);


   // Check if the email address exists
   if($user_id) {
      // Send the email
      send_forgot_email($_POST['email'],$user_id,$config, $db);

      $twig = new HackerNews\TwigEngine($config);
      echo $twig->setVars(array('mode' => 'email_sent'))->render('login');

   } else {
      $twig = new HackerNews\TwigEngine($config);
      echo $twig->setVars(array('error_message' => "That email does not exist in our system. Please retry.", 'mode' => 'error'))->render('login');

      exit;
   }
}

$login_error = '';
// Check if a user has submitted the form
if(isset($_POST['username'])) {
   if(!isset($_POST['redirect'])) {
      $_POST['redirect'] = '';
   }

   // Lookup the users table for that user
   $db->where("username", HackerNews\Common::validate_input($_POST['username']));
   $db->where("password", HackerNews\Common::validate_input(md5($_POST['password'])));
   $userinfo = $db->getOne("users", Array("user_id", "remember", "commentst", "status"));
   //echo $db->getLastQuery();

   // The submitted details are valid
   if(isset($userinfo["user_id"])) {
      if($userinfo["status"] == '0') {
         $login_error = "This account is not configured";
      } else {
         if(isset($_POST['remember'])) {
            $rem = array();
            $rem['uid'] = $userinfo["user_id"];
            $rem['username'] = $_POST['username'];
            $rem['rem'] = $userinfo["remember"];
            $rem['tries'] = 0;

            setcookie($config['cookies.name'],serialize($rem),time()+$config['cookies.time']);
         }

         $_SESSION['hn_login']['id'] = $userinfo["user_id"];
         $_SESSION['hn_login']['name'] = $_POST['username'];
         $_SESSION['hn_login']['comm'] = $userinfo["commentst"];
         $_SESSION['voted'] = array();

         if($_POST['redirect']) {
            header('Location: '.$config['site_url'].urldecode($_POST['redirect']));
         } else {
            header('Location: '.$config['site_url'].'index.php');
         }
         exit;
      }
   } else {
      $login_error = "Password incorrect";
   }
}

if(isset($_POST['redirect'])) $_GET['redirect'] = $_POST['redirect'];

$twig = new HackerNews\TwigEngine($config);
echo $twig->setVars(array('redirect' => $_GET['redirect'], 'mode' => 'login', 'login_error' => $login_error))
   ->render('login');
?>
