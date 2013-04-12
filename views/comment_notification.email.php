A comment was just posted on the blog '<?php echo $blog->title(); ?>'.

<?php echo $blog_comment->username() ?> wrote:

<?php
print H::purify_text($blog_comment->comments());
?>


View this entry and it's comments:
<?php echo STANDARD_URL.$blog_url; ?>#blog-comments
