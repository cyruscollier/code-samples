<?php

namespace Client;

use CWD\Query\QueryFilter\RelatedPostSortQueryFilter;
use CWD\Query\QueryFilter\DateSortQueryFilter;
use CWD\Query\SearchQuery;
use WP_Query;

/**
 * Controller to modify main query with sorting parameters
 * 
 * @author ccollier
 *
 */
class SortController {

    /**
     * Current search query
     * 
     * @var SearchQuery
     */
	protected $SearchQuery;

	/**
	 * Actions
	 */
	function __construct() {
		add_action( 'init', [$this, 'setSearchQuery'] );
		add_action( 'pre_get_posts', [$this, 'setupSort'], 11 );
	}
	
	/**
	 * Initializes search query instance
	 */
	function setSearchQuery() {
		global $SearchQuery;
		if ( !$SearchQuery ) $SearchQuery = new SearchQuery( $_GET );
		$this->SearchQuery = $SearchQuery;
	}
	
	/**
	 * Modify query with sort filters
	 * 
	 * @param WP_Query $wp_query
	 */
	function setupSort( WP_Query $wp_query ) {
	    if ( !in_array( $wp_query->get( 'post_type' ), ['composition','composer'] ) ) return;
	    if ( $this->SearchQuery->orderby == 'composer' ) {
    	    $SortQueryFilter = new RelatedPostSortQueryFilter( $this->SearchQuery );
    	    return;
    	}
    	if ( $this->SearchQuery->orderby == 'end_date' ) {
    	    $SortQueryFilter = new DateSortQueryFilter( $this->SearchQuery );
    	    return;
    	}
    	$wp_query->set( 'orderby', $this->SearchQuery->orderby );
    	$wp_query->set( 'order', $this->SearchQuery->order );
	}
	
}