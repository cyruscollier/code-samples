<?php 

namespace Client;

/**
 * Client site execution 
 * 
 * @author ccollier
 *
 */
use CWD\Site;
use CWD\SiteController;
use Client\Smarty\PageTemplateController;
use Client\Smarty\PluginTemplateController;
use Client\Smarty\Plugin;


class ClientSite extends Site {

    /**
     * Load plugin or page template controller and register extensions
     * 
     * @return SiteController
     */
	function getController() {
		$Controller = 
			isset( $this->request->path[0] ) && $this->request->path[0] == 'plugin' ?
			new PluginTemplateController( $this ):
			new PageTemplateController( $this );
		
		$NavPlugin = new Plugin\NavPlugin( $Controller );
		$NavPlugin->setDelegate( $Controller );
		$ComponentPlugin = new Plugin\ComponentPlugin( $Controller );
		$ComponentPlugin->setContentDelegate( $Controller );
		
		$Controller->registerExtensions( [
			$NavPlugin, $ComponentPlugin,
			new Plugin\CalloutPlugin( $Controller ),
			new Plugin\VideoPlugin( $Controller ),
			new Plugin\SlideSharePlugin( $Controller ),
			new Plugin\ButtonPlugin( $Controller ),
			new Plugin\SectionTabPlugin( $Controller ),
		] );
		return $Controller;
	}
}

?>