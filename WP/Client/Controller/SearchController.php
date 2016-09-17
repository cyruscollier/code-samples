<?php

namespace Client\Controller;

use CWD\Query\QueryFilter\TermsQueryFilter;
use CWD\Query\SearchQuery;
use WP_Query;


/**
 * Controller for building up custom search query
 * 
 * @author ccollier
 *
 */
class SearchController {

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
		add_action( 'pre_get_posts', [$this, 'setupSearch'] );
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
	 * Builds query and initializes term query filter
	 * @param WP_Query $wp_query
	 */
	function setupSearch( WP_Query $wp_query ) {
	    $this->setQueryVarsForGetPosts( $wp_query );
	    if ( !empty( $this->SearchQuery->s ) ) {
	        $TermsQueryFilter = new TermsQueryFilter( $this->SearchQuery );
	        $TermsQueryFilter->setTaxonomies( 'composition_tag' );
	    }
	}
	
	/**
	 * Sets meta_query and tax_query
	 * 
	 * @param WP_Query $wp_query
	 */
	protected function setQueryVarsForGetPosts ( WP_Query $wp_query ) {
		if ( !$wp_query->is_search() ) return;
		$s = $this->SearchQuery;
		$MetaQueryBuilder = new MetaQueryBuilder();
		$MetaQueryBuilder->add( 'composer', $s->composer_id )
						 ->addRange( 'end_year', $s->date_from, $s->date_to )
						 ->addRange( 'duration', $s->duration_from, $s->duration_to );
		$wp_query->set( 'meta_query', $MetaQueryBuilder->build() );
		$TaxonomyQueryBuilder = new TaxonomyQueryBuilder();
		$TaxonomyQueryBuilder->add( 'difficulty_grade', $s->difficulty_filter ); 
		if ( $available = $s->availability_filter ) {
		    $TaxonomyQueryBuilder->add( 'availability', ['available-for-purchase','available-for-rental','contact-composer'] ); 
		}
		$wp_query->set( 'tax_query', $TaxonomyQueryBuilder->build() );
				 
	}

}