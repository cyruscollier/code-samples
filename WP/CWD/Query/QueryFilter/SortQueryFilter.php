<?php

namespace CWD\Query\QueryFilter;

use CWD\Query\SearchQuery;

/**
 * Intermediate QueryFilter class used for query sorting
 * 
 * @author ccollier
 *
 */
abstract class SortQueryFilter extends QueryFilter {

	protected $orderby;
	protected $order;
	
	protected $filter_join_clause = true;
	protected $filter_orderby_clause = true;
	
	/**
	 * Adds SearchQuery orderby and order before adding filters
	 * 
	 * @param SearchQuery $SearchQuery
	 */
	function __construct( SearchQuery $SearchQuery ) {
	    $this->orderby = $SearchQuery->orderby;
	    $this->order = $SearchQuery->order;
	    $this->addHooks();
	}
}