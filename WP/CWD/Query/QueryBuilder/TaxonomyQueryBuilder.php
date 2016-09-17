<?php

namespace CWD\Query\QueryBuilder;

/**
 * Builds query clauses for 'tax_query' variable of WP_Query
 *
 * @author ccollier
 *
 */
class TaxonomyQueryBuilder extends QueryBuilder {

    /**
     * Adds clause
     *
     * @param string $taxonomy
     * @param mixed $terms
     * @param string $operator
     * @param string $field
     * @return $this
     */
	function add( $taxonomy, $terms, $operator = 'IN', $field = 'slug' ) {
		return $this->addClause( compact( 'taxonomy', 'terms', 'operator', 'field' ) );
	}
	
	/**
	 * Gets value inside of clause
	 *
	 * @param array $clause
	 * @return mixed
	 */
	protected function getClauseValue( array $clause ) {
		return $clause['terms'];
	}
	
	
}