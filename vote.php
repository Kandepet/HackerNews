<?php
require 'vendor/autoload.php';

// Connect to database
$config = new HackerNews\Config('config.ini');
$base_url = $config['site.base_url'] . $config['site.base_path'] . '/';

$db = HackerNews\Common::database($config);

// Start the session
session_start();

// If no type then it's for a story
if(!isset($_GET['type'])) {
   $_GET['type'] = 'story';
}

// Check user is logged in
if(isset($_SESSION['hn_login']['id'])) {
   if($_GET['type'] == 'story') {
      if(!isset($_SESSION['voted'][$_GET['i']])) {
         // Lookup votes table to see if they have already voted this
         $db->where("story_id", hackerNews\Common::validate_input($_GET['i']));
         $db->where("user_id", $_SESSION['hn_login']['id']);
         $voted = $db->getValue("votes", "count(*)");

         // Check that they havn't voted it
         if(!$voted) {
            // Update voted count on story
            $update = Array(
               "story_votes" => $db->inc(1),
               "story_last5" => $db->inc(1)
            );
            $db->where("story_id", hackerNews\Common::validate_input($_GET['i']));
            $db->update("stories", $update);

            // Insert vote into votes table
            $insert = Array(
               "story_id" => hackerNews\Common::validate_input($_GET['i']),
               "user_id" => $_SESSION['hn_login']['id'],
               "time" => time()
            );
            $db->insert("votes", $insert);

            // Check number of votes on story
            $db->where("story_id", hackerNews\Common::validate_input($_GET['i']));
            $votes = $db->getOne("stories", "story_votes");

            // Register that they have voted this story in session
            $_SESSION['voted'][$_GET['i']] = true;

            // Return success and number of votes
            echo '1|'.$_GET['i'].'|'.$votes['story_votes'];
         } else {
            // User has already voted this story
            echo '2|'.$_GET['i'].'|'.$votes['story_votes'];
         }
      } else {
         // User has already voted this story
         echo '2|'.$_GET['i'].'|'.$votes['story_votes'];
      }
   } elseif($_GET['type'] == 'comm') {
      // Lookup votes table to see if they have already voted this
      $db->where("comment_id", hackerNews\Common::validate_input($_GET['i']));
      $db->where("user_id", $_SESSION['hn_login']['id']);
      $voted = $db->getValue("comment_votes", "count(*)");

      // Check that they havn't voted it
      if(!$voted) {
         // Update voted count on story
         if($_GET['dir']) {

            $update = Array(
               "comment_votes" => $db->inc(1),
            );
            $db->where("comment_id", hackerNews\Common::validate_input($_GET['i']));
            $db->update("comments", $update);

            // Insert vote into votes table
            $insert = Array(
               "story_id" => hackerNews\Common::validate_input($_GET['story']),
               "comment_id" => hackerNews\Common::validate_input($_GET['i']),
               "user_id" => $_SESSION['hn_login']['id'],
               "vote" => 1,
               "time" => time()
            );
            $db->insert("comment_votes", $insert);

         } else {

            $update = Array(
               "comment_votes" => $db->inc(1),
            );
            $db->where("comment_id", hackerNews\Common::validate_input($_GET['i']));
            $db->update("comments", $update);

            // Insert vote into votes table
            $insert = Array(
               "story_id" => hackerNews\Common::validate_input($_GET['story']),
               "comment_id" => hackerNews\Common::validate_input($_GET['i']),
               "user_id" => $_SESSION['hn_login']['id'],
               "vote" => 1,
               "time" => time()
            );
            $db->insert("comment_votes", $insert);

         }

         // Check number of votes on story
         $db->where("comment_id", hackerNews\Common::validate_input($_GET['i']));
         $votes = $db->getOne("comments", "comment_votes");

         //print_r($votes);


         if($votes['comment_votes'] > -1) {
            $votes['comment_votes'] = '+'.$votes['comment_votes'];
         }

         // Return success and number of votes
         echo '1|'.$_GET['i'].'|'.$votes['comment_votes'];
      } else {
         // User has already voted this story
         echo '2|'.$_GET['i'].'|'.$votes['comment_votes'];
      }
   }
} else {
   // User isn't logged in
   echo '0|'.$_GET['i'].'|0';
}
?>
