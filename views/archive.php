<?php
if (!$BlogManager->at_top_level()) {
	?><p><a href="/<?php echo $BlogManager->top_level_slug() ?>/archive">Full Archive</a></p><?php
	print $Navigation->render_admin_bar($BlogManager,$blog_entry,array(
		'bar_title' => 'Blog Administration',
		'has_new_button' => $BlogManager->user_can_create(),
		'new_button_label' => 'New Entry'
	));
}
if (empty($blog_entries)) {
	?><p class="none-found">There are currently no entries to display.</p><?php
} else {
	?><div id="blog-archive-list"><?php
	if ($blog_paginator->GetPageCount() > 1) {
		?>
		<div class="controls paging"><?php echo $blog_paginator->GetPageLinks(); ?></div>
		<?php
	}
	$actual_display_count = 0;
	foreach ($blog_entries as $index => $entry) {
		if (!$entry->is_draft() || $BlogManager->user_can_edit()) {
			$fcache = new FragmentCache('Blog', $entry->id());
			$actual_display_count += 1;
			$comment_count = (!empty($blog_comment_count) && (!empty($blog_comment_count[$entry->id()]))) ? $blog_comment_count[$entry->id()] : 0;
			?><div class="index-blog-entry <?php echo $Navigation->tiger_stripe('blog-archive-list') ?>">
				<p class="small" style="float: right"><a href="<?php echo $BlogManager->url('show',$entry) ?>#blog-comments"><?php echo $comment_count ?> Comment<?php echo ($comment_count != 1) ? 's' : ''; ?></a></p><?php
			if ($fcache->start('archive-list')) {
				?><h3><a href="<?php echo $BlogManager->url('show',$entry) ?>"><?php echo $entry->title(); ?></a><?php
				if ($entry->is_draft()) {
					?> <span class="small">(Draft)</span><?php
				}
				?></h3>
				<p class="small"><?php
				?><strong>Published:</strong> <?php
				echo Crumbs::date_format($entry->post_date(),'g:ia F jS, Y');
				if ($entry->post_date() != $entry->updated_date()) {
					?> &bull; <strong>Updated:</strong> <?php
					echo Crumbs::date_format($entry->updated_date(),'g:ia F jS, Y');
				}
				?></p><?php
				$fcache->end('archive-list');
			}
			?></div><?php
		}
	}
	if ($actual_display_count > 0) {
		if ($blog_paginator->GetPageCount() > 1) {
			?>
			<div class="cantrols paging"><strong>Page:</strong> <?php echo $blog_paginator->GetPageLinks(); ?></div>
			<?php
		}
	}
	?></div><?php
	if ($actual_display_count == 0) {
		?><p class="none-found">There are currently no entries to display.</p><?php
	}
}
?>