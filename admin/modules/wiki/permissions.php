<?php
if(!defined("IN_MYBB"))
{
	header("HTTP/1.0 404 Not Found");
	exit;
}
if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}
$PL or require_once PLUGINLIBRARY;

if($mybb->request_method == "post")
{
	$db->query('TRUNCATE TABLE '.TABLE_PREFIX.'wiki_permissions');

	foreach($mybb->input['perm'] as $gid => $perms)
	{
		$permRow = array(
				"gid" => intval($gid),
				"can_view" => in_array("can_view", $perms),
				"can_create" => in_array("can_create", $perms),
				"can_edit" => in_array("can_edit", $perms),
				"can_search" => in_array("can_search", $perms),
				"can_version_view" => in_array("can_version_view", $perms),
				"can_version_restore" => in_array("can_version_restore", $perms),
				"can_version_delete" => in_array("can_version_delete", $perms),
				"can_version_diff" => in_array("can_version_diff", $perms),
				"can_trash_view" => in_array("can_trash_view", $perms),
				"can_trash_restore" => in_array("can_trash_restore", $perms),
				"can_trash_delete" => in_array("can_trash_delete", $perms),
				"can_edit_closed" => in_array("can_edit_closed", $perms),
				"can_view_hidden" => in_array("can_view_hidden", $perms),
				"can_edit_sort" => in_array("can_edit_sort", $perms),
				"can_unlock" => in_array("can_unlock", $perms)
		);

		$db->insert_query('wiki_permissions', $permRow);
	}
	wiki_cache_update("permissions");
	
	flash_message($lang->wiki_permissions_saved, 'success');
	admin_redirect("index.php?module=wiki-permissions");
}

$page->add_breadcrumb_item($lang->wiki, "index.php?module=wiki");
$page->add_breadcrumb_item($lang->wiki_permissions, "index.php?module=wiki-permissions");

$page->output_header($lang->wiki_permissions);

$permissions = wiki_cache_load("permissions");

$tabs = array(
	"generall" => $lang->wiki_generall,
	"versions" => $lang->wiki_versions,
	"trash" => $lang->wiki_trash,
	"other" => $lang->wiki_other,
);
$page->output_tab_control($tabs);

// Display permissions
$form = new Form('index.php?module=wiki-permissions', 'post');

echo "<div id=\"tab_generall\">\n";
$table = new Table;
$table->construct_header($lang->wiki_group, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_view, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_create, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_edit, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_search, array('style' => 'text-align: center;'));

foreach($groupscache as $group)
{
	$row = $permissions[$group['gid']];
	$table->construct_cell(htmlspecialchars_uni($group['title']));

	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_view'            , $row['can_view']            ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_create' 		 , $row['can_create']    	   ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_edit'            , $row['can_edit']            ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_search'		     , $row['can_search']          ), array('style' => 'text-align: center;'));

	$table->construct_row();
	
	$rows[] = $row;
}

$table->output($lang->wiki_permissions);
echo "</div>\n";


echo "<div id=\"tab_versions\">\n";
$table = new Table;
$table->construct_header($lang->wiki_group, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_version_view, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_version_restore, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_version_delete, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_version_diff, array('style' => 'text-align: center;'));

foreach($groupscache as $group)
{
	$row = $permissions[$group['gid']];
	$table->construct_cell(htmlspecialchars_uni($group['title']));

	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_version_view'   , $row['can_version_view']   ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_version_restore', $row['can_version_restore']), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_version_delete' , $row['can_version_delete'] ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_version_diff' 	, $row['can_version_diff'] 	 ), array('style' => 'text-align: center;'));

	$table->construct_row();
}

$table->output($lang->wiki_permissions);
echo "</div>\n";


echo "<div id=\"tab_trash\">\n";
$table = new Table;
$table->construct_header($lang->wiki_group, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_trash_view, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_trash_restore, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_trash_delete, array('style' => 'text-align: center;'));

foreach($groupscache as $group)
{
	$row = $permissions[$group['gid']];
	$table->construct_cell(htmlspecialchars_uni($group['title']));

	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_trash_view'      , $row['can_trash_view']      ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_trash_restore'   , $row['can_trash_restore']   ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_trash_delete'    , $row['can_trash_delete']    ), array('style' => 'text-align: center;'));

	$table->construct_row();
}

$table->output($lang->wiki_permissions);
echo "</div>\n";


echo "<div id=\"tab_other\">\n";
$table = new Table;
$table->construct_header($lang->wiki_group, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_edit_closed, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_view_hidden, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_edit_sort, array('style' => 'text-align: center;'));
$table->construct_header($lang->wiki_can_unlock, array('style' => 'text-align: center;'));

foreach($groupscache as $group)
{
	$row = $permissions[$group['gid']];
	$table->construct_cell(htmlspecialchars_uni($group['title']));

	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_edit_closed'     , $row['can_edit_closed']     ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_view_hidden'     , $row['can_view_hidden']     ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_edit_sort'	     , $row['can_edit_sort']       ), array('style' => 'text-align: center;'));
	$table->construct_cell(wiki_build_permission_checkbox($row['gid'], 'can_unlock'	     	 , $row['can_unlock']    	   ), array('style' => 'text-align: center;'));

	$table->construct_row();
}

$table->output($lang->wiki_permissions);
echo "</div>\n";


$buttons[]=$form->generate_submit_button($lang->wiki_permissions_save);
$form->output_submit_wrapper($buttons);
$form->end();


$page->output_footer();

function wiki_build_permission_checkbox($gid, $field, $bool)
{
	return '<input type="checkbox" name="perm['.$gid.'][]" value="'.$field.'"'.($bool ? ' checked="checked"' : '').' />';
}
?>