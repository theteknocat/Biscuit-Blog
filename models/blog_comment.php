<?php
/**
 * Mode the blog_comments table
 *
 * @package Modules
 * @subpackage Blog
 * @author Peter Epp
 * @version $Id: blog_comment.php 13843 2011-07-27 19:45:49Z teknocat $
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