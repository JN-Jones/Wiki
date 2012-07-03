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

if($mybb->input['action']=="export") {
	if($mybb->input['cat']) {
		$query = $db->simple_select("wiki_cats", "*", "id='{$mybb->input['cat']}'");
		while($cat=$db->fetch_array($query))
			$data['cats'][] = $cat;
		$filename = "wiki_cat_".$data['cats'][0]['title'];
		$comment = $lang->wiki_export_cat_comment;
	} elseif($mybb->input['art']) {
		$query = $db->simple_select("wiki", "*", "id='{$mybb->input['art']}'");
		while ($art=$db->fetch_array($query))
			$data['art'][] = $art;
		$query = $db->simple_select("wiki_versions", "*", "wid='{$mybb->input['art']}'");
		while($versions=$db->fetch_array($query))
			$data['versions'][] = $versions;
		$filename = "wiki_art_".$data['art'][0]['title'];
		$comment = $lang->wiki_export_art_comment;
	} else {
		$query = $db->simple_select("wiki");
		while ($art=$db->fetch_array($query))
			$data['art'][] = $art;
		$query = $db->simple_select("wiki_cats");
		while($cat=$db->fetch_array($query))
			$data['cats'][] = $cat;
		$query = $db->simple_select("wiki_trash");
		while($trash=$db->fetch_array($query))
			$data['trash'][] = $trash;
		$query = $db->simple_select("wiki_versions");
		while($versions=$db->fetch_array($query))
			$data['versions'][] = $versions;
		$filename = "wiki";
		$comment = $lang->wiki_export_comment;
	}
	if(!$data) {
		flash_message($lang->wiki_export_no_data, 'error');
		admin_redirect("index.php?module=wiki-article");
	}
	$PL->xml_export($data, $filename.".xml", $comment);
}

$page->add_breadcrumb_item($lang->wiki, "index.php?module=wiki");
$page->add_breadcrumb_item($lang->wiki_article, "index.php?module=wiki-article");

$page->output_header($lang->wiki_article);

if($mybb->input['action']=="cat") {
	generate_tabs("cat");
	$table = new Table;
	$table->construct_header($lang->wiki_cat_title);
	$table->construct_header($lang->wiki_cat_number);
	$table->construct_header($lang->wiki_export);

	$query = $db->simple_select("wiki_cats", "*", "", array("order_by"=>"Sort"));
	if($db->num_rows($query)>0) {
		while($t=$db->fetch_array($query)) {
			$t['number'] = $db->num_rows($db->simple_select("wiki", "id", "cid='{$t['id']}'"));
			$table->construct_cell("<a href=\"index.php?module=wiki-article&cat={$t['id']}\">{$t['title']}</a>", array('width' => '50%'));
			$table->construct_cell($t['number'], array('width' => '25%'));
			$table->construct_cell("<a href=\"index.php?module=wiki-article&action=export&cat={$t['id']}\" target=\"_blank\">{$lang->wiki_export}</a>", array('width' => '25%'));
			$table->construct_row();
		}
	} else {
		$table->construct_cell($lang->wiki_cat_no, array('colspan' => '3'));
		$table->construct_row();		
	}
	$table->output($lang->wiki_list_cat);
} elseif($mybb->input['action']=="trash") {
	generate_tabs("trash");
	$table = new Table;
	$table->construct_header($lang->wiki_art_title);
	$table->construct_header($lang->wiki_art_art);
	$table->construct_header($lang->wiki_art_short);
	$table->construct_header($lang->wiki_cat_title);
	$table->construct_header($lang->wiki_trash_date);
	$table->construct_header($lang->wiki_trash_from);

	$query = $db->simple_select("wiki_trash", "*", "", array("order_by"=>"date", "order_dir"=>"desc"));
	if($db->num_rows($query)>0) {
		while($t=$db->fetch_array($query)) {
			$trash=@unserialize($t['entry']);
			$uid=intval($t['from']);
			$user = $db->simple_select("users", "uid, username, usergroup, displaygroup", "uid='{$uid}'");
			$user = $db->fetch_array($user);
			// Get the usergroup
			if($user['username'])
			{
				if(!$user['displaygroup'])
				{
					$user['displaygroup'] = $user['usergroup'];
				}
				$usergroup = $groupscache[$user['displaygroup']];
			}
			else
			{
				$usergroup = $groupscache[1];
			}
			$username_formatted = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
			$profilelink = build_profile_link($username_formatted, $user['uid']);
			$cat_query=$db->simple_select("wiki_cats", "title", "id='{$trash['cid']}'");
			$cat = $db->fetch_field($cat_query, "title");
			$date=date($mybb->settings['dateformat'], $t['date'])." ".date($mybb->settings['timeformat'], $t['date']);

			$table->construct_cell($trash['title'], array('width' => '20%'));
    		if($trash['link'])
				$table->construct_cell($lang->wiki_art_art_link, array('width' => '10%'));
			else if($trash['text'])
				$table->construct_cell($lang->wiki_art_art_text, array('width' => '10%'));
			else
				$table->construct_cell($lang->wiki_art_art_error, array('width' => '10%'));
			$table->construct_cell($trash['short'], array('width' => '40%'));
			$table->construct_cell($cat, array('width' => '10%'));
			$table->construct_cell($date, array('width' => '10%'));
			$table->construct_cell($profilelink, array('width' => '10%'));
			$table->construct_row();
		}
	} else {
		$table->construct_cell($lang->wiki_art_no, array('colspan' => '6'));
		$table->construct_row();
	}
	$table->output($lang->wiki_list_art);
} else {
	generate_tabs("art");
	$table = new Table;
	$table->construct_header($lang->wiki_art_title);
	$table->construct_header($lang->wiki_art_art);
	$table->construct_header($lang->wiki_art_short);
	$table->construct_header($lang->wiki_art_user);
	$table->construct_header($lang->wiki_art_date);
	$table->construct_header($lang->wiki_export);

	$where = "";
	if($mybb->input['cat'])
	    $where = "cid='{$mybb->input['cat']}'";

	$query = $db->simple_select("wiki", "*", $where, array('oder_by'=>'title'));
	if($db->num_rows($query)>0) {
		while($t=$db->fetch_array($query)) {
			$uid=intval($t['uid']);
			$user = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
			$user = $db->fetch_array($user);
			// Get the usergroup
			if($user['username'])
			{
				if(!$user['displaygroup'])
				{
					$user['displaygroup'] = $user['usergroup'];
				}
				$usergroup = $groupscache[$user['displaygroup']];
			}
			else
			{
				$usergroup = $groupscache[1];
			}
			$username_formatted = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
			$profilelink = build_profile_link($username_formatted, $user['uid']);
			$date=date($mybb->settings['dateformat'], $t['date'])." ".date($mybb->settings['timeformat'], $t['date']);
			
			$table->construct_cell($t['title'], array('width' => '20%'));
    		if($t['link'])
				$table->construct_cell($lang->wiki_art_art_link, array('width' => '10%'));
			else if($t['text'])
				$table->construct_cell($lang->wiki_art_art_text, array('width' => '10%'));
			else
				$table->construct_cell($lang->wiki_art_art_error, array('width' => '10%'));
			$table->construct_cell($t['short'], array('width' => '40%'));
			$table->construct_cell($profilelink, array('width' => '10%'));
			$table->construct_cell($date, array('width' => '10%'));
			$table->construct_cell("<a href=\"index.php?module=wiki-article&action=export&art={$t['id']}\" target=\"_blank\">{$lang->wiki_export}</a>", array('width' => '10%'));
			$table->construct_row();
		}
	} else {
		$table->construct_cell($lang->wiki_art_no, array('colspan' => '6'));
		$table->construct_row();
	}
	$table->output($lang->wiki_list_art);
}
$page->output_footer();

function generate_tabs($selected)
{
	global $lang, $page;

	$sub_tabs = array();
	$sub_tabs['art'] = array(
		'title' => $lang->wiki_list_art,
		'link' => "index.php?module=wiki-article",
		'description' => $lang->wiki_list_art_desc
	);
	$sub_tabs['cat'] = array(
		'title' => $lang->wiki_list_cat,
		'link' => "index.php?module=wiki-article&amp;action=cat",
		'description' => $lang->wiki_list_cat_desc
	);
	$sub_tabs['trash'] = array(
		'title' => $lang->wiki_list_trash,
		'link' => "index.php?module=wiki-article&amp;action=trash",
		'description' => $lang->wiki_list_trash_desc
	);
	$sub_tabs['export'] = array(
		'title' => $lang->wiki_list_export,
		'link' => "index.php?module=wiki-article&amp;action=export",
		'description' => $lang->wiki_list_export_desc
	);

	$page->output_nav_tabs($sub_tabs, $selected);
}
?>