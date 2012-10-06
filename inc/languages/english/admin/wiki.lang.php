<?php
$l['wiki'] = "Wiki";


/* Settings (Just needed in other languages than english)
$l['setting_group_wiki'] = "Wiki";
$l['setting_group_wiki_desc'] = "Settings for the \"Wiki\" Plugin";
$l['setting_wiki_moderate_new'] = "Moderate new entries?";
$l['setting_wiki_moderate_new_desc'] = "";
$l['setting_wiki_own_sort'] = "Allow own order?";
$l['setting_wiki_own_sort_desc'] = "Otherwise only the order you saved is available";
$l['setting_wiki_stype'] = "Search Type";
$l['setting_wiki_stype_desc'] = "Which search type should be standard in your forum?";
$l['setting_wiki_mycode'] = "MyCode";
$l['setting_wiki_mycode_desc'] = "Which MyCode do you want to use?";
$l['setting_wiki_autolink'] = "Link Article automatically";
$l['setting_wiki_autolink_desc'] = "Automatically link Articles in Posts? Attention: If you have more than one article with the same name or an article with a name like \"and\" this function can make mistakes";
$l['setting_wiki_copy'] = "Show Copyright?";
$l['setting_wiki_copy_desc'] = "It isn't necessary but it would be nice if you display it";
*/


/* Menü */
$l['wiki_index'] = "Overview";
$l['wiki_article'] = "Entries";
$l['wiki_permissions'] = "Permissions";
$l['wiki_option'] = "Settings";
$l['wiki_import'] = "Import";
$l['wiki_cache'] = "Cache";
$l['wiki_update'] = "Update";


/* Übersicht/Stats */
$l['wiki_info'] = "Wiki Infos";
$l['wiki_pl_info'] = "PluginLibrary Infos";
$l['wiki_stat'] = "Wiki Statistics";
$l['wiki_installed'] = "Installed version";
$l['wiki_uploaded'] = "File version";
$l['wiki_newest'] = "Newest version";
$l['wiki_buildwith'] = "Current version at publication";
$l['wiki_num_art'] = "Number of Entries";
$l['wiki_num_cat'] = "Number of Categories";
$l['wiki_num_trash'] = "Number of entries in the trash";
$l['wiki_num_vers'] = "Total number of entries incl versions";
$l['wiki_update_message'] = "Your file version is \"{1}\" but your installed version is \"{2}\".<br />Please make an update.";
$l['wiki_version_error'] = "You installed \"{1}\" but your files are from version \"{2}\".<br />Please reupload the files from \"{1}\".";
$l['wiki_pl_version_error'] = "Your PluginLibrary version ({1}) is less than the recommended ({2}).<br />This can cause errors.";
$l['wiki_version_update'] = "You've installed version \"{1}\" the newest is \"{2}\".<br />Please update the plugin.";


/* Liste */
$l['wiki_list_art'] = "Entries";
$l['wiki_list_cat'] = "Categories";
$l['wiki_list_trash'] = "Trash";
$l['wiki_list_export'] = "Export all";
$l['wiki_list_art_desc'] = "List all entries";
$l['wiki_list_cat_desc'] = "List all categories";
$l['wiki_list_trash_desc'] = "List all entries in the trash";
$l['wiki_list_export_desc'] = "Export all entries and categories";

$l['wiki_cat_title'] = "Category title";
$l['wiki_cat_number'] = "Number of articles";
$l['wiki_cat_no'] = "There are no categories";
$l['wiki_export'] = "Export";

$l['wiki_art_title'] = "Article title";
$l['wiki_art_art'] = "Article type";
$l['wiki_art_art_link'] = "Link";
$l['wiki_art_art_text'] = "Text";
$l['wiki_art_art_error'] = "Error";
$l['wiki_art_short'] = "Shortdescription";
$l['wiki_art_user'] = "User";
$l['wiki_art_date'] = "Date";
$l['wiki_art_no'] = "There are no articles";

$l['wiki_trash_from'] = "Deleted from";
$l['wiki_trash_date'] = "Deleted on";

$l['wiki_export_cat_comment'] = "Export file of the MyBB Wiki Plugin - Category - {time}";
$l['wiki_export_art_comment'] = "Export file of the MyBB Wiki Plugin - Entry - {time}";
$l['wiki_export_comment'] = "Export file of the MyBB Wiki Plugin - Complete - {time}";
$l['wiki_export_no_data'] = "Can't build the export file";


/* Import */
$l['wiki_import_desc'] = "Choose a .xml file to import.<br /><span style=\"color: #FF0000;\">Attention: If you import files from another Board there can be some errors with the Usernames.</span>";
$l['wiki_import_success'] = "Import successfull.<br />{1} Entries imported<br />{2} Categories imported<br />{3} Entries in the trash imported<br />{4} Versions imported";
$l['wiki_import_success_new_cat'] = "<br />All entries without category have been added to the category {1}";
$l['wiki_import_no_file'] = "No file choosed";
$l['wiki_import_error_file'] = "Error while uploading the file";
$l['wiki_import_invalid_file'] = "No .xml file";


/* Update */
$l['wiki_update_submit'] = "Start update";
$l['wiki_update_submit_desc'] = "You can update from version \"{1}\" to version \"{2}\".";
$l['wiki_update_no'] = "No update";
$l['wiki_update_no_desc'] = "There is no update available. Please upload first the new files and then start the update";
$l['wiki_update_ok'] = "Back";
$l['wiki_canceled'] = "Update canceled";
$l['wiki_success'] = "Update successfull";
$l['wiki_error'] = "Unknown error while updating";


/* Permissions */
$l['wiki_group'] = "Usergroup";
$l['wiki_generall'] = "General";
$l['wiki_can_view'] = "Can see the wiki";
$l['wiki_can_create'] = "Can add articles";
$l['wiki_can_edit'] = "Can edit/delete articles";
$l['wiki_can_search'] = "Can use the search";
$l['wiki_versions'] = "Versions";
$l['wiki_can_version_view'] = "Can see versions";
$l['wiki_can_version_restore'] = "Can restore versions";
$l['wiki_can_version_delete'] = "Can delete versions";
$l['wiki_can_version_diff'] = "Can view difference between versions";
$l['wiki_trash'] = "Trashcan";
$l['wiki_can_trash_view'] = "Can see the trashcan";
$l['wiki_can_trash_restore'] = "Can restore deleted articles";
$l['wiki_can_trash_delete'] = "Can definitively delete articles";
$l['wiki_other'] = "Other";
$l['wiki_can_edit_closed'] = "Can edit closed articles";
$l['wiki_can_view_hidden'] = "Can see hidden articles";
$l['wiki_can_edit_sort'] = "Can change the sorting";
$l['wiki_can_unlock'] = "Can unlock articles";
$l['wiki_permissions_save'] = "Save permissions";
$l['wiki_permissions_saved'] = "Permissions saved";


/* Cache */
$l['wiki_articles'] = "Articles";
$l['wiki_categories'] = "Categories";
$l['wiki_elements'] = "Number of elements";
$l['wiki_reload'] = "Reload";
$l['wiki_cache_empty'] = "The cache \"{1}\" does not exist or is empty";
$l['wiki_cache_reload'] = "Cache \"{1}\" reloaded";


/* Admin Permissions */
$l['wiki_permission_index'] = "Can see the overview";
$l['wiki_permission_article'] = "Can see all entries";
$l['wiki_permission_permissions'] = "Can change permissions";
$l['wiki_permission_import'] = "Can import entries and categories";
$l['wiki_permission_cache'] = "Can manage the cache";
$l['wiki_permission_update'] = "Can update the plugin";


/* Plugin Library */
$l['wiki_pl_missing'] = "The installation can't be started because the <b><a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a></b> is missing";
$l['wiki_pl_old'] = "The installation can't be started because the <b><a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a></b> is too old (version 8 or newer is needed)";
?>