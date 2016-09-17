<?php 

namespace Client\Model;

use CWD\Model\PDOModel;
use CWD\Model\LinkNode;

/**
 * Represents page link in tree
 * 
 * @author ccollier
 *
 */
class PageLink extends PDOModel implements LinkNode {
	
	const ROOT = 'home';
	const TABLE_NAME = 'content_pages';
	
	public $slug;
	public $parent;
	public $title;
	
	protected $url;
	protected $url_walker_callback;
	
	function getURL() {
		return $this->url;
	}
	
	/**
	 * Use provided callback to build url from parent pages
	 * 
	 * @param string $path
	 * @param callable $url_walker_callback
	 */
	function setURL( $path = null, callable $url_walker_callback = null ) {
		if ( !is_array( $path ) ) $path = [$path];
		if ( $url_walker_callback && is_callable( $url_walker_callback ) ) {
			$this->url_walker_callback = $url_walker_callback;
			$path = $this->buildURLPath( $this );
		}
		$this->url = '/' . implode( '/', array_filter( $path ) );
	}
	
	/**
	 * Use parent link to set URL
	 * 
	 * @param PageLink $parent_page_link
	 */
	function setURLFromParent( PageLink $parent_page_link ) {
		$path = $parent_page_link->getURLPath();
		$path[] = $this->slug;
		$this->setURL( $path );
	}
	
	/**
	 * Split URL into path components
	 */
	function getURLPath() {
		return explode( '/', trim( $this->url, '/' ) );
	}
	
	function getTitle() { 
	    return $this->title; 
	}
	
	function setTitle( $title ) { 
	    $this->title = $title; 
	}
	
	function getParent() { 
	    return $this->parent; 
	}
	
	function setParent( LinkNode $parent ) { 
	    $this->parent = $parent; 
	}
	
	/**
	 * Prepare statement with page slug
	 */
	protected function setPreparedStatements() {
		$this->setPreparedModelStatement( self::KEY_OBJECT, 'slug=:slug' );
	}
	
	/**
	 * Recursive URL path builder
	 * 
	 * @param PageLink $page_link
	 * 
	 * @return array
	 */
	private function buildURLPath( PageLink $page_link = null ) {
		if ( is_null( $page_link ) || $page_link->slug == self::ROOT ) {
			return [];
		}
		$parent_page_link = call_user_func( $this->url_walker_callback, $page_link->parent );
		$path = $this->buildURLPath( $parent_page_link );
		$path[] = $page_link->slug;
		return $path;
	}
	
}
