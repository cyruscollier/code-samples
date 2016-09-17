<?php 

namespace CWD\Smarty;

use CWD\Site;
use CWD\SiteController;
use CWD\Model\Model;
use Smarty;

/**
 * Base controller for serving views via Smarty templates
 * 
 * @author ccollier
 *
 */
abstract class SmartyController implements SiteController {
	
	/**
	 * Reference to main Site object
	 * @var Site
	 */
	protected $Site;
	
	/**
	 * @var Smarty
	 */
	protected $Smarty;
	
	/**
	 * Request variables
	 * @var object
	 */
	public $request;
	
	/**
	 * Smarty Template object
	 * @var \Smarty_Internal_Template
	 */
	protected $View;
	
	/**
	 * Page view variables 
	 * @var Model
	 */
	protected $view_data;
	
	/**
	 * Configure Smarty
	 * 
	 * @param Site $site
	 */
	function __construct( Site $Site ) {
		$this->Site = $Site;
		$this->Smarty = new Smarty();
		$this->Smarty->caching = false;
		$this->Smarty->cache_lifetime = 120;
		$this->Smarty->addTemplateDir( $this->getTemplateDir() );
		$this->Smarty->setCompileDir( $this->getCompileDir() );
		$this->Smarty->setCacheDir( $this->getCacheDir() );
	}
	
	/**
	 * Set request and template view
	 * 
	 * @param object $request
	 */
	function execute( $request ) {
		$this->request = $request;
		$this->setView();
	}

	/**
	 * Serve content of Smarty template
	 * 
	 * @return string
	 */
	function getView() {
		return $this->View->fetch();
	}

	/**
	 * Create and setup Smarty template with file
	 */
	function setView() {
		$this->view_data = $this->getViewData();
		$template = $this->get404Template() . 'tpl';
		if ( 
			is_object( $this->view_data ) && 
			property_exists( $this->view_data, 'template' ) && 
			!empty( $this->view_data->template )
		) {
			$template_file = $this->getTemplateDir() . '/' . $this->view_data->template . '.tpl';
			$template = file_exists( $template_file ) ?
				$this->view_data->template  . '.tpl':
				'string: '. $this->view_data->template;
		}
		$this->View = $this->Smarty->createTemplate( $template );
	}
	
	/**
	 * Register each supplied extension
	 * 
	 * @param SmartyExtension[] $objects
	 */
	function registerExtensions( $objects ) {
		foreach ( $objects as $object ) { /* @var $object SmartyExtension */
			$object->registerWith( $this->Smarty );
		}
	}
	
	/**
	 * Matches view template against stored 404 template
	 */
	function is404() {
		return $this->View->template_resource == $this->get404Template() . '.tpl';
	}
	
	abstract function getTemplateDir();
	abstract function getCompileDir();
	abstract function getCacheDir();
	abstract function getViewData();
	abstract function get404Template();
}
