<?php 

namespace CWD\Smarty\Filter;

use CWD\Smarty\SmartyExtension;

/**
 * Base for all Smarty filter extensions
 * 
 * @author ccollier
 *
 */
abstract class FilterExtension extends SmartyExtension {
	
    /**
     * Registers filter with Smarty
     * 
     * @param \Smarty $Smarty
     */
	function registerWith( \Smarty $Smarty ) {
		$Smarty->registerFilter( static::TYPE, array( $this, 'execute' ) );
	}
	
	/**
	 * Executor for output filter
	 *
	 * @param string $content
	 * @param Smarty_Internal_Template $template
	 *
	 * @return string
	 */
	function execute( $content = '', Smarty_Internal_Template $template = null) {
		return $content;
	}
	
}
