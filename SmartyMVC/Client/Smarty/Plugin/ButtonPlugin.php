<?php 

namespace Client\Smarty\Plugin;

use CWD\Smarty\Plugin\FunctionPlugin;

/*
 * Smarty Button Plugin
 * 
 * Button plugin for creating button component from a URL and label
 * 
 * @author ccollier
 *
 */

class ButtonPlugin extends FunctionPlugin {
	
	protected $name = 'button';
	
	/**
	 * Assign URL parameters to template
	 * 
	 * @param array $params
	 * @param \Smarty_Internal_Template $template
	 * 
	 * @return string
	 */
	function execute( array $params = [], \Smarty_Internal_Template $template = null ) {
		
		$target = '_blank';
		if (isset($params['open_new'])){
			if($params['open_new'] == 'false') $target = '_self';
		}

		$template->smarty->assign( 'url', $params['url'] );
		$template->smarty->assign( 'target', $target);
		$template->smarty->assign( 'label', $params['label'] );
		
		return $template->smarty->fetch("plugins/$this->name.tpl");
	}
}