<?php 

namespace Client\Smarty\Plugin;

use Client\Smarty\Plugin\ComponentPlugin;

/*
 * Smarty Video Plugin
 * 
 * Video plugin for creating callout comonent from content url
 * 
 * @author ccollier
 *
 */
class VideoPlugin extends ComponentPlugin {
	
	protected $name = 'video';
	
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
		$template->smarty->assign( $this->name, ['content' => $content] );
		return $template->smarty->fetch( "plugins/$this->name.tpl" );
		
	}
}
