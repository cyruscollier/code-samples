<?php 

namespace Client\Smarty;

use Client\Smarty\Plugin\RestAPIPlugin;

/**
 * Controller for executing a single plugin from a URL request
 * 
 * @author ccollier
 *
 */
class PluginTemplateController extends ClientTemplateController {
	
	/**
	 * Gets matching rest api plugin to execute
	 * 
	 * @return object
	 */
	function getViewData() {
		if( isset( $this->view_data ) ) return $this->view_data;
		$view_data = new \stdClass;
		$tag = end($this->request->path);
		$Smarty = $this->Smarty;
		$plugins = $Smarty->registered_plugins[$Smarty::PLUGIN_FUNCTION];
		if( 
			isset( $plugins[$tag] ) &&
			is_callable($plugins[$tag][0]) &&
			isset( $plugins[$tag][0][0] ) &&
			$plugins[$tag][0][0] instanceof RestAPIPlugin
		) {
			$params = $this->getPluginParams( $this->request->GET );
			$view_data->template = sprintf( '{%s %s}', $tag, $params ); 
		} else {
			die( 'Invalid plugin call' );
		}	
		return $view_data;
	}
	
	/**
	 * Convert array to Smarty plugin tag parameters
	 * @param unknown $query
	 */
	private function getPluginParams( $query ) {
		$params = array();
		foreach ( $query as $key => $value ) {
			$params[] = sprintf( '%s="%s"', $key, $value );
		}
		return implode( ' ', $params );
	}

}
