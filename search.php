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
if(!isset($_GET['page'])) $_GET['page'] = 1;
if(!isset($_GET['q'])) exit("No search query");

// Initiate stories array
$stories = array();

$_GET['q'] = urldecode($_GET['q']);

// Retreive stories from the database
if($config['search.type'] == 'pattern') {

   $db->where("story_title", Array("LIKE" => "%".HackerNews\Common::validate_input($_GET['q'])."%"));
   $db->orWhere("story_desc", Array("LIKE" => "%".HackerNews\Common::validate_input($_GET['q'])."%"));
   $db->orderBy("story_score", "ASC");
   $limit = Array(HackerNews\Common::validate_input(($_GET['page']-1)*10),10);

   $matched_stories = $db->get("stories", $limit);

}

$votes_to_check = [];

foreach($matched_stories as $story) {
   $story['story_title'] = stripslashes($story['story_title']);
   $story['story_desc'] = stripslashes($story['story_desc']);
   $story['story_tags'] = stripslashes($story['story_tags']);

   if($story['story_url'] == '') {
      $story['story_url'] = $base_url.'story.php?id='.$story['story_id'];
      $story['story_domain'] = '';
   } else {
      $story['story_domain'] = HackerNews\Common::getDomain($story['story_url']);
   }

   $stories[$story['story_id']] = $story;
   $stories[$story['story_id']]['voted'] = 0;
   $stories[$story['story_id']]['buried'] = 0;
   $stories[$story['story_id']]['ago'] = HackerNews\Common::time_taken((time()-$story['story_time']));

   $stories[$story['story_id']]['story_link'] = $base_url.'story.php?id='.$story['story_id'];
   $stories[$story['story_id']]['user_link'] = $base_url.'profile.php?id='.$story['user_id'];

   if(isset($_SESSION['hn_login']['id'])) {
      // If user logged in, check if they voted on any story.
      if(!isset($_SESSION['voted'][$story['story_id']])) {
         $votes_to_check[] = $story['story_id'];
      }

   }
}

// Get total number of stories in this category
if($config['search.type'] == 'pattern') {
   $db->where("story_title", Array("LIKE" => "%".HackerNews\Common::validate_input($_GET['q'])."%"));
   $db->orWhere("story_desc", Array("LIKE" => "%".HackerNews\Common::validate_input($_GET['q'])."%"));
   $results_count = $db->getValue("stories", "count(*)");
}

if(isset($_SESSION['hn_login']['id'])) {
   if(count($votes_to_check)) {
      $db->where("user_id", HackerNews\Common::validate_input($_SESSION['hn_login']['id']));
      $db->Where("story_id", Array("IN"=>$votes_to_check));
      $diggs = $db->get("votes");

      foreach($diggs as $dig) {
         $stories[$dig['story_id']]['voted'] = 1;
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

$loggedin = 0;
if(isset($_SESSION['hn_login']['id'])) $loggedin = 1;

$twig = new HackerNews\TwigEngine($config);
echo $twig->setVars(array(
   'stories' => $stories,
   'loggedin' => $loggedin,
   'username' => $_SESSION['hn_login']['name'],
   'userid' => $_SESSION['hn_login']['id'],
   'page_nav' => HackerNews\Common::page_nav($results_count,$_GET['page'],10,'search.php?q='.$_GET['q'])
))->render('list');

?>
