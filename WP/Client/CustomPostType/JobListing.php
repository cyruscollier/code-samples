<?php 

namespace Client\CustomPostType;

use CWD\CustomPostType\CustomPostType;

/**
 * Job listing post type with location region taxonomy
 * 
 * @author ccollier
 *
 */
class JobListing extends CustomPostType {
    
    const POST_TYPE = 'job_listing';
    const POST_TAXONOMY = 'location_region';
    
    public $employment_type;
    public $adp_link;
    public $featured;
    
    protected $locations = [];
    
    /**
     * Get all location terms in list, with parents
     * @return string
     */
    function getLocations() {
    	if ( empty( $this->locations ) ) {
    		$regions = get_the_terms( $this->post, self::POST_TAXONOMY );
    		$regions_map = [];
    		foreach( $regions as $region ) {
    			$regions_map[$region->term_id] = $region;
    		}
    		foreach( $regions_map as $id => $region ) {
    			$location = $region->name;
    			if ( $region->parent ) {
    				$parent = isset( $regions_map[$region->parent] ) ?
    					$regions_map[$region->parent] :
    					get_term( $region->parent, self::POST_TAXONOMY );
    				if ( $parent )
    					$location .= ', ' . $parent->name;
    			} else {
    				continue;
    			}
    			$this->locations[] = $location;
    		}
    	}
    	return implode( '; ', $this->locations );
    }
    
    /**
     * Shortcode for showing featured job listing using template part
     * 
     * 
     * @param array $atts
     * @param string $content
     * 
     * @return string
     */
    static function shortcodeFeaturedJobListings( $atts, $content = null ) {
    	global $job_listings;
    	extract( shortcode_atts( array(
    		'count' => '8'
    	), $atts ) );
    	$args = array( 
    		self::POST_TAXONOMY => $category,
    		'meta_key' => 'featured',
    		'meta_value' => '1',
    		'posts_per_page' => $count
    	);
    	$job_listings = self::getPosts( $args );
    	ob_start();
    	get_template_part( 'content/featured-job-listings' );
    	return ob_get_clean();
    }
}