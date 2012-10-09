<?php
define("IN_MYBB", 1);
define("THIS_SCRIPT", "wiki.php");

$perpage = 10;

$templatelist = "wiki,wiki_table,wiki_category,wiki_category_table,wiki_text,wiki_header,wiki_header_edit,wiki_header_hidden";
$templatelist .= ",wiki_panel,wiki_panel_category,wiki_panel_text,wiki_panel_versions,wiki_panel_unlock";
$templatelist .= ",wiki_add,wiki_edit,wiki_delete,wiki_category_add,wiki_category_edit,wiki_category_delete";
$templatelist .= ",wiki_trash,wiki_trash_table,wiki_trash_table_element,wiki_versions,wiki_versions_table, wiki_new, wiki_new_element";
$templatelist .= ",wiki_search,wiki_sort,wiki_control,wiki_table_sort,wiki_table_control,wiki_category_sort,wiki_category_control";
$templatelist .= ",wiki_category_table_sort,wiki_category_table_control,wiki_trash_table_restore,wiki_trash_table_delete";
$templatelist .= ",wiki_trash_table_element_restore,wiki_trash_table_element_delete,wiki_versions_restore,wiki_versions_delete";
$templatelist .= ",wiki_versions_table_restore,wiki_versions_table_delete,wiki_search_results,wiki_search_results_no,wiki_results_table";
$templatelist .= ",wiki_sortoptions,wiki_versions_diff,wiki_versions_diff_panel";

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
	if($cats) {
		uasort($cats, "wiki_sort_title");
		$wiki_cats="";
		foreach($cats as $t) {
	   		if($t['id'] == $mybb->input['cid'])
				$wiki_cats.="<option value=\"".$t['id']."\" selected=\"selected\">".$t['title']."</option>";
			else
				$wiki_cats.="<option value=\"".$t['id']."\">".$t['title']."</option>";
		}
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

	$wid = (int)$mybb->input['wid'];

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

	$wid = (int)$mybb->input['wid'];
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
	if($cats) {
		uasort($cats, "wiki_sort_title");
		$wiki_cats="";
		foreach($cats as $t) {
			if($wiki['cid']==$t['id']) {
				$wiki_cats.="<option value=\"".$t['id']."\" selected=\"selected\">".$t['title']."</option>";
				$category = $t;
			} else
				$wiki_cats.="<option value=\"".$t['id']."\">".$t['title']."</option>";
		}
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
	wiki_create_navy($wiki, true, $cats);
	add_breadcrumb($wiki['title'], wiki_get_article($wiki['id']));
	add_breadcrumb($lang->wiki_nav_edit, "wiki.php?action=article_edit");
	output_page($wiki_edit);
}
if($mybb->input['action'] == "do_article_delete" && $mybb->request_method == "post")
{
	if(!wiki_is_allowed("can_edit"))
	    error_no_permission();

	verify_post_check($mybb->input['my_post_key']);

	$wid = (int)$mybb->input['wid'];
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

	$wid = (int)$mybb->input['wid'];

	$wiki = wiki_cache_load("articles", $wid);
	$category = wiki_cache_load("categories", $wiki['cid']);
	$wiki['cat'] = $category['title'];

   	if($wiki['is_closed']) {
	    if($wiki['uid'] != $mybb->user['uid'] && !wiki_is_allowed("can_edit_closed"))
			error($lang->wiki_closed);
	}
	eval("\$wiki_delete = \"".$templates->get("wiki_delete")."\";");

	// Make navigation
	wiki_create_navy($category);
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

	if(!$mybb->input['wiki_cat'])
	    $mybb->input['wiki_cat'] = -1;

	if(!$errors) {
		$insert_array = array(
			'title' => $db->escape_string($mybb->input['wiki_name']),
			'pid' => (int)$mybb->input['wiki_cat']
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
		$errors = inline_error($errors);

	$cats = wiki_cache_load("categories");
	if($cats) {
		uasort($cats, "wiki_sort_title");
		$wiki_cats = "<option value=\"-1\">-</option>";
		foreach($cats as $t) {
	   		if($t['id'] == $mybb->input['cid'])
				$wiki_cats .= "<option value=\"".$t['id']."\" selected=\"selected\">".$t['title']."</option>";
			else
				$wiki_cats .= "<option value=\"".$t['id']."\">".$t['title']."</option>";
		}
	}

	eval("\$wiki_category_add = \"".$templates->get("wiki_category_add")."\";");
	output_page($wiki_category_add);
}
if($mybb->input['action'] == "do_category_edit" && $mybb->request_method == "post") {
	if(!wiki_is_allowed("can_edit"))
	    error_no_permission();

	verify_post_check($mybb->input['my_post_key']);

	$cid = (int)$mybb->input['cid'];

	if(!$mybb->input['wiki_name'])
	    $errors[] = $lang->wiki_no_name;

	if(!$errors) {
		$update_array = array(
			'title' => $db->escape_string($mybb->input['wiki_name']),
			'pid' => (int)$mybb->input['wiki_cat']
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

	$cats = wiki_cache_load("categories");
	$cid = (int)$mybb->input['cid'];
	$wiki = $cats[$cid];
	// Coming back to this page from an error?
	if($errors)
		$errors = inline_error($errors);

	uasort($cats, "wiki_sort_title");
	$wiki_cats = "<option value=\"-1\">-</option>";
	foreach($cats as $t) {
   		if($t['id'] == $wiki['cid'])
			$wiki_cats .= "<option value=\"".$t['id']."\" selected=\"selected\">".$t['title']."</option>";
		else
			$wiki_cats .= "<option value=\"".$t['id']."\">".$t['title']."</option>";
	}

	eval("\$wiki_category_edit = \"".$templates->get("wiki_category_edit")."\";");

	// Make navigation
	wiki_create_navy($wiki, false, $cats);
	add_breadcrumb($lang->wiki_nav_edit, "wiki.php?action=category_edit");
	output_page($wiki_category_edit);
}
if($mybb->input['action'] == "do_category_delete" && $mybb->request_method == "post") {
	if(!wiki_is_allowed("can_edit"))
	    error_no_permission();

	verify_post_check($mybb->input['my_post_key']);

	$cid = (int)$mybb->input['cid'];
	$wiki = wiki_cache_load("categories", $cid);

	if(!$wiki['id'])
	{
		error($lang->wiki_error_invalid_category);
	}

	$db->delete_query("wiki_cats", "id='{$cid}'");
	$db->delete_query("wiki_cats", "pid='{$cid}'");
	$db->delete_query("wiki", "cid='{$cid}'");

	wiki_cache_update("articles");
	wiki_cache_update("categories");

	log_moderator_action("", $lang->wiki_log_category_delete.": ".$wiki['title']);

	redirect($wiki_link, $lang->redirect_wiki_category_delete);
}
if($mybb->input['action']=="category_delete") {
	if(!wiki_is_allowed("can_edit"))
		error_no_permission();

	$cid = (int)$mybb->input['cid'];
	$wiki = wiki_cache_load("categories", $cid);
	eval("\$wiki_category_delete = \"".$templates->get("wiki_category_delete")."\";");

	wiki_create_navy($wiki);
	add_breadcrumb($lang->wiki_nav_category_delete, "wiki.php?action=category_delete");
	output_page($wiki_category_delete);
}
if($mybb->input['action']=="restore") {
	if(!wiki_is_allowed("can_trash_restore"))
	    error_no_permission();

	$tid = (int)$mybb->input['wid'];
	$entry = wiki_cache_load("trash", $tid);
	if(!$entry['entry'])
	    $errors[]=$lang->wiki_error_invalid_wiki;

	if(!$errors) {
		$entry=@unserialize($entry['entry']);
		foreach($entry as $key => $value) {
		    $entry[$key] = $db->escape_string($value);
			if($key == "cid") {
				$cid = (int)$entry['cid'];
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

	$wid = (int)$mybb->input['wid'];
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
		$num = sizeOf($trashs);
		$page = (int)$mybb->input['page'];

		if($page > 0)
			$start = ($page-1) *$perpage;
		else {
			$start = 0;
			$page = 1;
		}

		$multipage = multipage($num, $perpage, $page, WIKI_TRASH);

		uasort($trashs, "wiki_sort_date");
		$trashs = array_reverse($trashs, true);
		$trashs = array_slice($trashs, $start, $perpage, true);
		$category = wiki_cache_load("categories");
		$wiki_trash = WIKI_TRASH;
		foreach($trashs as $entry) {
			$trash=@unserialize($entry['entry']);
			$trash_cid = (int)$trash['cid'];
			if(is_array($category) && array_key_exists($trash_cid, $category))
				$trash['category'] = $category[$trash_cid]['title'];
			else
				$trash['category'] = $lang->wiki_trash_unknown_cat;
			$trash['deleteddate']=date($mybb->settings['dateformat'], $entry['date'])." ".date($mybb->settings['timeformat'], $entry['date']);
			$entry_from = (int)$entry['from'];
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
	$vid=(int)$mybb->input['vid'];
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

	$vid = (int)$mybb->input['vid'];
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
	$vid=(int)$mybb->input['vid'];
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
	$uid=(int)$wiki['uid'];
	$query = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
	$user=$db->fetch_array($query);
	$user_header = createHeader($user, $wiki, false);
	eval("\$showversion = \"".$templates->get("wiki_text")."\";");

	wiki_create_navy($category);
	add_breadcrumb($awiki['title'], wiki_get_article($awiki['id']));
	add_breadcrumb($lang->wiki_versions, wiki_get_versions($awiki['id']));
	add_breadcrumb($wiki['formateddate'], wiki_get_version($vid));
	output_page($showversion);
}
if($mybb->input['action']=="version_diff") {
	if(!wiki_is_allowed("can_version_diff"))
	    error_no_permission();
	verify_post_check($mybb->input['my_post_key']);

	if($mybb->input['version1'] == $mybb->input['version2'])
	    $errors[] = $lang->wiki_versions_diff_same;

	$wid = (int)$mybb->input['wid'];

	$versions = wiki_cache_load("versions");
	uasort($versions, "wiki_sort_versions_date");
	$versions = array_reverse($versions, true);

	$wiki = wiki_cache_load("articles", $wid);
	$wiki['date'] = date($mybb->settings['dateformat'], $wiki['date'])." ".date($mybb->settings['timeformat'], $wiki['date']);
    if($mybb->input['version1'] == "w") {
		$v1['entry'] = $wiki;
		$versions1 = "<option value=\"w\" selected=\"selected\">{$v1['entry']['date']}</option>";
	} else {
		$v1 = $versions[$mybb->input['version1']];
		$v1['entry'] = @unserialize($v1['entry']);
		$v1['entry']['date'] = date($mybb->settings['dateformat'], $v1['entry']['date'])." ".date($mybb->settings['timeformat'], $v1['entry']['date']);
		$versions1 = "<option value=\"w\">{$wiki['date']}</option>";
	}
    if($mybb->input['version2'] == "w") {
		$v2['entry'] = $wiki;
		$versions2 = "<option value=\"w\" selected=\"selected\">{$v2['entry']['date']}</option>";
	} else {
		$v2 = $versions[$mybb->input['version2']];
		$v2['entry'] = @unserialize($v2['entry']);
		$v2['entry']['date'] = date($mybb->settings['dateformat'], $v2['entry']['date'])." ".date($mybb->settings['timeformat'], $v2['entry']['date']);
		$versions2 = "<option value=\"w\">{$wiki['date']}</option>";
	}

	if(!$errors) {
		if($v2['entry']['date'] > $v1['entry']['date']) {
			$temp = $v1; $v1 = $v2; $v2 = $temp;
		}

		$lang->wiki_versions_diff_between = $lang->sprintf($lang->wiki_versions_diff_between, $v1['entry']['date'], $v2['entry']['date']);

		$v1['entry']['text'] = explode(' ', $v1['entry']['text']);
		$v2['entry']['text'] = explode(' ', $v2['entry']['text']);

		$diff = diff($v2['entry']['text'], $v1['entry']['text']);

		$diffStr = '';

		foreach($diff as $segment) {
			if(is_array($segment)) {
				if(count($segment['d']))
					$diffStr .= '<span class="wiki-diff-deleted">'.nl2br(htmlspecialchars_uni(implode(' ', $segment['d']))).'</span>';

				if(count($segment['i']))
					$diffStr .= '<span class="wiki-diff-inserted">'.nl2br(htmlspecialchars_uni(implode(' ', $segment['i']))).'</span>';
			} else {
				$diffStr .= nl2br(htmlspecialchars_uni($segment));
			}
		}
		$diff = $diffStr;

		foreach($versions as $version) {
			if($version['wid'] != $wid)
			    continue;
			$version['entry'] = @unserialize($version['entry']);
			$version['entry']['date'] = date($mybb->settings['dateformat'], $version['entry']['date'])." ".date($mybb->settings['timeformat'], $version['entry']['date']);
			if($version['id'] == $v1['id'])
			    $versions1 .= "<option value=\"{$version['id']}\" selected=\"selected\">{$version['entry']['date']}</option>";
			else
			    $versions1 .= "<option value=\"{$version['id']}\">{$version['entry']['date']}</option>";
			if($version['id'] == $v2['id'])
			    $versions2 .= "<option value=\"{$version['id']}\" selected=\"selected\">{$version['entry']['date']}</option>";
			else
			    $versions2 .= "<option value=\"{$version['id']}\">{$version['entry']['date']}</option>";
		}

		eval("\$diffs = \"".$templates->get("wiki_versions_diff_panel")."\";");
		eval("\$version_diff = \"".$templates->get("wiki_versions_diff")."\";");
		wiki_create_navy($wiki, true);
		add_breadcrumb($wiki['title'], wiki_get_article($wiki['id']));
		add_breadcrumb($lang->wiki_versions, wiki_get_versions($wiki['id']));
		add_breadcrumb($v1['entry']['date']." - ".$v2['entry']['date']);
		output_page($version_diff);
	} else {
		$mybb->input['wid'] = $wid;
		$mybb->input['action']="versions";
	}
}
if($mybb->input['action']=="versions") {
	if(!wiki_is_allowed("can_version_view"))
	    error_no_permission();

	if(!isset($mybb->input['wid']))
	    error($lang->wiki_invalid_id);
	$wid=(int)$mybb->input['wid'];
	$awiki = wiki_cache_load("articles", $wid);
	$category = wiki_cache_load("categories", $awiki['cid']);
	$awiki['cat'] = $category['title'];
	if($errors)
		$errors = inline_error($errors);
	$versions = wiki_cache_load("versions");
	if($versions) {
		$num = 0;;
		$page = (int)$mybb->input['page'];
		if($page > 1)
			$start = ($page-1) *$perpage +1;
		else {
			$start = 0;
			$page = 1;
		}
		$end = $start +$perpage;

		$wiki['date']=date($mybb->settings['dateformat'], $awiki['date'])." ".date($mybb->settings['timeformat'], $awiki['date']);
		$versions1 = "<option value=\"w\">{$wiki['date']}</option>";
		uasort($versions, "wiki_sort_versions_date");
		$versions = array_reverse($versions, true);
		foreach($versions as $wiki) {
			if($wiki['wid'] != $wid)
			    continue;

			++$num;

			$vid=$wiki['id'];
			$wiki=@unserialize($wiki['entry']);
			$uid=(int)$wiki['uid'];
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
			$user['username_formatted'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
			$wiki['user'] = build_profile_link($user['username_formatted'], $user['uid']);
			$wiki_version = wiki_get_version($vid);

			if(wiki_is_allowed("can_version_diff"))
			    $versions1 .= "<option value=\"$vid\">{$wiki['date']}</option>";

			if($start > $num || $num > $end)
			    continue;


			if(wiki_is_allowed("can_version_restore"))
				eval("\$restore = \"".$templates->get("wiki_versions_table_restore")."\";");
			if(wiki_is_allowed("can_version_delete"))
				eval("\$delete = \"".$templates->get("wiki_versions_table_delete")."\";");

			eval("\$wiki_table .= \"".$templates->get("wiki_versions_table")."\";");
		}
	}
	$multipage = multipage($num, $perpage, $page, wiki_get_versions($wid));

	if(wiki_is_allowed("can_version_diff")) {
		$versions2 = $versions1;
		eval("\$diffs = \"".$templates->get("wiki_versions_diff_panel")."\";");
	}
   	if(wiki_is_allowed("can_version_restore"))
		eval("\$restore = \"".$templates->get("wiki_versions_restore")."\";");
	if(wiki_is_allowed("can_version_delete"))
		eval("\$delete = \"".$templates->get("wiki_versions_delete")."\";");
	eval("\$wiki_versions = \"".$templates->get("wiki_versions")."\";");

	wiki_create_navy($category);
	add_breadcrumb($awiki['title'], wiki_get_article($wid));
	add_breadcrumb($lang->wiki_versions, wiki_get_versions($wid));
	output_page($wiki_versions);
}
if($mybb->input['action']=="unlock") {
	if(!isset($mybb->input['wid']))
	    error($lang->wiki_invalid_id);
	$wid=(int)$mybb->input['wid'];

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
	if($articles) {
		uasort($articles, "wiki_sort_date");
		$articles = array_reverse($articles, true);
		array_splice($articles, $perpage);
		foreach($articles as $wiki) {
			$uid = (int)$wiki['uid'];
			$user_query = $db->simple_select("users", "uid, username, usergroup, displaygroup", "uid='{$uid}'");
			$user=$db->fetch_array($user_query);
			$username_formatted = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
			$wiki['user'] = build_profile_link($username_formatted, $user['uid']);
			$wiki['date'] = date($mybb->settings['dateformat'], $wiki['date'])." ".date($mybb->settings['timeformat'], $wiki['date']);
			$wiki_article = wiki_get_article($wiki['id']);
			eval("\$article_table .= \"".$templates->get("wiki_new_element")."\";");
		}
	}
	eval("\$wiki_new = \"".$templates->get("wiki_new")."\";");
	output_page($wiki_new);
}
if($mybb->input['action']=="do_save_order") {
	if(!wiki_is_allowed("can_edit_sort"))
	    error_no_permission();

    if($mybb->input['order']=="article") {
	    $table = "wiki";
	    $c = "articles";
	} elseif($mybb->input['order']=="category") {
	    $table = "wiki_cats";
	    $c = "categories";
	} else
		error($lang->wiki_oder_error);

	foreach($mybb->input['disporder'] as $ID => $Sort) {
		$ID = $db->escape_string($ID); $Sort = $db->escape_string($Sort);
		$db->update_query($table, array("Sort"=>$Sort), "id='{$ID}'");
	}
	wiki_cache_update($c);

	if($table=="wiki_cats")
		redirect($wiki_link, $lang->redirect_wiki_order);
	else
		redirect(wiki_get_category($mybb->input['cat']), $lang->redirect_wiki_order);
}
if($mybb->input['action']=="search") {
	if(!wiki_is_allowed("can_search"))
	    error_no_permission();

	add_breadcrumb($lang->search, "wiki.php?action=search");
	$category = wiki_cache_load("categories");

	//Are we performing fulltext search or just title?
	$type = isset($mybb->input['type']) ? $mybb->input['type'] : $mybb->settings['wiki_stype'];
   	if($type != 'full' && $type != 'title')
		$type = $mybb->settings['wiki_stype'];

	//If we don't know where to search we search for articles
    if(!isset($mybb->input['where']) || sizeOf($mybb->input['where']) == 0)
		$mybb->input['where'] = array("articles");

	//If no category is set we search in all
	$cat_all = false;
    if(!isset($mybb->input['cats']) || sizeOf($mybb->input['cats']) == 0)
		$cat_all = true;

	if(!isset($mybb->input['date']))
	    $mybb->input['date'] = "all";

	if(!isset($mybb->input['sort']))
	    $mybb->input['sort'] = "date";

	if(!isset($mybb->input['dir']))
	    $mybb->input['dir'] = "asc";

	//Create some variables for showing the actual options
	if($type=="full") {
	    $full_checked = "checked=\"checked\"";
	    $title_checked = "";
	} else {
	    $full_checked = "";
	    $title_checked = "checked=\"checked\"";
	}

    $days1 = ''; $days2 = '';
	for($i = 1; $i <= 31; ++$i) {
		if($mybb->input['day1'] == $i && $mybb->input['date'] == "other") {
			$days1 .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		} else {
			$days1 .= "<option value=\"$i\">$i</option>\n";
		}
		if($mybb->input['day2'] == $i && $mybb->input['date'] == "other") {
			$days2 .= "<option value=\"$i\" selected=\"selected\">$i</option>\n";
		} else {
			$days2 .= "<option value=\"$i\">$i</option>\n";
		}
	}
	if($mybb->input['date'] == "other") {
		$month1[$mybb->input['month1']] = 'selected="selected"';
		$month2[$mybb->input['month2']] = 'selected="selected"';
		if($mybb->input['year1']<1970 || $mybb->input['year1'] > $copy_year)
		    $mybb->input['year1'] = $copy_year;
		if($mybb->input['year2']<1970 || $mybb->input['year2'] > $copy_year)
		    $mybb->input['year2'] = $copy_year;
		$year1 = (int)$mybb->input['year1'];
		$year2 = (int)$mybb->input['year2'];

		if($mybb->input['day1'] > 31 || $mybb->input['day1'] < 1 || $mybb->input['day2'] > 31 || $mybb->input['day2'] < 0 ||
		   	$mybb->input['month1'] > 12 || $mybb->input['month1'] < 1 || $mybb->input['month2'] > 12 || $mybb->input['month2'] < 1)
		   $mybb->input['date'] = "all";

		$time1 = mktime(0, 0, 0, $mybb->input['month1'], $mybb->input['day1'], $mybb->input['year1']);
		$time2 = mktime(23, 59, 59, $mybb->input['month2'], $mybb->input['day2'], $mybb->input['year2']);

		if($time2 < $time1) {
			$temp = $time2; $time2 = $time1; $time1 = $temp;
		}
	}

	$all_checked = ""; $day_checked = ""; $week_checked = "";
	$month_checked = ""; $other_checked = "";
	switch($mybb->input['date']) {
		case "all":
		default:
		    $all_checked = "checked=\"checked\"";
		    break;
		case "day":
			$day_checked = "checked=\"checked\"";
			$time1 = TIME_NOW - 24*60*60;
			$time2 = TIME_NOW;
			break;
		case "week":
			$week_checked = "checked=\"checked\"";
			$time1 = TIME_NOW - 7*24*60*60;
			$time2 = TIME_NOW;
			break;
		case "month":
			$month_checked = "checked=\"checked\"";
			$time1 = TIME_NOW - 31*24*60*60;
			$time2 = TIME_NOW;
			break;
		case "other":
			$other_checked = "checked=\"checked\"";
			break;
	}

	if($mybb->input['sort'] == "date") {
		$date_checked = "checked=\"checked\"";
		$stitle_checked = "";
	} else {
		$date_checked = "";
		$stitle_checked = "checked=\"checked\"";
	}

	if($mybb->input['dir'] == "asc") {
		$asc_checked = "checked=\"checked\"";
		$desc_checked = "";
	} else {
		$asc_checked = "";
		$desc_checked = "checked=\"checked\"";
	}

	if(wiki_is_allowed("can_view")) {
		if(in_array("articles", $mybb->input['where']))
			$wopt['articles'] = "<option value=\"articles\" selected=\"selected\">{$lang->wiki_articles}</option>";
		else
			$wopt['articles'] = "<option value=\"articles\">{$lang->wiki_articles}</option>";

		if(in_array("category", $mybb->input['where']))
			$wopt['category'] = "<option value=\"category\" selected=\"selected\">{$lang->wiki_categories}</option>";
		else
			$wopt['category'] = "<option value=\"category\">{$lang->wiki_categories}</option>";
	}

	if(wiki_is_allowed("can_version_view")) {
		if(in_array("versions", $mybb->input['where']))
			$wopt['versions'] = "<option value=\"versions\" selected=\"selected\">{$lang->wiki_versions}</option>";
		else
			$wopt['versions'] = "<option value=\"versions\">{$lang->wiki_versions}</option>";
	}

	if(wiki_is_allowed("can_trash_view")) {
		$wiki_trash = WIKI_TRASH;
		if(in_array("trash", $mybb->input['where']))
			$wopt['trash'] = "<option value=\"trash\" selected=\"selected\">{$lang->wiki_trash}</option>";
		else
			$wopt['trash'] = "<option value=\"trash\">{$lang->wiki_trash}</option>";
	}

	if($category) {
		foreach($category as $cat) {
			if($cat_all)
			    $mybb->input['cats'][] = $cat['id'];

			if(in_array($cat['id'], $mybb->input['cats']))
			    $cats .= "<option value=\"{$cat['id']}\" selected=\"selected\">{$cat['title']}</option>";
			else
			    $cats .= "<option value=\"{$cat['id']}\">{$cat['title']}</option>";
		}
	}

	//Do we have a string to search for?
	if(isset($mybb->input['searchString']) && $mybb->input['searchString'] != "") {
		$searchString = $db->escape_string($mybb->input['searchString']);
		$results = array();

		//Search for articles
		if(wiki_is_allowed("can_view") && in_array("articles", $mybb->input['where'])) {
			$query = 'SELECT id, cid, title, short, link, text, date FROM '.TABLE_PREFIX.'wiki WHERE ((title LIKE "%'.$searchString.'%"';

			if($type == 'full')
				$query .= ' OR text LIKE "%'.$searchString.'%"';

			if(isset($time1, $time2) && $mybb->input['date'] != "all")
			    $query .= ') AND (date > "'.$time1.'" AND date < "'.$time2.'"';

			$query .= ") AND cid IN (".$db->escape_string(implode(',', $mybb->input['cats']))."))";

			$resultQuery = $db->query($query);

			while($result = $db->fetch_array($resultQuery)) {
				$result['type'] = "articles";
				$results[] = $result;
			}
		}

		//Search for categories
		if(wiki_is_allowed("can_view") && in_array("category", $mybb->input['where'])) {
			$resultQuery = $db->simple_select("wiki_cats", "id, title", "title LIKE '%".$searchString."%'");

			while($result = $db->fetch_array($resultQuery)) {
				$result['type'] = "category";
				$results[] = $result;
			}
		}

		//Search for versions (A bit tricky)
		if(wiki_is_allowed("can_version_view") && in_array("versions", $mybb->input['where'])) {
			$versions = wiki_cache_load("versions");

			if($versions) {
				foreach($versions as $version) {
					if($version['tid'] != -1)
					    continue;

					$version['entry'] = @unserialize($version['entry']);
					$version['type'] = "versions";

					if(isset($time1, $time2) && $mybb->input['date'] != "all" && ($version['entry']['date'] < $time1 || $version['entry']['date'] > $time2))
						continue;

    				if(!in_array($version['entry']['cid'], $mybb->input['cats']))
					    continue;

					if(strpos($version['entry']['title'], $searchString) !== false) {
					    $results[] = $version;
					    continue;
					}

					if(strpos($version['entry']['text'], $searchString) !== false && $type == "full") {
					    $results[] = $version;
					    continue;
					}
				}
			}
		}

		//Search in the trash (It's like search in versions)
		if(wiki_is_allowed("can_trash_view") && in_array("trash", $mybb->input['where'])) {
			$trashs = wiki_cache_load("trash");

			if($trashs) {
				foreach($trashs as $trash) {
					$trash['entry'] = @unserialize($trash['entry']);
					$trash['type'] = "trash";

					if(isset($time1, $time2) && $mybb->input['date'] != "all" && ($trash['entry']['date'] < $time1 || $trash['entry']['date'] > $time2))
						continue;

					if(strpos($trash['entry']['title'], $searchString) !== false) {
					    $results[] = $trash;
					    continue;
					}

					if(strpos($trash['entry']['text'], $searchString) !== false && $type == "full") {
					    $results[] = $trash;
					    continue;
					}
				}
			}
		}

		if(sizeOf($results) < 1) {
			eval("\$searchResults = \"".$templates->get("wiki_search_results_no")."\";");
		} else {
			$articles = wiki_cache_load("articles");

			//Sort our results
			if($mybb->input['sort'] == "date")
			    uasort($results, "wiki_sort_search_date");
			else
				uasort($results, "wiki_sort_search_title");

			if($mybb->input['dir'] == "desc")
			    $results = array_reverse($results);

			foreach($results as $result) {
				if($result['type'] == "articles") {
					$date=date($mybb->settings['dateformat'], $result['date'])." ".date($mybb->settings['timeformat'], $result['date']);
				    $rtitle = "[".$lang->wiki_articles."] ";
		       		if($result['link'])
					    $rtitle .= '<a rel="nofollow" href="'.$result['link'].'" target="_blank">'.$result['title'].'</a>';
					else if($result['text'])
						$rtitle .= '<a href="'.$settings['bburl'].'/'.wiki_get_article($result['id']).'">'.$result['title'].'</a>';
					$rcategory = $category[$result['cid']]['title'];
					$rother = "<b>{$lang->wiki_short}: </b>{$result['short']}<br />";
					$rother .= "<b>{$lang->wiki_date}: </b>$date";
				} elseif($result['type'] == "category") {
				    $rtitle = "[".$lang->wiki_category."] ";
					$rtitle .= '<a href="'.$settings['bburl'].'/'.wiki_get_category($result['id']).'">'.$result['title'].'</a>';
					$rcategory = "-";
					$number = 0;
					if($articles) {
						foreach($articles as $article) {
							if($article['cid'] == $result['id'])
							    ++$number;
						}
					}
					$rother = "<b>{$lang->wiki_number}: </b>{$number}";
				} elseif($result['type'] == "versions") {
					$date=date($mybb->settings['dateformat'], $result['entry']['date'])." ".date($mybb->settings['timeformat'], $result['entry']['date']);
				    $rtitle = "[".$lang->wiki_version."] ";
					$rtitle .= '<a href="'.$settings['bburl'].'/'.wiki_get_version($result['id']).'">'.$result['entry']['title'].'</a>';
					$rcategory = $category[$result['entry']['cid']]['title'];
					$article = $articles[$result['wid']];
					$rother = "<b>{$lang->wiki_articles}: </b><a href=\"{$settings['bburl']}/".wiki_get_article($article['id'])."\">{$article['title']}</a><br />";
					$rother .= "<b>{$lang->wiki_date}: </b>$date";
				} elseif($result['type'] == "trash") {
					$date=date($mybb->settings['dateformat'], $result['entry']['date'])." ".date($mybb->settings['timeformat'], $result['entry']['date']);
				    $rtitle = "[".$lang->wiki_trash."] ";
					$rtitle .= '<a href="'.$settings['bburl'].'/'.$wiki_trash.'">'.$result['entry']['title'].'</a>';
					if(array_key_exists($result['entry']['cid'], $category))
						$rcategory = $category[$result['entry']['cid']]['title'];
					else
						$rcategory = $lang->wiki_trash_unknown_cat;
					$rother = "<b>{$lang->wiki_date}: </b>$date";
				} else
					continue;

				eval("\$searchResults .= \"".$templates->get("wiki_search_results_table")."\";");
			}
		}
	} else {
		$lang->search_no_result = $lang->search_not;
		eval("\$searchResults = \"".$templates->get("wiki_search_results_no")."\";");
	}

	eval("\$searchOutput = \"".$templates->get("wiki_search_results")."\";");
	output_page($searchOutput);
}
if(!isset($mybb->input['action']) || $mybb->input['action']=="show") {
	if(isset($mybb->input['cid']) && $mybb->input['cid']!="") {
		$cid=(int)$mybb->input['cid'];
		$link = "wiki.php?cid=".$cid;
		if($mybb->input['cpage']) {
		    $cadd = "?cpage=".(int)$mybb->input['cpage'];
		    $link .= "&amp;cpage=".(int)$mybb->input['cpage'];
		}
   		if($mybb->input['page']) {
		    $aadd = "&amp;page=".(int)$mybb->input['page'];
			$link .= $aadd;
		}
		if($mybb->input['sort']) {
		    $sadd = "&amp;sort={$mybb->input['sort']}";
			if(!$cadd)
			    $sortadd = "?sort={$mybb->input['asort']}";
			else
				$sortadd = $sadd;
		}
		if($mybb->input['asort']) {
		    $saadd = "&amp;asort={$mybb->input['asort']}";
			if(!$cadd && !$sadd)
			    $asortadd = "?asort={$mybb->input['asort']}";
			else
				$asortadd = $saadd;
		}

		$categorys = wiki_cache_load("categories");
		$category = $categorys[$cid];
		wiki_create_navy($category, false, $categorys);

		$moderation = wiki_is_allowed("can_unlock");
		$articles = wiki_cache_load("articles");

		if($categorys) {
			$num = 0;
			$page = (int)$mybb->input['cpage'];

			if($page > 1)
				$start = ($page-1) *$perpage +1;
			else {
				$start = 0;
				$page = 1;
			}
			$end = $start +$perpage;

			switch($mybb->input['sort']) {
				default:
				case "normal":
				case "date":
					uasort($categorys, "wiki_sort_sort");
					break;
				case "title":
					uasort($categorys, "wiki_sort_title");
					break;
			}
			$wiki_table="";
			$temp = $cid;
			foreach($categorys as $t) {
				if($t['pid'] != $category['id'])
				    continue;

				++$num;

				if($start > $num || $num > $end)
				    continue;

				$cid=(int)$t['id'];
				$category_title = '<a href="'.$settings['bburl'].'/'.wiki_get_category($cid).'">'.$t['title'].'</a>';
				$category_number = 0;
				if($articles) {
					foreach($articles as $article) {
						if($article['cid'] == $cid)
						    ++$category_number;
					}
				}
				if(wiki_is_allowed("can_edit_sort")) {
					$category_sort = $t['Sort'];
					eval("\$additional['sort'] = \"".$templates->get("wiki_table_sort")."\";");
				}

				if(wiki_is_allowed("can_edit"))
					eval("\$additional['control'] = \"".$templates->get("wiki_table_control")."\";");

				eval("\$wiki_table .= \"".$templates->get("wiki_table_element")."\";");
			}
			$cid = $temp;
			if($wiki_table != "") {
				$multipage = multipage($num, $perpage, $page, wiki_get_category($cid)."?cpage={page}{$aadd}{$sadd}{$saadd}")."<br />";
				$sort = getsort($link.$saadd);
				if(wiki_is_allowed("can_edit_sort")) {
					$submit = "<center><input type=\"submit\" value=\"{$lang->wiki_save_order}\" /></center>";
					eval("\$additional['sort'] = \"".$templates->get("wiki_sort")."\";");
				}

				if(wiki_is_allowed("can_edit"))
					eval("\$additional['control'] = \"".$templates->get("wiki_control")."\";");
				eval("\$wiki_category = \"".$templates->get("wiki_table")."\";");
				unset($wiki_table);
			}
		}

		if($articles) {
			$num = 0;
			$page = (int)$mybb->input['page'];

			if($page > 1)
				$start = ($page-1) *$perpage +1;
			else {
				$start = 0;
				$page = 1;
			}
			$end = $start +$perpage;

			switch($mybb->input['asort']) {
				default:
				case "normal":
					uasort($articles, "wiki_sort_sort");
					break;
				case "title":
					uasort($articles, "wiki_sort_title");
					break;
				case "date":
					uasort($articles, "wiki_sort_date");
					break;
			}
			$wiki_table="";
			foreach($articles as $wiki) {
				if($wiki['cid'] != $cid)
				    continue;
				if($wiki['awaiting_moderation'] && !$moderation)
				    continue;

				++$num;
				if($start > $num || $num > $end)
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
			$amultipage = multipage($num, $perpage, $page, wiki_get_category($cid)."{$cadd}{$sortadd}{$asortadd}");
		}

		if(wiki_is_allowed("can_create")) {
		    $category_add = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=category_add&cid={$cid}\" title=\"{$lang->wiki_nav_category_add}\">{$lang->wiki_nav_category_add}</a> | ";
		    $article_add = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=article_add&cid={$cid}\" title=\"{$lang->wiki_nav_add}\">{$lang->wiki_nav_add}</a>";
		}
		if(wiki_is_allowed("can_edit")) {
			if(isset($article_add))
			    $article_add .= " | ";
		    $article_edit = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=category_edit&cid={$cid}\" title=\"{$lang->wiki_nav_category_edit}\">{$lang->wiki_nav_category_edit}</a> | ";
			$article_delete = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=category_delete&cid={$cid}\" title=\"{$lang->wiki_nav_category_delete}\">{$lang->wiki_nav_category_delete}</a>";
			eval("\$additional['control'] = \"".$templates->get("wiki_category_control")."\";");
		}

		if(isset($article_add) || isset($article_edit))
			eval("\$wiki_header = \"".$templates->get("wiki_panel_category")."\";");

		if(wiki_is_allowed("can_edit_sort")) {
			$submit = "<center><input type=\"submit\" value=\"{$lang->wiki_save_order}\" /></center>";
			eval("\$additional['sort'] = \"".$templates->get("wiki_category_sort")."\";");
		}

		if($num > 0)
			$asort = getsort($link.$sadd, "asort");

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
		$id = (int)$mybb->input['wid'];
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
			if($arversions) {
		 		foreach($arversions as $version) {
					if($version['wid'] == $id)
					    ++$vnumber;
				}
			}

			$wiki_versions = wiki_get_versions($id);
			if($vnumber!=0)
				eval("\$versions = \"".$templates->get("wiki_panel_versions")."\";");
		}

		if($wiki['awaiting_moderation'])
			eval("\$unlock = \"".$templates->get("wiki_panel_unlock")."\";");

		$cid=(int)$wiki['cid'];
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
		$uid=(int)$wiki['uid'];
		$query = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
		$user=$db->fetch_array($query);
		$user_header = createHeader($user, $wiki);
		wiki_create_navy($category);
		add_breadcrumb($wiki_title, wiki_get_article($id));
		eval("\$showwiki = \"".$templates->get("wiki_text")."\";");
	} else {
		if($mybb->input['sort'])
		    $sadd = "?sort={$mybb->input['sort']}";

		$category = wiki_cache_load("categories");
		$articles = wiki_cache_load("articles");
		if($category) {
			$num = 0;
			$page = (int)$mybb->input['page'];

			if($page > 1)
				$start = ($page-1) *$perpage +1;
			else {
				$start = 0;
				$page = 1;
			}
			$end = $start +$perpage;

			switch($mybb->input['sort']) {
				default:
				case "normal":
				case "date":
					uasort($category, "wiki_sort_sort");
					break;
				case "title":
					uasort($category, "wiki_sort_title");
					break;
			}
			$wiki_table="";
			foreach($category as $t) {
				if($t['pid'] != -1)
				    continue;

				++$num;

				if($start > $num || $num > $end)
				    continue;

				$cid=(int)$t['id'];
				$category_title = '<a href="'.$settings['bburl'].'/'.wiki_get_category($cid).'">'.$t['title'].'</a>';
				$category_number = 0;
				if($articles) {
					foreach($articles as $article) {
						if($article['cid'] == $cid)
						    ++$category_number;
					}
				}
				if(wiki_is_allowed("can_edit_sort")) {
					$category_sort = $t['Sort'];
					eval("\$additional['sort'] = \"".$templates->get("wiki_table_sort")."\";");
				}

				if(wiki_is_allowed("can_edit"))
					eval("\$additional['control'] = \"".$templates->get("wiki_table_control")."\";");

				eval("\$wiki_table .= \"".$templates->get("wiki_table_element")."\";");
			}
			$multipage = multipage($num, $perpage, $page, $wiki_link.$sadd);
		}

		if(wiki_is_allowed("can_trash_view")) {
			$trashs = wiki_cache_load("trash");
			if($trashs) {
				$category = wiki_cache_load("categories");
				uasort($trashs, "wiki_sort_date");
				$trashs = array_reverse($trashs, true);
				array_splice($trashs, $perpage);
				foreach($trashs as $entry) {
					$trash=@unserialize($entry['entry']);
					$trash_cid = (int)$trash['cid'];
					if(is_array($category) && array_key_exists($trash_cid, $category))
						$trash['category'] = $category[$trash_cid]['title'];
					else
						$trash['category'] = $lang->wiki_trash_unknown_cat;
					$trash['deleteddate']=date($mybb->settings['dateformat'], $entry['date'])." ".date($mybb->settings['timeformat'], $entry['date']);
					$entry_from = (int)$entry['from'];
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
		if(wiki_is_allowed("can_search"))
			eval("\$search = \"".$templates->get("wiki_search")."\";");

    	if(wiki_is_allowed("can_create"))
		    $category_add = "<a href=\"{$mybb->settings['bburl']}/wiki.php?action=category_add\" title=\"{$lang->wiki_nav_category_add}\">{$lang->wiki_nav_category_add}</a>";

		eval("\$wiki_header = \"".$templates->get("wiki_panel")."\";");

		if(wiki_is_allowed("can_edit_sort")) {
			$submit = "<center><input type=\"submit\" value=\"{$lang->wiki_save_order}\" /></center>";
			eval("\$additional['sort'] = \"".$templates->get("wiki_sort")."\";");
		}

		if(wiki_is_allowed("can_edit"))
			eval("\$additional['control'] = \"".$templates->get("wiki_control")."\";");

		$link = "wiki.php";
		if($mybb->input['page'])
		    $link .= "?page=".(int)$mybb->input['page'];

		if($num > 0)
			$sort = getsort($link);

		eval("\$wiki_category = \"".$templates->get("wiki_table")."\";");
		eval("\$showwiki = \"".$templates->get("wiki")."\";");
	}
	output_page($showwiki);
}
?>