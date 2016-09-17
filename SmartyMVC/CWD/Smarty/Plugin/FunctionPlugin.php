<?php

namespace CWD\Smarty\Plugin;

/**
 * Base class for Smarty function plugins
 * 
 * @author ccollier
 *
 */
abstract class FunctionPlugin extends PluginExtension {

	const TYPE = \Smarty::PLUGIN_FUNCTION;
	
	/**
	 * Executor for function plugin
	 * 
	 * @param array $params
	 * @param Smarty_Internal_Template $template
	 * 
	 * @return string
	 */
	function execute( array $params = [], \Smarty_Internal_Template $template = null ) {
		return '';
	}
		
}