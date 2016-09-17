<?php 

namespace Client\Model;

use CWD\Model\Model;
use CWD\Model\LinkNode;

/**
 * Presents an HTML anchor lnk
 * 
 * @author ccollier
 *
 */
class NavItemAnchor extends Model implements LinkNode {	
	
	public $slug;
	public $parent;
	public $title;
	
	protected $url;
	protected $parent_node;
	
	function setURL( $path = null ) {
		$this->url = $this->getParent()->getURL() . '#' . $this->slug;
	}
	
	function getURL() { 
	    return $this->url; 
	}
	
	function setParent( LinkNode $parent ) {
		if ( $parent instanceof NavItem ) {
			$this->parent_node = $parent;
		}
	}
	
	/**
	 * @return NavItem
	 */
	function getParent() {
		return $this->parent_node;
	}
	
	function getTitle() { 
	    return $this->title; 
	}
	
	function setTitle( $title ) { 
	    $this->title = $title; 
	}
	
}
