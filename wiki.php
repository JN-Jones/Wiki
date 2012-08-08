<?php
//	uasort($articles, "wiki_sort");


define("IN_MYBB", 1);
define("THIS_SCRIPT", "wiki.php");

$templatelist = "wiki,wiki_table,wiki_category,wiki_category_table,wiki_text,wiki_header,wiki_header_edit,wiki_header_hidden";
$templatelist .= ",wiki_panel,wiki_panel_category,wiki_panel_text,wiki_panel_versions,wiki_panel_unlock";
$templatelist .= ",wiki_add,wiki_edit,wiki_delete,wiki_category_add,wiki_category_edit,wiki_category_delete";
$templatelist .= ",wiki_trash,wiki_trash_table,wiki_trash_table_element,wiki_versions,wiki_versions_table, wiki_new, wiki_new_element";
$templatelist .= ",wiki_search,wiki_sort,wiki_control,wiki_table_sort,wiki_table_control,wiki_category_sort,wiki_category_control";
$templatelist .= ",wiki_category_table_sort,wiki_category_table_control,wiki_trash_table_restore,wiki_trash_table_delete";
$templatelist .= ",wiki_trash_table_element_restore,wiki_trash_table_element_delete,wiki_versions_restore,wiki_versions_delete";
$templatelist .= ",wiki_versions_table_restore,wiki_versions_table_delete";

require("global.php");
$PL or require_once PLUGINLIBRARY;

add_breadcrumb($lang->wiki, $wiki_link);

if(!function_exists("wiki_is_allowed")) {
	$lang->load("wiki");
	error($lang->wiki_deactivated);
}

if(!wiki_is_allowed("can_view"))
    error_no_permission();


if($mybb->input['action']=="do_article_add" && $mybb->request_method == "post") {
    if(!wiki_is_allowed("can_create"))
	    error_no_permission();

	// Verify incoming POST request
	verify_post_check($mybb->input['my_post_key']);
	if(!$mybb->input['wiki_cat'])
	    $errors[] = $lang->wiki_no_cat;

	if(!$mybb->input['wiki_title'])
	    $errors[] = $lang->wiki_no_title;

	if(!$mybb->input['wiki_link']&&!$mybb->input['wiki_text'])
	    $errors[] = $lang->wiki_no_text;

	if(!$mybb->input['wiki_short'])
	    $errors[] = $lang->wiki_no_short;

	$hide=false;
	if($mybb->input['hide'])
	    $hide=true;

	$close=false;
	if($mybb->input['close'])
	    $close=true;

	if(!$errors) {
		$moderate=false;
		if($mybb->settings['wiki_moderate_new'] && !wiki_is_allowed("can_unlock"))
		    $moderate=true;
		$insert_array = array(
			'cid' => $db->escape_string($mybb->input['wiki_cat']),
			'title' => $db->escape_string($mybb->input['wiki_title']),
			'link' => $db->escape_string($mybb->input['wiki_link']),
			'short' => $db->escape_string($mybb->input['wiki_short']),
			'text' => $db->escape_string($mybb->input['wiki_text']),
			'uid' => $mybb->user['uid'],
			'username' => $db->escape_string($mybb->user['username']),
			'date' => time(),
			'is_hidden' => $hide,
			'is_closed' => $close,
			'awaiting_moderation' => $moderate
		);
		$nid = $db->insert_query('wiki', $insert_array);

		wiki_cache_update("articles");

		log_moderator_action("", $lang->wiki_log_add.": ".$mybb->input['wiki_title']);

		if($moderate)
			redirect(wiki_get_category($mybb->input['wiki_cat']), $lang->redirect_wiki_add_moderate);
		else
			redirect(wiki_get_article($nid), $lang->redirect_wiki_add);
	} else {
		$mybb->input['action'] = "article_add";
	}
}
if($mybb->input['action']=="article_add") {
	// Make navigation
	add_breadcrumb($lang->wiki_nav_add, "wiki.php?action=article_add");

	if(!wiki_is_allowed("can_create"))
		error_no_permission();


	// Coming back to this page from an error?
	if($errors)
	{
		$errors = inline_error($errors);
		$title = $mybb->input['wiki_title'];
		$link = $mybb->input['wiki_link'];
		$short = $mybb->input['wiki_short'];
		$message = $mybb->input['wiki_text'];
		if($mybb->input['hide'])
		    $hide = "checked=\"checked\" ";
		if($mybb->input['close'])
		    $close = "checked=\"checked\" ";
	    $mybb->input['cid'] = $mybb->input['wiki_cat'];
	}
	$cats = wiki_cache_load("categories");
	uasort($cats, "wiki_sort_title");
	$wiki_cats="";
	foreach($cats as $t) {
   		if($t['id'] == $mybb->input['cid'])
			$wiki_cats.="<option value=\"".$t['id']."\" selected=\"selected\">".$t['title']."</option>";
		else
			$wiki_cats.="<option value=\"".$t['id']."\">".$t['title']."</option>";
	}

	$codebuttons = build_mycode_inserter();
	eval("\$wiki_add = \"".$templates->get("wiki_add")."\";");
	output_page($wiki_add);
}
if($mybb->input['action'] == "do_article_edit" && $mybb->request_method == "post")
{
	if(!wiki_is_allowed("can_edit"))
	    error_no_permission();

	verify_post_check($mybb->input['my_post_key']);

	$wid = intval($mybb->input['wid']);

	if(!$mybb->input['wiki_cat'])
	    $errors[] = $lang->wiki_no_cat;

	if(!$mybb->input['wiki_title'])
	    $errors[] = $lang->wiki_no_title;

	if(!$mybb->input['wiki_link']&&!$mybb->input['wiki_text'])
	    $errors[] = $lang->wiki_no_text;

	if(!$mybb->input['wiki_short'])
	    $errors[] = $lang->wiki_no_short;

	$hide=false;
	if($mybb->input['hide'])
	    $hide=true;

	$close=false;
	if($mybb->input['close'])
	    $close=true;

	if(!$errors) {
		$wiki = wiki_cache_load("articles", $wid);
		$entry=serialize($wiki);
		$vid = $db->insert_query('wiki_versions', array("wid"=>$wiki['id'],"entry"=>$db->escape_string($entry)));

		$update_array = array(
			'cid' => $db->escape_string($mybb->input['wiki_cat']),
			'title' => $db->escape_string($mybb->input['wiki_title']),
			'link' => $db->escape_string($mybb->input['wiki_link']),
			'short' => $db->escape_string($mybb->input['wiki_short']),
			'text' => $db->escape_string($mybb->input['wiki_text']),
			'uid' => $mybb->user['uid'],
			'username' => $db->escape_string($mybb->user['username']),
			'date' => time(),
			'is_hidden' => $hide,
			'is_closed' => $close
		);
		$db->update_query('wiki', $update_array, "id='{$wid}'");

		wiki_cache_update("versions");
		wiki_cache_update("articles");

		log_moderator_action("", $lang->wiki_log_edit.": ".$mybb->input['wiki_title']);

		redirect(wiki_get_article($wid), $lang->redirect_wiki_edit);
	} else {
		$mybb->input['action'] = "article_edit";
	}
}
if($mybb->input['action']=="article_edit") {
	if(!wiki_is_allowed("can_edit"))
		error_no_permission();

	$wid = intval($mybb->input['wid']);
	$wiki = wiki_cache_load("articles", $wid);
	// Coming back to this page from an error?
	if($errors)
	{
		$errors = inline_error($errors);
		$wiki['title'] = $mybb->input['wiki_title'];
		$wiki['link'] = $mybb->input['wiki_link'];
		$wiki['short'] = $mybb->input['wiki_short'];
		$wiki['text'] = $mybb->input['wiki_text'];
		$wiki['is_hidden'] = $mybb->input['hide'];
		$wiki['is_closed'] = $mybb->input['close'];
	    $wiki['cid'] = $mybb->input['wiki_cat'];
	}
	$cats = wiki_cache_load("categories");
	uasort($cats, "wiki_sort_title");
	$wiki_cats="";
	foreach($cats as $t) {
		if($wiki['cid']==$t['id']) {
			$wiki_cats.="<option value=\"".$t['id']."\" selected=\"selected\">".$t['title']."</option>";
			$category = $t;
		} else
			$wiki_cats.="<option value=\"".$t['id']."\">".$t['title']."</option>";
	}
	$codebuttons = build_mycode_inserter();
   	if($wiki['is_hidden'])
	    $hidden_checked="checked=\"checked\" ";
   	if($wiki['is_closed']) {
	    if($wiki['uid']==$mybb->user['uid'] || wiki_is_allowed("can_edit_closed"))
		    $closed_checked="checked=\"checked\" ";
		else
			error($lang->wiki_closed);
	}
	eval("\$wiki_edit = \"".$templates->get("wiki_edit")."\";");

	// Make navigation
	add_breadcrumb($category['title'], wiki_get_category($category['id']));
	add_breadcrumb($wiki['title'], wiki_get_article($wiki['id']));
	add_breadcrumb($lang->wiki_nav_edit, "wiki.php?action=article_edit");
	output_page($wiki_edit);
}
if($mybb->input['action'] == "do_article_delete" && $mybb->request_method == "post")
{
	if(!wiki_is_allowed("can_edit"))
	    error_no_permission();

	verify_post_check($mybb->input['my_post_key']);

	$wid = intval($mybb->input['wid']);
	$wiki = wiki_cache_load("articles", $wid);

	if(!$wiki['id'])
		error($lang->wiki_error_invalid_wiki);

	unset($wiki['id']);
	$insert_array = array(
		'entry' => $db->escape_string(serialize($wiki)),
		'from' => $mybb->user['uid'],
		'date' => time()
	);

	$tid = $db->insert_query("wiki_trash", $insert_array);
	$db->delete_query("wiki", "id='{$wid}'");
	$db->update_query("wiki_versions", array("wid"=>-1, "tid"=>$tid), "wid='{$wid}'");

	wiki_cache_update("articles");
	wiki_cache_update("versions");
	wiki_cache_update("trash");

	log_moderator_action("", $lang->wiki_log_delete.": ".$wiki['title']);

	redirect(wiki_get_category($wiki['cid']), $lang->redirect_wiki_delete);
}
if($mybb->input['action']=="article_delete") {
	if(!wiki_is_allowed("can_edit"))
		error_no_permission();

	$wid = intval($mybb->input['wid']);

	$wiki = wiki_cache_load("articles", $wid);
	$category = wiki_cache_load("categories", $wiki['cid']);
	$wiki['cat'] = $category['title'];
	
   	if($wiki['is_closed']) {
	    if($wiki['uid'] != $mybb->user['uid'] && !wiki_is_allowed("can_edit_closed"))
			error($lang->wiki_closed);
	}
	eval("\$wiki_delete = \"".$templates->get("wiki_delete")."\";");

	// Make navigation
	add_breadcrumb($wiki['cat'], wiki_get_category($wiki['cid']));
	add_breadcrumb($wiki['title'], wiki_get_article($wiki['id']));
	add_breadcrumb($lang->wiki_nav_delete, "wiki.php?action=article_delete");
	output_page($wiki_delete);
}
/**/
if($mybb->input['action']=="do_category_add" && $mybb->request_method == "post") {
	if(!wiki_is_allowed("can_create"))
	    error_no_permission();

	// Verify incoming POST request
	verify_post_check($mybb->input['my_post_key']);
	if(!$mybb->input['wiki_name'])
	    $errors[] = $lang->wiki_no_name;

	if(!$errors) {
		$insert_array = array(
			'title' => $db->escape_string($mybb->input['wiki_name'])
		);
		$nid = $db->insert_query('wiki_cats', $insert_array);

		wiki_cache_update("categories");

		log_moderator_action("", $lang->wiki_log_category_add.": ".$mybb->input['wiki_name']);

		redirect(wiki_get_category($nid), $lang->redirect_wiki_category_add);
	} else {
		$mybb->input['action'] = "wiki_category_add";
	}
}
if($mybb->input['action']=="category_add") {
	// Make navigation
	add_breadcrumb($lang->wiki_nav_category_add, "wiki.php?action=wiki_category_add");

	if(!wiki_is_allowed("can_create"))
		error_no_permission();

	// Coming back to this page from an error?
	if($errors)
	{
		$errors = inline_error($errors);
	}
	eval("\$wiki_category_add = \"".$templates->get("wiki_category_add")."\";");
	output_page($wiki_category_add);
}
if($mybb->input['action'] == "do_category_edit" && $mybb->request_method == "post") {
	if(!wiki_is_allowed("can_edit"))
	    error_no_permission();

	verify_post_check($mybb->input['my_post_key']);

	$cid = intval($mybb->input['cid']);

	if(!$mybb->input['wiki_name'])
	    $errors[] = $lang->wiki_no_name;

	if(!$errors) {
		$update_array = array(
			'title' => $db->escape_string($mybb->input['wiki_name'])
		);

		$db->update_query('wiki_cats', $update_array, "id='{$cid}'");

		wiki_cache_update("categories");

		log_moderator_action("", $lang->wiki_log_category_edit.": ".$mybb->input['wiki_name']);

		redirect(wiki_get_category($cid), $lang->redirect_wiki_category_edit);
	} else {
		$mybb->input['action'] = "wiki_category_edit";
	}
}
if($mybb->input['action']=="category_edit") {
	if(!wiki_is_allowed("can_edit"))
		error_no_permission();

	$cid = intval($mybb->input['cid']);
	$wiki = wiki_cache_load("categories", $cid);
	// Coming back to this page from an error?
	if($errors)
	{
		$errors = inline_error($errors);
	}
	eval("\$wiki_category_edit = \"".$templates->get("wiki_category_edit")."\";");

	// Make navigation
	add_breadcrumb($wiki['title'], wiki_get_category($wiki['id']));
	add_breadcrumb($lang->wiki_nav_edit, "wiki.php?action=category_edit");
	output_page($wiki_category_edit);
}
if($mybb->input['action'] == "do_category_delete" && $mybb->request_method == "post") {
	if(!wiki_is_allowed("can_edit"))
	    error_no_permission();

	verify_post_check($mybb->input['my_post_key']);

	$cid = intval($mybb->input['cid']);
	$wiki = wiki_cache_load("categories", $cid);

	if(!$wiki['id'])
	{
		error($lang->wiki_error_invalid_category);
	}

	$db->delete_query("wiki_cats", "id='{$cid}'");
	$db->delete_query("wiki", "cid='{$cid}'");

	wiki_cache_update("articles");
	wiki_cache_update("categories");

	log_moderator_action("", $lang->wiki_log_category_delete.": ".$wiki['title']);

	redirect($wiki_link, $lang->redirect_wiki_category_delete);
}
if($mybb->input['action']=="category_delete") {
	if(!wiki_is_allowed("can_edit"))
		error_no_permission();

	$cid = intval($mybb->input['cid']);
	$wiki = wiki_cache_load("categories", $cid);
	eval("\$wiki_category_delete = \"".$templates->get("wiki_category_delete")."\";");

	add_breadcrumb($wiki['title'], wiki_get_category($wiki['id']));
	add_breadcrumb($lang->wiki_nav_category_delete, "wiki.php?action=category_delete");
	output_page($wiki_category_delete);
}
if($mybb->input['action']=="restore") {
	if(!wiki_is_allowed("can_trash_restore"))
	    error_no_permission();

	$tid = intval($mybb->input['wid']);
	$entry = wiki_cache_load("trash", $tid);
	if(!$entry['entry'])
	    $errors[]=$lang->wiki_error_invalid_wiki;

	if(!$errors) {
		$entry=@unserialize($entry['entry']);
		foreach($entry as $key => $value) {
		    $entry[$key] = $db->escape_string($value);
			if($key == "cid") {
				$cid = intval($entry['cid']);
				$cats = wiki_cache_load("categories");
				if(!$cats[$cid] || $cats['cid'] == "") {
					reset($cats);
	 				$entry['cid'] = current($cats);
				}
			}
		}
		$wid = $db->insert_query('wiki', $entry);
		$db->delete_query("wiki_trash", "id='{$tid}'");
		$db->update_query("wiki_versions", array("wid"=>$wid, "tid"=>-1), "tid='{$tid}'");

		wiki_cache_update("articles");
		wiki_cache_update("versions");
		wiki_cache_update("trash");

		log_moderator_action("", $lang->wiki_log_restored.": ".$entry['title']);
		redirect($wiki_link, $lang->redirect_wiki_restore);
	} else
		$mybb->input['action']="trash";
}
if($mybb->input['action']=="trash_delete") {
	if(!wiki_is_allowed("can_trash_delete"))
	    error_no_permission();

	$wid = intval($mybb->input['wid']);
	$entry = wiki_cache_load("trash", $wid);
	if(!$entry['id'])
	    $errors[]=$lang->wiki_error_invalid_wiki;

	if(!$errors) {
		$entry=@unserialize($entry['entry']);
		$db->delete_query("wiki_trash", "id='{$wid}'");
		$db->delete_query("wiki_versions", "tid='{$wid}'");
		wiki_cache_update("versions");
		wiki_cache_update("trash");
		log_moderator_action("", $lang->wiki_log_trash_delete.": ".$entry['title']);
		redirect($wiki_link, $lang->redirect_wiki_trash_delete);
	} else
		$mybb->input['action']="trash";
}
if($mybb->input['action']=="trash") {
	add_breadcrumb($lang->wiki_trash, WIKI_TRASH);

	if(!wiki_is_allowed("can_trash_view"))
	    error_no_permission();

	$trashs = wiki_cache_load("trash");
	if($trashs) {
		uasort($trashs, "wiki_sort_date");
		$trashs = array_reverse($trashs, true);
		$category = wiki_cache_load("categories");
		foreach($trashs as $entry) {
			$trash=@unserialize($entry['entry']);
			$trash_cid = intval($trash['cid']);
			if(array_key_exists($trash_cid, $category))
				$trash['category'] = $category[$trash_cid]['title'];
			else
				$trash['category'] = $lang->wiki_trash_unknown_cat;
			$trash['deleteddate']=date($mybb->settings['dateformat'], $entry['date'])." ".date($mybb->settings['timeformat'], $entry['date']);
			$entry_from = intval($entry['from']);
			$query=$db->simple_select("users", "username AS deletedfrom", "uid='{$entry_from}'");
			$trash=array_merge($trash, $db->fetch_array($query));
			$trash['id']=$entry['id'];
			$wiki_trash = WIKI_TRASH;
	
			if(wiki_is_allowed("can_trash_restore"))
			    eval("\$restore = \"".$templates->get("wiki_trash_table_element_restore")."\";");
			if(wiki_is_allowed("can_trash_delete"))
			    eval("\$delete = \"".$templates->get("wiki_trash_table_element_delete")."\";");
	
			eval("\$wiki_trash_table .= \"".$templates->get("wiki_trash_table_element")."\";");
		}
	
	    if(wiki_is_allowed("can_trash_restore"))
		    eval("\$restore = \"".$templates->get("wiki_trash_table_restore")."\";");
		if(wiki_is_allowed("can_trash_delete"))
		    eval("\$delete = \"".$templates->get("wiki_trash_table_delete")."\";");
	}
	
	eval("\$wiki_trash_table= \"".$templates->get("wiki_trash_table")."\";");
	if($errors)
		$errors = inline_error($errors);
	eval("\$wiki_trash= \"".$templates->get("wiki_trash")."\";");
	output_page($wiki_trash);
}
if($mybb->input['action']=="restore_version") {
	if(!wiki_is_allowed("can_version_restore"))
	    error_no_permission();

	if(!isset($mybb->input['vid']))
	    error($lang->wiki_invalid_id);
	$vid=intval($mybb->input['vid']);
	$wiki = wiki_cache_load("versions", $vid);
	$wid = $wiki['wid'];
	$wiki=@unserialize($wiki['entry']);
	$awiki = wiki_cache_load("articles", $wid);
	$entry=serialize($awiki);
	foreach($wiki as $key => $value)
	    $wiki[$key] = $db->escape_string($value);
	$db->insert_query('wiki_versions', array("wid"=>$awiki['id'],"entry"=>$db->escape_string($entry)));
	$db->delete_query('wiki_versions', "id='{$vid}'");
	$db->update_query('wiki', $wiki, "id='".$awiki['id']."'");

	wiki_cache_update("articles");
	wiki_cache_update("versions");

	log_moderator_action("", $lang->wiki_log_restored_version.": ".$wiki['title']);

	redirect(wiki_get_article($awiki['id']), $lang->redirect_wiki_restore_version);
}
if($mybb->input['action']=="delete_version") {
	if(!wiki_is_allowed("can_version_delete"))
	    error_no_permission();

	$vid = intval($mybb->input['vid']);
	$entry = wiki_cache_load("versions", $vid);
	$wid = $entry['wid'];
   	if(!$entry['id'])
	    $errors[]=$lang->wiki_error_invalid_wiki;

	if(!$errors) {
		$entry=@unserialize($entry['entry']);
		$db->delete_query("wiki_versions", "id='{$vid}'");
		wiki_cache_update("versions");
		log_moderator_action("", $lang->wiki_log_version_delete.": ".$entry['title']);
		redirect(wiki_get_versions($wid), $lang->redirect_wiki_version_delete);
	} else
		$mybb->input['action']="versions";
}
if($mybb->input['action']=="show_version") {
	if(!wiki_is_allowed("can_version_view"))
	    error_no_permission();

	if(!isset($mybb->input['vid']))
	    error($lang->wiki_invalid_id);
	require_once MYBB_ROOT."inc/class_parser.php";
	$parser = new postParser;
	$parser_options = array(
		"allow_html" => 1,
		"allow_mycode" => 1,
		"allow_smilies" => 0,
		"allow_imgcode" => 1,
		"allow_videocode" => 0,
		"filter_badwords" => 0
	);
	$vid=intval($mybb->input['vid']);
	$wiki = wiki_cache_load("versions", $vid);
	$wid = $wiki['wid'];
	$wiki=@unserialize($wiki['entry']);
	$wiki['formateddate']=date($mybb->settings['dateformat'], $wiki['date'])." ".date($mybb->settings['timeformat'], $wiki['date']);
	$awiki = wiki_cache_load("articles", $wid);
	$category = wiki_cache_load("categories", $awiki['cid']);
	$awiki['cat'] = $category['title'];
	
	if($wiki['is_hidden']) {
		eval("\$wiki['hidden'] = \"".$templates->get("wiki_header_hidden")."\";");
	}

	$wiki_title = $wiki['title'];
	$wiki_text = $parser->parse_message($wiki['text'], $parser_options);
	$uid=intval($wiki['uid']);
	$query = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
	$user=$db->fetch_array($query);
	$user_header = createHeader($user, $wiki, false);
	eval("\$showversion = \"".$templates->get("wiki_text")."\";");

	add_breadcrumb($awiki['cat'], wiki_get_category($awiki['cid']));
	add_breadcrumb($awiki['title'], wiki_get_article($awiki['id']));
	add_breadcrumb($lang->wiki_versions, wiki_get_versions($awiki['id']));
	add_breadcrumb($wiki['formateddate'], wiki_get_version($vid));
	output_page($showversion);
}
if($mybb->input['action']=="versions") {
	if(!wiki_is_allowed("can_version_view"))
	    error_no_permission();

	if(!isset($mybb->input['wid']))
	    error($lang->wiki_invalid_id);
	$wid=intval($mybb->input['wid']);
	$awiki = wiki_cache_load("articles", $wid);
	$category = wiki_cache_load("categories", $awiki['cid']);
	$awiki['cat'] = $category['title'];
	if($errors)
		$errors = inline_error($errors);
	$versions = wiki_cache_load("versions");
	uasort($versions, "wiki_sort_versions_date");
	$versions = array_reverse($versions, true);	
	foreach($versions as $wiki) {
		if($wiki['wid'] != $wid)
		    continue;
		$vid=$wiki['id'];
		$wiki=@unserialize($wiki['entry']);
		$uid=intval($wiki['uid']);
		$wiki['date']=date($mybb->settings['dateformat'], $wiki['date'])." ".date($mybb->settings['timeformat'], $wiki['date']);
		$user = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
		$user = $db->fetch_array($user);
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
//		$post['profilelink_plain'] = get_profile_link($wiki['uid']);
		$user['username_formatted'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
		$wiki['user'] = build_profile_link($user['username_formatted'], $user['uid']);
		$wiki_version = wiki_get_version($vid);

		if(wiki_is_allowed("can_version_restore"))
			eval("\$restore = \"".$templates->get("wiki_versions_table_restore")."\";");
		if(wiki_is_allowed("can_version_delete"))
			eval("\$delete = \"".$templates->get("wiki_versions_table_delete")."\";");

		eval("\$wiki_table .= \"".$templates->get("wiki_versions_table")."\";");
	}
	if(wiki_is_allowed("can_version_restore"))
		eval("\$restore = \"".$templates->get("wiki_versions_restore")."\";");
	if(wiki_is_allowed("can_version_delete"))
		eval("\$delete = \"".$templates->get("wiki_versions_delete")."\";");
	eval("\$wiki_versions = \"".$templates->get("wiki_versions")."\";");

	add_breadcrumb($awiki['cat'], wiki_get_category($awiki['cid']));
	add_breadcrumb($awiki['title'], wiki_get_article($wid));
	add_breadcrumb($lang->wiki_versions, wiki_get_versions($wid));
	output_page($wiki_versions);
}
if($mybb->input['action']=="unlock") {
	if(!isset($mybb->input['wid']))
	    error($lang->wiki_invalid_id);
	$wid=intval($mybb->input['wid']);

	if(!wiki_is_allowed("can_edit_closed"))
	    error_no_permission();

	$db->update_query('wiki', array('awaiting_moderation'=>'0'), "id='{$wid}'");

	wiki_cache_update("articles");

	log_moderator_action("", $lang->wiki_log_unlock.": ".$wiki['title']);

	redirect(wiki_get_article($wid), $lang->redirect_wiki_unlock);
}
if($mybb->input['action']=="new") {
	add_breadcrumb($lang->wiki_nav_new, WIKI_NEW);
	$articles = wiki_cache_load("articles");
	uasort($articles, "wiki_sort_date");
	$articles = array_reverse($articles, true);
	array_splice($articles, 10);
	foreach($articles as $wiki) {
		$uid = intval($wiki['uid']);
		$user_query = $db->simple_select("users", "uid, username, usergroup, displaygroup", "uid='{$uid}'");
		$user=$db->fetch_array($user_query);
		$username_formatted = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
		$wiki['user'] = build_profile_link($username_formatted, $user['uid']);
		$wiki['date'] = date($mybb->settings['dateformat'], $wiki['date'])." ".date($mybb->settings['timeformat'], $wiki['date']);
		$wiki_article = wiki_get_article($wiki['id']);
		eval("\$article_table .= \"".$templates->get("wiki_new_element")."\";");
	}
	eval("\$wiki_new = \"".$templates->get("wiki_new")."\";");
	output_page($wiki_new);
}
if($mybb->input['action']=="do_save_order") {
	if(!wiki_is_allowed("can_edit_sort"))
	    error_no_permission();

    if($mybb->input['order']=="article") {
	    $table = "wiki";
	    $cache = "articles";
	} elseif($mybb->input['order']=="category") {
	    $table = "wiki_cats";
	    $cache = "categories";
	} else
		error($lang->wiki_oder_error);

	foreach($mybb->input['disporder'] as $ID => $Sort) {
		$ID = $db->escape_string($ID); $Sort = $db->escape_string($Sort);
		$db->update_query($table, array("Sort"=>$Sort), "id='{$ID}'");
	}
	wiki_cache_update($cache);

	if($table=="wiki_cats")
		redirect($wiki_link, $lang->redirect_wiki_order);
	else
		redirect(wiki_get_category($mybb->input['cat']), $lang->redirect_wiki_order);
}
if($mybb->input['action']=="search") {
	if(!wiki_is_allowed("can_search"))
	    error_no_permission();

	add_breadcrumb($lang->search, "wiki.php?action=search");
	$type = isset($mybb->input['type']) ? $mybb->input['type'] : 'full';

   	if($type != 'full' && $type != 'title')
		$type = 'full';
	$searchString = $db->escape_string($mybb->input['searchString']);
	$query = 'SELECT id, cid, title, short, link, text FROM '.TABLE_PREFIX.'wiki WHERE (title LIKE "%'.$searchString.'%"';

	if($type == 'full')
		$query .= ' OR text LIKE "%'.$searchString.'%"';

	$query .= ')';

	$resultQuery = $db->query($query);
	if($db->num_rows($resultQuery) < 1) {
		eval("\$searchResults = \"".$templates->get("wiki_search_results_no")."\";");
	} else {
		$category = wiki_cache_load("categories");
		while($result = $db->fetch_array($resultQuery)) {
       		if($result['link'])
			    $result['title'] = '<a rel="nofollow" href="'.$result['link'].'" target="_blank">'.$result['title'].'</a>';
			else if($result['text'])
				$result['title'] = '<a href="'.$settings['bburl'].'/'.wiki_get_article($result['id']).'">'.$result['title'].'</a>';

			$result_cid = intval($result['cid']);
			$result['category'] = $category[$result['cid']]['title'];
			eval("\$searchResults .= \"".$templates->get("wiki_search_results_table")."\";");
		}
	}
	if($type=="full") {
	    $full_checked = "checked=\"checked\"";
	    $title_checked = "";
	} else {
	    $full_checked = "";
	    $title_checked = "checked=\"checked\"";
	}
	eval("\$search = \"".$templates->get("wiki_search")."\";");
	eval("\$searchOutput = \"".$templates->get("wiki_search_results")."\";");
	output_page($searchOutput);
}
if(!isset($mybb->input['action']) || $mybb->input['action']=="show") {
	if(isset($mybb->input['cid']) && $mybb->input['cid']!="") {
		$cid=intval($mybb->input['cid']);
		$category = wiki_cache_load("categories", $cid);
		add_breadcrumb($category['title'], wiki_get_category($category['id']));

		$moderation = wiki_is_allowed("can_unlock");
		$articles = wiki_cache_load("articles");
		uasort($articles, "wiki_sort_sort");

		$wiki_table="";
		foreach($articles as $wiki) {
			if($wiki['cid'] != $cid)
			    continue;
			if($wiki['awaiting_moderation'] && !$moderation)
			    continue;
			
       		if($wiki['link'])
			    $wiki_title = '<a rel="nofollow" href="'.$wiki['link'].'" target="_blank">'.$wiki['title'].'</a>';
			else if($wiki['text'])
				$wiki_title = '<a href="'.$settings['bburl'].'/'.wiki_get_article($wiki['id']).'">'.$wiki['title'].'</a>';
			else
				$wiki_title = $wiki['title'];
			$background="";
			$wiki_short = $wiki['short'];
	  		if($wiki['awaiting_moderation'])
			    $background="#6EFF6E";

			if(wiki_is_allowed("can_edit_sort"))
				eval("\$additional['sort'] = \"".$templates->get("wiki_category_table_sort")."\";");

			if(wiki_is_allowed("can_edit"))
				eval("\$additional['control'] = \"".$templates->get("wiki_category_table_control")."\";");

       		if(!$wiki['is_hidden'] || wiki_is_allowed("can_view_hidden") || $wiki['uid'] == $mybb->user['uid'])
				eval("\$wiki_table .= \"".$templates->get("wiki_category_table")."\";");
		}

		if(wiki_is_allowed("can_create"))
		    $article_add = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=article_add&cid={$wiki_cid}\" title=\"{$lang->wiki_nav_add}\">{$lang->wiki_nav_add}</a>";
		if(wiki_is_allowed("can_edit")) {
			if(isset($article_add))
			    $article_add .= " | ";
		    $article_edit = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=category_edit&cid={$wiki_cid}\" title=\"{$lang->wiki_nav_category_edit}\">{$lang->wiki_nav_category_edit}</a> | ";
			$article_delete = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=category_delete&cid={$wiki_cid}\" title=\"{$lang->wiki_nav_category_delete}\">{$lang->wiki_nav_category_delete}</a>";
			eval("\$additional['control'] = \"".$templates->get("wiki_category_control")."\";");
		}

		if(isset($article_add) || isset($article_edit))
			eval("\$wiki_header = \"".$templates->get("wiki_panel_category")."\";");

		if(wiki_is_allowed("can_edit_sort")) {
			$submit = "<center><input type=\"submit\" value=\"{$lang->wiki_save_order}\" /></center>";
			eval("\$additional['sort'] = \"".$templates->get("wiki_category_sort")."\";");
		}
		eval("\$showwiki = \"".$templates->get("wiki_category")."\";");
	}elseif(isset($mybb->input['wid']) && $mybb->input['wid']!="") {
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
		$parser_options = array(
			"allow_html" => 1,
			"allow_mycode" => 1,
			"allow_smilies" => 0,
			"allow_imgcode" => 1,
			"allow_videocode" => 0,
			"filter_badwords" => 0
		);
		$id = intval($mybb->input['wid']);
		$wiki = wiki_cache_load("articles", $id);

   		if($wiki['awaiting_moderation'] && !wiki_is_allowed("can_unlock"))
		    error_no_permission();

		if($wiki['is_hidden']) {
		    if($wiki['uid']==$mybb->user['uid'] || wiki_is_allowed("can_view_hidden"))
				eval("\$wiki['hidden'] = \"".$templates->get("wiki_header_hidden")."\";");
			else
				error($lang->wiki_hidden);
		}

     	if(wiki_is_allowed("can_version_view")) {
     		$arversions = wiki_cache_load("versions");
     		$vnumber = 0;
	 		foreach($arversions as $version) {
				if($version['wid'] == $id)
				    $vnumber++;
			}
     		    
			$wiki_versions = wiki_get_versions($id);
			if($vnumber!=0)
				eval("\$versions = \"".$templates->get("wiki_panel_versions")."\";");
		}

		if($wiki['awaiting_moderation'])
			eval("\$unlock = \"".$templates->get("wiki_panel_unlock")."\";");

		$cid=intval($wiki['cid']);
		if(wiki_is_allowed("can_create"))
		    $article_add = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=article_add&cid={$cid}\" title=\"{$lang->wiki_nav_add}\">{$lang->wiki_nav_add}</a>";

		if(wiki_is_allowed("can_edit")) {
			if(!$wiki['is_closed'] || wiki_is_allowed("can_edit_closed") || $wiki['uid']==$mybb->user['uid']) {
				if(isset($article_add))
				    $article_add .= " | ";
				$article_edit = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=article_edit&wid={$id}\" title=\"{$lang->wiki_nav_edit}\">{$lang->wiki_nav_edit}</a> | ";
				$article_delete = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=article_delete&wid={$id}\" title=\"{$lang->wiki_nav_delete}\">{$lang->wiki_nav_delete}</a>";
			}
		}

		if(isset($article_add) || isset($article_edit) || isset($versions) || isset($unlock))
		    eval("\$wiki_header = \"".$templates->get("wiki_panel_text")."\";");

		$wiki_title = $wiki['title'];
		$wiki_text = $parser->parse_message($wiki['text'], $parser_options);
		$category = wiki_cache_load("categories", $cid);
		$uid=intval($wiki['uid']);
		$query = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
		$user=$db->fetch_array($query);
		$user_header = createHeader($user, $wiki);
		add_breadcrumb($category['title'], wiki_get_category($category['id']));
		add_breadcrumb($wiki_title, wiki_get_article($id));
		eval("\$showwiki = \"".$templates->get("wiki_text")."\";");
	} else {
		$category = wiki_cache_load("categories");
		$articles = wiki_cache_load("articles");
		uasort($category, "wiki_sort_sort");
		$wiki_table="";
		foreach($category as $t) {
			$cid=intval($t['id']);
			$category_title = '<a href="'.$settings['bburl'].'/'.wiki_get_category($cid).'">'.$t['title'].'</a>';
			$category_number = 0;
			foreach($articles as $article) {
				if($article['cid'] == $cid)
				    $category_number++;
			}
			if(wiki_is_allowed("can_edit_sort")) {
				$category_sort = $t['Sort'];
				eval("\$additional['sort'] = \"".$templates->get("wiki_table_sort")."\";");
			}

			if(wiki_is_allowed("can_edit"))
				eval("\$additional['control'] = \"".$templates->get("wiki_table_control")."\";");

			eval("\$wiki_table .= \"".$templates->get("wiki_table")."\";");
		}

		if(wiki_is_allowed("can_trash_view")) {
			$trashs = wiki_cache_load("trash");
			if($trashs) {
				$category = wiki_cache_load("categories");
				uasort($trashs, "wiki_sort_date");
				$trashs = array_reverse($trashs, true);
				array_splice($trashs, 2);
				foreach($trashs as $entry) {
					$trash=@unserialize($entry['entry']);
					$trash_cid = intval($trash['cid']);
					if(array_key_exists($trash_cid, $category))
						$trash['category'] = $category[$trash_cid]['title'];
					else
						$trash['category'] = $lang->wiki_trash_unknown_cat;
					$trash['deleteddate']=date($mybb->settings['dateformat'], $entry['date'])." ".date($mybb->settings['timeformat'], $entry['date']);
					$entry_from = intval($entry['from']);
					$query=$db->simple_select("users", "username AS deletedfrom", "uid='{$entry_from}'");
					$trash=array_merge($trash, $db->fetch_array($query));
					$trash['id']=$entry['id'];
	
					if(wiki_is_allowed("can_trash_restore"))
					    eval("\$restore = \"".$templates->get("wiki_trash_table_element_restore")."\";");
					if(wiki_is_allowed("can_trash_delete"))
					    eval("\$delete = \"".$templates->get("wiki_trash_table_element_delete")."\";");
	
					eval("\$wiki_trash_table .= \"".$templates->get("wiki_trash_table_element")."\";");
				}
				if(wiki_is_allowed("can_trash_restore"))
				    eval("\$restore = \"".$templates->get("wiki_trash_table_restore")."\";");
				if(wiki_is_allowed("can_trash_delete"))
				    eval("\$delete = \"".$templates->get("wiki_trash_table_delete")."\";");
			}
			$wiki_trash = WIKI_TRASH;
			eval("\$wiki_trash= \"".$templates->get("wiki_trash_table")."\";");
		}
		$wiki_new = WIKI_NEW;
		if(wiki_is_allowed("can_search")) {
			if($mybb->settings['wiki_stype']=="full") {
			    $full_checked = "checked=\"checked\"";
			    $title_checked = "";
			} else {
			    $full_checked = "";
			    $title_checked = "checked=\"checked\"";
			}
			eval("\$search = \"".$templates->get("wiki_search")."\";");
		}
		if(wiki_is_allowed("can_create"))
		    $category_add = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=category_add\" title=\"{$lang->wiki_nav_category_add}\">{$lang->wiki_nav_category_add}</a>";

		eval("\$wiki_header = \"".$templates->get("wiki_panel")."\";");

		if(wiki_is_allowed("can_edit_sort")) {
			$submit = "<center><input type=\"submit\" value=\"{$lang->wiki_save_order}\" /></center>";
			eval("\$additional['sort'] = \"".$templates->get("wiki_sort")."\";");
		}

		if(wiki_is_allowed("can_edit"))
			eval("\$additional['control'] = \"".$templates->get("wiki_control")."\";");

		eval("\$showwiki = \"".$templates->get("wiki")."\";");
	}
	output_page($showwiki);
}
?>