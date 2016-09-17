<?php

namespace CWD\Query\QueryFilter;

use CWD\Query\SearchQuery;

/**
 * Find posts with terms based on search keywords
 * 
 * @author ccollier
 *
 */
class TermsQueryFilter extends QueryFilter {

    /**
     * Placeholder for each term search clause
     */
    const PLACEHOLDER = '@terms_search@';
    
    /**
     * List of taxonomies to search in
     * 
     * @var array
     */
    protected $taxonomies = [];
    
    /**
     * Search keywords string
     * 
     * @var string
     */
    protected $search = '';
    
    /**
     * List of search stopwords to ignore
     * 
     * @var array
     */
    protected $stopwords = [];
    
    protected $filter_join_clause = true;
    protected $filter_groupby_clause = true;
    protected $filter_search = true;
    
    /**
     * Set search keywords before adding filters
     * 
     * @param SearchQuery $SearchQuery
     */
    function __construct( SearchQuery $SearchQuery ) {
        $this->search = $SearchQuery->s;
        $this->addHooks();
    }
    
    /**
     * Sets taxonomy or taxonomies to search
     * 
     * @param string|array $taxonomies
     */
    function setTaxonomies( $taxonomies = '' ) {
        $this->taxonomies = (array) $taxonomies;
    }
    
    /**
     * Adds filter to load WP standard stopwords
     */
    function addHooks() {
        parent::addHooks();
        add_filter( 'wp_search_stopwords', [$this, 'setStopWords'] );
    }
    
    /**
     * Join terms/taxonomy/relationship tables to include all assigned terms matching allowed taxonomies
     * filter: posts_join
     *
     * @param string $join
     * @return string
     */
    function joinClause( $join ) {
        global $wpdb;
        $taxonomy_list = implode( "','", $this->taxonomies );
        $join .= " 
            LEFT JOIN $wpdb->term_relationships tr ON $wpdb->posts.ID = tr.object_id
            INNER JOIN $wpdb->term_taxonomy tt ON 
                (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy IN ('$taxonomy_list'))
            INNER JOIN $wpdb->terms t ON tt.term_id = t.term_id
        ";
        return $join;
    }
    
    /**
     * Group by post ID to prevent duplicates from including terms
     * filter: posts_groupby
     *
     * @param string $groupby
     * @return string
     */
    function groupByClause( $groupby ) {
        global $wpdb;
        $groupby_id = "{$wpdb->posts}.ID";
        if(strpos($groupby, $groupby_id) !== false) return $groupby;
        if(!strlen(trim($groupby))) return $groupby_id;
        return $groupby.", ".$groupby_id;
    }
    
    /**
     * Alter WHERE clause to match term names from keywords
     * filter: posts_search
     *
     * @param string $search
     * @return string
     */
    function search( $search ) {
        global $wpdb;
        $search_where = str_replace( ['(((',')))'], ['((((',')))'.self::PLACEHOLDER.')'], $search );
        $terms_search = '';
        foreach ( $this->parseSearchWords( $this->search ) as $search_word ) {
            $terms_search .= " OR t.name LIKE '%{$search_word}%'";
        }
        return str_replace( self::PLACEHOLDER, $terms_search, $search_where );
    }
    
    /**
     * Sets list of stopwords used in WP
     * 
     * @param array $stopwords
     * @return array
     */
    function setStopWords( $stopwords ) {
        $this->stopwords = $stopwords;
        return $stopwords;
    }
    
    /**
     * Splits keyword string into filtered, searchable words
     * 
     * @param string $search
     * @return array
     */
    protected function parseSearchWords( $search ) {
		$search_words = array_map( [$this, 'normalizeSearchWord'], explode( ' ', $search ) );
		return array_diff( array_filter( $search_words ), $this->stopwords );
	}
	
	/**
	 * Only return word longer than 2 characters 
	 * 
	 * @param string $word
	 * @return boolean|string
	 */
	protected function normalizeSearchWord( $word ) {
	    if ( strlen( $word ) < 3 ) return false;
	    return trim( strtolower( $word ) );
	}
    
}