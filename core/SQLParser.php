<?php

namespace HackerNews;

class SQLParser {

   /**
    * Take off comments from an sql string
    *
    * Referring documentation at:
    * http://dev.mysql.com/doc/refman/5.6/en/comments.html
    *
    * @return string Query without comments
    */
   public function skipComments($query) {
      /*
       * Commented version
       * $sqlComments = '@
       *     (([\'"]).*?[^\\\]\2) # $1 : Skip single & double quoted expressions
       *     |(                   # $3 : Match comments
       *         (?:\#|--).*?$    # - Single line comments
       *         |                # - Multi line (nested) comments
       *          /\*             #   . comment open marker
       *             (?: [^/*]    #   . non comment-marker characters
       *                 |/(?!\*) #   . ! not a comment open
       *                 |\*(?!/) #   . ! not a comment close
       *                 |(?R)    #   . recursive case
       *             )*           #   . repeat eventually
       *         \*\/             #   . comment close marker
       *     )\s*                 # Trim after comments
       *     |(?<=;)\s+           # Trim after semi-colon
       *     @msx';
       */
      $sqlComments = '@(([\'"]).*?[^\\\]\2)|((?:\#|--).*?$|/\*(?:[^/*]|/(?!\*)|\*(?!/)|(?R))*\*\/)\s*|(?<=;)\s+@ms';

      $query = trim( preg_replace( $sqlComments, '$1', $query ) );

      //Eventually remove the last ;
      if(strrpos($query, ";") === strlen($query) - 1) {
         $query = substr($query, 0, strlen($query) - 1);
      }

      return $query;
   }

   protected function trimSQL($str) {
      //return trim(rtrim($str, "; \r\n\t")) . ' ';
      return trim(rtrim($str, "; \r\n\t"));
   }

   protected function nextPart(&$parseString, $regex, $trimAll = FALSE) {
      //echo "PARSE STRING: $parseString\n";
      $reg = array();
      if (preg_match('/' . $regex . '/i', $parseString . ' ', $reg)) {
         $parseString = ltrim(substr($parseString, strlen($reg[$trimAll ? 0 : 1])));
         return $reg[1];
      }
      // No match found
      return '';
   }

   protected function parseError($msg, $restQuery) {
      die('SQL engine parse ERROR: ' . $msg . ': near "' . substr($restQuery, 0, 50) . '"');
   }

   protected function getValue(&$parseString, $comparator = '', $mode = '') {
      $value = '';

      if (in_array(strtoupper(str_replace(array(' ', '\n', '\r', '\t'), '', $comparator)), Array('NOTIN', 'IN', '_LIST'))) { // List of values:
         if ($this->nextPart($parseString, '^([(])')) {
            $listValues = array();
            $comma = ',';

            while ($comma == ',') {
               $listValues[] = $this->getValue($parseString);
               if ($mode === 'INDEX') {
                  // Remove any length restriction on INDEX definition
                  $this->nextPart($parseString, '^([(]\d+[)])');
               }
               $comma = $this->nextPart($parseString, '^([,])');
            }

            $out = $this->nextPart($parseString, '^([)])');
            if ($out) {
               if ($comparator == '_LIST') {
                  $kVals = array();
                  foreach ($listValues as $vArr) {
                     $kVals[] = $vArr[0];
                  }
                  return $kVals;
               } else {
                  return $listValues;
               }
            } else {
               $this->parseError('No ) parenthesis in list', $parseString);
            }
         } else {
            $this->parseError('No ( parenthesis starting the list', $parseString);
         }

      } else { // Just plain string value, in quotes or not:

         // Quote?
         $firstChar = substr($parseString, 0, 1);
         switch ($firstChar) {
         case '"':
            $value = array($this->getValueInQuotes($parseString, '"'), '"');
            break;
         case "'":
            $value = array($this->getValueInQuotes($parseString, "'"), "'");
            break;
         default:
            $reg = array();
            if (preg_match('/^([[:alnum:]._-]+)/i', $parseString, $reg)) {
               $parseString = ltrim(substr($parseString, strlen($reg[0])));
               $value = array($reg[1]);
            }
            break;
         }
      }
      return $value;
   }

   protected function getValueInQuotes(&$parseString, $quote) {

      $parts = explode($quote, substr($parseString, 1));
      $buffer = '';
      foreach ($parts as $k => $v) {
         $buffer .= $v;

         $reg = array();
         preg_match('/\\\\$/', $v, $reg);
         if ($reg AND strlen($reg[0]) % 2) {
            $buffer .= $quote;
         } else {
            $parseString = ltrim(substr($parseString, strlen($buffer) + 2));
            return $this->parseStripslashes($buffer);
         }
      }
   }

   protected function parseStripslashes($str) {
      $search = array('\\\\', '\\\'', '\\"', '\0', '\n', '\r', '\Z');
      $replace = array('\\', '\'', '"', "\x00", "\x0a", "\x0d", "\x1a");

      return str_replace($search, $replace, $str);
   }


   // LOCK TABLES `dug_penalties` WRITE;
   protected function parseLOCKTABLES($parseString) {

      // Removing LOCK TABLE
      $parseString = $this->trimSQL($parseString);
      //$parseString = ltrim(substr(ltrim(substr($parseString, 4)), 5));

      // Init output variable:
      $result = array();
      $result['type'] = 'LOCK TABLES';

      // Get table:
      $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

      $result['LOCK_TYPE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

      if ($result['LOCK_TYPE']) {

         // Should be no more content now:
         if ($parseString) {
            $this->parseError('LOCK: Still content in clause after parsing!', $parseString);
         }

         return $result;
      } else {
         $this->parseError('No table found!', $parseString);
      }
   }

   // LOCK TABLES `dug_penalties` WRITE;
   protected function parseUNLOCKTABLES($parseString) {

      // Removing LOCK TABLE
      $parseString = $this->trimSQL($parseString);
      //$parseString = ltrim(substr(ltrim(substr($parseString, 4)), 5));

      // Init output variable:
      $result = array();
      $result['type'] = 'UNLOCK TABLES';

      // Get table:
      //$result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

      if ($result['type']) {

         // Should be no more content now:
         if ($parseString) {
            $this->parseError('UNLOCK: Still content in clause after parsing!', $parseString);
         }

         return $result;
      } else {
         $this->parseError('No table found!', $parseString);
      }
   }
   protected function parseINSERT($parseString) {

      //echo "'$parseString'\n";
      // Removing INSERT
      $parseString = $this->trimSQL($parseString);
      //$parseString = ltrim(substr(ltrim(substr($parseString, 6)), 4));

      // Init output variable:
      $result = array();
      $result['type'] = 'INSERT';

      // Get table:
      $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\()');

      if ($result['TABLE']) {

         //echo "$parseString\n";
         if ($this->nextPart($parseString, '^(VALUES)([[:space:]]+|\()')) { // In this case there are no field names mentioned in the SQL!
            //echo "Here";
            // Get values/fieldnames (depending...)
            $result['VALUES_ONLY'] = $this->getValue($parseString, 'IN');
            if (preg_match('/^,/', $parseString)) {
               $result['VALUES_ONLY'] = array($result['VALUES_ONLY']);
               $result['EXTENDED'] = '1';
               while ($this->nextPart($parseString, '^(,)') === ',') {
                  $result['VALUES_ONLY'][] = $this->getValue($parseString, 'IN');
               }
            }
            //echo "Str: '$parseString'\n";
         } else { // There are apparently fieldnames listed:
            $fieldNames = $this->getValue($parseString, '_LIST');

            if ($this->nextPart($parseString, '^(VALUES)([[:space:]]+|\()')) { // "VALUES" keyword binds the fieldnames to values:
               $result['FIELDS'] = array();
               do {
                  $values = $this->getValue($parseString, 'IN'); // Using the "getValue" function to get the field list...

                  $insertValues = array();
                  foreach ($fieldNames as $k => $fN) {
                     if (preg_match('/^[[:alnum:]_]+$/', $fN)) {
                        if (isset($values[$k])) {
                           if (!isset($insertValues[$fN])) {
                              $insertValues[$fN] = $values[$k];
                           } else {
                              $this->parseError('Fieldname ("' . $fN . '") already found in list!', $parseString);
                           }
                        } else {
                           $this->parseError('No value set!', $parseString);
                        }
                     } else {
                        $this->parseError('Invalid fieldname ("' . $fN . '")', $parseString);
                     }
                  }
                  if (isset($values[$k + 1])) {
                     $this->parseError('Too many values in list!', $parseString);
                  }
                  $result['FIELDS'][] = $insertValues;
               } while ($this->nextPart($parseString, '^(,)') === ',');

               if (count($result['FIELDS']) === 1) {
                  $result['FIELDS'] = $result['FIELDS'][0];
               } else {
                  $result['EXTENDED'] = '1';
               }
            } else {
               $this->parseError('VALUES keyword expected', $parseString);
            }
         }
      } else {
         $this->parseError('No table found!', $parseString);
      }

      //$parseString = trim($parseString);
      //echo "Str: '$parseString' Length: " . sizeof($parseString);

      // Should be no more content now:
      if (!empty($parseString)) {
         $this->parseError('Still content after parsing!', $parseString);
      }

      //print_r($result);
      // Return result
      return $result;
   }


   protected function parseDROPTABLE($parseString) {

      // Removing DROP TABLE
      $parseString = $this->trimSQL($parseString);
      //$parseString = ltrim(substr(ltrim(substr($parseString, 4)), 5));

      // Init output variable:
      $result = array();
      $result['type'] = 'DROP TABLE';

      // IF EXISTS
      $result['ifExists'] = $this->nextPart($parseString, '^(IF[[:space:]]+EXISTS[[:space:]]+)');

      // Get table:
      $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');

      if ($result['TABLE']) {

         // Should be no more content now:
         if ($parseString) {
            $this->parseError('DROP: Still content in clause after parsing!', $parseString);
         }

         return $result;
      } else {
         $this->parseError('No table found!', $parseString);
      }
   }

   protected function parseCREATETABLE($parseString) {

      // Removing CREATE TABLE
      $parseString = $this->trimSQL($parseString);
      //$parseString = ltrim(substr(ltrim(substr($parseString, 6)), 5));

      // Init output variable:
      $result = array();
      $result['type'] = 'CREATE TABLE';

      // Get table:
      $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]*\(', TRUE);

      if ($result['TABLE']) {

         // While the parseString is not yet empty:
         while (strlen($parseString) > 0) {
            if ($key = $this->nextPart($parseString, '^(FULLTEXT KEY|KEY|PRIMARY KEY|UNIQUE KEY|UNIQUE)([[:space:]]+|\()')) { // Getting key
               $key = strtoupper(str_replace(array(' ', '\t', '\r', '\n'), '', $key));
               //echo "KEY: $key\n";

               switch ($key) {
               case 'PRIMARYKEY':
                  $result['KEYS']['PRIMARYKEY'] = $this->getValue($parseString, '_LIST');
                  break;
               case 'UNIQUE':
               case 'UNIQUEKEY':
                  if ($keyName = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\()')) {
                     $result['KEYS']['UNIQUE'] = array($keyName => $this->getValue($parseString, '_LIST'));
                  } else {
                     $this->parseError('No keyname found', $parseString);
                  }
                  break;
               case 'KEY':
                  if ($keyName = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\()')) {
                     $result['KEYS'][$keyName] = $this->getValue($parseString, '_LIST', 'INDEX');
                  } else {
                     $this->parseError('No keyname found', $parseString);
                  }
                  break;
               case 'FULLTEXTKEY':
                  if ($keyName = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\()')) {
                     //echo "1. FULLTEXT KEY: $keyName\n";
                     $result['KEYS']['FULLTEXT'][] = array($keyName => $this->getValue($parseString, '_LIST'));
                     //echo "1. FULLTEXT: " . $result['KEYS']['FULLTEXT'] . "\n";
                  } else {
                     $this->parseError('No keyname found', $parseString);
                  }
                  break;
               }
            } elseif ($fieldName = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+')) { // Getting field:
               //echo "Field: $fieldName \n";
               $result['FIELDS'][$fieldName]['definition'] = $this->parseFieldDef($parseString);
            }

            // Finding delimiter:
            $delim = $this->nextPart($parseString, '^(,|\))');
            if (!$delim) {
               $this->parseError('No delimiter found', $parseString);
            } elseif ($delim == ')') {
               break;
            }
         }

         // Finding what is after the table definition - table type in MySQL
         if ($delim == ')') {
            if ($this->nextPart($parseString, '^((ENGINE|TYPE)[[:space:]]*=)')) {
               $result['tableType'] = $parseString;
               $parseString = '';
            }
         } else {
            $this->parseError('No fieldname found!', $parseString);
         }

         // Getting table type
      } else {
         $this->parseError('No table found!', $parseString);
      }

      // Should be no more content now:
      if ($parseString) {
         $this->parseError('CREATE: Still content in clause after parsing!', $parseString);
      }


      return $result;
   }

   public function parseFieldDef(&$parseString, $stopRegex = '') {
      // Prepare variables:
      $parseString = $this->trimSQL($parseString);
      //$this->lastStopKeyWord = '';
      //$this->parse_error = '';

      $result = array();

      // Field type:
      if ($result['fieldType'] = $this->nextPart($parseString, '^(key|enum|int|smallint|tinyint|mediumint|bigint|double|numeric|decimal|float|varchar|char|text|tinytext|mediumtext|longtext|blob|tinyblob|mediumblob|longblob)([[:space:],]+|\()')) {

         // Looking for value:
         if (substr($parseString, 0, 1) == '(') {
            $parseString = substr($parseString, 1);
            if ($result['value'] = $this->nextPart($parseString, '^([^)]*)')) {
               $parseString = ltrim(substr($parseString, 1));
            } else {
               $this->parseError('No end-parenthesis for value found in parseFieldDef()!', $parseString);
            }
         }

         // Looking for keywords
         while ($keyword = $this->nextPart($parseString, '^(DEFAULT|NOT[[:space:]]+NULL|AUTO_INCREMENT|UNSIGNED)([[:space:]]+|,|\))')) {
            $keywordCmp = strtoupper(str_replace(array(' ', '\t', '\r', '\n'), '', $keyword));

            $result['featureIndex'][$keywordCmp]['keyword'] = $keyword;

            switch ($keywordCmp) {
            case 'DEFAULT':
               $result['featureIndex'][$keywordCmp]['value'] = $this->getValue($parseString);
               break;
            }
         }
      } else {
         return $this->parseError('Field type unknown in parseFieldDef()!', $parseString);
      }

      return $result;
   }


   protected function parseStatement($query) {
      //echo "Parsing: $query\n";
      $parseString = $this->trimSQL($query);
      $parseString = str_replace(array('`', '\r', '\n'), '', $parseString);

      $keyword = $this->nextPart($parseString, '^(SELECT|UPDATE|INSERT[[:space:]]+INTO|DELETE[[:space:]]+FROM|EXPLAIN|UNLOCK[[:space:]]+TABLES|LOCK[[:space:]]+TABLES|DROP[[:space:]]+TABLE|CREATE[[:space:]]+TABLE|CREATE[[:space:]]+DATABASE|ALTER[[:space:]]+TABLE|TRUNCATE[[:space:]]+TABLE)[[:space:]]+');
      $keyword = strtoupper(str_replace(array(' ', '\t', '\r', '\n'), '', $keyword));
      //echo " KEYWORD: $keyword \n";

      switch ($keyword) {
      case 'LOCKTABLES':
         // Parsing DROP TABLE query:
         $result = $this->parseLOCKTABLES($parseString);
         break;
      case 'UNLOCKTABLES':
         // Parsing DROP TABLE query:
         $result = $this->parseUNLOCKTABLES($parseString);
         break;
      case 'INSERTINTO':
         // Parsing INSERT query:
         $result = $this->parseINSERT($parseString);
         break;
      case 'DROPTABLE':
         // Parsing DROP TABLE query:
         $result = $this->parseDROPTABLE($parseString);
         break;
      case 'CREATETABLE':
         // Parsing CREATE TABLE query:
         $result = $this->parseCREATETABLE($parseString);
         break;
      default:
         $this->parseError('"' . $keyword . '" is not a keyword', $parseString);
      }
      return $result;
   }

   public function load($content) {

      $sqlList = array();

      // Processing the SQL file content
      $lines = explode("\n", $content);

      $query = "";

      // Parsing the SQL file content
      foreach ($lines as $sql_line) {
         $sql_line = trim($sql_line);
         if($sql_line === "") continue;
         else if(strpos($sql_line, "--") === 0) continue;
         else if(strpos($sql_line, "#") === 0) continue;

         $query .= $sql_line;
         // Checking whether the line is a valid statement
         if (preg_match("/(.*);/", $sql_line)) {
            $query = trim($query);
            $query = substr($query, 0, strlen($query) - 1);

            $query = $this->skipComments($query);

            //store this query
            if(!empty($query)) {
               //echo "Parsing: $query\n";
               $sqlList[] = Array("query" => $query, "query_parsed" => $this->parseStatement($query));
            }

            //reset the variable
            $query = "";
         }

      }

      return $sqlList;
   }

}

