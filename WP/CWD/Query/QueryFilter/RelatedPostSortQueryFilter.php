<?php

namespace CWD\Query\QueryFilter;

/**
 * Sort based on a related post title, where related post ID is present in a post meta field
 * 
 * @author ccollier
 *
 */
class RelatedPostSortQueryFilter extends SortQueryFilter {
	
    /**
     * Join postmeta on orderby meta_key and related posts tables
     * filter: posts_join
     *
     * @param string $join
     * @return string
     */
	function joinClause( $join ) {
	    global $wpdb;
	    $join .= " 
	        INNER JOIN $wpdb->postmeta sm ON (sm.post_id = $wpdb->posts.ID AND sm.meta_key = '$this->orderby')
	        INNER JOIN $wpdb->posts sp on sp.ID = sm.meta_value
	    ";
	    return $join;
	}
	
	/**
	 * Order by related post title, post title
	 * filter: posts_orderby
	 *
	 * @param string $orderby
	 * @return string
	 */
	function orderByClause( $orderby ) {
	    global $wpdb;
	    return "sp.post_title {$this->order}, $wpdb->posts.post_title ASC";
	}

}