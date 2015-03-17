<?php
require 'vendor/autoload.php';

$config = new HackerNews\Config('config.ini');
$base_url = $config['site.base_url'] . $config['site.base_path'] . '/';

$db = HackerNews\Common::database($config);

ignore_user_abort(1);
@set_time_limit(0);

$start_time = time();

// Get all stories in the last 24hrs.
$db->where("story_time", (time()-86400), ">");
$stories = $db->get("stories", 600, Array("story_id", "story_time", "story_votes", "story_title", "story_prom"));

// Rank the stories based on HN raking: http://www.righto.com/2013/11/how-hacker-news-ranking-really-works.html
foreach($stories as $story) {
   $age_in_hours = round((time() - $story['story_time'])/3600);
   $votes = $story['story_votes'];
   $score = round((pow(($votes - 1), 0.8)/pow(($age_in_hours + 2), 1.8))*10000);

   $db->where("story_id", $story['story_id']);
   $db->update("stories", Array("story_score" => HackerNews\Common::validate_input($score)));
}

$end_time = (time()-$start_time);
$cron_details = "Cron Took: ".$end_time." seconds";
echo $cron_details
?>
