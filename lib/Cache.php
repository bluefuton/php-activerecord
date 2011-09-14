<?php

/**
 * Cache::get('the-cache-key', function() {
 *	 # this gets executed when cache is stale
 *	 return "your cacheable datas";
 * });
 */
class ActiveRecord_Cache
{
	static $adapter = null;
	static $options = array();

	/**
	 * Initializes the cache.
	 *
	 * With the $options array it's possible to define:
	 * - expiration of the key, (time in seconds)
	 * - a namespace for the key
	 *
	 * this last one is useful in the case two applications use
	 * a shared key/store (for instance a shared Memcached db)
	 *
	 * Ex:
	 * $cfg_ar = ActiveRecord_Config::instance();
	 * $cfg_ar->set_cache('memcache://localhost:11211',array('namespace' => 'my_cool_app',
	 *																											 'expire'		 => 120
	 *																											 ));
	 *
	 * In the example above all the keys expire after 120 seconds, and the
	 * all get a postfix 'my_cool_app'.
	 *
	 * (Note: expiring needs to be implemented in your cache store.)
	 *
	 * @param string $url URL to your cache server
	 * @param array $options Specify additional options
	 */
	public static function initialize($url, $options=array())
	{
		if ($url)
		{
			$url = parse_url($url);
			$file = ucwords(ActiveRecord_Inflector::instance()->camelize($url['scheme']));
			$class = "ActiveRecord_$file";
			require_once __DIR__ . "/cache/$file.php";

			eval(get_called_class() . '::$adapter = new ' . $class . "('$url')");
		}
		else
			eval(get_called_class() . '::$adapter = null');

		eval(get_called_class() . '::$options = array_merge(array(\'expire\' => 30, \'namespace\' => \'\'),$options)');
	}

	public static function flush()
	{
		if (eval('return ' . get_called_class() . '::$adapter'))
			eval(get_called_class() . '::$adapter->flush()');
	}

	public static function get($key, $closure)
	{
		$key = call_user_func(array(get_called_class(), 'get_namespace')) . $key;

		if (!eval('return ' . get_called_class() . '::$adapter;'))
			return $closure();

		$static_adapter = eval('return ' . get_called_class() . '::$adapter');
		if (!($value = $static_adapter->read($key))) {
			$static_adapter->write($key,($value = $closure()), eval('return ' . get_called_class() . '::$options[\'expire\']'));
		}

		return $value;
	}

	private static function get_namespace()
	{
		$static_options = eval('return ' . get_called_class() . '::$options;');
		return (isset($static_options['namespace']) && strlen($static_options['namespace']) > 0) ? ($static_options['namespace'] . "::") : "";
	}
}
?>
