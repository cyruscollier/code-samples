<?php 

namespace Client\Smarty\Plugin;

use SimpleXMLElement;

/*
 * SlideShare Plugin
 * 
 * Fetches SlideShare slideshow using back-end API
 *
 */
class SlideSharePlugin extends RestAPIPlugin {
	
	protected $name = 'slideshare';
	
	private $api_url = 'https://www.slideshare.net/api/2/get_slideshow';
	private $api_key = SLIDESHARE_API_KEY;
	private $api_shared_secret = SLIDESHARE_API_SHARED_SECRET;
	private $image_template = 'http://image.slidesharecdn.com/%s/95/slide-1-1024.jpg';
	
	/**
	 * Parse and assign from SlideShare XML response
	 * 
	 * @param array $params
	 * @param Smarty_Internal_Template $template
	 * 
	 * @return string
	 */
	function execute( array $params = [], \Smarty_Internal_Template $template = null  ) {
		$message = 'Unknown error';
		if ( isset( $params['url'] ) ) {
			$query = $this->buildQuery( $params['url'] );
			$result = $this->getAPIRequest( $query );
			if ( $result ) {
				$slideshow = new SimpleXMLElement( $result );
				if ( $image = $this->getImage( $slideshow ) ) {
					$template->smarty->assign( 'image', $image );
					$template->smarty->assign( 'slide_count', $slideshow->NumSlides );
					$template->smarty->assign( 'embed', isset( $params['embed'] ) && '1' == $params['embed'] );
					$template->smarty->assign( 'embed_code', $slideshow->Embed );
					return $template->smarty->fetch("plugins/$this->name.tpl");
				} elseif ( 'SlideShareServiceError' == $slideshow->getName() ) {
					$message = $slideshow->message;
				} else {
					$message = 'Image fetch failed';
				}
			} else {
				$message = $result;
			}
		} else {
			$message = 'Invalid parameters';
		}
		return $message;
	}
	
	/**
	 * Construct API request parameters
	 * 
	 * @param string $url
	 * 
	 * @return string
	 */
	private function buildQuery( $url ) {
		$time = time();
		$hash = sha1( $this->api_shared_secret . $time );
		$query = array( 
			'slideshow_url' => $url,
			'api_key' => $this->api_key,
			'ts' => $time,
			'hash' => $hash,
			'detailed' => 1
		);
		return http_build_query( $query );
	}
	
	/**
	 * Send request and get response using curl or fopen socket as fallback
	 * 
	 * @param string $query
	 * 
	 * @return string
	 */
	private function getAPIRequest( $query ) {
		$url = $this->api_url . '?' . $query;
		if ( !function_exists( 'curl_init' ) )
			return file_get_contents( $url );
		return $this->curl( $url );
	}
	
	/**
	 * Generic curl request
	 * 
	 * @param string $url
	 * 
	 * @return string
	 */
	private function curl( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$result = curl_exec( $ch );
		curl_close( $ch );
		return $result;
	}
	
	/**
	 * Parse image id to construct image URL
	 * 
	 * @param SimpleXMLElement $xml_element
	 * 
	 * @return string
	 */
	private function getImage( SimpleXMLElement $xml_element ) {
		$key = $xml_element->PPTLocation;
		if ( $key ) {
			return sprintf( $this->image_template, $key );
		}
		return '';
	}
	
}