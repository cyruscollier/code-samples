<?php 

namespace Client\Model;

/**
 * Nav-specific component based on name
 * 
 * @author ccollier
 *
 */
class NavComponent extends ContentComponent {
	
	protected function setPreparedStatements() {
		parent::setPreparedStatements();
		$this->setPreparedModelStatement( self::KEY_OBJECTS, "name LIKE 'nav-header_%' OR name LIKE 'nav-content_%'", null, 100 );
	}
}
