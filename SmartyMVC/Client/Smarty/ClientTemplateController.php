<?php 

namespace Client\Smarty;

use CWD\Smarty\SmartyController;
use CWD\Site;
use CWD\SiteCache;
use Client\Model;
use Client\NavDelegate;
use Client\ContentDelegate;


/**
 * Base controller for client controllers
 * 
 * @author ccollier
 *
 */
abstract class ClientTemplateController extends SmartyController implements NavDelegate, ContentDelegate {
	
	const TEMPLATE_DIR = './templates';
	const COMPILE_DIR = './templates_c';
	const CACHE_DIR = './cache';
	const T_404_TEMPLATE = '404';
	const T_NAV_TEMPLATE = 'nav';
	const T_NAVCONTAINER_TEMPLATE = 'nav-container';
	const T_SCROLL_TEMPLATE = 'page-scroll';
	
	/**
	 * @var SiteCache
	 */
	protected $Cache;
	
	/**
	 * Set cache
	 * 
	 * @param Site $Site
	 */
	function __construct( Site $Site ) {
		parent::__construct( $Site );
		$this->Cache = new SiteCache( $this->getCacheDir() );
	}
	
	function getTemplateDir() {
		return self::TEMPLATE_DIR;
	}
	
	function getCompileDir() {
		return self::COMPILE_DIR;
	}
	
	function getCacheDir() {
		return self::CACHE_DIR;
	}
	
	function get404Template() {
		return self::T_404_TEMPLATE;
	}

	/**
	 * Cache view before serving content
	 *
	 * @return string
	 */
	function getView() {
	    $content = $this->View->fetch();
	    if ( $this->canCachePage() ){
	        $this->Cache->cache( $content, $this->request->fullpath );
	    }
	    return $content;
	}
	
	/**
	 * Only cache if global and page flags present
	 *
	 * @return boolean
	 */
	function canCachePage() {
	    return (
	            STATIC_CACHING &&
	            $this->view_data->cache //page-specific flag set
	            );
	}
	
	/**
	 * Test if generic model object is a nav container
	 * 
	 * @param object $obj
	 * 
	 * @return boolean
	 */
	function isNavContainer( $obj ) {
		return (
			is_object( $obj ) &&
			property_exists( $obj, 'template' ) &&
			$obj->template == self::T_NAVCONTAINER_TEMPLATE
		);
	}
	
	/**
	 * Gets all nav items from DB
	 * @return Model\NavItem[]
	 */
	function getNavItems() {
		$nav_items = model\NavItem::getObjects( array( 'root' => Model\NavItem::ROOT ) );
		return false !== $nav_items ? $nav_items : [];
	}
	
	/**
	 * Gets all nav items from DB, with name as key
	 * 
	 * @return Model\ContentComponent[]
	 */
	function getNavComponents() {
		$nav_components = Model\NavComponent::getObjects();
		return false !== $nav_components ? $nav_components : array();
	}
	
	/**
	 * Gets page content from nav item's slug
	 * 
	 * @param string $slug
	 * @return string
	 */
	function getNavItemPageContent( $slug ) {
		return Model\ContentPage::getContent( compact( 'slug') );
	}

	/**
	 * Gets content component from DB
	 *
	 * @param string $name
	 * @param string $type
	 *
	 * @return Model\ContentComponent
	 */
	function getContentComponent( $name, $type ) {
	    $component = Model\ContentComponent::getObject( compact( 'name', 'type' ) );
	    return false !== $component ? $component : null;
	}

}
