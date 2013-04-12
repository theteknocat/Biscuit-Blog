<?php
/**
 * Model the blogs table
 *
 * @package Modules
 * @subpackage Blog
 * @author Peter Epp
 * @version $Id: blog.php 14628 2012-04-30 16:30:56Z teknocat $
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
	/**
	 * Set the default post date to now
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function post_date_field_default() {
		return date('Y-m-d H:i:s');
	}
	/**
	 * Set the default updated date to now
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function updated_date_field_default() {
		return date('Y-m-d H:i:s');
	}
	/**
	 * Set the category ID for new entries to the current page ID, otherwise nothing if it's not new
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function category_id_field_default() {
		return Biscuit::instance()->Page->id();
	}
	/**
	 * Set defaults for certain attributes to ensure correct values when saving:
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function _set_attribute_defaults() {
		$was_draft = $this->user_input('was_draft');
		if ($this->is_draft() || ($was_draft && !$this->is_draft())) {
			// If this is a draft, or we just changed from draft to non-draft status, set both dates to now:
			$this->set_post_date(date('Y-m-d H:i:s'));
			$this->set_updated_date(date('Y-m-d H:i:s'));
		} else if (!$was_draft && !$this->is_draft()) {
			// If we did not change draft status and it is not a draft, make sure the updated date is set to now:
			$this->set_updated_date(date('Y-m-d H:i:s'));
		}
	}
}
