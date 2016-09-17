<?php

namespace CWD\Smarty\Plugin;

/**
 * Base class for Smarty compiler plugins
 *
 * @author ccollier
 *
 */
abstract class CompilerPlugin extends PluginExtension {

	const TYPE = \Smarty::PLUGIN_COMPILER;
	
	/**
	 * Executor for compiler plugin
	 * 
	 * @param array $params
	 * @param \Smarty $smarty
	 * 
	 * @return string
	 */
	function execute( array $params = [], \Smarty $smarty = null ) {
		return '';
	}
	
}