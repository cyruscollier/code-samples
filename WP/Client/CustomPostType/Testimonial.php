<?php 

namespace Client\CustomPostType;

use CWD\CustomPostType\CustomPostType;

/**
 * Testimonial post type with testimonial category taxonomy
 * 
 * @author ccollier
 *
 */
class Testimonial extends CustomPostType {
    
    const POST_TYPE = 'testimonial';
    const POST_TAXONOMY = 'testimonial_category';
    
    static $default_thumbnail_atts = ['class' => 'img-responsive img-rounded'];
    
    protected static $duplicates = array();

    /**
     * Format content with quotes
     * 
     * @return string
     */
    function getContent() {
    	$content = '&ldquo;' . $this->post->post_content . '&rdquo;';
    	return apply_filters( 'the_content', $content );
    }
    
    /**
     * Add current post to duplicates list
     * 
     * @param bool $echo
     * @param string $template
     * 
     * @return string
     */
    function render( $echo = false, $template = '' ) {
    	array_push( self::$duplicates, $this->post->ID );
    	return parent::render( $echo, $template );
    }
    
    /**
     * Shortcode for displaying testimonials using template part, skipping duplicates elsewhere on page
     * 
     * @param array $atts
     * @param string $content
     * 
     * @return string
     */
    static function shortcodeTestimonialsLoop( $atts, $content = null ) {
    	global $testimonials;
    	extract( shortcode_atts( array(
    		'category' => '',
    		'style' => 'list'
    	), $atts ) );
    	$args = array( 
    		self::POST_TAXONOMY => $category,
    		'post__not_in' => self::$duplicates
    	);
    	$testimonials = self::getPosts( $args );
    	ob_start();
    	get_template_part( 'content/testimonials', $style );
    	return ob_get_clean();
    }
}