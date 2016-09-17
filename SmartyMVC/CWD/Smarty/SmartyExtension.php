<?php 

namespace CWD\Smarty;

/**
 * Base abstract for smarty plugins
 * 
 * @author ccollier
 *
 */
abstract class SmartyExtension {
	
	/**
	 * Reference to page controller
	 * 
	 * @var SmartyController
	 */
	protected $Controller;
	
	const TYPE = null;
	
	function __construct( SmartyController $Controller ) {
		$this->Controller = $Controller;
	}
	
	/**
	 * Subclasses must specify regisration method
	 * 
	 * @param \Smarty $smarty
	 */
	abstract function registerWith( \Smarty $smarty );
	
	/**
	 * Extension execution callback, variable argumens
	 * 
	 * @param mixed $var,...
	 */
	abstract function execute( $var );
}
