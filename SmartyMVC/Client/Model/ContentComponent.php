<?php 

namespace Client\Model;

use CWD\Model\PDOModel;

/**
 * Model class for a content component
 * 
 * @author ccollier
 *
 */
class ContentComponent extends PDOModel {
	
	const TABLE_NAME = 'content_components';
	
	public $id;
	public $name;
	public $type;
	public $body;
	
	protected function setPreparedStatements() {
		$this->setPreparedModelStatement( self::KEY_OBJECT, 'name=:name AND type=:type' );
	}
}
