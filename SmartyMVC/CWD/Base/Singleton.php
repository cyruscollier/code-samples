<?php 

namespace CWD\Base;

/**
 * Base class for creating singletons
 * 
 * @author ccollier
 *
 */
class Singleton {

	/**
	 * Holds list of instantiated singleton objects
	 * 
	 * @var array
	 */
	private static $instances = array();
	
	
	/**
	 * No public object instantiation
	 */
	protected function __construct() {}
	
	/**
	 * No public cloning
	 */
	protected function __clone() {}
	
	/**
	 * No object unserialization
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
	
	/**
	 * Creates or retrieves one object instance
	 * 
	 * @return static
	 */
	public static function getInstance() {
		$cls = get_called_class(); // late-static-bound class name
		if (!isset(self::$instances[$cls])) {
			self::$instances[$cls] = new static;
		}
		return self::$instances[$cls];
	}

}
