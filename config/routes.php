<?
//Routing, views files (from folder 'views') to include according to url parts
//'404' - view of 404 error when the requsted page was not founded
//'index' - main page (index page) view
//'default' - if url was not founded any match in this list we go to this view
//'module/' - exact url match goes to this view (high priority)
//'module/*/' - all urls with 2 parts starting with first part ('module') go to this view
//'module->' - all urls starting with first part ('module') go to this view
//'module/extra/*/' - all urls with 3 parts starting with 'module/extra/' go to this view
//'module/extra->' - all urls starting with 'module/extra' go to this view
//Examples: 'contacts/' => 'view-contacts.php', 'articles/*/' => 'view-texts.php', 'products->' => 'shop/view-catalog.php'
//'register/complete/' => 'view-finish-register.php', 'albums/summer/' => 'gallery/summer-photos.php'
//'user/orders/*/' => 'user/view-user-orders.php', 'blog/comments->' => 'common/blog/comments.php'
$mvFrontendRoutes = array(

"index" => "view-index.php",
"404" => "view-404.php",
"default" => "view-default.php",
"form/" => "view-form.php"
);
?>