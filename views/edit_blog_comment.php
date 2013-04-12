<?php print Form::header($blog_comment,null,$BlogManager->url('new_blog_comment')) ?>
	<input type="hidden" name="blog_comment[blog_id]" value="<?php echo $blog_comment->blog_id() ?>">
	<input type="hidden" name="return_url" value="<?php echo $BlogManager->url('show',$blog_comment->blog_id()) ?>">
	<?php print ModelForm::text($blog_comment,'username') ?>
	<?php print ModelForm::text($blog_comment,'email','Will not be displayed') ?>
	<?php print ModelForm::textarea($blog_comment,'comments',false,null,'Please be respectful. Inappropriate comments will be removed without notice.') ?>
	<div class="controls"><a id="comment-form-cancel-button" href="#cancel-blog-comment">Cancel</a><input type="submit" name="SubmitButton" class="SubmitButton" value="Submit"></div>
</form>
<script type="text/javascript" charset="utf-8">
	jQuery('#comment-form-cancel-button').click(function() {
		BlogComments.close_form();
		return false;
	});
	jQuery('#blog-comment-form').submit(function() {
		$('#comment-form-cancel-button').bind('ajaxSend', function() {
			Biscuit.Console.log(arguments);
			// Hide the cancel button while doing ajax request
			$(this).hide();
		});
		$('#comment-form-cancel-button').bind('ajaxError', function() {
			Biscuit.Console.log(arguments);
			// Show the cancel button again if ajax request had an error
			$(this).show();
		});
		new Biscuit.Ajax.FormValidator('blog-comment-form',{
			ajax_submit: true,
			update_div: 'blog-entry-comments',
			throbber_id: 'blog-comment-load-throbber',
			complete_callback: function() {
				BlogComments.comment_save_post_actions();
			}
		});
		return false;
	});
</script>