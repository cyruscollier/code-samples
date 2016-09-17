<?php 

namespace CWD;

use CWD\Base\Singleton;
use CWD\Model\PDOModel;
use PDO;

/**
 * Main site execution class
 * 
 * @author ccollier
 *
 */
abstract class Site extends Singleton {
	
	/**
	 * Debug flag
	 * 
	 * @var boolean
	 */
	var $debug = false;
	
	/**
	 * Parsed request path and variables
	 * @var object
	 */
	protected $request;
	
	/**
	 * Executes controller from parsed URL
	 * 
	 * @param PDO $db optional db connection object
	 */
	function run( PDO $db = null ) {
		session_start();
		$this->parseRequest();
		if( $db ) PDOModel::init( $db );
		$controller = $this->getController();
		$controller->execute( $this->request );
		if ( $controller->is404() )
			header( 'HTTP/1.0 404 Not Found', true, 404);
		$view = $controller->getView();
		echo $view;
	}
	
	/**
	 * Constants setup
	 */
	function setup() {
		$request_base = defined( 'URL_REWRITES' ) && URL_REWRITES ? dirname( $_SERVER['SCRIPT_NAME'] ) : $_SERVER['SCRIPT_NAME'];
		define( 'SITE_REQUEST_BASE', rtrim( $request_base, '/' ) );
		$protocol = 'http://';
		define( 'SITE_URL', $protocol . $_SERVER['HTTP_HOST'] . SITE_REQUEST_BASE );
		$assets_url = defined( 'ASSETS_URL' ) && ASSETS_URL ?
			rtrim( ASSETS_URL, '/' ) :
			rtrim($protocol . $_SERVER['HTTP_HOST'] . dirname( $_SERVER['SCRIPT_NAME'] ), '/');
		if ( !defined( 'SITE_ASSETS_PATH') ) define( 'SITE_ASSETS_PATH', '' );
		$assets_path = defined( 'ASSETS_VERSION') && ASSETS_VERSION ?
			'/' . ASSETS_VERSION . SITE_ASSETS_PATH:
			SITE_ASSETS_PATH;
		define( 'SITE_ASSETS_URL', $assets_url . $assets_path );
		define( 'UTIL_URL', $assets_url . '/util');
	}
		
	
	function getDebug() {
		return $this->debug;
	}
	
	/**
	 * @param bool $debug
	 */
	function setDebug( $debug ) {
		$this->debug = (boolean) $debug;
	}
	
	/**
	 * @param string $url
	 * @param bool $perm
	 */
	function redirect( $url, $perm = true ) {
		if ( $perm ) {
			header( 'HTTP/1.1 301 Moved Permanently ');
		}
		header( 'Location: ' . $url );
		exit();
	}
	
	/**
	 * Optional hook to handle URL parsing
	 */
	function parseRequest() {
		$request = array();
		$path = strtok( $_SERVER['REQUEST_URI'], '?' );
		if( defined( 'SITE_REQUEST_BASE' ) ) {
			$path = str_replace( SITE_REQUEST_BASE, '', $path );
		}
		$request['fullpath'] = $path;
		$request['path'] = array_values( array_filter( explode( '/', $path ) ) );
		$request['GET'] = $_GET;
		$request['POST'] = $_POST;
		$this->request = (object) $request;
	}
	
	/**
	 * Creates PDO instance
	 * 
	 * @return PDO
	 */
	function getDBConnection() {
		$dsn = \sprintf("mysql:dbname=%s;host=%s", DB_NAME, DB_HOST );
		
		try {
			$db = new PDO($dsn, DB_USER, DB_PASSWORD);
		} catch (\PDOException $e) {
			die( "Could not connect to database. Error message: " . $e->getMessage() );
		}
		return $db;
	}
	
	/** 
	 * @return SiteController
	 */
	abstract function getController();
}

