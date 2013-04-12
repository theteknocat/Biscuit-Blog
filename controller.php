<?php
/**
 * Blog module
 *
 * @package Modules
 * @subpackage Blog
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0 $Id: controller.php 14350 2011-10-26 18:17:34Z teknocat $
 */
class BlogManager extends AbstractModuleController {
	protected $_dependencies = array('primary' => 'PageContent','primary' => "HtmlPurify", "new" => "TinyMce", "edit" => "TinyMce");
	protected $_models = array(
		'Blog' => 'Blog',
		'BlogComment' => 'BlogComment',
		'Page' => 'Page',
		'BlogCommentSubscriber' => 'BlogCommentSubscriber'
	);
	/**
	 * How many recent entries to show in list on top level page
	 *
	 * @var string
	 */
	private $_recent_entry_count = 5;
	/**
	 * Place to cache category page models when fetching them in the URL method to reduce DB queries and model creation overhead
	 *
	 * @var array
	 */
	private $_category_cache = array();
	/**
	 * How many blog entries per page for archives
	 *
	 * @var string
	 */
	private $_archive_entries_per_page = 20;
	/**
	 * How many blog comments per page when viewing an entry
	 *
	 * @var string
	 */
	private $_comments_per_page = 15;
	/**
	 * Pagination previous marker
	 *
	 * @var string
	 */
	private $_pagination_prev_marker = '&laquo;';
	/**
	 * Pagination separator symbol
	 *
	 * @var string
	 */
	private $_pagination_separator = '';
	/**
	 * Pagination next marker
	 *
	 * @var string
	 */
	private $_pagination_next_marker = '&raquo;';
	/**
	 * How many pagination links to display for comments
	 *
	 * @var int
	 */
	private $_pagination_links_to_display = 10;
	/**
	 * List of actions that require an ID in the request, in addition to the base actions that always require an id (show, edit, delete)
	 *
	 * @var string
	 */
	protected $_actions_requiring_id = array('display_comments');
	/**
	 * Place to cache the top-level slug of the blog
	 *
	 * @var string
	 */
	private $_top_level_slug;
	/**
	 * Fetch the 5 most recent entries from all categories to display in the sidebar of all blog pages on show and index actions, then call the parent run method
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function run() {
		if (defined('BLOG_RECENT_ENTRY_COUNT') && (int)BLOG_RECENT_ENTRY_COUNT > 0) {
			$this->_recent_entry_count = BLOG_RECENT_ENTRY_COUNT;
		}
		if (defined('BLOG_PAGINATION_LINKS_TO_DISPLAY') && (int)BLOG_PAGINATION_LINKS_TO_DISPLAY > 0) {
			$this->_pagination_links_to_display = BLOG_PAGINATION_LINKS_TO_DISPLAY;
		}
		if ($this->action() == 'index' || $this->action() == 'show') {
			$this->set_view_var('all_recent_entries',$this->Blog->find_all(array('post_date' => 'DESC'),5));
		}
		// Initialize ShareThis extension if it's not already loaded but the file exists in the load path. We do this because it's not required, just
		// a nice-to-have
		if (!$this->Biscuit->extension_exists('ShareThis') && Crumbs::file_exists_in_load_path('extensions/share_this')) {
			Console::log("BlogManager: initializing ShareThis extension on the fly");
			$this->Biscuit->init_extension('ShareThis');
		} else {
			Console::log("BlogManager: cannot find ShareThis extension");
		}
		if (!$this->Biscuit->extension_exists('FbLike') && Crumbs::file_exists_in_load_path('extensions/fb_like')) {
			Console::log("BlogManager: initializing FbLike extension on the fly");
			$this->Biscuit->init_extension('FbLike');
		} else {
			Console::log("BlogManager: cannot find FbLike extension");
		}
		$this->register_css(array('filename' => 'common.css', 'media' => 'screen'));
		if (defined('BLOG_FEED_TITLE') && BLOG_FEED_TITLE != '') {
			$feed_title = BLOG_FEED_TITLE;
		} else {
			$feed_title = 'Blog Feed :: '.SITE_TITLE;
		}
		$this->Biscuit->register_header_tag('link',array(
			'rel' => 'alternate',
			'type' => 'application/rss+xml',
			'title' => $feed_title,
			'href' => STANDARD_URL.$this->url('rss')
		));
		parent::run();
	}
	/**
	 * Shortcut to the ShareThis extension button render method that checks first to see if the extension is installed. If using the ShareThis
	 * extension throughout your site, not just for the blog, you can just call it's render method directly from within your customized views
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function render_share_this_button() {
		if ($this->Biscuit->extension_exists('ShareThis')) {
			return $this->Biscuit->ExtensionShareThis()->render_button();
		}
		return '';
	}
	/**
	 * Shortcut to the FbLike extension button render method that checks first to see if the extension is installed. If using the FbLike extension
	 * throughout your site, not just for the blog, you can just call it's render method directly from within your customized views
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function render_fb_like_button() {
		if ($this->Biscuit->extension_exists('FbLike')) {
			return $this->Biscuit->ExtensionFbLike()->render_button();
		}
		return '';
	}
	/**
	 * Override default index to fetch paginated list of entries for the current category, but only if not the top-level
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_index() {
		// Always fetch the most recent entry:
		if ($this->at_top_level()) {
			// For the top level, fetch the latest X entries in any category:
			$latest_entries = $this->Blog->find_most_recent($this->_recent_entry_count);
			$total_entry_count = $this->Blog->record_count();
		} else {
			// Otherwise fetch the latest X entries for the current category or it's sub-categories:
			$latest_entries = $this->Blog->find_most_recent($this->_recent_entry_count,$this->Biscuit->Page);
			$total_entry_count = $this->Blog->record_count('`category_id` = '.$this->Biscuit->Page->id());
		}
		if (!empty($latest_entries)) {
			$this->set_view_var('blog_entries',$latest_entries);
			$this->set_view_var('recent_entry_count',$this->_recent_entry_count);
			$this->set_view_var('total_entry_count',$total_entry_count);
		}
		if (!$this->at_top_level()) {
			$page_title = $this->Biscuit->Page->title();
		} else {
			$page_title = 'Blog Home';
		}
		$this->set_view_var('blog_page_title',$page_title);
		$this->set_common_view_vars();
		$this->render();
	}
	/**
	 * Customized edit method that sets common view vars after running the parent edit method
	 *
	 * @param string $mode 
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_edit($mode = 'edit') {
		if ($this->action() == 'edit' || $this->action() == 'new') {
			$this->Biscuit->ExtensionTinyMce()->register_components();
			if ($this->Biscuit->module_exists('FileManager')) {
				$this->set_view_var('file_browser_callback', 'FileManagerActivate.tiny_mce');
			} else {
				$this->set_view_var('file_browser_callback', 'tinyBrowser');
			}
			$this->register_css(array('filename' => 'edit.css', 'media' => 'screen'));
			if ($this->action() == 'new' && $this->at_top_level()) {
				// Not allowed to create new entry from the top level, must be in a sub-category
				Session::flash('user_error','Please select a category before creating a new entry.');
				Response::redirect($this->url());
			}
		} else if ($this->action() == 'new_blog_comment' && !Request::is_ajax()) {
			Session::flash('user_error','New comments may only be posted by clicking the "Leave Comment" link on an article\'s page.');
			Response::redirect('/'.$this->top_level_slug());
		} else if ($this->action() == 'edit_blog_comment') {
			Session::flash('user_error','Comments may not be edited once published.');
			Response::redirect('/'.$this->top_level_slug());
		}
		parent::action_edit($mode);
		if ($this->action() == 'edit' || $this->action() == 'new') {
			$this->set_common_view_vars();
			$this->set_view_var('blog_page_title',AkInflector::humanize($mode).' Blog Entry');
		}
	}
	/**
	 * Customized show action to set the view var used by the show view file to 'blog_entry' rather than the default 'blog' that the parent method
	 * would set. Also custom set the page and special blog_page_title vars
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_show() {
		$this->register_js('footer','comments.js');
		$entry = $this->Blog->find($this->params['id']);
		if (!$entry) {
			throw new RecordNotFoundException();
		}
		// The set_common_view_vars() method will need this:
		if ($entry->is_draft() && !$this->user_can_edit()) {
			$page = $this->Page->find($entry->category_id());
			Session::flash('user_message',"Sorry, the entry you tried to access is currently in Draft status. You can read it once it's been published.");
			Response::redirect('/'.$page->slug());
		}
		$this->enforce_canonical_show_url($entry);
		Event::fire('instantiated_model',$entry);
		// Stash the category page title before setting it on the view so we have it to send to the set_common_view_vars() method
		$category_page_title = $this->Biscuit->Page->title();
		$blog_page_title = $entry->title();
		$this->title($blog_page_title);
		$this->set_view_var('blog_page_title',$blog_page_title);
		$this->set_view_var('blog_entry',$entry);
		$this->set_view_var('blog_id',$entry->id());
		$all_other_entries = $this->Blog->find_all_by('category_id',$this->Biscuit->Page->id(),array('post_date' => 'DESC'),'`id` != '.$this->params['id'].((!$this->Biscuit->ModuleAuthenticator()->user_is_logged_in()) ? ' AND `is_draft` = 0' : ''));
		$this->set_view_var('blog_entry_list',Crumbs::capture_include('blog/views/entry-list.php',array('BlogManager' => $this, 'blog_entries' => $all_other_entries)));
		$this->get_comments($entry->id());
		// Set an empty blog comment object in the view for helping render the comment form:
		$this->set_view_var('blog_comment',$this->BlogComment->create(array('blog_id' => $entry->id())));
		$this->Biscuit->ExtensionFbLike()->set_url(STANDARD_URL.$this->url('show',$entry));
		$this->set_common_view_vars($category_page_title);
		$this->render();
	}
	/**
	 * Display the list of comments after save. Only called on ajax requests.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_display_comments() {
		if (!empty($this->params['id'])) {
			$blog_id = $this->params['id'];
		} else if (!empty($this->params['blog_comment']['blog_id'])) {
			$blog_id = $this->params['blog_comment']['blog_id'];
		}
		if (!Request::is_ajax()) {
			Session::flash('user_message','Blog comments may not be accessed outside the context of their associated blog entry.');
			Response::redirect($this->url('show',$blog_id));
		}
		$this->set_view_var('blog_id',$blog_id);
		$this->get_comments($blog_id);
		$this->render();
	}
	/**
	 * Common method for fetching comments and setting them in the view
	 *
	 * @param string $blog_id 
	 * @return void
	 * @author Peter Epp
	 */
	private function get_comments($blog_id) {
		$record_count = $this->BlogComment->record_count('`blog_id` = '.$blog_id);
		if (!empty($this->params['comment_pages'])) {
			$pages_to_show = $this->params['comment_pages'];
			$this->set_view_var('is_show_more_request',true);
		} else {
			$pages_to_show = 1;
			$this->set_view_var('is_show_more_request',false);
		}
		if (Request::is_ajax()) {
			// If ajax request, only fetch the next X comments
			$starting_record = $pages_to_show*$this->_comments_per_page-$this->_comments_per_page;
			$records_to_fetch = $this->_comments_per_page;
		} else {
			// If not ajax request, fetch all records up to the number of requested pages to show
			$starting_record = 0;
			$records_to_fetch = $this->_comments_per_page*$pages_to_show;
		}
		$sql_limit = $starting_record.', '.$records_to_fetch;
		$total_pages = ceil($record_count/$this->_comments_per_page);
		if ($pages_to_show < $total_pages) {
			$this->set_view_var('next_page',($pages_to_show+1));
		}
		$this->set_view_var('blog_total_comment_count',$record_count);
		$this->set_view_var('blog_comments',$this->BlogComment->find_all_by('blog_id',$blog_id,array('post_date' => 'DESC'),'',$sql_limit));
	}
	/**
	 * Delete a blog comment and return a response based on the success or failure of the delete operation. This customized function returns special
	 * responses to ajax requests for the special ajax deletion handler JS
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_delete_blog_comment() {
		Console::log("Running custom comment delete action...");
		$model_name = 'BlogComment';
		$model = $this->BlogComment->find($this->params['id']);
		if (!$model) {
			throw new RecordNotFoundException();
		}
		Event::fire('instantiated_model',$model);
		if ($this->confirm_deletion($model,'BlogComment')) {
			Console::log("Deletion confirmed!");
			// Before we proceed with delete, get the URL we'll need to pass to the successful_delete event. This is just in case the module has a custom url()
			// method that may need to still lookup the model in order to create the URL before it gets deleted, which would cause an error if it's already
			// been trashed. An example is the page content module.
			$show_action = 'show';
			if ($model_name != $this->_primary_model) {
				$show_action .= '_'.AkInflector::underscore($model_name);
			}
			$url = $this->url($show_action,$model->id());
			// Either the request is post or the delete_confirmed parameter was provided. Proceed with delete operation.
			if (!$model->delete()) {
				Session::flash('user_error', "Failed to remove ".AkInflector::titleize(AkInflector::singularize($model_name))." item with ID ".$model->id());
				$success = false;
			} else {
				Event::fire("successful_delete",$model,$url);
				$success = true;
			}
			if (!Request::is_ajax()) {
				Response::redirect($this->return_url($model_name));
			}
			else {
				Response::content_type('text/plain');
				if ($success) {
					$this->Biscuit->render('OK');
				} else {
					Response::http_status(500);
					$this->Biscuit->render('ERROR');
				}
			}
		}
	}
	/**
	 * Find all entries paginated
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_archive() {
		if ($this->at_top_level()) {
			$record_count = $this->Blog->record_count();
		} else {
			$record_count = $this->Blog->record_count('`category_id` = '.$this->Biscuit->Page->id());
		}
		$paginator = new PaginateIt();
		$paginator->SetLinksFormat($this->_pagination_prev_marker,$this->_pagination_separator,$this->_pagination_next_marker);
		$paginator->SetItemsPerPage($this->_archive_entries_per_page);
		$paginator->SetLinksToDisplay($this->_pagination_links_to_display);
		$paginator->SetLinksHref($this->url());
		$paginator->SetItemCount($record_count);
		$sql_limit = $paginator->GetSqlLimit();
		$this->set_view_var('blog_paginator',$paginator);
		if ($this->at_top_level()) {
			// For the top level, fetch all
			$blogs = $this->Blog->find_all(array('post_date' => 'DESC'),'',$sql_limit);
			$title = 'Archives';
		} else {
			// Otherwise fetch all in the current category
			$blogs = $this->Blog->find_all_by('category_id',$this->Biscuit->Page->id(),array('post_date' => 'DESC'),'',$sql_limit);
			$title = $this->Biscuit->Page->title().' Archives';
		}
		$this->title($title);
		$this->set_view_var('blog_page_title',$title);
		$this->set_view_var('blog_entries',$blogs);
		$this->set_common_view_vars();
		$this->render();
	}
	/**
	 * Render Atom RSS feed
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_rss_feed() {
		$this->Biscuit->render_with_template(false);
		require_once('biscuit-core/vendor/FeedWriter/FeedWriter.php');
		Response::content_type('application/rss+xml; charset=utf-8');
		$blogs = $this->Blog->find_all(array('post_date' => 'DESC'));
		$last_updated = strtotime($blogs[0]->post_date());

		// Creating an instance of FeedWriter class. 
		// The constant RSS2 is passed to mention the version
		$feed = new FeedWriter(RSS2);

		// Setting the channel elements
		// Use wrapper functions for common elements
		if (defined('BLOG_FEED_TITLE') && BLOG_FEED_TITLE) {
			$feed_title = BLOG_FEED_TITLE;
		} else {
			$feed_title = 'Blog Feed :: '.SITE_TITLE;
		}
		Console::log("Blog feed title: ".$feed_title);
		$feed->setTitle($feed_title);
		$feed_link = STANDARD_URL.'/var/feeds/blog_feed.xml';
		$feed->setLink($feed_link);
		$feed->setDescription(H::purify_text($this->Biscuit->Page->content()));

		// For other channel elements, use setChannelElement() function
		$feed->setChannelElement('pubDate', date(DATE_RSS , $last_updated));
		$feed->setChannelElement('lastBuildDate', date(DATE_RSS, time()));
		$feed->setChannelElement('language', 'en-us');

		$site_image = $this->Biscuit->site_image_url();
		if (!empty($site_image)) {
			$feed->setImage($feed_title,$feed_link,$site_image);
		}

		// Adding a feed. Genarally this protion will be in a loop and add all feeds.
		foreach ($blogs as $blog) {
			if (!$blog->is_draft()) {
				// Create an empty FeedItem
				$newItem = $feed->createNewItem();
				$link = STANDARD_URL.$this->url('show',$blog);

				// Add elements to the feed item
				// Use wrapper functions to add common feed elements
				$newItem->setTitle($blog->title());
				$newItem->setLink($link);
				$newItem->setDate(strtotime($blog->post_date()));
				// Internally changed to "summary" tag for ATOM feed
				$newItem->setDescription($blog->content());
				$newItem->addElement('guid',$link);

				// Now add the feed item	
				$feed->addItem($newItem);
			}
		}
		// OK. Everything is done. Now genarate the feed.
		ob_start();
		$feed->genarateFeed();
		$feed_code = ob_get_clean();
		// Render it for the current request to get a response:
		$this->set_view_var('feed_code',$feed_code);
		$this->render();
	}
	/**
	 * Are we at the blog top level?
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function at_top_level() {
		return ($this->Biscuit->Page->slug() == $this->top_level_slug());
	}
	/**
	 * Return the top level slug of the blog
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function top_level_slug() {
		if (empty($this->_top_level_slug)) {
			$this->_top_level_slug = $this->Blog->get_top_level();
		}
		return $this->_top_level_slug;
	}
	/**
	 * Make the new/edit page title say "Blog Entry" instead of just "Blog"
	 *
	 * @param string $mode 
	 * @param string $model_name 
	 * @return void
	 * @author Peter Epp
	 */
	protected function set_edit_title($mode,$model_name) {
		if ($model_name == 'Blog') {
			$model_name = 'BlogEntry';
		}
		$this->title(AkInflector::titleize($mode.' '.AkInflector::singularize($model_name)));
	}
	/**
	 * Set vars common to all views
	 *
	 * @param string $archive_page_title Optional. Set this to override the current page's title in the archive link
	 * @return void
	 * @author Peter Epp
	 */
	private function set_common_view_vars($archive_page_title = null) {
		if ($this->action() != 'archive') {
			if ($this->at_top_level()) {
				$link_title = 'Full Archive';
			} else {
				if (!empty($archive_page_title)) {
					$page_title = $archive_page_title;
				} else {
					$page_title = $this->Biscuit->Page->title();
				}
				$link_title = '"'.$page_title.'" Archive';
			}
			$archive_link = '<a href="'.$this->url('archive').'">'.$link_title.'</a>';
			$this->set_view_var('blog_archive_link',$archive_link);
		} else {
			$this->set_view_var('blog_archive_link','');
		}
		$this->set_view_var('blog_categories',$this->Biscuit->ExtensionNavigation()->render_list_menu('blog_categories'));
		$this->set_view_var('blog_breadcrumbs',$this->Biscuit->ExtensionNavigation()->render_bread_crumbs('Home'));
		if ($this->action() == 'index' || $this->action() == 'archive') {
			$this->set_view_var('blog_comment_count',$this->Blog->comment_count());
		}
	}
	/**
	 * Take care of some business when different models are saved:
	 *
	 * When a page is saved, ensure the blog module is installed on that page
	 * When a blog is saved, trash the RSS XML static cache file so it's re-generated on the next request for it
	 * When a blog comment is saved, set the action to render and set a view var so we know not to render the content heading
	 *
	 * @param Page|Blog|BlogComment $model 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_successful_save($model) {
		$class_name = get_class($model);
		if ($class_name == 'Page' && $model->slug_segment(1) == 'blog') {
			// Install this module as primary on the page being saved. Note that this install method will ensure that any other modules installed on the page
			// as primary will become secondary
			$this->Biscuit->install_module_on_page('Blog',$model->slug(),1);
		} else if ($class_name == 'Blog') {
			@unlink(SITE_ROOT.'/var/feeds/blog_feed.xml');
			$this->_return_url = $this->url('show',$model->id());
		} else if ($class_name == 'BlogComment') {
			$this->set_successful_save_ajax_action('display_comments');
			$this->set_view_var('new_comment_posted',true);
			// $this->subscribe_user_to_comments($model);
			$this->notify_comment_subscribers($model);
		}
	}
	/**
	 * If the user opted to subscribe to comments when they submitted their comment, add them to the subscriber list. This cannot be implemented until Biscuit has
	 * mail queue capability
	 *
	 * @param string $blog_comment 
	 * @return void
	 * @author Peter Epp
	 */
	protected function subscribe_user_to_comments($blog_comment) {
		if (!empty($this->params['subscribe_to_comments'])) {
			$email = $blog_comment->email();
			$subscriber_factory = ModelFactory::instance('BlogCommentSubscriber');
			$subscriber_data = array(
				'blog_id' => $blog_comment->blog_id(),
				'name'    => $blog_comment->username(),
				'email'   => $blog_comment->email()
			);
			$subscriber = $subscriber_factory->create($subscriber_data);
			$subscriber->save();
		}
	}
	/**
	 * Send email notification to blog comment subscribers. Right now this ONLY sends email to the site owner. Mail queue needs to be implemented in Biscuit before
	 * this feature can be fully realized.
	 *
	 * @param string $blog_comment 
	 * @return void
	 * @author Peter Epp
	 */
	protected function notify_comment_subscribers($blog_comment) {
		if ($blog_comment->email() != OWNER_EMAIL) {
			$blog = $this->Blog->find($blog_comment->blog_id());
			$mail_options = array(
				'From'         => 'blog@'.Request::host(),
				'FromName'     => SITE_TITLE,
				'ReplyTo'      => $blog_comment->email(),
				'ReplyToName'  => $blog_comment->username(),
				'Subject'      => "Comment made on '".$blog->title()."'",
				'To'           => OWNER_EMAIL,
				'ToName'       => SITE_OWNER
			);
			$message_vars = array(
				'blog'         => $blog,
				'blog_comment' => $blog_comment,
				'blog_url'     => $this->url('show',$blog)
			);
			$mail = new Mailer();
			$mail->send_mail('blog/views/comment_notification', $mail_options, $message_vars);
		}
	}
	/**
	 * When a page or blog is deleted, toss the RSS feed so it gets re-generated on the next RSS request
	 *
	 * @param string $model 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_successful_delete($model) {
		$class_name = get_class($model);
		if ($class_name == 'Page' || $class_name == 'Blog') {
			@unlink(SITE_ROOT.'/var/feeds/blog_feed.xml');
		}
	}
	protected function act_on_compile_footer() {
		if ($this->is_primary() && (($this->action() == 'edit' && $this->user_can_edit()) || ($this->action() == 'new' && $this->user_can_create()))) {
			$tb_browser_script = $this->Biscuit->ExtensionTinyMce()->render_tinymce_tb_browser_script();
		}
		if (!empty($tb_browser_script)) {
			$this->Biscuit->append_view_var('footer',$tb_browser_script);
		}
	}
	protected function act_on_render_search_form($search_module) {
		$search_module->set_search_root('/'.$this->top_level_slug());
	}
	protected function act_on_fb_like_init($fb_like) {
		if ($this->action() == 'index') {
			$fb_like->set_url(STANDARD_URL.$this->Biscuit->Page->url());
		} else if ($this->action() != 'show') {
			$fb_like->set_can_use(false);
		}
	}
	/**
	 * Add blog entry links to the Tiny MCE link list.
	 *
	 * @return array
	 * @author Peter Epp
	 */
	protected function act_on_build_mce_link_list() {
		$blogs = $this->Blog->find_all(array('post_date' => 'DESC'));
		if (!empty($blogs)) {
			$list_items = array();
			foreach ($blogs as $blog) {
				$list_items[] = array(
					"title" => $blog->title(),
					"url"   => '/canonical-page-link/'.$blog->category_id().'/show/'.$blog->id().$this->friendly_show_slug('show',$blog->id())
				);
			}
			$this->Biscuit->ModuleTinyMceLinkList()->add_to_list($list_items,"Blog Entries");
		}
	}
	/**
	 * Cache the RSS feed to an XML file once it's been compiled
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_content_compiled() {
		if ($this->action() == 'rss_feed') {
			if (Crumbs::ensure_directory(SITE_ROOT.'/var/feeds')) {
				$compiled_content = $this->Biscuit->get_compiled_content();
				// Replace links that start with a slash with the fully qualified URL:
				$compiled_content = preg_replace('/href=\"\//','href="'.STANDARD_URL.'/',$compiled_content);
				$compiled_content = preg_replace('/src=\"\//','src="'.STANDARD_URL.'/',$compiled_content);
				$this->Biscuit->set_compiled_content($compiled_content);
				// Cache to file for subsequent requests:
				file_put_contents(SITE_ROOT.'/var/feeds/blog_feed.xml',$compiled_content);
			}
		}
	}
	/**
	 * When this module is primary, it's primary page is always the one being currently viewed. Otherwise we'll defer to the abstract method
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function primary_page() {
		if ($this->is_primary()) {
			$this->_primary_page = $this->Biscuit->Page->slug();
			return $this->_primary_page;
		}
		return parent::primary_page();
	}
	/**
	 * Handle some custom URLs for this module
	 *
	 * @param string $action 
	 * @param string $id 
	 * @return void
	 * @author Peter Epp
	 */
	public function url($action=null,$id=null) {
		if ($action == 'rss') {
			return '/var/feeds/blog_feed.xml';
		}
		if ($action == 'archive') {
			$page_slug = trim($this->Biscuit->Page->slug(),'/');
			return '/'.$page_slug.'/archive';
		} else if ($action == 'show' || $action == 'edit') {
			// We use the abstract controller's $_models_for_show_url array to cache entry models here to avoid double-caching
			// when the friendly_show_slug() method is called
			if (is_object($id)) {
				// This custom URL method can optionally allow an entry model for show or edit as the ID argument for greater efficiency.
				// We'll cache it here for future use by this URL method as well as the abstract one
				$entry = $id;
				$this->_models_for_show_url['Blog'][$entry->id()] = $entry;
			} else {
				if (empty($this->_models_for_show_url['Blog'][$id])) {
					$this->_models_for_show_url['Blog'][$id] = $this->Blog->find($id);
				}
				$entry = $this->_models_for_show_url['Blog'][$id];
			}
			if (empty($this->_category_cache[$entry->category_id()])) {
				$this->_category_cache[$entry->category_id()] = $this->Page->find($entry->category_id());
			}
			$category = $this->_category_cache[$entry->category_id()];
			$url = '/'.$category->slug().'/'.$action.'/'.$entry->id();
			if ($action == 'show') {
				$url .= $this->friendly_show_slug($action, $entry->id());
			}
			return $url;
		} else {
			return parent::url($action,$id);
		}
	}
	/**
	 * Run migrations to install the module, first checking to ensure that the PageContent module is already installed
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function install_migration() {
		DB::query("CREATE TABLE `blogs` (
		  `id` int(11) NOT NULL auto_increment,
		  `category_id` int(9) unsigned NOT NULL default '0',
		  `title` varchar(255) NOT NULL,
		  `teaser` text,
		  `content` text NOT NULL,
		  `post_date` datetime NOT NULL default '0000-00-00 00:00:00',
		  `updated_date` datetime NOT NULL default '0000-00-00 00:00:00',
		  `is_draft` int(1) NOT NULL default '1',
		  PRIMARY KEY  (`id`),
		  KEY `category_id` (`category_id`),
		  CONSTRAINT `blogs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `page_index` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		DB::query("CREATE TABLE `blog_comments` (
		  `id` int(11) NOT NULL auto_increment,
		  `blog_id` int(11) NOT NULL,
		  `username` varchar(255) NOT NULL default '',
		  `email` varchar(255) NOT NULL,
		  `comments` text NOT NULL,
		  `post_date` datetime default NULL,
		  PRIMARY KEY  (`id`),
		  KEY `blog_id` (`blog_id`),
		  KEY `user_id` (`username`),
		  CONSTRAINT `blog_comments_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		DB::query("CREATE TABLE `blog_comment_subscribers` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `blog_id` int(11) NOT NULL,
		  `name` varchar(255) NOT NULL,
		  `email` varchar(255) NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `blog_id` (`blog_id`),
		  CONSTRAINT `blog_comment_subscribers_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
		// Make sure the page content module is already installed, and if not bail out:
		$page_content_is_installed = DB::fetch_one("SELECT `installed` FROM `modules` WHERE `name` = 'PageContent'");
		if (!$page_content_is_installed) {
			// Page content module not installed. Set Blog module as uninstalled, unset success message, set an error and abort the rest of the installation.
			DB::query("UPDATE `modules` SET `installed` = 0 WHERE `name` = 'Blog'");
			Session::flash_unset('user_message');
			Session::flash('user_error','Blog module could not be installed because it requires the PageContent module to be installed first.');
		} else {
			// Create the blog categories menu:
			$menu_id = DB::insert("INSERT INTO `menus` SET `var_name` = 'blog_categories', `name` = 'Blog Categories'");
			// Create the top-level blog page:
			DB::insert("INSERT INTO `page_index`
				SET `parent` = ?,
				`slug` = 'blog',
				`title` = 'Blog',
				`content` = '<p>Welcome to your new blog. You can edit this page to customize your blog introduction.</p>',
				`updated` = ?,
				`allow_delete` = 0",array($menu_id,date('Y-m-d H:i:s')));
			$blog_module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'Blog'");
			$page_module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'PageContent'");
			// Install the Blog and page content modules on the blog top-level page:
			DB::insert("INSERT INTO `module_pages` (`module_id`,`page_name`,`is_primary`) VALUES (?,'blog',1), (?,'blog',0)",array($blog_module_id,$page_module_id));
			// Install the blog module as secondary on the content_editor page so that it can respond to successful save event of page
			DB::insert("INSERT INTO `module_pages` (`module_id`,`page_name`,`is_primary`) VALUES (?,'content_editor',0)",array($blog_module_id));
			DB::query("REPLACE INTO `system_settings` (`constant_name`, `friendly_name`, `description`, `value`, `required`, `group_name`) VALUES
			('BLOG_RECENT_ENTRY_COUNT', 'No. of Recent Entries to Display', 'This affects the blog main page and category top-level pages. Defaults to 5 if left blank.', '', 0, 'Blog'),
			('BLOG_PAGINATION_LINKS_TO_DISPLAY', 'No. of Pagination Links in Archive', 'How many links to display in the blog archive pagination. Defaults to 10 if left blank.', '', 0, 'Blog'),
			('BLOG_FEED_TITLE','RSS Feed Title','Custom title for the RSS feed if you don&rsquo;t want to use the website title. Defaults to &ldquo;Blog Feed :: [website title]&rdquo; if left blank.','',0,'Blog')");
			Permissions::add(__CLASS__,array('new' => 99, 'edit' => 99, 'delete' => 99, 'edit_blog_comment' => 99, 'delete_blog_comment' => 99),true);
		}
	}
	/**
	 * Completely uninstall the module
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function uninstall_migration() {
		// Drop tables
		DB::query("DROP TABLE IF EXISTS `blog_comments`");
		DB::query("DROP TABLE IF EXISTS `blogs`");
		// Delete pages
		DB::query("DELETE FROM `page_index` WHERE `slug` = 'blog' OR `slug` LIKE 'blog/%'");
		// Delete categories menu
		DB::query("DELETE FROM `menus` WHERE `var_name` = 'blog_categories'");
		// Remove module from pages:
		DB::query("DELETE FROM `module_pages` WHERE `page_name` = 'blog' OR `page_name` LIKE 'blog/%'");
		DB::qruey("DELETE FROM `system_settings` WHERE `constant_name` LIKE 'BLOG_%'");
		Permissions::remove(__CLASS__);
	}
	/**
	 * Provide special URI mapping rules
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public static function uri_mapping_rules() {
		require_once('factories/blog_factory.php');
		$blog_factory = ModelFactory::instance('Blog');
		$top_level_slug = $blog_factory->get_top_level();
		$mapping_rules = array(
			'/^(?P<page_slug>[^\.]+)\/(?P<action>archive)$/',
			'/^(?P<page_slug>[^\.]+)\/(?P<action>display_comments)\/(?P<id>[0-9]+)$/'
		);
		$mapping_rules['page_slug='.$top_level_slug.'&action=rss_feed'] = '/^var\/feeds\/blog_feed\.xml$/';
		return $mapping_rules;
	}
}
