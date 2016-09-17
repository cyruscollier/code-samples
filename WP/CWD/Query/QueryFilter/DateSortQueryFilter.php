<?php

namespace CWD\Query\QueryFilter;

/**
 * Sort by date stored as post meta field
 * 
 * @author ccollier
 *
 */
class DateSortQueryFilter extends SortQueryFilter {
	
    /**
     * Join postmeta table on orderby meta_key
     * filter: posts_join
     *
     * @param string $join
     * @return string
     */
	function joinClause( $join ) {
	    global $wpdb;
	    $join .= " INNER JOIN $wpdb->postmeta dm ON (dm.post_id = $wpdb->posts.ID AND dm.meta_key = '$this->orderby') ";
	    return $join;
	}
	
	/**
	 * Order by meta_value cast as number, post title
	 * filter: posts_orderby
	 *
	 * @param string $orderby
	 * @return string
	 */
	function orderByClause( $orderby ) {
	    global $wpdb;
	    return "dm.meta_value+0 {$this->order}, $wpdb->posts.post_title ASC";
	}
}