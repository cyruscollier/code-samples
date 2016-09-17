<?php

namespace CWD\CustomPostType;

use WP_Post;

/**
 * Base wrapper class for WP_Post object and metadata.
 * Use to create child classes for specific custom post types 
 * and extend functionality specific to those post types
 * 
 * @author ccollier
 *
 */
abstract class CustomPostType {
    
    /**
     * Must be set in subclass
     */
	const POST_TYPE = null;
	
	/**
	 * Set in subclass if automatic global instance name different than post type name
	 */
	const POST_GLOBAL_VAR = null;
	
	static $default_thumbnail_atts = ['class' => 'img-responsive img-thumbnail'];
	
	/**
	 * Post instance
	 * 
	 * @var WP_Post
	 */
    public $post;
    
    /**
     * Sets post instance and post metadata
     * 
     * @param int $post_id
     */
    function __construct( $post_id ) {
        if ( $post_id ) {
            $this->post = get_post( $post_id );
            if ( !static::check_post_type( $this->post ) ) {
                $this->post = new WP_Error( 'incorrect post type used in initializing ' . get_called_class() );
                return;
            }
            $meta = get_post_custom( $this->post->ID );
            foreach ( $meta as $key => $values ) {
                if ( property_exists( $this, $key ) && !empty( $values[0] ) ) {
                    $val = $values[0];
                    $this->{$key} = $this->getProperty( $key, $val );
                }
            }
        }
    }
    
    /**
     * For assigning metadata value, may be overridden in child class to filter value
     * 
     * @param string $key
     * @param string $val
     * 
     * @return string
     */
    protected function getProperty( $key, $val ) {
       return $val; 
    }
    
    /**
     * Convenience wrapper to get filtered post content
     */
    function getContent() {
        return apply_filters( 'the_content', $this->post->post_content );
    }
    
    /**
     * Convenience wrapper to get filtered post title
     */
    function getTitle() {
        return apply_filters( 'the_title', $this->post->post_title );
    }
    
    /**
     * Convenience wrapper for post url
     */
    function getLink() {
        return get_permalink( $this->post->ID );
    }
    
    /**
	 * Convenience wrapper for post thumbnail, adds bootstrap image classes
	 * 
	 * @return string
	 */
	function getThumbnail( $size = 'medium', $atts = [] ) {
	    $atts = wp_parse_args( $atts, static::$default_thumbnail_atts );
		return get_the_post_thumbnail( $this->post->ID, $size, $atts );
	}
    
    /**
     * Convenience wrapper for post type archive link
     */
    function getArchiveLink() {
        return get_post_type_archive_link( static::POST_TYPE );
    }
    
    /**
     * Sets up and calls template part associated with post type
     * @param bool $echo
     * @param string $template
     */
    function render( $echo = true, $template = '' ) {
        if ( !( $this->post && 'publish' == $this->post->post_status ) ) return;
        $global = !is_null( static::POST_GLOBAL_VAR ) ? static::POST_GLOBAL_VAR : static::POST_TYPE;
        if ( !$template ) $template = 'content/' . static::POST_TYPE;
        $GLOBALS[$global] = $this;
        ob_start();
    	get_template_part( $template );
    	$output = ob_get_clean();
    	if ( $echo ) echo $output;
    	else return $output;
    }
    
    /**
     * Checks class matches requested post instance
     * 
     * @param WP_Post $post
     * @return boolean
     */
    static function checkPostType( $post ) {
        return $post->post_type == static::POST_TYPE;
    }
    
    /**
     * Wrapper for get_posts that sets up array of CustomPostType child instances
     * 
     * @param array $args
     * @return static[]
     */
    static function getPosts( $args = array() ) {
        $defaults = array(
            'posts_per_page' => -1, 
            'post_type' => static::POST_TYPE
        );
        $r = wp_parse_args( $args, $defaults );
        $get_posts = new WP_Query;
        $posts = get_posts( $r );
        $class = get_called_class();
        $objects = array();
        foreach( $posts as $post ) {
            $objects[] = new $class( $post->ID );
        }
        return $objects;
    }
    
    /**
     * Shortcode wrapper for CustomPostType::render(), post slug in attributes translated to post ID
     * 
     * @param array $atts
     * @param string $content
     * @return string
     */
    static function shortcode($atts, $content = null ) {
    	extract( shortcode_atts( array(
    		'slug' => '',
    	), $atts ) );
    	$post = get_page_by_path( $slug, ARRAY_A, static::POST_TYPE );
    	$class = get_called_class();
    	if ( $post && isset( $post['ID'] ) ) {
    		$cpt = new $class( $post['ID'] );
    		return $cpt->render( false );
    	} else {
    		return "<!-- $class not found for slug: $slug -->";
    	}
    }
}