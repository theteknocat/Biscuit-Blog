<?php
// Implementation Notes
// ====================

// This template provides an example of the output to put in the body area of the site template you would create in your theme for blog pages.
// It includes all the variables provided by the blog module.  You can copy and paste the code below into your custom blog page template, customize
// the layout as desired and code up the appropriate CSS.

// Note that you will also want to customize the PageContent index view file, which is used for rendering all the categories, to remove the last updated
// Date so the blog entry's dates are the ones users see.

// Also, if your template has an outer container with the class of "indexable-content", be sure to remove that class as this template puts that class
// on the blog content area only.  Don't want the site search indexing the breadcrumbs and other menus as that isn't necessary for effective search
// results.

?>
<div id="left-sidebar">
	<div class="sidebar-box">
		<a class="rss-link" href="<?php echo $BlogManager->url('rss') ?>">Subscribe</a>
	</div>
	<?php
	if ($BlogManager->action() == 'index') {
		?><div class="sidebar-box">
		<h4>Facebook</h4><?php
		echo $BlogManager->render_fb_like_button();
		?></div><?php
	}
	?>
	<div id="blog-search-box" class="sidebar-box">
		<h4>Search Blog</h4>
		<?php print $search_form ?>
	</div>
	<div class="sidebar-box category-menu">
		<h4>Categories</h4>
		<?php print $blog_categories ?>
		<a href="<?php echo $BlogManager->url('archive') ?>"><?php echo $Biscuit->Page->title() ?> Archives</a>
	</div>
	<?php
	if (!empty($blog_entry_list)) {
		?><div class="sidebar-box">
			<h4><?php
			if ($BlogManager->at_top_level()) {
				// This will be most recent entries on the top level page
				?>Recent Entries<?php
			} else {
				// Or all entries for the category
				?>Other Entries in this Category<?php
			}
			?></h4>
			<?php print $blog_entry_list ?>
		</div><?php
	}
	?>
</div>
<div id="blog-content">
	<?php
	if (!empty($blog_breadcrumbs)) {
		?>
	<div class="breadcrumbs"><?php print $blog_breadcrumbs ?></div>
		<?php
	}
	?>
	<div class="indexable-content">
		<h2><?php print $blog_page_title; ?></h2>
		<?php
		print $page_content;
		?>
	</div>
</div>
<div style="clear: both"></div>