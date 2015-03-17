<?php
(PHP_SAPI === 'cli') or die("HA!!! Nice try! Go get a command line.");

require 'vendor/autoload.php';

$config = new HackerNews\Config('config.ini');
$table_prefix = $config['db.prefix'];

$sql = <<<HN_MYSQL_DUMP_EOT__

--
-- Table structure for table `{$table_prefix}penalties`
--

DROP TABLE IF EXISTS `{$table_prefix}penalties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `{$table_prefix}penalties` (
  `penalty_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `story_id` int(11) unsigned NOT NULL DEFAULT '0',
  `penalty` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`penalty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `{$table_prefix}penalties`
--

LOCK TABLES `{$table_prefix}penalties` WRITE;
/*!40000 ALTER TABLE `{$table_prefix}penalties` DISABLE KEYS */;
INSERT INTO `{$table_prefix}penalties` VALUES (1,1,20);
/*!40000 ALTER TABLE `{$table_prefix}penalties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `{$table_prefix}cdigs`
--

DROP TABLE IF EXISTS `{$table_prefix}cdigs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `{$table_prefix}comment_votes` (
  `comment_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `story_id` int(11) unsigned NOT NULL DEFAULT '0',
  `time` int(11) unsigned NOT NULL DEFAULT '0',
  `vote` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`,`user_id`),
  KEY `user_id` (`user_id`,`story_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `{$table_prefix}comments`
--

DROP TABLE IF EXISTS `{$table_prefix}comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `{$table_prefix}comments` (
  `comment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `story_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_name` varchar(50) NOT NULL DEFAULT '',
  `comment_desc` mediumtext NOT NULL,
  `comment_time` int(11) unsigned NOT NULL DEFAULT '0',
  `comment_votes` mediumint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`),
  KEY `story_id` (`story_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `{$table_prefix}votes`
--

DROP TABLE IF EXISTS `{$table_prefix}votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `{$table_prefix}votes` (
  `story_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`story_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `{$table_prefix}logs`
--

DROP TABLE IF EXISTS `{$table_prefix}logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `{$table_prefix}logs` (
  `log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `log_date` int(11) unsigned NOT NULL DEFAULT '0',
  `log_summary` varchar(100) NOT NULL DEFAULT '',
  `log_details` mediumtext NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `{$table_prefix}stories`
--

DROP TABLE IF EXISTS `{$table_prefix}stories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `{$table_prefix}stories` (
  `story_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_name` varchar(50) NOT NULL DEFAULT '',
  `story_url` varchar(255) NOT NULL DEFAULT '',
  `story_title` varchar(255) NOT NULL DEFAULT '',
  `story_desc` mediumtext NOT NULL,
  `story_cat` int(11) unsigned NOT NULL DEFAULT '0',
  `story_score` int(11) unsigned NOT NULL DEFAULT '0',
  `story_votes` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `story_buries` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `story_time` int(11) unsigned NOT NULL DEFAULT '0',
  `story_comments` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `story_last5` int(11) unsigned NOT NULL DEFAULT '0',
  `story_thumb` varchar(80) NOT NULL DEFAULT '',
  `story_prom` enum('0','1') NOT NULL DEFAULT '0',
  `story_prom_date` int(11) unsigned NOT NULL DEFAULT '0',
  `story_tags` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`story_id`),
  FULLTEXT KEY `story_title` (`story_title`,`story_desc`),
  FULLTEXT KEY `story_title_2` (`story_title`,`story_desc`)
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `{$table_prefix}users`
--

DROP TABLE IF EXISTS `{$table_prefix}users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `{$table_prefix}users` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `forgot` varchar(40) NOT NULL DEFAULT '',
  `remember` varchar(40) NOT NULL DEFAULT '',
  `avatar` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `commentst` mediumint(3) NOT NULL DEFAULT '-4',
  `created_on` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Dump completed on 2015-03-13 18:51:52
HN_MYSQL_DUMP_EOT__;

//echo $sql;

function table_exist($db, $table){
   $sql = "SHOW TABLES LIKE '".$table."'";
   $res = $db->rawQuery($sql);
   return ($res->num_rows > 0);
}

$db = HackerNews\Common::database($config);

$SqlParser = new HackerNews\SQLParser();

$sql_statements = $SqlParser->load($sql);
//print_r($sql_statements);

foreach($sql_statements as $statement) {
   echo "Executing: " . $statement["query_parsed"]["type"] . "\n";
   //echo "Query: " . $statement["query"] . "\n";
   $db->unPreparedRawQuery($statement["query"]);
}

exit;

?>
