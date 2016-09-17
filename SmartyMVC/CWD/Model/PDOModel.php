<?php 

namespace CWD\Model;

use PDO, PDOStatement;

/**
 * Uses PDO statements to provide provide some CRUD operations
 * 
 * @author ccollier
 *
 */
abstract class PDOModel extends Model {
	
	const TABLE_NAME = 'undefined';
	const KEY_OBJECT = 'object';
	const KEY_OBJECTS = 'objects';
	const OBJECTS_FETCH_STYLE = null;
	
	/**
	 * @var PDO
	 */
	private static $model_pdo;
	
	/**
	 * @var PDOModel[]
	 */
	private static $model_singletons = [];
	
	/**
	 * @var PDOStatement[]
	 */
	protected $prepared_statements = [];
	
	protected $columns = [];
	
	/**
	 * Sets model table columns
	 * @param array $data
	 */
	function __construct( $data = [] ) {
		parent::__construct( $data );
		$this->columns = $this->getColumns();
	}
	
	/**
	 * Sets singleton PDO instance
	 * 
	 * @param PDO $pdo
	 */
	static function init( PDO $pdo ) {
		self::$model_pdo = $pdo;
	}
	
	/**
	 * Retrieves a single instance of called model class
	 * 
	 * @param array $params
	 * 
	 * @return static
	 * 
	 */
	static function getObject( array $params = [] ) {
		$statement = self::singleton()->getPreparedStatement( self::KEY_OBJECT, $params );
		$statement->execute( $params );
		return $statement->fetch();
	}
	
	/**
	 * Retrieves multiple instances of called model class
	 *
	 * @param array $params
	 *
	 * @return static
	 *
	 */
	static function getObjects( array $params = [] ) {
		$statement = self::singleton()->getPreparedStatement( self::KEY_OBJECTS, $params );
		$statement->execute( $params );
		return $statement->fetchAll( static::OBJECTS_FETCH_STYLE );
	}
	
	/**
	 * Get stored singleton of called class
	 * 
	 * @return PDOModel
	 */
	protected static function singleton() {
		$cls = get_called_class(); // late-static-bound class name
		if (!isset(self::$model_singletons[$cls])) {
			self::$model_singletons[$cls] = new static;
			self::$model_singletons[$cls]->setPreparedStatements();
		}
		return self::$model_singletons[$cls];
	}
	
	/**
	 * @return PDO
	 */
	protected static function pdo() {
		return self::$model_pdo;
	}
	
	/**
	 * Generate SELECT query with configurable clauses
	 *  
	 * @param array $columns
	 * @param string $where
	 * @param string $orderby
	 * @param string $limit
	 * 
	 * @return string
	 */
	protected function buildSelectQuery( array $columns, $where = '1=1', $orderby = null, $limit = '0, 1') {
		return sprintf( 'SELECT %s FROM %s WHERE %s ORDER BY %s LIMIT %s',
			implode( ',', (array) $columns ),
			static::TABLE_NAME,
			$where,
			!is_null( $orderby ) ? $orderby: $this->columns[0] . ' ASC',
			$limit
		);
	}
	
	/**
	 * Generate SQL WHERE clause from key-value pairs
	 * 
	 * @param array $params
	 * 
	 * @return string
	 */
	protected function buildWhereClause( array $params ) {
		$parts = [];
		foreach ( $params as $key => $value ) {
			$parts[] = $key . '=:' . $key;
		}
		return implode( ' AND ', $parts );
	}
	
	/**
	 * Generate query for PDOStatement and store instance with key reference
	 * 
	 * @param string $key
	 * @param array $columns
	 * @param string $where
	 * @param string $orderby
	 * @param string $limit
	 */
	protected function setPreparedStatement( $key, array $columns, $where = '1=1', $orderby = null, $limit = '0, 1' ) {
		$query = $this->buildSelectQuery( $columns, $where, $orderby, $limit );
		$statement = $this->prepareStatement( $query );
		$this->prepared_statements[$key] = $statement;
	}
	
	/**
	 * Wrapper for PDOModel::setPreparedStatement() using defined model columns
	 * 
	 * @param string $key
	 * @param string $where
	 * @param string $orderby
	 * @param string $limit
	 */
	protected function setPreparedModelStatement( $key, $where = '1=1', $orderby = null, $limit = '0, 1' ) {
		$this->setPreparedStatement( $key, $this->columns, $where, $orderby, $limit );
	}
	
	/**
	 * Get existing PDOStatement by key or generate new instance without storing it
	 * 
	 * @param string $key
	 * @param array $params
	 * 
	 * @return PDOStatement
	 */
	protected function getPreparedStatement( $key, array $params ) {
		if ( array_key_exists( $key, $this->prepared_statements ) ) {
			$statement = $this->prepared_statements[$key];
		} else {
			$query = $this->buildSelectQuery( $this->buildWhereClause( $params ) );
			$statement = $this->prepareStatement( $query );
		}
		return $statement;
	}
	
	/**
	 * New PDOStatement instance based on query
	 * 
	 * @param string $query
	 * 
	 * @return PDOStatement
	 */
	protected function prepareStatement( $query ) {
		$statement = self::pdo()->prepare( $query );
		$statement->setFetchMode( PDO::FETCH_CLASS, get_called_class() );
		return $statement;
	}
	
	/**
	 * Defined columns from model properties
	 * 
	 * @return array
	 */
	protected function getColumns() {
		$model_fields = create_function( '$obj', 'return get_object_vars($obj);' );
		return array_keys( $model_fields( $this ) );
	}
	
	/**
	 * Optionally implement in child classes to preload prepared statements
	 */
	protected function setPreparedStatements() {}

}
