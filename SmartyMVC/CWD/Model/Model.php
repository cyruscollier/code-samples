<?php 

namespace CWD\Model;

/**
 * Base class to store data as properties
 * 
 * @author ccollier
 *
 */
abstract class Model {
	
    /**
     * Optional metadata 
     * 
     * @var array
     */
	protected $data = [];
	
	/**
	 * Sets input array items as properties if present in subclass
	 * 
	 * @param array $data
	 */
	function __construct( $data = [] ) {
		foreach ( (array) $data as $name => $value ) {
			if ( property_exists( $this, $name ) )
				$this->$name = $value;
		}
	}
	
	/**
	 * Look in metadata for requested property
	 * 
	 * @param string $name
	 * @return mixed|null
	 */
	function __get( $name ) {
		if ( array_key_exists( $name, $this->data ) )
			return $this->data[$name];
		return null;
	}
	
	/**
	 * Attempt to set existing metadata item
	 * @param string $name
	 * @param mixed $value
	 */
	function __set( $name, $value ) {
		if ( array_key_exists( $name, $this->data ) ) {
			$this->data[$name] = $value;
		}
	}
}
