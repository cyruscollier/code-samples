<?php 

namespace Client;

use Client\Model;

/**
 * Delegate methods related to nav model
 * 
 * @author ccollier
 *
 */
interface NavDelegate {
    
    /**
     * Test if generic model object is a nav container
     *
     * @param object $obj
     *
     * @return boolean
     */
    function isNavContainer( $obj );
    
    /**
     * Gets all nav items from DB
     * 
     * @return Model\NavItem[]
     */
    function getNavItems();
    
    /**
     * Gets all nav items from DB, with name as key
     *
     * @return Model\ContentComponent[]
     */
    function getNavComponents();
    
    /**
     * Gets page content from nav item's slug
     *
     * @param string $slug
     * 
     * @return string
     */
    function getNavItemPageContent( $slug );
    
}