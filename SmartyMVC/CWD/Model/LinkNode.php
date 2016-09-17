<?php 

namespace CWD\Model;

/**
 * A navigation node with a title, url and optional parent
 * 
 * @author ccollier
 *
 */
interface LinkNode {
	
    /**
     * $return string
     */
	function getURL();
	
	/**
	 * @param string $path
	 */
	function setURL( $path );
	
	/**
	 * @return string
	 */
	function getTitle();
	
	/**
	 * @param string $title
	 */
	function setTitle( $title );
	
	/**
	 * @return LinkNode
	 */
	function getParent();
	
	/**
	 * @param LinkNode $parent
	 */
	function setParent( LinkNode $parent );
}
