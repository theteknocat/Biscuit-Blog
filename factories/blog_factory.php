<?php
/**
 * Special factory for the blog model with method for fetching the most recent blog entry
 *
 * @package default
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0
 */
class BlogFactory extends ModelFactory {
	/**
	 * Find the most recent entry, either for any category or a specified one
	 *
	 * @param Page|null $page Instance of Page model of the category to find the most recent entry in, or NULL for the most recent in any category
	 * @return void
	 * @author Peter Epp
	 */
	public function find_most_recent($limit,$page = null) {
		if (!empty($page)) {
			$slug = $page->slug();
			$query = "SELECT * FROM `blogs` WHERE `category_id` IN (SELECT `id` FROM `page_index` WHERE `slug` = '{$slug}' OR `slug` LIKE '{$slug}/%') ORDER BY `updated_date` DESC, `post_date` DESC LIMIT ".$limit;
		} else {
			$query = "SELECT * FROM `blogs` ORDER BY `updated_date` DESC, `post_date` DESC LIMIT ".$limit;
		}
		return $this->models_from_query($query);
	}
	/**
	 * Get the count of all comments for all blog entries and return them in an array by blog id
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function comment_count() {
		$query = "SELECT `bc`.`blog_id`, COUNT(DISTINCT `bc`.`id`) AS `comment_count` FROM `blogs` `b` LEFT JOIN `blog_comments` `bc` ON (`bc`.`blog_id` = `b`.`id`) GROUP BY `bc`.`blog_id`";
		$comment_counts = DB::fetch($query);
		$counts_by_blog_id = array();
		if (!empty($comment_counts)) {
			foreach ($comment_counts as $comment_count) {
				$counts_by_blog_id[$comment_count['blog_id']] = $comment_count['comment_count'];
			}
		}
		return $counts_by_blog_id;
	}
	/**
	 * Find the slug of the top-level page of the blog
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function get_top_level() {
		return DB::fetch_one("SELECT `slug` FROM `page_index` WHERE `parent` = (SELECT `id` FROM `menus` WHERE `var_name` = 'blog_categories')");
	}
}
?>