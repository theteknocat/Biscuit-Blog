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
print Form::header($blog);
if ($blog->is_new() || $blog->is_draft()) {
	$post_date = date('Y-m-d H:i:s');
} else {
	$post_date = $blog->post_date();
}
if (!$blog->is_new() && $blog->is_draft()) {
	$updated_date = $post_date;
} else {
	$updated_date = date('Y-m-d H:i:s');
}
?>
<input type="hidden" name="blog[post_date]" value="<?php echo $post_date ?>">
<input type="hidden" name="blog[updated_date]" value="<?php echo $updated_date ?>">
<?php
// The edit action checks to ensure that the current page is not the top level and spits out an error if trying to add a new entry from the top level
if ($blog->is_new()) {
	$cat_id = $Biscuit->Page->id();
} else {
	$cat_id = $blog->category_id();
}
?>
<input type="hidden" name="blog[category_id]" value="<?php echo $cat_id ?>">
<fieldset>
	<legend>Entry Content</legend>
	<?php echo ModelForm::text($blog,'title') ?>

	<?php echo ModelForm::textarea($blog,'teaser', true, $allowed_html) ?>

	<?php echo ModelForm::textarea($blog,'content', true, $allowed_html) ?>

	<?php echo ModelForm::radios(array(
		array(
			'label' => 'Yes',
			'value' => 1
		),
		array(
			'label' => 'No',
			'value' => 0
		)), $blog, 'is_draft') ?>

</fieldset>
<?php
if ($blog->is_new()) {
	$return_url = $BlogManager->return_url('Blog');
} else {
	$return_url = $BlogManager->url('show',$blog->id());
}
echo Form::footer($BlogManager, $blog, (!$blog->is_new() && $BlogManager->user_can_delete()), 'Save', $return_url, 'this entry');
?>
<script type="text/javascript" charset="utf-8">
	document.observe("dom:loaded",function() {
		jQuery('#blog-form').submit(function() {
			new Biscuit.Ajax.FormValidator('blog-form');
			return false;
		});
		Biscuit.Session.KeepAlive.init_form_observer();
	});
	tinyMCE.init({
		mode : "exact",
		elements: "attr_teaser,attr_content",
		theme: 'advanced',
		theme_advanced_buttons1: 'undo,redo,|,pasteword,pastetext,|,search,replace,|,justifyleft,justifycenter,justifyright,justifyfull,|,indent,outdent,|,bullist,numlist,|,hr,|,anchor,link,unlink,image,|,charmap<?php if ($Authenticator->user_is_super()) { ?>,|,code<?php } ?>',
		theme_advanced_buttons2: 'bold,italic,underline,|,sup,sub,styleselect,formatselect,removeformat',
		theme_advanced_buttons3: 'table,tablecontrols',
		theme_advanced_buttons4: null,
		theme_advanced_buttons5: null,
		theme_advanced_buttons6: null,
		theme_advanced_toolbar_align: 'left',
		theme_advanced_toolbar_location: 'top',
		theme_advanced_resizing: true,
		theme_advanced_resize_horizontal: false,
		theme_advanced_statusbar_location: 'bottom',
		theme_advanced_blockformats: "p,h1,h2,h3,h4",
		relative_urls: false,
		remove_script_host: true,
		document_base_url: "<?php echo STANDARD_URL ?>/",
		skin: 'o2k7',
		skin_variant: 'silver',
		width: 610,
		height: 600,
		cleanup_on_startup: true,
		<?php echo $Biscuit->ExtensionTinyMce()->theme_css_setting($Biscuit->Page) ?>
		external_link_list_url : "/tiny_mce_link_list",
		plugins : "table,safari,style,iespell,insertdatetime,preview,media,searchreplace,contextmenu,paste,pasteword,pastetext,directionality,fullscreen,noneditable,visualchars,nonbreaking,template,inlinepopups",
		<?php
		if ($PageContentManager->user_can_manage_pages()) {
			// Add the TinyBrowser plugin to the RTE:
			?>
		file_browser_callback : "tinyBrowser",
			<?php
		}
		?>
		setup: function(ed) {
			ed.onChange.add(function() {
				Biscuit.Session.KeepAlive.ping();
			});
		}
	});
</script>