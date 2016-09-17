<?php

namespace CWD\Smarty\Plugin;

/**
 * Base class for Smarty block plugins
 *
 * @author ccollier
 *
 */
abstract class BlockPlugin extends PluginExtension {
	
	const TYPE = \Smarty::PLUGIN_BLOCK;

	/**
	 * Executor for block plugin
	 * 
	 * @param array $params
	 * @param string $content
	 * @param Smarty_Internal_Template $template
	 * @param boolean $repeat
	 * 
	 * @return string
	 */
	function execute( array $params = [], $content = '',
			Smarty_Internal_Template $template = null, &$repeat = false
	) {
		return $content;
	}
	
}