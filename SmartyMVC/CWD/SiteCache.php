<?php 

namespace CWD;

/**
 * Basic filesystem page cache
 * 
 * @author ccollier
 *
 */
class SiteCache  {
	
	private $cache_dir;
	
	/**
	 * Sets cache directory, creating if necessary
	 * 
	 * @param string $cache_dir
	 */
	function __construct( $cache_dir ) {
		$this->cache_dir = realpath(rtrim( $cache_dir , DIRECTORY_SEPARATOR ));
		@mkdir( $this->cache_dir );
	}
	
	/**
	 * Cache/recache file contents for static retrieval by web server
	 * 
	 * @param string $content
	 */
	function cache( &$content ) {
		$path = strtok( $_SERVER['REQUEST_URI'], '?' );
		$cache_file_dir = rtrim( $this->cache_dir . $path, '/' );
		$cache_file = $cache_file_dir . DIRECTORY_SEPARATOR . 'index.html' ;
		if ( !file_exists( $cache_file ) || isset( $_GET['flush_cache'] ) ) {
			$content_tag = '<!-- Content served from static cache at ' . $path . '-->' ;
			@mkdir( $cache_file_dir, 0755, true );
			$result = file_put_contents($cache_file, $content . $content_tag);
		}
	}
}
