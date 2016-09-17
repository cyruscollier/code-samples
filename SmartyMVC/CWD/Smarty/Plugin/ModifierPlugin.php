<?php

namespace CWD\Smarty\Plugin;

/**
 * Base class for Smarty modifier plugins
 *
 * @author ccollier
 *
 */
abstract class ModifierPlugin extends PluginExtension {

	const TYPE = \Smarty::PLUGIN_MODIFIER;
	
	/**
	 * Executor for modifier plugin
	 * 
	 * @param string $value
	 * 
	 * @return string
	 */
	function execute( $value = '' ) {
		return $value;
	}
	
}