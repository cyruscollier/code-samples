<?php 

namespace CWD\Smarty\Plugin;

use CWD\Smarty\SmartyExtension;

/**
 * Base class for all types of Smarty plugin extensions
 * 
 * @author ccollier
 *
 */
abstract class PluginExtension extends SmartyExtension {
	
	/**
	 * Tag name, must be set in concrete subclasses
	 *
	 * @var string
	 */
	protected $name;
	
	function getName() {
		return $this->name;
	}
	
	/**
	 * Registers plugin with Smarty
	 *
	 * @param \Smarty $Smarty
	 */
	function registerWith( \Smarty $Smarty ) {
		$Smarty->registerPlugin( static::TYPE, $this->getName(), array( $this, 'execute' ) );
	}
	
}
