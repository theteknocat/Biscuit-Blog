Installation Notes
==================

This module relies on the PageContent module for providing blog "categories" (basically it uses pages to act as categories).  Upon
installation it will create a special menu for blog category pages and will ensure that new pages you create within the blog section of the site
have the blog module installed on them as primary.  When blog entries are created they will be associated with their respective category page.

Please ensure that you have installed the PageContent module prior to installing this one. If it is not installed, installation of this module
will bail with an error message.

This module will look for both the ShareThis and FbLike extensions on initialization and initialize and them if found in the extensions folder.  In
order for FbLike to work, however, you will need to place a file in your /themes/default/images folder named "fb-like-image" either in jpg, gif or png format.  If that file is not present FbLike will not function.  FbLike is used to allow users to like the category they are viewing or the entire blog.

Special Template Requirement
============================

In order to implement this module a special page template is required to render blog pages correctly.  Special variables are set for use
in the template file for rendering correct page titles, breadcrumbs and the category and recent posts menus.

To create the required template:

1. Create a template in your theme folder named "module-blog.php".
2. Copy and paste the code from your default template into the new one, then remove everything you are currently rendering within
   the body content area of that template (with the exception of the user messages, if that is where you want them to appear)
3. Paste the code from the example template found in the templates folder of this module into the body content area of your template.
   Instructions are provided at the top of the example template.  You can then customize the layout and define the CSS to display
   correctly in your site.  Note that this template puts the "indexable-content" class on a container for just the blog content area,
   so if you had an outer container with that class on it already be sure to remove the class from that element.
