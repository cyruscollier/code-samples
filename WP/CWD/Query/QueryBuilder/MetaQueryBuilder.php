<?php

namespace CWD\Query\QueryBuilder;

/**
 * Builds query clauses for 'meta_query' variable of WP_Query
 * 
 * @author ccollier
 *
 */
class MetaQueryBuilder extends QueryBuilder {

    /**
     * Adds clause
     *
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string $type
     * @return $this
     */
	function add( $key, $value, $compare = '=', $type = 'CHAR' ) {
		return $this->addClause( compact( 'key', 'value', 'compare', 'type' ) );
	}
	
	/**
	 * Public fluent wrapper for adding range clause
	 *
	 * @param string $key
	 * @param int $lower_value
	 * @param int $upper_value
	 * @return $this
	 */
	function addRange( $key, $lower_value, $upper_value ) {
		return $this->add( $key, [$lower_value, $upper_value], 'BETWEEN', 'NUMERIC' );
	}
	
    /**
	 * Gets value inside of clause
	 * 
	 * @param array $clause
	 * @return mixed
	 */	
	 protected function getClauseValue( array $clause ) {
		return $clause['value'];
	}
	
	
}