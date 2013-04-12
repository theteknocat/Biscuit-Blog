<?php
if (!empty($blog_comments)) {
	if (!$is_show_more_request) {
	?>
	<h3 class="comment-heading"><?php echo $blog_total_comment_count ?> Comment<?php if ($blog_total_comment_count > 1) { ?>s<?php } ?></h3><?php
	}
	foreach ($blog_comments as $index => $blog_comment) {
		$fcache = new FragmentCache('BlogComment', $blog_comment->id());
		$extra_attr = '';
		if (Request::is_ajax() && !empty($new_comment_posted) && $new_comment_posted === true && $index == 0) {
			$extra_attr = ' style="display: none"';
		}
		?><div class="blog-comment"<?php echo $extra_attr ?>>
			<?php
			if ($BlogManager->user_can_delete_blog_comment()) {
				?><div class="controls" style="float: right"><a href="<?php echo $BlogManager->url('delete_blog_comment',$blog_comment->id()) ?>?return_url=<?php echo $BlogManager->url('show',$blog_id) ?>" data-delete-function="BlogComments.delete" class="delete-button" data-item-title="<?php echo $blog_comment ?>"><?php echo __('Delete'); ?></a></div><?php
			}
			if ($fcache->start('entry-list')) {
				?>
			<h4 class="comment-title"><?php echo $blog_comment->username() ?> says:<br></h4>
			<?php print Crumbs::auto_paragraph(H::purify_text($blog_comment->comments()));
				?><p class="small"><?php echo Crumbs::relative_date($blog_comment->post_date());
				if ($BlogManager->user_can_edit()) {
					?><br><strong><?php echo __('User\'s Email:'); ?></strong> <a href="mailto:<?php echo $blog_comment->email() ?>"><?php echo $blog_comment->email() ?></a><?php
				}
				?></p><?php
				$fcache->end('entry-list');
			}
			?>
		</div><?php
	}
	if (!empty($next_page)) {
		?>
	<div class="paging"><img class="comment-paging-throbber" style="display: none" src="/modules/blog/images/throbber.gif" alt="Please wait..."><div class="page-links-right"><a href="<?php echo $BlogManager->url('show',$blog_id) ?>?comment_pages=<?php echo $next_page ?>">Load More...</a></div></div>
		<?php
	}
	?>
<script type="text/javascript">
	<?php
	if (Request::is_ajax()) {
		?>
	$('.delete-button').button({
		icons: {
			primary: 'ui-icon-trash'
		}
	});
		<?php
	}
	?>
</script>
	<?php
}
