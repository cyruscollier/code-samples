<?php 

namespace Client\Smarty\Plugin;

use CWD\Smarty\Plugin\FunctionPlugin;
use Smarty_Internal_Template;
use Client\ContentDelegator;
use Client\ContentDelegate;

/*
 * Smarty Component Plugin
 * 
 * Basic Component plugin for creating component from content
 *
 */

class ComponentPlugin extends FunctionPlugin implements ContentDelegator {
	
	protected $name = 'component'; 
	protected $parameters;
	
	/**
	 * @var ContentDelegate
	 */
	protected static $Delegate;
	
	function setDelegate( ContentDelegate $Delegate ) {
	    static::$Delegate = $Delegate;
	}
	
	/**
	 * Loads component and evaluates output
	 *
	 * @param array $params
	 * @param Smarty_Internal_Template $template
	 *
	 * @return string
	 */
	function execute( array $params = [], \Smarty_Internal_Template $template = null ) {
		
		$this->parameters = $params;
		$results = static::$Delegate->getContentComponent( $params['name'], $this->name );

		if ( !$results ) {
			return "<!-- No matching component for '{$params['name']}' -->";
		}
		// Evaluate smarty content retrieved from the DB		
		return $this->output( $template, $results );
	}
	
	/**
	 * Render component body as template
	 * 
	 * @param \Smarty_Internal_Template $template
	 * @param object $results
	 * 
	 * @return string
	 */
	function output( \Smarty_Internal_Template $template, $results ) {
		return $template->smarty->fetch( 'string:' . $results->body );
	}
	
}