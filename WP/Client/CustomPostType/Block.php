<?php 

namespace Client\CustomPostType;

use CWD\CustomPostType\CustomPostType;

/**
 * Modular content block with lots of customizable style and layout settings
 * 
 * @author ccollier
 *
 */
class Block extends CustomPostType {
    
    const POST_TYPE = 'block';
    const DEFAULT_COLOR_BACKGROUND = 'default';
    const DEFAULT_COLOR_ELEMENT = 'primary';
    const DEFAULT_TEMPLATE = 'basic';
    const DEFAULT_FEATURED_IMAGE_POSITION = 'right';
    const MAX_CONTENT_WIDTH = 12;
        
    protected $color_background = self::DEFAULT_COLOR_BACKGROUND;
    protected $color_element = self::DEFAULT_COLOR_ELEMENT;
    public $content_text_uses_element_color = false;
    public $template = self::DEFAULT_TEMPLATE;
    public $featured_image_position = self::DEFAULT_FEATURED_IMAGE_POSITION;
    public $featured_image_gutter_inset = false;
    public $hide_title = false;
    public $content_width = 0;
    public $show_featured_image_on_mobile = false;
    
    public $in_block = false;
    public $is_top_banner = false;
    public $in_panel = false;
    
    /**
     * Check if instance post is the top banner block
     * 
     * @param int $post_id
     */
    function __construct( $post_id ) {
        parent::__construct( $post_id );
        if ( $this->post ) {
            $banner_block = get_post_meta( get_the_ID(), 'top_banner_block', true );
            if ( $banner_block && $banner_block['ID'] == $this->post->ID ) $this->is_top_banner = true;
        }
    }    
    
    /**
     * Fetch color term if applicable
     * 
     * @param string $key
     * @param string $val
     * 
     * @return string
     */
    protected function getProperty( $key, $val ) {
        $val = parent::getProperty( $key, $val );
        return $this->isColorField( $key ) ? get_term_field( 'slug', $val, 'color') : $val;
    }
    
    /**
     * Add post edit link to block output
     * 
     * @param bool $echo
     * @param string $template
     */
    function render( $echo = true, $template = '' ) {
        $this->in_block = true;
        $output = parent::render( false, "blocks/$this->template" );
        if ( !empty( $output ) && $url = get_edit_post_link( $this->post->ID ) ) {
        	$output .= '<a class="post-edit-link" href="' . $url . '">Edit Block</a>';
        }
        $this->in_block = false;
        if ( $echo ) echo $output;
        else return $output;
    }
    
    /**
     * @return string
     */
    function getBackgroundColor() {
        return $this->color_background;
    }
    
    /**
     * Return element color only if "light" color
     * 
     * @return string
     */
    function getElementColor() {
        return self::isLightColor( $this->color_background ) ? $this->color_element : self::DEFAULT_COLOR_BACKGROUND;
    }
    
    /**
     * Return element color only if "light" color
     *
     * @return string
     */
    function getPanelElementColor() {
        return self::isLightColor( $this->color_background ) ? $this->color_element : $this->color_background;
    }
    
    /**
     * Return panel element color if within panel block
     * 
     * @return string
     */
    function getButtonElementColor() {
        if ( $this->in_panel ) return $this->getPanelElementColor();
        return $this->getElementColor();
    }
    
    /**
     * Additional thumbnail classes
     */
    function getThumbnail( $size = 'full' ) {
        $image_atts = ['class' => 'img-responsive'];
        if ( !$this->show_featured_image_on_mobile ) $image_atts['class'] .= ' hidden-xs';
        if ( $this->featured_image_gutter_inset ) {
            $image_atts['class'] .= " gutter-inset-$this->featured_image_gutter_inset";
        }
        return parent::getThumbnail( $size, $image_atts );
    }
    
    /**
     * Return default if same color as background
     * 
     * @return string
     */
    function getPostElementColor() {
        return $this->color_background != self::DEFAULT_COLOR_BACKGROUND ?
            $this->color_background :
            self::DEFAULT_COLOR_ELEMENT;
    }
    
    /**
     * Checks if currently inside block template
     *
     * @return boolean
     */
    static function inBlock() {
        global $block;
        return is_a( $block, __CLASS__ ) && $block->in_block;
    }
    
    /**
     * Checks if requested field name matches color prefix
     *
     * @param string $key
     * @param string $prefix
     *
     * @return boolean
     */
    static function isColorField( $key, $prefix = '' ) {
        $field = str_replace( $prefix, '', $key );
        return 0 === strpos( $field, 'color_' );
    }
    
    /**
     * Checks if requested color name matches 'light'
     *
     * @param string $color
     *
     * @return bool
     */
    static function isLightColor( $color ) {
        return $color == self::DEFAULT_COLOR_BACKGROUND || false !== strpos( $color, 'light' );
    }
    
}
