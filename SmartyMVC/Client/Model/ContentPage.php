<?php 

namespace Client\Model;

use CWD\Model\PDOModel;
use DateTime;

/**
 * Model class for a content page
 *
 * @author ccollier
 *
 */
class ContentPage extends PDOModel {
	
	const TABLE_NAME = 'content_pages';
	const KEY_OBJECT_CONTENT = 'content';
	
	public $id;
	public $slug;
	public $parent;
	public $title;
	public $content = '';
	public $template;
	public $cache = 0;
	public $published = 0;
	public $published_date = '0000-00-00 00:00:00';
	public $unpublished_date = "9111-11-11 11:11:11";
	public $seo_title;
	public $seo_description;
	public $seo_keywords;
	
	function isPublished() {
		return $this->published == 1 && $this->isReleased();
	}
	
	/**
	 * Check if active publish date of page
	 * 
	 * @return boolean
	 */
	function isReleased() {
		date_default_timezone_set( 'UTC' );
		$published = new DateTime( $this->published_date );
		$unpublished = new DateTime( $this->unpublished_date );
		return $published < new DateTime && $unpublished > new DateTime;
	}
	
	/**
	 * Use SEO title if available
	 */
	function getTitle() {
		return !is_null( $this->seo_title ) ? $this->seo_title : $this->title;
	}
	
	/**
	 * Fetch content from DB based on query params
	 * 
	 * @param array $params
	 * 
	 * @return string
	 */
	static function getContent( array $params = [] ) {
		$statement = self::singleton()->getPreparedStatement( self::KEY_OBJECT_CONTENT, $params );
		$statement->setFetchMode( \PDO::FETCH_COLUMN, 0 );
		$statement->execute( $params );
		return $statement->fetch();
	}
	
	/**
	 * Prepare statements with page slug
	 */
	protected function setPreparedStatements() {
		$this->setPreparedModelStatement( self::KEY_OBJECT, 'slug=:slug' );
		$this->setPreparedStatement( self::KEY_OBJECT_CONTENT, 'content', 'slug=:slug' );
	}
}
