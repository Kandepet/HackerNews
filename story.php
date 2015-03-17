<?php
require 'vendor/autoload.php';

// Start the session
session_start();

$config = new HackerNews\Config('config.ini');
$base_url = $config['site.base_url'] . $config['site.base_path'] . '/';

$db = HackerNews\Common::database($config);

// Check if the user has a remember cookie set
HackerNews\Common::checkremember($config, $db);

$loggedin = 0;
if(isset($_SESSION['hn_login']['id'])) $loggedin = 1;

if(!isset($_GET['cmd'])) {
   $_GET['cmd'] = 'comments';
}

/* Add a new comment */
if(isset($_POST['comment'])) {
   if(isset($_SESSION['hn_login']['id'])) {
      $errors = 0;

      $_POST['comment'] = strip_tags($_POST['comment']);

      if(strlen(trim($_POST['comment'])) == 0) {
         $errors++;
         $comment_error = 'Please enter a comment';
      }

      if($errors == 0) {
         $data = Array (
            "comment_id" => "",
            "parent_id" => addslashes($_POST['parentid']),
            "story_id" => addslashes($_GET['id']),
            "user_id" => $_SESSION['hn_login']['id'],
            "user_name" => $_SESSION['hn_login']['name'],
            "comment_desc" => addslashes($_POST['comment']),
            "comment_time" => time()
         );
         $insert_id = $db->insert("comments", $data);

         if($insert_id) {
            $data = Array ("story_comments" => $db->inc(1));
            $db->where("story_id", addslashes($_GET['id']));
            $db->update ('stories', $data);
         }

         header("Location: story.php?id=".$_GET['id']);
         exit;
      }
   }
}

$current_time = time();

/* Get the story to display */
$db->where("story_id", addslashes($_GET['id']));
$story = $db->getOne("stories");

if(!isset($story['story_id'])) {

   $twig = new HackerNews\TwigEngine($config);
   echo $twig->setVars(array(
      'loggedin' => $loggedin,
      'username' => $_SESSION['hn_login']['name'],
      'userid' => $_SESSION['hn_login']['id'],
   ))->render('story');
   exit;
}

$story['domain'] = HackerNews\Common::getDomain($story['story_url']);
$story['ago'] = HackerNews\Common::time_taken($current_time - $story['story_time']);
$story['user_link'] = $base_url.'profile.php?id='.$story['user_name'];

if($_GET['cmd'] == 'comments') {

   $db->where("story_id", addslashes($_GET['id']));
   $db->orderBy("comment_time", "asc");
   $comments = $db->get("comments");

   foreach($comments as &$comment) {
      $comment['took'] = ($current_time-$comment['comment_time']);
      $comment['ago'] = HackerNews\Common::time_taken($comment['took']);
      $comment['comment_desc'] = stripslashes($comment['comment_desc']);
      $comment['voted'] = 0;
      $comment['hide'] = 0;

      if(isset($_SESSION['user']['id'])) {
         if($comment['comment_votes'] < $_SESSION['hn_login']['comm']) {
            $comment['hide'] = 1;
         }
      } else {
         if($comment['comment_votes'] < -4) {
            $comment['hide'] = 1;
         }
      }

      if($comment['comment_votes'] > -1) {
         $comment['comment_votes'] = '+'.$comment['comment_votes'];
      }

      $comment['user_link'] = $base_url.'profile.php?id='.$comment['user_id'];

   }
   $comment_tree = new HackerNews\ThreadedComments($comments);
   $threaded_comments = $comment_tree->get_comments();
}

$twig = new HackerNews\TwigEngine($config);
echo $twig->setVars(array(
   'story' => $story,
   'comments' => $threaded_comments,
   'loggedin' => $loggedin,
   'username' => $_SESSION['hn_login']['name'],
   'userid' => $_SESSION['hn_login']['id'],
))->render('story');
?>
