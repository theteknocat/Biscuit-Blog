<?php
if (!empty($blog_comments)) {
	if (!$is_show_more_request) {
	?>
	<h3 class="comment-heading"><?php echo $blog_total_comment_count ?> Comment<?php if ($blog_total_comment_count > 1) { ?>s<?php } ?></h3><?php
	}
	foreach ($blog_comments as $index => $blog_comment) {
		$extra_attr = '';
		if (Request::is_ajax() && !empty($new_comment_posted) && $new_comment_posted === true && $index == 0) {
			$extra_attr = ' style="display: none"';
		}
		?><div class="blog-comment"<?php echo $extra_attr ?>>
			<?php
			if ($BlogManager->user_can_delete_blog_comment()) {
				?><div class="controls" style="float: right"><a href="<?php echo $BlogManager->url('delete_blog_comment',$blog_comment->id()) ?>?return_url=<?php echo $BlogManager->url('show',$blog_id) ?>" class="comment-delete" rel="<?php echo $blog_comment ?>">Delete</a></div><?php
			}
			?>
			<h4 class="comment-title"><?php echo $blog_comment->username() ?> says:<br><span class="small comment-date"><?php echo Crumbs::date_format($blog_comment->post_date(),'g:ia F jS, Y') ?></span></h4>
			<?php print Crumbs::auto_paragraph(H::purify_text($blog_comment->comments()));
			if ($BlogManager->user_can_edit()) {
				?>
			<p class="small"><strong>User's Email:</strong> <a href="mailto:<?php echo $blog_comment->email() ?>"><?php echo $blog_comment->email() ?></a></p>
				<?php
			}
			?>
		</div><?php
	}
	if (!empty($next_page)) {
		?>
	<div class="paging"><img class="comment-paging-throbber" style="display: none" src="/modules/blog/images/throbber.gif" alt="Please wait..."><div class="page-links-right"><a href="<?php echo $BlogManager->url('show',$blog_id) ?>?comment_pages=<?php echo $next_page ?>">Load More...</a></div></div>
		<?php
	}
	if (Request::is_ajax()) {
		?><script type="text/javascript" charset="utf-8">
			BlogComments.add_del_button_handler();
			BlogComments.add_pagination_handler();
		</script><?php
	}
}
?>