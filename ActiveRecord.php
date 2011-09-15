<?php

/**
 * Very hacked version of get_called_class() for PHP 5.2.x
 * Uses debug_backtrace() to determine where the function was called
 * from and gets the function name from the file itself
 *
 * Using this hacked function will have definate performance implications
 *
 * @see http://php.net/manual/en/function.get-called-class.php
 *
 * @return string
 */
if (!function_exists('get_called_class')) {
	function get_called_class() {
		static $cache = array();
		foreach (debug_backtrace() as $backtrace) {
			// handle call_user_func and call_user_func_array static calls
			if (isset($backtrace["function"]) and ($backtrace["function"] == "call_user_func" or $backtrace["function"] == "call_user_func_array")) {
				$class = $backtrace["args"][0];
				if (is_string($class)) {
					$found = strpos($class, '::');
					if ($found !== false) {
						return substr($class, 0, $found);
					}
				}
				if (is_array($class) and is_string($class[0])) {
					return $class[0];
				}
			}
			// handle explict static calls, by searching the source file (caching the results)
			if (isset($backtrace["file"]) and isset($backtrace["type"]) and $backtrace["type"] == "::") {
				if (!isset($cache[$backtrace["file"].$backtrace["line"]])) {
					$file = file_get_contents($backtrace["file"]);
					$file = split("\n", $file);
					for($line = $backtrace["line"] - 1; $line > 0; $line--) {
						preg_match("/(?P<class>\w+)::(.*)/", trim($file[$line]), $matches);
						if (isset($matches["class"])) {
							return $cache[$backtrace["file"].$backtrace["line"]] = $matches["class"];
						}
					}
					throw new Exception("Could not find class in get_called_class()");
				}
				return $cache[$backtrace["file"].$backtrace["line"]];
			}
		}
		throw new Exception("The function get_called_class() must be called from a static context.");
	}
}



if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50200)
	die('PHP ActiveRecord requires PHP 5.2 or higher');

define('PHP_ACTIVERECORD_VERSION_ID','1.0');

if (!defined('PHP_ACTIVERECORD_AUTOLOAD_PREPEND'))
	define('PHP_ACTIVERECORD_AUTOLOAD_PREPEND',true);

require 'lib/Singleton.php';
require 'lib/Config.php';
require 'lib/Utils.php';
require 'lib/DateTime.php';
require 'lib/Model.php';
require 'lib/Table.php';
require 'lib/ConnectionManager.php';
require 'lib/Connection.php';
require 'lib/SQLBuilder.php';
require 'lib/Reflections.php';
require 'lib/Inflector.php';
require 'lib/CallBack.php';
require 'lib/Exceptions.php';
require 'lib/Cache.php';

if (!defined('PHP_ACTIVERECORD_AUTOLOAD_DISABLE'))
	spl_autoload_register('activerecord_autoload',false,PHP_ACTIVERECORD_AUTOLOAD_PREPEND);

function activerecord_autoload($class_name)
{
	$path = ActiveRecord_Config::instance()->get_model_directory();
	$root = realpath(isset($path) ? $path : '.');

	if (($namespaces = ActiveRecord_get_namespaces($class_name)))
	{
		$class_name = array_pop($namespaces);
		$directories = array();

		foreach ($namespaces as $directory)
			$directories[] = $directory;

		$root .= DIRECTORY_SEPARATOR . implode($directories, DIRECTORY_SEPARATOR);
	}

	$file = "$root/$class_name.php";

	if (file_exists($file))
		require $file;
}
?>
