<?php

namespace CWD\Query\QueryFilter;

use CWD\Query\SearchQuery;

/**
 * Base class for altering parts of the main SQL query for WP_Query::get_posts()
 * 
 * @author ccollier
 *
 */
abstract class QueryFilter {
    
    /**
     * Set any of these to true in child class to activate associated filter
     */
    protected $filter_join_clause = false;
    protected $filter_where_clause = false;
    protected $filter_orderby_clause = false;
    protected $filter_groupby_clause = false;
    protected $filter_search = false;
   
    /**
     * Conditionally adds filters for query clauses
     * Override callback methods in child class to alter query SQL 
     * 
     * @param SearchQuery $SearchQuery
     */
    function __construct( SearchQuery $SearchQuery ) {
        $this->SearchQuery = $SearchQuery;
        
        if ( $this->filter_join_clause ) 
            add_filter( 'posts_join', [$this, 'joinClause'] );
        if ( $this->filter_where_clause ) 
            add_filter( 'posts_where', [$this, 'whereClause'] );
        if ( $this->filter_orderby_clause ) 
            add_filter( 'posts_orderby', [$this, 'orderByClause'] );
        if ( $this->filter_groupby_clause ) 
            add_filter( 'posts_groupby', [$this, 'groupByClause'] );
        if ( $this->filter_search ) 
            add_filter( 'posts_search', [$this, 'search'] );
    }
    
    /**
     * filter: posts_join
     * 
     * @param string $join
     * @return string
     */
    function joinClause( $join ) {
        return $join;
    }
    
    /**
     * filter: posts_where
     *
     * @param string $where
     * @return string
     */
    function whereClause( $where ) {
        return $where;
    }
    
    /**
     * filter: posts_orderby
     *
     * @param string $orderby
     * @return string
     */
    function orderbyByClause( $orderby ) {
        return $orderby;
    }
    
    /**
     * filter: posts_groupby
     *
     * @param string $groupby
     * @return string
     */
    function groupByClause( $groupby ) {
        return $groupby;
    }
    
    /**
     * filter: posts_search
     *
     * @param string $search
     * @return string
     */
    function search( $search ) {
        return $search;
    }
    
}