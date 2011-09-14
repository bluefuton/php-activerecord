<?php
/**
 * @package ActiveRecord
 */

/**
 * Singleton to manage any and all database connections.
 *
 * @package ActiveRecord
 */
class ActiveRecord_ConnectionManager extends ActiveRecord_Singleton
{
	/**
	 * Array of {@link Connection} objects.
	 * @var array
	 */
	static private $connections = array();

	/**
	 * If $name is null then the default connection will be returned.
	 *
	 * @see Config
	 * @param string $name Optional name of a connection
	 * @return Connection
	 */
	public static function get_connection($name=null)
	{
        $config = ActiveRecord_Config::instance();
        $name = $name ? $name : $config->get_default_connection();

		if (!isset(self::$connections[$name]) || !self::$connections[$name]->connection)
            self::$connections[$name] = ActiveRecord_Connection::instance($config->get_connection($name));

		return self::$connections[$name];
	}

  /**
   * Drops the connection from the connection manager. Does not actually close it since there
   * is no close method in PDO.
   *
   * @param string $name Name of the connection to forget about
   */
	public static function drop_connection($name=null)
	{
		if (isset(self::$connections[$name]))
			unset(self::$connections[$name]);
	}
};
?>
