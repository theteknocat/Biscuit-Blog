<?php
print $Navigation->render_admin_bar($BlogManager,$blog_entry,array(
	'bar_title' => 'Blog Administration',
	'has_edit_button' => $BlogManager->user_can_edit(),
	'has_del_button' => $BlogManager->user_can_delete(),
	'del_button_rel' => 'this entry'
));
if ($BlogManager->action() == 'index') {
	?>
<h3><?php
if ($BlogManager->action() == 'index') {
	echo "Latest Entry: ";
}
echo $blog_entry->title();
?></h3>
<?php
}
$comment_count = (empty($blog_total_comment_count) ? 0 : $blog_total_comment_count);
?>
<p class="small" style="float: right"><a href="#blog-comments"><?php echo $comment_count ?> Comment<?php echo ($comment_count != 1) ? 's' : ''; ?></a></p><p class="small"><?php
?><strong>Published:</strong> <?php echo Crumbs::date_format($blog_entry->post_date(),'g:ia F jS, Y');
if ($blog_entry->post_date() != $blog_entry->updated_date()) {
	?><br><strong>Updated:</strong> <?php echo Crumbs::date_format($blog_entry->updated_date(),'g:ia F jS, Y'); ?><?php
}
?></p>
<?php
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
print H::purify_html($blog_entry->content(),array('allowed' => $allowed_html));
$share_button = $BlogManager->render_share_this_button();
if (!empty($share_button)) {
	echo '<p style="text-align: right">'.$share_button.'</p>';
}
?>
<script type="text/javascript" charset="utf-8">
	var NewBlogCommentUrl = '<?php echo $BlogManager->url("new_blog_comment") ?>?blog_comment_defaults[blog_id]=<?php echo $blog_entry->id(); ?>';
</script>
<h3><img style="display: none" id="blog-comment-load-throbber" src="/modules/blog/images/throbber.gif" alt="Please wait..."><a href="#leave-comment" id="blog-comment-form-link">Leave a Comment</a></h3>
<noscript><p><strong>For security purposes JavaScript must be enabled to post comments.</strong></p></noscript>
<div id="blog-comment-form-container">
	<a name="leave-comment"></a>
	<div id="blog-comment-form-content"></div>
</div>
<a name="blog-comments"></a>
<div id="blog-entry-comments">
<?php
include('display_comments.php');
?>
</div>