<?php 

namespace Client\CustomPostType;

use CWD\CustomPostType\CustomPostType;
use WP_Post;

/**
 * Specific resource for 'video' resource_category, with Youtube rendering support
 * 
 * @author ccollier
 *
 */
class Video extends Resource {
    
    const POST_TERM_FILTER = 'videos';
    const POST_GLOBAL_VAR = 'video';
    const VIDEO_EMBED_WIDTH = 640;
    const VIDEO_EMBED_HEIGHT = 360;
    
    protected $video_id;
    
    public $web_link;
    
    /**
     * Additional check if post has 'video' resource_category term
     * 
     * @param WP_Post $post
     * 
     * @return boolean
     */
    static function checkPostType( $post ) {
        return parent::checkPostType( $post ) && has_term( self::POST_TERM_FILTER, self::POST_TAXONOMY, $post );
    }
    
    /**
     * Youtube video id derived from URL
     * 
     * @return string|false
     */
    function getVideoId() {
        if ( is_null( $this->video_id ) ) {
            if ( false !== strpos( $this->web_link, 'youtube.com/watch?v=' ) ) {
                $video_parts = explode( 'youtube.com/watch?v=', $this->web_link );
                $video_id_parts = explode( '&', $video_parts[1] );
                $this->video_id = $video_id_parts[0];
            } else {
                $this->video_id = false;
            }
        }
        return $this->video_id;
    }
    
    /**
     * Use video template part
     */
    function render( $echo = false, $template = false ) {
    	return parent::render( $echo, 'content/video' );
    }
    
    /**
     * Use video id for embed HTML
     * 
     * @return string
     */
    function getVideoEmbed() {
        $video_id = $this->getVideoId();
        return self::getVideoEmbedHTML( $video_id );
    }
    
    /**
     * Youtube link
     */
    function getResourceLink() {
        $video_id = $this->getVideoId();
        return '//www.youtube.com/embed/' . $video_id;
    }
    
    /**
     * Youtube iframe embed
     * 
     * @param string $video_id
     * @return string
     */
    static function getVideoEmbedHTML( $video_id = false ) {
        $video_link = $video_id ? '//www.youtube.com/embed/' . $video_id : '';
        return sprintf( '<iframe width="%d" height="%d" src="%s" frameborder="0" allowfullscreen></iframe>',
            self::VIDEO_EMBED_WIDTH,
            self::VIDEO_EMBED_HEIGHT,
            $video_link
        );
    }
    
    /**
     * Overrides post thumbnail with Youtube thumbnail
     * 
     * @return string
     */
    function getThumbnail( $size = 'medium', $atts = [] ) {
        $atts = wp_merge_args( $atts, static::$default_thumbnail_atts );
    	return sprintf( 
    		'<img src="%s" alt="" class="%s" />',  
    		$this->getVideoThumbnail(), $atts['class']
    	);
    }
    
    /**
     * Youtube thumbnail
     * @return string
     */
    function getVideoThumbnail() {
        return sprintf( 'http://img.youtube.com/vi/%s/0.jpg', $this->getVideoId() );
    }
    
    /**
     * Term archive link instead of post type archive link
     */
    static function getArchiveLink() {
        return get_term_link( self::POST_TERM_FILTER, self::POST_TAXONOMY );
    }

}