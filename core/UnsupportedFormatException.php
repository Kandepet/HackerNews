<?php

namespace HackerNews\Exceptions;

class UnsupportedFormatException extends \Exception
{
   public function __construct($message)
   {
      parent::__construct($message);
   }
}


?>
