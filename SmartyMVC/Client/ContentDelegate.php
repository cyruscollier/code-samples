<?php 

namespace Client;

use Client\Model;

/**
 * Delegate methods related to content model
 * 
 * @author ccollier
 *
 */
interface ContentDelegate {
    
    /**
     * Gets content component from DB
     *
     * @param string $name
     * @param string $type
     *
     * @return Model\ContentComponent
     */
    function getContentComponent( $name, $type );
}