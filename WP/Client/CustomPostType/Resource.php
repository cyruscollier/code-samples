<?php 

namespace Client\CustomPostType;

use CWD\CustomPostType\CustomPostType;

/**
 * Generic resource post type with resource category taxonomy
 * 
 * @author ccollier
 *
 */
class Resource extends CustomPostType {
    
    const POST_TYPE = 'resource';
    const POST_TAXONOMY = 'resource_category';
    
    protected $video_id;
    protected $file_url;
    
    public $file_upload;
    public $web_link;
    public $alt_title;
    
    /**
     * Wrapper for attachment url
     */
	function getResourceLink() {
		if ( $this->file_upload ) {
		    if ( is_null( $this->file_url ) ) {
		        $this->file_url = wp_get_attachment_url( $this->file_upload );
		    }
		    return $this->file_url;
		}
		return $this->web_link;
	}
    
	/**
	 * Get alternate title if set
	 */
	function getTitle() {
		if ( $this->alt_title )
			return apply_filters( 'the_title', $this->alt_title );
		return $this->getAltTitle();
	}
	
	/**
	 * Original post title
	 */
	function getAltTitle() {
		return apply_filters( 'the_title', $this->post->post_title );
	}
		
	/**
	 * Shortcode for displaying list of resources using template part
	 * @param array $atts
	 * @param string $content
	 * 
	 * @return string
	 */
	static function shortcodeResourcesLoop( $atts, $content = null ) {
		global $resources, $resource_category;
		extract( shortcode_atts( array(
			'category' => '',
			'limit' => '-1',
			'showtitle' => '1'
		), $atts ) );
		$args = array( 
		    self::POST_TAXONOMY => $category,
		    'posts_per_page' => (int) $limit
		);
		if ( !empty( $showtitle ) ) $resource_category = get_term_by( 'slug', $category, self::POST_TAXONOMY );
		$resources = self::getPosts( $args );
		ob_start();
		get_template_part( 'content/resources' );
		return ob_get_clean();
	}
}