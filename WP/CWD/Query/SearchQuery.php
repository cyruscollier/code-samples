<?php

namespace CWD\Query;

/**
 * Convenience model wrapper for URL query / $_GET array
 * 
 * @author ccollier
 *
 */
class SearchQuery {
    
    /**
     * Default orderby value when used for sorting
     * 
     * @var string
     */
    protected $orderby = 'title';
    
    /**
     * Default order value when used for sorting
     *
     * @var string
     */
    protected $order = 'ASC';
	
    /**
     * Dyamically set properties based on query array
     * 
     * @param array $query
     */
	function __construct( $query ) {
	    if ( isset( $query['order'] ) && !in_array( $query['order'], ['ASC','DESC'] ) ) {
	        unset( $query['order'] );
	    }
		foreach ( $query as $key => $value ) {
			$this->$key = $value;
		}
	}
	
	/**
	 * Original query array, plus sorting properties
	 * 
	 * @return array
	 */
	function getAll() {
		return get_defined_vars( $this );
	}
	
	
	/**
	 * Returns empty value for query value not present in original array
	 * 
	 * @param string $name
	 * 
	 * @return string
	 */
	function __get( $name ) {
		if ( !empty( $this->$name ) ) return $this->$name;
		return '';
	}
}