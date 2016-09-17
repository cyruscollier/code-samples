<?php 

namespace Client\CustomPostType;

use CWD\CustomPostType\CustomPostType;

/**
 * Location post type with location region taxonomy
 * 
 * @author ccollier
 *
 */
class Location extends CustomPostType {
    
    const POST_TYPE = 'location';
    const POST_TAXONOMY = 'location_region';
    
    public $address;
    public $telephone;
    public $fax;
    public $email;
    protected $map_coordinates;
    
    /**
     * All location fields, formatted
     * 
     * @return string
     */
    function getFieldsContent() {
        $fields_content = array_filter( array(
            $this->address,
            $this->telephone,
            $this->fax,
            '<a href="mailto:'.$this->email.'">'.$this->email.'</a>',
        ) );
        return apply_filters( 'the_content', implode( "\n", $fields_content ) );
    }
    
    /**
     * Map coordinates as comma-separated list
     * 
     * @return string
     */
    function getCoordinates() {
        return explode( ',', $this->map_coordinates );
    }
    
    /**
     * Generates css style based on coordinates
     * 
     * @return string
     */
    function getCoordinatesStyle() {
        $coord_parts = $this->getCoordinates();
        $left = !empty( $coord_parts[0] ) ? $coord_parts[0] : 0;
        $top = !empty( $coord_parts[1] ) ? $coord_parts[1] : 0;
        return "left:$left%;top:$top%;";
    }
    
    /**
     * Shortcode for displaying map of location using template part
     * 
     * @param array $atts
     * @param string $content
     * 
     * @return string
     */
    static function shortcodeLocationsMap( $atts, $content = null ) {
    	global $locations;
    	$locations = self::getPosts( array( 'orderby' => 'menu_order' ) );
    	ob_start();
    	get_template_part( 'content/locations-map' );
    	return ob_get_clean();
    }
    
}