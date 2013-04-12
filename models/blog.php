<?php
/**
 * Model the blogs table
 *
 * @package Modules
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0
 */
class Blog extends AbstractModel {
	/**
	 * This model has an actual teaser attribute as opposed to making a teaser out of the body content, so we need to override the parent teaser()
	 * method to return that attribute.
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function teaser() {
		return $this->_get_attribute('teaser');
	}
}
?>