<?php
require 'vendor/autoload.php';

// Start session
session_start();

$config = new HackerNews\Config('config.ini');
$base_url = $config['site.base_url'] . $config['site.base_path'] . '/';
$db = HackerNews\Common::database($config);

// Check if the user has a remember cookie set
HackerNews\Common::checkremember($config, $db);

// Initiate story array
$stories = array();

// If no page is specified set to first page
if(!isset($_GET['page'])) {
   $_GET['page'] = 1;
} else {
   $_GET['page'] = makeInt($_GET['page']);
}

$db->orderBy("story_score", "DESC");
$all_stories = $db->get("stories", Array(HackerNews\Common::validate_input(($_GET['page']-1)*10),10));

$votes_to_check = [];

foreach($all_stories as $story) {
   $story['story_title'] = stripslashes($story['story_title']);
   $story['story_desc'] = stripslashes($story['story_desc']);

   if($story['story_url'] == '') {
      $story['story_url'] = $base_url.'story.php?id='.$story['story_id'];
      $story['story_domain'] = '';
   } else {
      $story['story_domain'] = HackerNews\Common::getDomain($story['story_url']);
   }

   $stories[$story['story_id']] = $story;
   $stories[$story['story_id']]['votes'] = 0;
   $stories[$story['story_id']]['buried'] = 0;
   $stories[$story['story_id']]['ago'] = HackerNews\Common::time_taken((time()-$story['story_time']));
   $stories[$story['story_id']]['domain'] = $story['story_domain'];

   $stories[$story['story_id']]['story_link'] = $base_url.'story.php?id='.$story['story_id'];
   $stories[$story['story_id']]['user_link'] = $base_url.'profile.php?id='.$story['user_id'];

   if(isset($_SESSION['hn_login']['id'])) {
      // If user logged in, check if they voted on any story.
      if(!isset($_SESSION['voted'][$story['story_id']])) {
         $votes_to_check[] = $story['story_id'];
      }

   }
}

// Get total stories in the system.
$posts_count = $db->getValue("stories", "count(*)");

if(isset($_SESSION['hn_login']['id'])) {
   if(count($votes_to_check)) {
      $db->where("user_id", HackerNews\Common::validate_input($_SESSION['hn_login']['id']));
      $db->Where("story_id", Array("IN"=>$votes_to_check));
      $votes = $db->get("votes");

      foreach($votes as $vote) {
         $stories[$vote['story_id']]['voted'] = 1;
      }
   }
}

foreach ($stories as $key => $story) {
   if(!$stories[$story['story_id']]['voted']) {
      if(isset($_SESSION['voted'][$story['story_id']])) {
         $stories[$story['story_id']]['voted'] = 1;
      }
   }

   if(!$stories[$story['story_id']]['buried']) {
      if(isset($_SESSION['bury'][$story['story_id']])) {
         $stories[$story['story_id']]['buried'] = 1;
      }
   }
}

//echo '<pre>';print_r($stories);echo '</pre>';
$loggedin = 0;
if(isset($_SESSION['hn_login']['id'])) $loggedin = 1;

$twig = new HackerNews\TwigEngine($config);
echo $twig->setVars(array(
   'stories' => $stories,
   'loggedin' => $loggedin,
   'username' => $_SESSION['hn_login']['name'],
   'userid' => $_SESSION['hn_login']['id'],
   'page_nav' => HackerNews\Common::page_nav($posts_count,$_GET['page'],10,'index.php')
))->render('list');

?>
