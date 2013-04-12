<?php
if (!$BlogManager->at_top_level()) {
	print $Navigation->render_admin_bar($BlogManager,$blog_entry,array(
		'bar_title' => 'Blog Administration',
		'has_new_button' => $BlogManager->user_can_create(),
		'new_button_label' => 'New Entry'
	));
}
if (empty($blog_entries)) {
	?><p class="none-found"><?php
	if (!$BlogManager->at_top_level()) {
		?>There are currently no entries in this category.<?php
	}
	?></p><?php
} else {
	$allowed_html = "p[class|style],
					strong,
					b,
					i,
					em,
					h1[style|class],
					h2[style|class],
					h3[style|class],
					h4[style|class],
					br,
					hr,
					a[href|title|class|style|target|name],
					ul[class|style],
					ol[class|style],
					li[class|style],
					dl[class|style],
					dt[class|style],
					dd[class|style],
					span[class|style],
					img[alt|src|width|height|border|class|style],
					sup,
					sub,
					table[width|cellpadding|cellspacing|border|class|style],
					tr[class|style],
					td[width|align|valign|style|class]";
	$actual_display_count = 0;
	foreach ($blog_entries as $index => $entry) {
		if (!$entry->is_draft() || $BlogManager->user_can_edit()) {
			$actual_display_count += 1;
			$fcache = new FragmentCache('Blog', $entry->id());
			$comment_count = (!empty($blog_comment_count) && (!empty($blog_comment_count[$entry->id()]))) ? $blog_comment_count[$entry->id()] : 0;
			?><div class="index-blog-entry"><p class="small" style="float: right"><a href="<?php echo $BlogManager->url('show',$entry) ?>#blog-comments"><?php echo $comment_count ?> Comment<?php echo ($comment_count != 1) ? 's' : ''; ?></a></p><?php
			if ($fcache->start('entry-list-item')) {
				?><h3><a href="<?php echo $BlogManager->url('show',$entry) ?>"><?php echo $entry->title(); ?></a><?php
					if ($entry->is_draft()) {
						?> <span class="small">(Draft)</span><?php
					}
					?></h3>
				<p class="small"><?php
				?><strong>Published:</strong> <?php
				echo Crumbs::date_format($entry->post_date(),'g:ia F jS, Y');
				if ($entry->post_date() != $entry->updated_date()) {
					?><br><strong>Updated:</strong> <?php
					echo Crumbs::date_format($entry->updated_date(),'g:ia F jS, Y'); ?><?php
				}
				?></p><?php
				if ($entry->teaser()) {
					print H::purify_html($entry->teaser(),array('allowed' => $allowed_html));
				}
				$fcache->end('entry-list-item');
			}
			if ($BlogManager->user_can_edit() || $BlogManager->user_can_delete()) {
				?><div class="controls">
					<?php
					if ($BlogManager->user_can_delete()) {
						?><a style="float: right; margin: 0 0 0 5px" href="<?php echo $BlogManager->url('delete',$entry->id()); ?>" class="delete-button" rel="Blog Entry|<?php echo Crumbs::entitize_utf8($entry->title()); ?>">Delete</a><?php
					}
					if ($BlogManager->user_can_edit()) {
						?><a style="float: right; margin: 0 0 0 5px" href="<?php echo $BlogManager->url('edit',$entry->id()); ?>" class="edit-button">Edit</a><?php
					}
					?>
				</div><?php
			}
			?></div><?php
		}
	}
	if ($actual_display_count > 0) {
		if ($total_entry_count > $recent_entry_count) {
			?><p><a href="<?php echo $BlogManager->url('archive') ?>">All Entries<?php if (!$BlogManager->at_top_level()) { ?> in This Category<?php } ?></a></p><?php
		}
	} else {
		if (!$BlogManager->at_top_level()) {
			?><p class="none-found">There are currently no entries in this category.</p><?php
		}
	}
}
?>