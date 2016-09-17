<?php

namespace Client\Smarty\Plugin;

use CWD\Model\LinkNode;
use Client\Model\NavItem;
use Client\Model\NavComponent;
use Client\NavDelegate;
use Client\NavDelegator;

/**
 * Build items for multi-level navigation
 * 
 * @author ccollier
 *
 */
class NavPlugin extends RestAPIPlugin implements NavDelegator {
	
	protected $name = 'nav';
	
	/**
	 * @var NavDelegate
	 */
	protected static $Delegate;
	
	/**
	 * @var array
	 */
	private $nav_item_content = array();
	
	private $page_data;
	
	function setDelegate( NavDelegate $Delegate ) {
	    static::$Delegate = $Delegate;
	}

	/**
	 * Build child items and setup template
	 * 
	 * @param array $params
	 * @param \Smarty_Internal_Template $template
	 * 
	 * @return string
	 */
	function execute( array $params = [], \Smarty_Internal_Template $template = null ) {
		$this->page_data = $template->smarty->getTemplateVars('page');
		$NavItems = static::$Delegate->getNavItems();
		$nav_list = $this->buildNavChildren( null, $NavItems );
		$root = $nav_list[0];
		$template->smarty->assign( 'nav_root', $root );
		foreach ( static::$Delegate->getNavComponents() as $nav_component)
			$this->nav_item_content[$nav_component->name] = $template->smarty->fetch( 'string:' . $nav_component->body );		
		$template->smarty->assign( 'nav', $this );
		return $template->smarty->fetch('plugins/nav.tpl');
	}
	
	/**
	 * Determine whether to show any children
	 * 
	 * @param LinkNode $NavItem
	 * 
	 * @return boolean
	 */
	function hasChildrenForLevel( $NavItem ) {
		return $this->showLevel( $NavItem, true ) && $NavItem instanceof NavItem && ( $NavItem->hasChildren() || $NavItem->hasAnchors() );
	}
	
	/**
	 * Get filtered list of children
	 * 
	 * @param NavItem $NavItem
	 * 
	 * @return NavItem[]
	 */
	function getChildrenForLevel( NavItem $NavItem ) {
		$children = array_merge( $NavItem->getAnchors(), $NavItem->getChildren() );
		$filtered_children = array_filter( $children, array( $this, 'showLevel' ) );
		return $filtered_children;
	}
	
	/**
	 * Calculates whether to display current level based on SEO siloing
	 * 
	 * @param LinkNode $NavItem
	 * @param boolean $children
	 * 
	 * @return boolean
	 */
	function showLevel( $NavItem = null, $children = false ) {
		$request = $this->Controller->request;
		$path = isset( $request->GET['path'] ) ? array_filter( explode( '/', $request->GET['path'] ) ) : $request->path;
		//some shorthands
		$parent = $NavItem->getParent();
		$grandparent = $parent ? $parent->getParent() : null;
		if ( empty( $path ) ) $path = array( NavItem::ROOT);
		if ( $NavItem->slug == NavItem::ROOT ) return true; // always show root
		//and all nodes when acccessed directly
		else if ( !( $this->page_data || isset( $request->GET['path'] ) ) ) return true;
		//grandchildren of nav container parent
		else if ( $grandparent->slug == end($path) && static::$Delegate->isNavContainer( $parent ) ) return true;
		else return (
			in_array( $NavItem->slug, $path ) || //in direct tree path
			$parent->slug == end($path) //children
		);
	}
	
	/**
	 * Sets nav item's anchors
	 * 
	 * @param NavItem $NavItem
	 */
	function setNavAnchors( NavItem $NavItem ) {
		$content = static::$Delegate->getNavItemPageContent( $NavItem->slug );
		$sectiontab = new SectionTabPlugin( $this->Controller );
		$anchors = $sectiontab->getAnchorsFromContent( $content );
		foreach ( $anchors as &$anchor ) {
			$anchor->setParent( $NavItem );
			$anchor->setURL();
		}
		$NavItem->setAnchors( $anchors );
	}
	
	/**
	 * Fetches matching component of subtype 'header'
	 * 
	 * @param NavItem $NavItem
	 */
	function getNavHeader( NavItem $NavItem ) {
		return $this->getNavComponent( 'header', $NavItem );
	}
	
	/**
	 * Fetches matching component of subtype 'content'
	 *
	 * @param NavItem $NavItem
	 */
	function getNavContent( NavItem $NavItem ) {
		return $this->getNavComponent( 'content', $NavItem );
	}
	
	/**
	 * Get nav components based on type and nav item slug
	 * 
	 * @param string $type
	 * @param NavItem $NavItem
	 * 
	 * @return NavComponent
	 */
	private function getNavComponent( $type, NavItem $NavItem ) {
		$key = sprintf( 'nav-%s_%s', $type, $NavItem->slug );
		return isset( $this->nav_item_content[$key] ) ? $this->nav_item_content[$key] : '';
	}
	
	/**
	 * Recursive method to build nav tree from child group of total results
	 * 
	 * @param NavItem|null $parent_nav_item
	 * @param NavItem[] $list
	 * 
	 * @return NavItem[]
	 */
	private function buildNavChildren( $parent_nav_item = null, &$list ) {
		$children = array();
		$parent = is_null( $parent_nav_item ) ? '' : $parent_nav_item->slug;
		$sublist = isset( $list[$parent] ) ? $list[$parent] : array();
		foreach ( $sublist as $NavItem ) {
			if ( $parent ) $NavItem->setParent( $parent_nav_item );
			$NavItem->setURL();
			if ( !empty( $list[$NavItem->slug] ) ) {
				$NavItem->setChildren( 
					$this->buildNavChildren( $NavItem, $list )
				);
			}
			if ( $NavItem->template == NavItem::T_ANCHOR_TEMPLATE )
				$this->setNavAnchors( $NavItem );
			$children[] = $NavItem;
		}
		
		return $children;
	}
		
}
