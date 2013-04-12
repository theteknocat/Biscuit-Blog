<?php
/**
 * Mode the blog_comments table
 *
 * @package Modules
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0
 */
class BlogComment extends AbstractModel {
	/**
	 * Always set the date to the current datetime
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function post_date_field_default() {
		return date('Y-m-d H:i:s');
	}
	public function __toString() {
		return 'the comment by '.$this->username().' at '.Crumbs::date_format($this->post_date(),'g:ia M j, Y');
	}
}
?>