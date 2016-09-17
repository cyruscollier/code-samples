<?php 

namespace CWD;

/**
 * Basic controller API
 * 
 * @author ccollier
 *
 */
interface SiteController {
	
    /**
     * Store global site instance to use its API if needed
     * 
     * @param Site $site
     */
	function __construct( Site $site );
	
	/**
	 * Handle URL request
	 * 
	 * @param object $request
	 */
	function execute( $request );
	
	/**
	 * Return view content for display
	 * 
	 * @return string
	 */
	function getView();
	
	/**
	 * Return true if invalid request or request doesn't return a valid view
	 * 
	 * @return bool
	 */
	function is404();
	
}
