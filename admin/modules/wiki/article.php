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

if(function_exists("myplugins_info"))
    define(MODULE, "myplugins-wiki");
else
    define(MODULE, "wiki");

if($mybb->input['action']=="export") {
	if($mybb->input['cat']) {
		$cat = wiki_cache_load("categories", $mybb->input['cat']);
		$data['cats'][] = $cat;
		$filename = "wiki_cat_".$data['cats'][0]['title'];
		$comment = $lang->wiki_export_cat_comment;
	} elseif($mybb->input['art']) {
		$art = wiki_cache_load("articles", $mybb->input['art']);
		$data['art'][] = $art;
		$versions = wiki_cache_load("versions");
		if($versions) {
			foreach($versions as $version) {
				if($version['wid'] != $mybb->input['art'])
				    continue;
				$data['versions'][] = $version;
			}
		}
		$filename = "wiki_art_".$data['art'][0]['title'];
		$comment = $lang->wiki_export_art_comment;
	} else {
		$articles = wiki_cache_load("articles");
		if($articles) {
			foreach($articles as $art)
				$data['art'][] = $art;
		}
		$category = wiki_cache_load("categories");
		if($category) {
			foreach($category as $cat)
				$data['cats'][] = $cat;
		}
		$trashs = wiki_cache_load("trash");
		if($trashs) {
    		foreach($trashs as $trash)
				$data['trash'][] = $trash;
		}
		$versions = wiki_cache_load("versions");
		if($versions) {
    		foreach($versions as $version)
				$data['versions'][] = $version;
		}
		$filename = "wiki";
		$comment = $lang->wiki_export_comment;
	}
	if(!$data) {
		flash_message($lang->wiki_export_no_data, 'error');
		admin_redirect("index.php?module=".MODULE."-article");
	}
	$PL->xml_export($data, $filename.".xml", $comment);
}

$page->add_breadcrumb_item($lang->wiki, "index.php?module=".MODULE."-index");
$page->add_breadcrumb_item($lang->wiki_article, "index.php?module=".MODULE."-article");

$page->output_header($lang->wiki_article);

if($mybb->input['action']=="cat") {
	generate_tabs("cat");
	$table = new Table;
	$table->construct_header($lang->wiki_cat_title);
	$table->construct_header($lang->wiki_cat_number);
	$table->construct_header($lang->wiki_export);

	$category = wiki_cache_load("categories");
	if(sizeOf($category) > 0 && $category) {
		$articles = wiki_cache_load("articles");
		uasort($category, "wiki_sort_sort");
		foreach($category as $t) {
     		$t['number'] = 0;
     		if($articles) {
		 		foreach($articles as $article) {
					if($article['cid'] == $t['id'])
					    $t['number']++;
				}
			}
			$t['title'] = htmlspecialchars_uni($t['title']);

			$table->construct_cell("<a href=\"index.php?module=".MODULE."-article&cat={$t['id']}\">{$t['title']}</a>", array('width' => '50%'));
			$table->construct_cell($t['number'], array('width' => '25%'));
			$table->construct_cell("<a href=\"index.php?module=".MODULE."-article&action=export&cat={$t['id']}\" target=\"_blank\">{$lang->wiki_export}</a>", array('width' => '25%'));
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

	$trashs = wiki_cache_load("trash");
	if(sizeOf($trashs) > 0 && $trashs) {
		$category = wiki_cache_load("categories");
		uasort($trashs, "wiki_sort_date");
		$trashs = array_reverse($trashs, true);
		foreach($trashs as $t) {
			$trash=@unserialize($t['entry']);
			$uid=(int)$t['from'];
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
			$cat = $category[$trash['cid']]['title'];
			$date=date($mybb->settings['dateformat'], $t['date'])." ".date($mybb->settings['timeformat'], $t['date']);

			$table->construct_cell(htmlspecialchars_uni($trash['title']), array('width' => '20%'));
    		if($trash['link'])
				$table->construct_cell($lang->wiki_art_art_link, array('width' => '10%'));
			else if($trash['text'])
				$table->construct_cell($lang->wiki_art_art_text, array('width' => '10%'));
			else
				$table->construct_cell($lang->wiki_art_art_error, array('width' => '10%'));
			$table->construct_cell(htmlspecialchars_uni($trash['short']), array('width' => '40%'));
			$table->construct_cell(htmlspecialchars_uni($cat), array('width' => '10%'));
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

	if($mybb->input['cat'])
	    $cid = $mybb->input['cat'];

	$articles = wiki_cache_load("articles");
	if(sizeOf($articles) > 0 && $articles) {
		uasort($articles, "wiki_sort_sort");
		foreach($articles as $t) {
			if(isset($cid) && $t['cid'] != $cid)
			    continue;
			
			$uid=(int)$t['uid'];
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
			
			$table->construct_cell(htmlspecialchars_uni($t['title']), array('width' => '20%'));
    		if($t['link'])
				$table->construct_cell($lang->wiki_art_art_link, array('width' => '10%'));
			else if($t['text'])
				$table->construct_cell($lang->wiki_art_art_text, array('width' => '10%'));
			else
				$table->construct_cell($lang->wiki_art_art_error, array('width' => '10%'));
			$table->construct_cell(htmlspecialchars_uni($t['short']), array('width' => '40%'));
			$table->construct_cell($profilelink, array('width' => '10%'));
			$table->construct_cell($date, array('width' => '10%'));
			$table->construct_cell("<a href=\"index.php?module=".MODULE."-article&action=export&art={$t['id']}\" target=\"_blank\">{$lang->wiki_export}</a>", array('width' => '10%'));
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
		'link' => "index.php?module=".MODULE."-article",
		'description' => $lang->wiki_list_art_desc
	);
	$sub_tabs['cat'] = array(
		'title' => $lang->wiki_list_cat,
		'link' => "index.php?module=".MODULE."-article&amp;action=cat",
		'description' => $lang->wiki_list_cat_desc
	);
	$sub_tabs['trash'] = array(
		'title' => $lang->wiki_list_trash,
		'link' => "index.php?module=".MODULE."-article&amp;action=trash",
		'description' => $lang->wiki_list_trash_desc
	);
	$sub_tabs['export'] = array(
		'title' => $lang->wiki_list_export,
		'link' => "index.php?module=".MODULE."-article&amp;action=export",
		'description' => $lang->wiki_list_export_desc
	);

	$page->output_nav_tabs($sub_tabs, $selected);
}
?>