<?php

namespace Client\Smarty\Plugin;

use CWD\Smarty\Plugin\BlockPlugin;
use Smarty_Internal_Template;
use Client\Model\NavItemAnchor;

/**
 * Loads template to create section tabs
 * 
 * @author ccollier
 *
 */
class SectionTabPlugin extends BlockPlugin {
	
	protected $name = 'sectiontab';
	
	private $aggregate_data = [];

	/**
	 * Assign tab data and appends to aggregate set
	 * 
	 * @param array $params
	 * @param string $content
	 * @param Smarty_Internal_Template $template
	 * @param boolean $repeat
	 * 
	 * @return string
	 */
	function execute( array $params = [], $content = '',
			Smarty_Internal_Template $template = null, &$repeat = false ) {
		if(!$repeat) { 
			$aggregate_count = count($this->aggregate_data);
			$template_data = $params;
			$template_data['content'] = $content;
			$template_data['id'] = $this->getAnchorId( $params['title'] );
			$template_data['active'] = false;
			$template->smarty->assign( 'sectiontab', $template_data );
			$this->aggregate_data[] = $template_data;
			$template->parent->assign('sectiontab_aggregate', $this->aggregate_data);
			return $template->smarty->fetch('plugins/sectiontab.tpl');
		}
		return $content;
	}
	
	/**
	 * Extract link anchors from tab content
	 * 
	 * @param string $content
	 * 
	 * @return NavItemAnchor[]
	 */
	function getAnchorsFromContent( &$content ) {
		$tag_regex = '/{'.$this->name.' .*?title="(.*?)".*?}/';
		$anchors = $matches = array();
		preg_match_all( $tag_regex, $content, $matches );
		if( !empty( $matches[1] ) ) {
			foreach ( $matches[1] as $match ) {
				$anchors[] = new NavItemAnchor( array( 'title' => $match, 'slug' => $this->getAnchorId( $match ) ) );
			}
		}
		return $anchors;
	}
	
	/**
	 * Convert title to anchor id
	 * 
	 * @param string $title
	 * 
	 * @return string
	 */
	private function getAnchorId( $title ) {
		$section_id = preg_replace( '/[^\w\s]/', '', strtolower( $title ) );
		return str_replace( ' ','-', $section_id );
	}
		
}