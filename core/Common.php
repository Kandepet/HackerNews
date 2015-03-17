<?php

namespace HackerNews;

class Common {

   static function database($config) {
      $db = new MysqliDb($config['db.host'], $config['db.username'], $config['db.password'], $config['db.database']);
      $db->setPrefix($config['db.prefix']);
      return $db;
   }

   static function logincheck($redirect='') {
      if(!isset($_SESSION['hn_login']['id'])) {
         if($redirect) {
            header("Location: ".$config['site_url']."login.php?redirect=".urlencode($redirect));
         } else {
            header("Location: ".$config['site_url']."login.php");
         }
         exit;
      }
   }

   static function checkremember($config, $db) {

      if(!isset($_SESSION['hn_login']['id'])) {
         if(isset($_COOKIE[$config['cookies.name']])) {
            $remarray = unserialize(str_replace('\\','',$_COOKIE[$config['cookies.name']]));

            // Lookup the users table for that user
            $db->where("username", validate_input($remarray['username']));
            $db->where("user_id", validate_input($remarray['uid']));
            $db->where("remember", validate_input($remarray['rem']));
            $user_info = $db->get("users", 1);

            // The submitted details are valid
            if(isset($user_info[0])) {
               $_SESSION['hn_login']['id'] = $user_info[0];
               $_SESSION['hn_login']['name'] = $remarray['username'];
               $_SESSION['hn_login']['comm'] = $user_info[2];

               header('Location: index.php');
               exit;
            }
         }
      }
   }

   static function page_nav($total,$page,$perpage,$url) {
      $page_arr = array();
      $arr_count = 0;

      if(strstr($url, "?")) {
         $url = $url . '&';
      } else {
         $url = $url . '?';
      }

      $total_pages = ceil($total/$perpage);
      $llimit = 1;
      $rlimit = $total_pages;
      $window = 5;

      if ($page<1 || !$page) {
         $page=1;
      }

      if(($page - floor($window/2)) <= 0) {
         $llimit = 1;
         if($window > $total_pages) {
            $rlimit = $total_pages;
         } else {
            $rlimit = $window;
         }
      } else {
         if(($page + floor($window/2)) > $total_pages) {
            if ($total_pages - $window < 0) {
               $llimit = 1;
            } else {
               $llimit = $total_pages - $window + 1;
            }
            $rlimit = $total_pages;
         } else {
            $llimit = $page - floor($window/2);
            $rlimit = $page + floor($window/2);
         }
      }

      if ($page>1) {
         $page_arr[$arr_count]['title'] = "Prev";
         $page_arr[$arr_count]['link'] = $url.'page='. ($page-1);
         $page_arr[$arr_count]['current'] = 0;

         $arr_count++;
      }

      for ($x=$llimit;$x <= $rlimit;$x++) {
         if ($x <> $page) {
            $page_arr[$arr_count]['title'] = $x;
            $page_arr[$arr_count]['link'] =  $url.'page='. $x;
            $page_arr[$arr_count]['current'] = 0;

         } else {
            $page_arr[$arr_count]['title'] = $x;
            $page_arr[$arr_count]['link'] =  $url.'page='. $x;
            $page_arr[$arr_count]['current'] = 1;
         }

         $arr_count++;
      }

      if($page < $total_pages) {
         $page_arr[$arr_count]['title'] = "Next";
         $page_arr[$arr_count]['link'] =  $url.'page='. ($page+1);
         $page_arr[$arr_count]['current'] = 0;

         $arr_count++;
      }

      return $page_arr;
   }

   static function getrandnum($length) {
      $randstr='';
      srand((double)microtime()*1000000);
      $chars = array ( 'a','b','C','D','e','f','G','h','i','J','k','L','m','N','P','Q','r','s','t','U','V','W','X','y','z','1','2','3','4','5','6','7','8','9');
      for ($rand = 0; $rand <= $length; $rand++) {
         $random = rand(0, count($chars) -1);
         $randstr .= $chars[$random];
      }

      return $randstr;
   }

   static function time_taken($time) {
      if($time > 86400) {
         $days = floor($time/86400);
         $hours = floor(($time-($days*86400))/3600);

         if($days > 1) {
            $took = $days . ' days';
         } else {
            $took = $days . ' day';
         }
      } elseif($time > 3600) {
         $hours = floor(($time/60)/60);
         $mins = floor(($time-($hours*3600))/60);

         if($hours > 1) {
            $took = $hours.' hours';
         } else {
            $took = $hours.' hour';
         }
      } elseif($time > 60) {
         $mins = floor($time/60);

         $took = $mins . ' minutes';
      } else {
         $took = $time . ' seconds';
      }

      return $took;
   }

   static function makeInt ($x,$signed=false) {
      if(!is_numeric($x)) {
         $x = intval($x);
      }

      if(!$x) {
         $x=1;
      }

      if(!$signed) {
         if($x<1) {
            $x=1;
         }
      }

      return $x;
   }

   static function getDomain($url) {
      $parts = parse_url($url);

      if(isset($parts['host'])) {
         $parts['host'] = str_replace('www.','',$parts['host']);

         return $parts['host'];
      } else {
         $url = str_replace('www.','',$url);

         return $url;
      }
   }

   static function validate_input($input,$dbcon=false,$content='all',$maxchars=0) {
      if(get_magic_quotes_gpc()) {
         if(ini_get('magic_quotes_sybase')) {
            $input = str_replace("''", "'", $input);
         } else {
            $input = stripslashes($input);
         }
      }

      if($content == 'alnum') {
         $input = ereg_replace("[^a-zA-Z0-9]", '', $input);
      } elseif($content == 'num') {
         $input = ereg_replace("[^0-9]", '', $input);
      } elseif($content == 'alpha') {
         $input = ereg_replace("[^a-zA-Z]", '', $input);
      }

      if($maxchars) {
         $input = substr($input,0,$maxchars);
      }

      if($dbcon) {
         $input = mysql_real_escape_string($input);
      } else {
         $input = mysql_escape_string($input);
      }

      return $input;
   }
}

?>
