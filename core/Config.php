<?php

namespace HackerNews;

use HackerNews\Exceptions;

/**
 * Config
 *
 * @package    Config
 * @author     Jesus A. Domingo <jesus.domingo@gmail.com>
 * @author     Hassan Khan <contact@hassankhan.me>
 * @link       https://github.com/noodlehaus/config
 * @license    MIT
 */
class Config implements \ArrayAccess
{
   /**
    * Stores the configuration data
    *
    * @var array|null
    */
   protected $data = null;

   /**
    * Caches the configuration data
    *
    * @var array
    */
   protected $cache = array();

   /**
    * Static method for loading a config instance.
    *
    * @param  string $path
    *
    * @return Config
    */
   public static function load($path)
   {
      return new static($path);
   }

   /**
    * Loads a supported configuration file format.
    *
    * @param  string $path
    *
    * @return void
    *
    * @throws FileNotFoundException      If a file is not found at `$path`
    * @throws UnsupportedFormatException If `$path` is an unsupported file format
    */
   public function __construct($path)
   {
      // Get file information
      $info = pathinfo($path);

      // Check if config file exists or throw an exception
      if (!file_exists($path)) {
         throw new FileNotFoundException("Configuration file: [$path] cannot be found");
      }

      // Check if a load-* method exists for the file extension, if not throw exception
      $load_method = 'load' . ucfirst($info['extension']);
      if (!method_exists(__CLASS__, $load_method)) {
         throw new UnsupportedFormatException('Unsupported configuration format');
      }

      // Try and load file
      $this->data = $this->$load_method($path);

   }

   /**
    * Loads an INI file as an array
    *
    * @param  string $path
    *
    * @return array
    *
    * @throws ParseException If there is an error parsing the INI file
    */
   protected function loadIni($path)
   {
      $data = @parse_ini_file($path, true);

      if (!$data) {
         $error = error_get_last();
         throw new ParseException($error);
      }

      return $data;
   }

   /**
    * Gets a configuration setting using a simple or nested key.
    * Nested keys are similar to JSON paths that use the dot
    * dot notation.
    *
    * @param  string $key
    * @param  mixed  $default
    *
    * @return mixed
    */
   public function get($key, $default = null) {

      // Check if already cached
      if (isset($this->cache[$key])) {
         return $this->cache[$key];
      }

      $segs = explode('.', $key);
      $root = $this->data;

      // nested case
      foreach ($segs as $part) {
         if (isset($root[$part])){
            $root = $root[$part];
            continue;
         }
         else {
            $root = $default;
            break;
         }
      }

      // whatever we have is what we needed
      return ($this->cache[$key] = $root);
   }

   /**
    * Function for setting configuration values, using
    * either simple or nested keys.
    *
    * @param  string $key
    * @param  mixed  $value
    *
    * @return void
    */
   public function set($key, $value) {

      $segs = explode('.', $key);
      $root = &$this->data;

      // Look for the key, creating nested keys if needed
      while ($part = array_shift($segs)) {
         if (!isset($root[$part]) && count($segs)) {
            $root[$part] = array();
         }
         $root = &$root[$part];
      }

      // Assign value at target node
      $this->cache[$key] = $root = $value;
   }

   /**
    * ArrayAccess Methods
    */

   /**
    * Gets a value using the offset as a key
    *
    * @param  string $offset
    *
    * @return mixed
    */
   public function offsetGet($offset)
   {
      return $this->get($offset);
   }

   /**
    * Checks if a key exists
    *
    * @param  string $offset
    *
    * @return bool
    */
   public function offsetExists($offset)
   {
      return !is_null($this->get($offset));
   }

   /**
    * Sets a value using the offset as a key
    *
    * @param  string $offset
    * @param  mixed  $value
    *
    * @return void
    */
   public function offsetSet($offset, $value)
   {
      $this->set($offset, $value);
   }

   /**
    * Deletes a key and its value
    *
    * @param  string $offset
    *
    * @return void
    */
   public function offsetUnset($offset)
   {
      $this->set($offset, NULL);
   }


}
