<?php

namespace HackerNews\Exceptions;

class FileNotFoundException extends \Exception
{
   public function __construct($message)
   {
      parent::__construct($message);
   }
}

?>
