<?php 

namespace Client\Smarty;

use CWD\Site;
use Client\Model;

/**
 * Controller for managing main page template and content
 * 
 * @author ccollier
 *
 */
class PageTemplateController extends ClientTemplateController {
	
	/**
	 * @var Model\ContentPage
	 */
	protected $view_data;
	
	/**
	 * Set global variables for view template and assign content
	 */
	function setView() {
		parent::setView();
		$this->View->assignGlobal( 'page', get_object_vars( $this->view_data ) );
		$this->View->assignGlobal( 'page_title', $this->view_data->getTitle() );
		$this->View->assignGlobal( 'GET', $this->request->GET );
		$content = $this->Smarty->fetch( 'string:' . $this->view_data->content, $this->View );
		$this->View->assign( 'content', $content );
	}
	
	/**
	 * Gets content page from DB
	 * 
	 * @return Model\ContentPage
	 */
	function getViewData() {
		if( isset( $this->view_data ) ) return $this->view_data;
		$page_slug = end($this->request->path);
		$page = $this->getContentPage( $page_slug );
		$this->checkPageRedirect( $page );
		return $page;
	}
	
	/**
	 * Page redirect logic
	 * 
	 * @param Model\ContentPage $page
	 */
	function checkPageRedirect( Model\ContentPage $page ) {
		if ( !$page->slug ) return false;
		$redirect_url = false;
		$page_link = new Model\PageLink( $page );
		$page_link->setURL( null, [$this, 'getPageLink'] );
		if ( $page_link->getURL() != $this->request->fullpath ) {
			$redirect_url =  SITE_URL . $page_link->getURL();
		} elseif ( $this->isNavContainer( $page ) && $first_child = $this->getFirstChildNavItem( $page_link->slug ) ) {
			$first_child->setURLFromParent( $page_link );
			$redirect_url = SITE_URL . $first_child->getURL();
		}
		if ( $redirect_url ) $this->Site->redirect( $redirect_url );
	}
	
	/**
	 * Gets page link from DB
	 * 
	 * @param string $slug
	 * 
	 * @return Model\PageLink
	 */
	function getPageLink( $slug ) {
		$page_link = Model\PageLink::getObject( compact( 'slug' ) );
		return false !== $page_link ? $page_link : null;
	}
	
	/**
	 * Gets page's first child nav item from DB
	 * 
	 * @param string $parent
	 * 
	 * @return Model\NavItem
	 */
	function getFirstChildNavItem( $parent ) {
		$first_child_nav_item = Model\NavItem::getFirstChild( compact( 'parent' ) );
		return false !== $first_child_nav_item ? $first_child_nav_item : null;
	}
	
	/**
	 * Retrieves full URL for matching slug
	 * 
	 * @param string $slug
	 * 
	 * @return string
	 */
	function getPageURL( $slug ) {
		$url = SITE_URL;
		$page_link = $this->getPageLink( $slug );
		if ( $page_link ) {
			$page_link->setURL( null, [$this, 'getPageLink'] );
			$url .= $page_link->getURL();
		}
		return $url;
	}
	
	/**
	 * Gets content page from DB / static object
	 * 
	 * @param string $slug
	 * 
	 * @return Model\ContentPage
	 */
	function getContentPage( $slug ) {
		if ( !$slug ) $slug = Model\PageLink::ROOT;
		$page = Model\ContentPage::getObject( compact( 'slug' ) );
		if( false === $page || !( $page->isPublished() ) ) {
			$page = new Model\ContentPage( [
				'title' => '404',
				'template' => $this->get404Template(),
			] );
		}
		return $page;
	}
	
}
