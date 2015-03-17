<?php
require 'vendor/autoload.php';

// Start the session
session_start();

$config = new HackerNews\Config('config.ini');
$base_url = $config['site.base_url'] . $config['site.base_path'] . '/';
$db = HackerNews\Common::database($config);

// Check if the user has a remember cookie set
HackerNews\Common::checkremember($config, $db);

// If no page is specified set to first page
if(!isset($_GET['page'])) {
   $_GET['page'] = 1;
} else {
   $_GET['page'] = HackerNews\Common::makeInt($_GET['page']);
}

$loggedin = 0;
if(isset($_SESSION['hn_login']['id'])) $loggedin = 1;

$db->where("user_id", HackerNews\Common::validate_input($_GET['id']));
$user_info = $db->getOne("users", "user_id, username, created_on");

if(!isset($user_info['user_id'])) {
   die('Sorry, this user could not be found');
} else {
   if(empty($_GET['list']) || ($_GET['list'] != 'submitted')) {

      $db->where("user_id", HackerNews\Common::validate_input($_GET['id']));
      $story_stats = $db->getOne("stories", "count(*) as story_count, sum(story_votes) as total_points");

      $db->where("user_id", HackerNews\Common::validate_input($_GET['id']));
      $comment_stats = $db->getOne("comments", "count(*) as comment_count");

      $twig = new HackerNews\TwigEngine($config);
      echo $twig->setVars(array(
         'loggedin' => $loggedin,
         'profile_user_id' => $_GET['id'],
         'profile_user_name' => $user_info['username'],
         'username' => $_SESSION['hn_login']['name'],
         'userid' => $_SESSION['hn_login']['id'],
         'created' => HackerNews\Common::time_taken(time()-$user_info['created_on']),
         'total_points' => $story_stats['total_points'],
         'submissions' => $story_stats['story_count'],
         'comment_count' => $comment_stats['comment_count']
      ))->render('profile');
   } else if($_GET['list'] == 'submitted') {

      $db->where("user_id", HackerNews\Common::validate_input($user_info['user_id']));
      $total[0] = $db->getValue("stories", "count(*)");

      $db->where("user_id", HackerNews\Common::validate_input($user_info['user_id']));
      $db->orderBy("story_time", "DESC");
      $limit = Array(HackerNews\Common::validate_input(($_GET['page']-1)*10), 10);
      $stories = $db->get("stories", $limit, "story_id,story_title,story_url,story_time,story_thumb,story_desc,story_cat,story_votes,story_comments,user_name,user_id");

      $voted = [];
      $story_list = [];
      foreach($stories as $story) {
         $story['story_title'] = stripslashes($story['story_title']);
         $story['story_desc'] = stripslashes($story['story_desc']);

         $story_list[] = $story['story_id'];

         $voted[$story['story_id']] = $story;
         $voted[$story['story_id']]['voted'] = 0;
         $voted[$story['story_id']]['ago'] = HackerNews\Common::time_taken((time()-$story['story_time']));
         $voted[$story['story_id']]['domain'] = HackerNews\Common::getDomain($story['story_url']);
         $voted[$story['story_id']]['story_link'] = $base_url.'story.php?id='.$story['story_id'];
         $voted[$story['story_id']]['user_link'] = $base_url.'profile.php?id='.$story['user_id'];
      }

      if(isset($_SESSION['hn_login']['id'])) {
         if(count($story_list)) {
            $db->where("user_id", $_SESSION['hn_login']['id']);
            $db->where("story_id", Array("IN" => $story_list));
            $stories = $db->get("comments", null, "story_id");

            foreach($stories as $story) {
               $voted[$story['story_id']]['voted'] = 1;
            }
         }
      }

      $twig = new HackerNews\TwigEngine($config);
      echo $twig->setVars(array(
         'stories' => $voted,
         'loggedin' => $loggedin,
         'profile_user_id' => $_GET['id'],
         'profile_user_name' => $user_info['username'],
         'username' => $_SESSION['hn_login']['name'],
         'userid' => $_SESSION['hn_login']['id'],
         'page_nav' => HackerNews\Common::page_nav($total[0], $_GET['page'], 10, 'profile.php?id='.$_GET['id'].'&list='.$_GET['list'])
      ))->render('list');
   }
}

?>
