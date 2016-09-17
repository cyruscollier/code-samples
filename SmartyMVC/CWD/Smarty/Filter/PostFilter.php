<?php

namespace CWD\Smarty\Filter;

/**
 * Base class for Smarty post-filters
 * 
 * @author ccollier
 *
 */
abstract class PostFilter extends FilterExtension {

	const TYPE = \Smarty::FILTER_POST;
	
}