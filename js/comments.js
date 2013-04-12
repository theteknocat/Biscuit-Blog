var BlogComments = {
	blog_comment_url: null,
	init: function() {
		if (window.NewBlogCommentUrl == undefined) {
			Biscuit.Console.log("Unable to initialize blog commenting.");
			return;
		}
		this.blog_comment_url = window.NewBlogCommentUrl;
		Biscuit.Console.log(this.blog_comment_url);
		$('#blog-comment-form-container').hide();
		if (top.location.hash == '#leave-comment') {
			this.open_form();
		}
		$('#blog-comment-form-link').click(function() {
			if ($('#blog-comment-form-container').css('display') == 'none') {
				BlogComments.open_form();
			} else {
				BlogComments.close_form();
			}
			return false;
		});
		this.add_pagination_handler();
	},
	delete: function(target_el) {
		var del_button = $(target_el);
		var comment_container = del_button.parent().parent();
		var del_url = del_button.attr('href');
		if (del_url.match(/\?/)) {
			del_url += '&';
		} else {
			del_url += '?';
		}
		del_url += 'delete_confirmed=1';
		$('#blog-comment-load-throbber').show();
		del_button.hide();
		Biscuit.Ajax.Request(del_url,'server_action',{
			complete: function() {
				$('#blog-comment-load-throbber').hide();
			},
			success: function(html) {
				Biscuit.Console.log("Comment deleted");
				// Visually remove the comment from the page:
				comment_container.slideUp('normal',function() {
					$(this).remove();
					// Modify the comments heading to reflect the new count:
					var comment_count = parseInt($('#blog-entry-comments > h3:first').text().match(/[0-9]+/));
					comment_count -= 1;
					if (comment_count > 0) {
						var comment_heading = comment_count+" Comment";
						if (comment_count > 1) {
							comment_heading += "s";
						}
						$('#blog-entry-comments > h3:first').text(comment_heading);
					} else {
						$('#blog-entry-comments > h3:first').remove();
					}
				});
			},
			error: function() {
				del_button.show();
				Biscuit.Crumbs.Alert('<h4 class="attention"><strong>Unable to delete comment! Please contact the system administrator.</strong></h4>');
			}
		});
	},
	add_pagination_handler: function() {
		$('.paging a').live('click',function() {
			var comment_url = $(this).attr('href');
			// Modify the URL to call on the blog comments action only
			// Extract the blog ID:
			var url_matches = comment_url.match(/(.+)\/show\/([0-9]+)\/[^\?]+\?comment_pages=([0-9]+)/);
			// Build new URL to display_comments action with blog id:
			comment_url = url_matches[1]+'/display_comments/'+url_matches[2]+'?comment_pages='+url_matches[3];
			$('.comment-paging-throbber').show();
			Biscuit.Ajax.Request(comment_url,'update',{
				success: function(html) {
					$('#blog-entry-comments .paging').remove();
					$('#blog-entry-comments').append('<div class="extra-comments" style="display: none">'+html+'</div>');
					$('#blog-entry-comments .extra-comments').slideDown();
				},
				error: function() {
					Biscuit.Crumbs.Alert('<h4 class="attention"><strong>Unable to retrieve comments! Please contact the system administrator.</strong></h4>');
				},
				complete: function() {
					$('.comment-paging-throbber').hide();
				}
			});
			return false;
		});
	},
	open_form: function() {
		if ($('#blog-comment-form-content').html() == '') {
			$('#blog-comment-load-throbber').show();
			Biscuit.Ajax.Request(this.blog_comment_url,'update',{
				success: function(html) {
					$('#blog-comment-form-content').html(html);
					$('#blog-comment-form-container').slideDown('fast',function() {
						$('#blog-comment-form-link').text('Cancel Comment');
						$('#attr_username').focus();
					});
				},
				complete: function() {
					$('#blog-comment-load-throbber').hide();
				}
			});
		} else {
			$('#blog-comment-form-container').slideDown('fast',function() {
				$('#blog-comment-form-link').text('Cancel Comment');
				$('#attr_username').focus();
			});
		}
	},
	close_form: function() {
		$(':input','#blog-comment-form').not(':button, :submit, :reset, :hidden').val('');
		$('#blog-comment-form .error').removeClass('error');
		$('#blog-comment-form-container').slideUp('fast',function() {
			$('#blog-comment-form-link').text('Leave a Comment');
		});
	},
	comment_save_post_actions: function() {
		Biscuit.Crumbs.HideThrobber('blog-comment-load-throbber');
		Biscuit.Crumbs.Forms.EnableSubmit('blog-comment-form');
		this.close_form();
		setTimeout("$('#blog-entry-comments > div.blog-comment:first').slideDown('normal',function() { $('#blog-entry-comments > div.blog-comment:first').effect('highlight',{},'slow');});",500);
	}
}

$(document).ready(function() {
	BlogComments.init();
});