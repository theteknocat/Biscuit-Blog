<?php
if (!empty($blog_entries)) {
	?><ul><?php
	foreach ($blog_entries as $entry) {
		?><li><a href="<?php echo $BlogManager->url('show',$entry->id()); ?>"><?php echo $entry->title(); ?></a><br><span class="small"><?php echo Crumbs::date_format($entry->post_date(),'g:ia F jS, Y'); ?></span></li><?php
	}
	?></ul><?php
}
?>