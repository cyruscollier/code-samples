<?php 

namespace Client\Model;

use CWD\Model\Model;
use CWD\Model\LinkNode;

/**
 * Page link node specific for navigiation usage
 * 
 * @author ccollier
 *
 */
class NavItem extends PageLink {
	
	const TABLE_NAME = 'content_pages';
	const KEY_OBJECT_FIRST_CHILD = 'first_child';
	const OBJECTS_FETCH_STYLE = \PDO::FETCH_GROUP;
	
	public $template;
	public $nav_title;
	public $nav_order = 0;
	
	/**
	 * @var NavItem
	 */
	protected $parentNavItem;
	
	/**
	 * @var NavItem[]
	 */
	protected $childNavItems = [];
	
	/**
	 * @var array
	 */
	protected $url_path;
	
	/**
	 * List of page anchors
	 * @var NavItemAnchor[]
	 */
	protected $anchors = [];
	
	/**
	 * @param LinkNode $parent
	 */
	function setParent( LinkNode $parent ) {
		if ( $parent instanceof NavItem ) {
			$this->parentNavItem = $parent;
		}
	}
	
	/**
	 * @return NavItem
	 */
	function getParent() {
		return $this->parentNavItem;
	}
	
	/**
	 * @param NavItem[] $children
	 */
	function setChildren( array $children ) {
		foreach ( $children as $child ) {
			$this->addChild( $child );
		}
	}
	
	/**
	 * @param NavItem $child
	 */
	function addChild( NavItem $child ) {
		$this->childNavItems[$child->slug] = $child;
	}
	
	
	/**
	 * @return boolean
	 */
	function hasChildren() {
		return !empty( $this->childNavItems );
	}
	
	/**
	 * @return array[LinkNode]
	 */
	function getChildren() {
		return $this->childNavItems;
	}
	
	/**
	 * @param string $slug
	 * 
	 * @return NavItem|null
	 */
	function getChild( $slug ) {
		return isset( $this->childNavItems[$slug] ) ? $this->childNavItems[$slug] : null;
	}
	
	/**
	 * First setup parent and child path components correctly
	 *
	 * @param string $path
	 * @param callable $url_walker_callback
	 */
	function setURL( $path = null, $url_walker_callback = false ) {
		if( $this->slug != self::ROOT && $this->parentNavItem ) {
			$this->url_path = $this->parentNavItem->url_path;
			$this->url_path[] = $this->slug;
		} else {
			$this->url_path = is_array( $path ) ? $path : array();
		}
		return parent::setURL( $this->url_path );
	}
	
	function getURL() {
		return $this->url;
	}

	/**
	 * @return boolean
	 */
	function hasAnchors() {
		return !empty( $this->anchors );
	}
	
	/**
	 * @param NavItemAnchor[] $anchors
	 */
	function setAnchors( array $anchors ) {
		foreach ( $anchors as $anchor ) {
			if ( $anchor instanceof NavItemAnchor ) {
				$this->anchors[] = $anchor;
			}
		}
	}
	
	/**
	 * @return NavItemAnchor[]
	 */
	function getAnchors() {
		return $this->anchors;
	}
	
	/**
	 * Use nav title if available
	 */
	function getTitle() {
		return !is_null( $this->nav_title ) ? $this->nav_title : $this->title;
	}
	
	/**
	 * Fetch first child from DB for display purposes
	 * 
	 * @param array $params
	 */
	static function getFirstChild( array $params = [] ) {
		$statement = self::singleton()->getPreparedStatement( self::KEY_OBJECT_FIRST_CHILD, $params );
		$statement->execute( $params );
		return $statement->fetch();
	}
	
	/**
	 * Prepare parent and root parameters
	 */
	protected function setPreparedStatements() {
		$columns = $this->columns;
		array_unshift( $columns, "parent AS 'key'");
		$this->setPreparedStatement( self::KEY_OBJECTS, $columns, 'nav_show=1 AND (slug =:root OR parent IS NOT NULL)', 'nav_order, id ASC', 100 );
		$this->setPreparedModelStatement( self::KEY_OBJECT_FIRST_CHILD, 'parent=:parent', 'nav_order, id ASC' );
	}
}
