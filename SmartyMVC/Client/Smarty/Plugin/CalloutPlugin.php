<?php 

namespace Client\Smarty\Plugin;

use Client\Smarty\Plugin\ComponentPlugin;
use Smarty_Internal_Template;

/*
 * Smarty Callout Plugin
 * 
 * Callout plugin for creating callout comonent from content
 * 
 * @author ccollier
 *
 */

class CalloutPlugin extends ComponentPlugin {
	
	protected $name = 'callout';
	
	/**
	 * Use wrapper template with component content inside
	 * 
	 * @param \Smarty_Internal_Template $template
	 * @param object $results
	 * 
	 * @return string
	 */
	function output( \Smarty_Internal_Template $template, $results ) {
		$content = parent::output( $template, $results );
		if ( !isset( $this->parameters['width'] ) ) {
		    $this->parameters['width'] = 'span4';
		}
		$template->smarty->assign( $this->name, ['content' => $content, 'width' => $this->parameters['width']] );
		return $template->smarty->fetch( "plugins/$this->name.tpl" );
		
	}
}