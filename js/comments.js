var BlogComments = {
	blog_comment_url: null,
	init: function() {
		if (window.NewBlogCommentUrl == undefined) {
			Biscuit.Console.log("Unable to initialize blog commenting.");
			return;
		}
		this.blog_comment_url = window.NewBlogCommentUrl;
		Biscuit.Console.log(this.blog_comment_url);
		jQuery('#blog-comment-form-container').hide();
		if (top.location.hash == '#leave-comment') {
			this.open_form();
		}
		jQuery('#blog-comment-form-link').click(function() {
			if (jQuery('#blog-comment-form-container').css('display') == 'none') {
				BlogComments.open_form();
			} else {
				BlogComments.close_form();
			}
			return false;
		});
		this.add_del_button_handler();
		this.add_pagination_handler();
	},
	add_del_button_handler: function() {
		jQuery('.comment-delete').click(function() {
			var rel = jQuery(this).attr('rel');
			if (confirm("Are you sure you want to delete "+rel+"?\n\nThis action cannot be undone.")) {
				var del_url = jQuery(this).attr('href');
				if (del_url.match(/\?/)) {
					del_url += '&';
				} else {
					del_url += '?';
				}
				del_url += 'delete_confirmed=1';
				var comment_container = jQuery(this).parent().parent();
				jQuery('#blog-comment-load-throbber').show();
				var del_button = jQuery(this);
				del_button.hide();
				jQuery.ajax({
					url: del_url,
					beforeSend: function(xhr) {
						xhr.setRequestHeader('X-Biscuit-Ajax-Request', 'true');
						xhr.setRequestHeader('X-Biscuit-Request-Type', 'update');
					},
					complete: function() {
						jQuery('#blog-comment-load-throbber').hide();
					},
					success: function(html) {
						Biscuit.Console.log("Comment deleted");
						// Visually remove the comment from the page:
						comment_container.slideUp('normal',function() {
							jQuery(this).remove();
							// Modify the comments heading to reflect the new count:
							var comment_count = parseInt(jQuery('#blog-entry-comments > h3:first').text().match(/[0-9]+/));
							comment_count -= 1;
							if (comment_count > 0) {
								var comment_heading = comment_count+" Comment";
								if (comment_count > 1) {
									comment_heading += "s";
								}
								jQuery('#blog-entry-comments > h3:first').text(comment_heading);
							} else {
								jQuery('#blog-entry-comments > h3:first').remove();
							}
						});
					},
					error: function() {
						del_button.show();
						alert("Unable to delete comment! Please contact the system administrator.");
					}
				});
			}
			return false;
		});
	},
	add_pagination_handler: function() {
		jQuery('.paging a').click(function() {
			var comment_url = jQuery(this).attr('href');
			// Modify the URL to call on the blog comments action only
			// Extract the blog ID:
			var url_matches = comment_url.match(/(.+)\/show\/([0-9]+)\/[^\?]+\?comment_pages=([0-9]+)/);
			// Build new URL to display_comments action with blog id:
			comment_url = url_matches[1]+'/display_comments/'+url_matches[2]+'?comment_pages='+url_matches[3];
			jQuery('.comment-paging-throbber').show();
			jQuery.ajax({
				url: comment_url,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-Biscuit-Ajax-Request', 'true');
					xhr.setRequestHeader('X-Biscuit-Request-Type', 'update');
				},
				success: function(html) {
					jQuery('#blog-entry-comments .paging').remove();
					jQuery('#blog-entry-comments').append('<div class="extra-comments" style="display: none">'+html+'</div>');
					jQuery('#blog-entry-comments .extra-comments').slideDown();
				},
				error: function() {
					alert("Unable to retrieve comments! Please contact the system administrator.");
				},
				complete: function() {
					jQuery('.comment-paging-throbber').hide();
				}
			});
			return false;
		});
	},
	open_form: function() {
		if (jQuery('#blog-comment-form-content').html() == '') {
			jQuery('#blog-comment-load-throbber').show();
			jQuery.ajax({
				url: this.blog_comment_url,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-Biscuit-Ajax-Request', 'true');
					xhr.setRequestHeader('X-Biscuit-Request-Type', 'update');
				},
				success: function(html) {
					jQuery('#blog-comment-form-content').html(html);
					jQuery('#blog-comment-form-container').slideDown('fast',function() {
						jQuery('#blog-comment-form-link').text('Cancel Comment');
						jQuery('#attr_username').focus();
					});
				},
				complete: function() {
					jQuery('#blog-comment-load-throbber').hide();
				}
			});
		} else {
			jQuery('#blog-comment-form-container').slideDown('fast',function() {
				jQuery('#blog-comment-form-link').text('Cancel Comment');
				jQuery('#attr_username').focus();
			});
		}
	},
	close_form: function() {
		jQuery(':input','#blog-comment-form').not(':button, :submit, :reset, :hidden').val('');
		jQuery('#blog-comment-form .error').removeClass('error');
		jQuery('#blog-comment-form-container').slideUp('fast',function() {
			jQuery('#blog-comment-form-link').text('Leave a Comment');
		});
	},
	comment_save_post_actions: function() {
		Biscuit.Crumbs.HideThrobber('blog-comment-load-throbber');
		Biscuit.Crumbs.Forms.EnableSubmit('blog-comment-form');
		this.close_form();
		setTimeout("jQuery('#blog-entry-comments > div.blog-comment:first').slideDown('normal',function() { jQuery('#blog-entry-comments > div.blog-comment:first').effect('highlight',{},'slow');});",500);
	}
}

jQuery(document).ready(function() {
	BlogComments.init();
});