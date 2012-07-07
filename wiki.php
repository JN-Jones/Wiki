<?php
define("IN_MYBB", 1);
define("THIS_SCRIPT", "wiki.php");

$templatelist = "wiki,wiki_table,wiki_category,wiki_category_table,wiki_text,wiki_header,wiki_header_edit,wiki_header_hidden";
$templatelist .= "wiki_panel,wiki_panel_category,wiki_panel_text,wiki_panel_versions,wiki_panel_unlock";
$templatelist .= "wiki_add,wiki_edit,wiki_delete,wiki_category_add,wiki_category_edit,wiki_category_delete";
$templatelist .= "wiki_trash,wiki_trash_table,wiki_trash_table_element,wiki_versions,wiki_versions_table, wiki_new, wiki_new_element, wiki_copy";
$templatelist .= "wiki_search";

require("global.php");
$PL or require_once PLUGINLIBRARY;

add_breadcrumb($lang->wiki, $wiki_link);

$allowed = explode(",", $mybb->settings['wiki_allowedgroups']);
if(!wiki_user_in_group($mybb->user, $allowed))
//if(!$PL->is_member($allowed))
    error_no_permission();

$allowed = explode(",", $mybb->settings['wiki_write_allowedgroups']);
$allowedmod = explode(",", $mybb->settings['wiki_mod_allowedgroups']);

if($mybb->input['action']=="do_article_add" && $mybb->request_method == "post") {
    if(!wiki_user_in_group($mybb->user, $allowed))
//    if(!$PL->is_member($allowed))
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
		if($mybb->settings['wiki_moderate_new']&&!wiki_user_in_group($mybb->user, $allowedmod))
//		if($mybb->settings['wiki_moderate_new']&&!$PL->is_member($allowedmod))
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

	if(!wiki_user_in_group($mybb->user, $allowed))
//	if(!$PL->is_member($allowed))
	{
		error_no_permission();
	}

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
	$query = $db->simple_select("wiki_cats", "id, title", "", array('order_by' => 'title'));
	$wiki_cats="";
	while($t=$db->fetch_array($query)) {
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
		$query=$db->simple_select('wiki', "*", "id='{$wid}'");
		$wiki=$db->fetch_array($query);
		$entry=serialize($wiki);
		$db->insert_query('wiki_versions', array("wid"=>$wiki['id'],"entry"=>$db->escape_string($entry)));
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

		log_moderator_action("", $lang->wiki_log_edit.": ".$mybb->input['wiki_title']);

		redirect(wiki_get_article($wid), $lang->redirect_wiki_edit);
	} else {
		$mybb->input['action'] = "article_edit";
	}
}
if($mybb->input['action']=="article_edit") {
	// Make navigation
	add_breadcrumb($lang->wiki_nav_edit, "wiki.php?action=article_edit");

	if(!wiki_user_in_group($mybb->user, $allowed))
//	if(!$PL->is_member($allowed))
	{
		error_no_permission();
	}

	$wiki_id = intval($mybb->input['wid']);
	$query = $db->simple_select("wiki", "id, uid, cid, title, link, short, text, is_hidden, is_closed", "id='{$wiki_id}'");
	$wiki = $db->fetch_array($query);
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
	$query = $db->simple_select("wiki_cats", "id, title", "", array('order_by' => 'title'));
	$wiki_cats="";
	while($t=$db->fetch_array($query)) {
		if($wiki['cid']==$t['id'])
			$wiki_cats.="<option value=\"".$t['id']."\" selected=\"selected\">".$t['title']."</option>";
		else
			$wiki_cats.="<option value=\"".$t['id']."\">".$t['title']."</option>";
	}
	$codebuttons = build_mycode_inserter();
   	if($wiki['is_hidden'])
	    $hidden_checked="checked=\"checked\" ";
   	if($wiki['is_closed']) {
	    if($wiki['uid']==$mybb->user['uid']||wiki_user_in_group($mybb->user, $allowedmod))
//	    if($wiki['uid']==$mybb->user['uid']||$PL->is_member($allowedmod))
		    $closed_checked="checked=\"checked\" ";
		else
			error($lang->wiki_closed);
	}
	eval("\$wiki_edit = \"".$templates->get("wiki_edit")."\";");
	output_page($wiki_edit);
}
if($mybb->input['action'] == "do_article_delete" && $mybb->request_method == "post")
{
	verify_post_check($mybb->input['my_post_key']);

	$wid = intval($mybb->input['wid']);
	$query = $db->simple_select("wiki", "*", "id='{$wid}'");
	$wiki = $db->fetch_array($query);

	if(!$wiki['id'])
	{
		error($lang->wiki_error_invalid_wiki);
	}

	$insert_array = array(
		'entry' => $db->escape_string(serialize($wiki)),
		'from' => $mybb->user['uid'],
		'date' => time()
	);

	$db->insert_query("wiki_trash", $insert_array);
	$db->delete_query("wiki", "id='{$wid}'");

	log_moderator_action("", $lang->wiki_log_delete.": ".$wiki['title']);

	redirect(wiki_get_category($wiki['cid']), $lang->redirect_wiki_delete);
}
if($mybb->input['action']=="article_delete") {
	// Make navigation
	add_breadcrumb($lang->wiki_nav_delete, "wiki.php?action=article_delete");

	if(!wiki_user_in_group($mybb->user, $allowed))
//	if(!$PL->is_member($allowed))
	{
		error_no_permission();
	}

	$wiki_id = intval($mybb->input['wid']);
	$query = $db->simple_select("wiki", "id, title, is_closed", "id='{$wiki_id}'");
	$wiki = $db->fetch_array($query);
   	if($wiki['is_closed']) {
	    if($wiki['uid']!=$mybb->user['uid']&&!wiki_user_in_group($mybb->user, $allowedmod))
//	    if($wiki['uid']!=$mybb->user['uid']&&!$PL->is_member($allowedmod))
			error($lang->wiki_closed);
	}
	eval("\$wiki_delete = \"".$templates->get("wiki_delete")."\";");
	output_page($wiki_delete);
}
/**/
if($mybb->input['action']=="do_category_add" && $mybb->request_method == "post") {
	// Verify incoming POST request
	verify_post_check($mybb->input['my_post_key']);
	if(!$mybb->input['wiki_name'])
	    $errors[] = $lang->wiki_no_name;

	if(!$errors) {
		$insert_array = array(
			'title' => $db->escape_string($mybb->input['wiki_name'])
		);
		$nid = $db->insert_query('wiki_cats', $insert_array);

		log_moderator_action("", $lang->wiki_log_category_add.": ".$mybb->input['wiki_name']);

		redirect(wiki_get_category($nid), $lang->redirect_wiki_category_add);
	} else {
		$mybb->input['action'] = "wiki_category_add";
	}
}
if($mybb->input['action']=="category_add") {
	// Make navigation
	add_breadcrumb($lang->wiki_nav_category_add, "wiki.php?action=wiki_category_add");

	if(!wiki_user_in_group($mybb->user, $allowed))
//	if(!$PL->is_member($allowed))
	{
		error_no_permission();
	}

	// Coming back to this page from an error?
	if($errors)
	{
		$errors = inline_error($errors);
	}
	eval("\$wiki_category_add = \"".$templates->get("wiki_category_add")."\";");
	output_page($wiki_category_add);
}
if($mybb->input['action'] == "do_category_edit" && $mybb->request_method == "post")
{
	verify_post_check($mybb->input['my_post_key']);

	$cid = intval($mybb->input['cid']);

	if(!$mybb->input['wiki_name'])
	    $errors[] = $lang->wiki_no_name;

	if(!$errors) {
		$update_array = array(
			'title' => $db->escape_string($mybb->input['wiki_name'])
		);

		$db->update_query('wiki_cats', $update_array, "id='{$cid}'");

		log_moderator_action("", $lang->wiki_log_category_edit.": ".$mybb->input['wiki_name']);

		redirect(wiki_get_category($cid), $lang->redirect_wiki_category_edit);
	} else {
		$mybb->input['action'] = "wiki_category_edit";
	}
}
if($mybb->input['action']=="category_edit") {
	// Make navigation
	add_breadcrumb($lang->wiki_nav_edit, "wiki.php?action=category_edit");

	if(!wiki_user_in_group($mybb->user, $allowed))
//	if(!$PL->is_member($allowed))
	{
		error_no_permission();
	}

	$wiki_id = intval($mybb->input['cid']);
	$query = $db->simple_select("wiki_cats", "id, title", "id='{$wiki_id}'");
	$wiki = $db->fetch_array($query);
	// Coming back to this page from an error?
	if($errors)
	{
		$errors = inline_error($errors);
	}
	eval("\$wiki_category_edit = \"".$templates->get("wiki_category_edit")."\";");
	output_page($wiki_category_edit);
}
if($mybb->input['action'] == "do_category_delete" && $mybb->request_method == "post")
{
	verify_post_check($mybb->input['my_post_key']);

	$cid = intval($mybb->input['cid']);
	$query = $db->simple_select("wiki_cats", "id, title", "id='{$cid}'");
	$wiki = $db->fetch_array($query);

	if(!$wiki['id'])
	{
		error($lang->wiki_error_invalid_category);
	}

	$db->delete_query("wiki_cats", "id='{$cid}'");
	$db->delete_query("wiki", "cid='{$cid}'");

	log_moderator_action("", $lang->wiki_log_category_delete.": ".$wiki['title']);

	redirect($wiki_link, $lang->redirect_wiki_category_delete);
}
if($mybb->input['action']=="category_delete") {
	add_breadcrumb($lang->wiki_nav_category_delete, "wiki.php?action=category_delete");

	if(!wiki_user_in_group($mybb->user, $allowed))
//	if(!$PL->is_member($allowed))
	{
		error_no_permission();
	}

	$wiki_id = intval($mybb->input['cid']);
	$query = $db->simple_select("wiki_cats", "id, title", "id='{$wiki_id}'");
	$wiki = $db->fetch_array($query);
	eval("\$wiki_category_delete = \"".$templates->get("wiki_category_delete")."\";");
	output_page($wiki_category_delete);
}
if($mybb->input['action']=="restore") {
	if(!wiki_user_in_group($mybb->user, $allowedmod))
//	if(!$PL->is_member($allowedmod))
	    error_no_permission();
	$wid = intval($mybb->input['wid']);
	$trash_query=$db->simple_select("wiki_trash", "entry", "id='{$wid}'");
	$entry=$db->fetch_array($trash_query);
	if(!$entry['entry'])
	    $errors[]=$lang->wiki_error_invalid_wiki;

	if(!$errors) {
		$entry=@unserialize($entry['entry']);
		foreach($entry as $key => $value)
		    $entry[$key] = $db->escape_string($value);
		$db->insert_query('wiki', $entry);
		$db->delete_query("wiki_trash", "id='{$wid}'");
		log_moderator_action("", $lang->wiki_log_restored.": ".$entry['title']);
		redirect($wiki_link, $lang->redirect_wiki_restore);
	} else
		$mybb->input['action']="trash";
}
if($mybb->input['action']=="trash_delete") {
	if(!wiki_user_in_group($mybb->user, $allowedmod))
//	if(!$PL->is_member($allowedmod))
	    error_no_permission();
	$wid = intval($mybb->input['wid']);
	$trash_query=$db->simple_select("wiki_trash", "id, entry", "id='{$wid}'");
	$entry=$db->fetch_array($trash_query);
	if(!$entry['id'])
	    $errors[]=$lang->wiki_error_invalid_wiki;

	if(!$errors) {
		$entry=@unserialize($entry['entry']);
		$db->delete_query("wiki_trash", "id='{$wid}'");
		log_moderator_action("", $lang->wiki_log_trash_delete.": ".$entry['title']);
		redirect($wiki_link, $lang->redirect_wiki_trash_delete);
	} else
		$mybb->input['action']="trash";
}
if($mybb->input['action']=="trash") {
	add_breadcrumb($lang->wiki_trash, WIKI_TRASH);
	if(!wiki_user_in_group($mybb->user, $allowedmod))
//	if(!$PL->is_member($allowedmod))
	    error_no_permission();

	$trash_query=$db->simple_select("wiki_trash", "*", "", array("order_by"=>"date", "order_dir"=>"desc"));
	while($entry=$db->fetch_array($trash_query)) {
		$trash=@unserialize($entry['entry']);
		$trash_cid = intval($trash['cid']);
		$query=$db->simple_select("wiki_cats", "title AS category", "id='{$trash_cid}'");
		$trash=array_merge($trash, $db->fetch_array($query));
		$trash['deleteddate']=date($mybb->settings['dateformat'], $entry['date'])." ".date($mybb->settings['timeformat'], $entry['date']);
		$entry_from = intval($entry['from']);
		$query=$db->simple_select("users", "username AS deletedfrom", "uid='{$entry_from}'");
		$trash=array_merge($trash, $db->fetch_array($query));
		$trash['id']=$entry['id'];
		$wiki_trash = WIKI_TRASH;
		eval("\$wiki_trash_table .= \"".$templates->get("wiki_trash_table_element")."\";");
	}
	eval("\$wiki_trash_table= \"".$templates->get("wiki_trash_table")."\";");
	if($errors)
	{
		$errors = inline_error($errors);
	}
	eval("\$wiki_trash= \"".$templates->get("wiki_trash")."\";");
	output_page($wiki_trash);
}
if($mybb->input['action']=="restore_version") {
	if(!isset($mybb->input['vid']))
	    error($lang->wiki_invalid_id);
	$vid=intval($mybb->input['vid']);
	$query=$db->simple_select('wiki_versions', "*", "id='{$vid}'");
	$wiki=$db->fetch_array($query);
	$wiki=@unserialize($wiki['entry']);
	$wid = intval($wiki['id']);
	$query=$db->simple_select('wiki', "*", "id='{$wid}'");
	$awiki=$db->fetch_array($query);
	$entry=serialize($awiki);
	foreach($wiki as $key => $value)
	    $wiki[$key] = $db->escape_string($value);
	$db->insert_query('wiki_versions', array("wid"=>$awiki['id'],"entry"=>$db->escape_string($entry)));
	$db->delete_query('wiki_versions', "id='{$vid}'");
	$db->update_query('wiki', $wiki, "id='".$awiki['id']."'");

	log_moderator_action("", $lang->wiki_log_restored_version.": ".$wiki['title']);

	redirect(wiki_get_article($awiki['id']), $lang->redirect_wiki_restore_version);
}
if($mybb->input['action']=="delete_version") {
	if(!wiki_user_in_group($mybb->user, $allowedmod))
//	if(!$PL->is_member($allowedmod))
	    error_no_permission();
	$vid = intval($mybb->input['vid']);
	$delete_query=$db->simple_select("wiki_versions", "id, wid, entry", "id='{$vid}'");
	$entry=$db->fetch_array($delete_query);
	if(!$entry['id'])
	    $errors[]=$lang->wiki_error_invalid_wiki;

	if(!$errors) {
		$entry=@unserialize($entry['entry']);
		$db->delete_query("wiki_versions", "id='{$vid}'");
		log_moderator_action("", $lang->wiki_log_version_delete.": ".$entry['title']);
		redirect(wiki_get_versions($entry['id']), $lang->redirect_wiki_version_delete);
	} else
		$mybb->input['action']="versions";
}
if($mybb->input['action']=="show_version") {
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
	$query=$db->simple_select('wiki_versions', "*", "id='{$vid}'");
	$wiki=$db->fetch_array($query);
	$wiki=@unserialize($wiki['entry']);
	$wiki['formateddate']=date($mybb->settings['dateformat'], $wiki['date'])." ".date($mybb->settings['timeformat'], $wiki['date']);
	$wid = intval($wiki['id']);
	$query=$db->simple_select('wiki', "*", "id='{$wid}'");
	$awiki=$db->fetch_array($query);
	add_breadcrumb($awiki['title'], wiki_get_article($awiki['id']));
	add_breadcrumb($lang->wiki_versions, wiki_get_versions($awiki['id']));
	add_breadcrumb($wiki['formateddate'], wiki_get_version($vid));
	if($wiki['is_hidden']) {
		eval("\$wiki['hidden'] = \"".$templates->get("wiki_header_hidden")."\";");
	}

	$wiki_title = $wiki['title'];
	$wiki_text = $parser->parse_message($wiki['text'], $parser_options);
	$cid=intval($wiki['cid']);
	$test = $db->simple_select("wiki_cats", "id, title", "id='{$cid}'");
	$category=$db->fetch_array($test);
	$uid=intval($wiki['uid']);
	$query = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
	$user=$db->fetch_array($query);
	$user_header = createHeader($user, $wiki, false);
	eval("\$showversion = \"".$templates->get("wiki_text")."\";");
	output_page($showversion);
}
if($mybb->input['action']=="versions") {
	if(!isset($mybb->input['wid']))
	    error($lang->wiki_invalid_id);
	$wid=intval($mybb->input['wid']);
	$query=$db->simple_select('wiki', "*", "id='{$wid}'");
	$awiki=$db->fetch_array($query);
	add_breadcrumb($awiki['title'], wiki_get_article($wid));
	add_breadcrumb($lang->wiki_versions, wiki_get_versions($wid));
	if($errors)
		$errors = inline_error($errors);
	$query=$db->simple_select('wiki_versions', "*", "wid='{$wid}'");
	while($wiki=$db->fetch_array($query)) {
		$vid=$wiki['id'];
		$wiki=@unserialize($wiki['entry']);
		$uid=intval($wiki['uid']);
		$wiki['date']=date($mybb->settings['dateformat'], $wiki['date'])." ".date($mybb->settings['timeformat'], $wiki['date']);
		$user = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
		$user=$db->fetch_array($user);
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
		$post['profilelink_plain'] = get_profile_link($user['uid']);
		$user['username_formatted'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
		$wiki['user'] = build_profile_link($user['username_formatted'], $user['uid']);
		$wiki_version = wiki_get_version($vid);
		eval("\$wiki_table .= \"".$templates->get("wiki_versions_table")."\";");
	}
	eval("\$wiki_versions = \"".$templates->get("wiki_versions")."\";");
	output_page($wiki_versions);
}
if($mybb->input['action']=="unlock") {
	if(!isset($mybb->input['wid']))
	    error($lang->wiki_invalid_id);
	$wid=intval($mybb->input['wid']);
	if(!wiki_user_in_group($mybb->user, $allowedmod))
//	if(!$PL->is_member($allowedmod))
	    error_no_permission();
	$db->update_query('wiki', array('awaiting_moderation'=>'0'), "id='{$wid}'");

	log_moderator_action("", $lang->wiki_log_unlock.": ".$wiki['title']);

	redirect(wiki_get_article($wid), $lang->redirect_wiki_unlock);
}
if($mybb->input['action']=="new") {
	add_breadcrumb($lang->wiki_nav_new, WIKI_NEW);
	$new_query = $db->simple_select("wiki", "id, title, short, uid, date", "", array("limit"=>"10", "order_by"=>"date", "order_dir"=>"desc"));
	while($wiki=$db->fetch_array($new_query)) {
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
	if(!wiki_user_in_group($mybb->user, $allowedmod))
//	if(!$PL->is_member($allowedmod))
	    error_no_permission();
	if($mybb->input['order']=="article")
	    $table = "wiki";
	elseif($mybb->input['order']=="category")
	    $table = "wiki_cats";
	else
		error($lang->wiki_oder_error);

	foreach($mybb->input['disporder'] as $ID => $Sort) {
		$ID = $db->escape_string($ID); $Sort = $db->escape_string($Sort);
		$db->update_query($table, array("Sort"=>$Sort), "ID='{$ID}'");
	}
	if($table=="wiki_cats")
		redirect($wiki_link, $lang->redirect_wiki_order);
	else
		redirect(wiki_get_category($mybb->input['cat']), $lang->redirect_wiki_order);
}
if($mybb->input['action']=="search") {
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
		while($result = $db->fetch_array($resultQuery)) {
       		if($result['link'])
			    $result['title'] = '<a rel="nofollow" href="'.$result['link'].'" target="_blank">'.$result['title'].'</a>';
			else if($result['text'])
				$result['title'] = '<a href="'.$settings['bburl'].'/'.wiki_get_article($result['id']).'">'.$result['title'].'</a>';

			$result_cid = intval($result['cid']);
			$catQuery = $db->simple_select("wiki_cats", "title", "id={$result_cid}");
			$result['category'] = $db->fetch_field($catQuery, "title");
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
if(!isset($mybb->input['action'])||$mybb->input['action']=="show") {
	if(isset($mybb->input['cid'])&&$mybb->input['cid']!="") {
		$wiki_cid=intval($mybb->input['cid']);
		$test = $db->simple_select("wiki_cats", "id, title", "id='{$wiki_cid}'");
		$category=$db->fetch_array($test);
		add_breadcrumb($category['title'], wiki_get_category($category['id']));
		if(wiki_user_in_group($mybb->user, $allowedmod))
//		if($PL->is_member($allowedmod))
			$query = $db->simple_select("wiki", "id, uid, is_hidden, is_closed, link, title, short, text, awaiting_moderation AS moderate, Sort", "cid='$wiki_cid'", array("order_by"=>"Sort"));
		else
			$query = $db->simple_select("wiki", "id, uid, is_hidden, is_closed, link, title, short, text, awaiting_moderation AS moderate, Sort", "cid='$wiki_cid' AND awaiting_moderation='false'", array("order_by"=>"Sort"));
		$wiki_table="";
		while($wiki=$db->fetch_array($query)) {
    		if($wiki['link'])
			    $wiki_title = '<a rel="nofollow" href="'.$wiki['link'].'" target="_blank">'.$wiki['title'].'</a>';
			else if($wiki['text'])
				$wiki_title = '<a href="'.$settings['bburl'].'/'.wiki_get_article($wiki['id']).'">'.$wiki['title'].'</a>';
			else
				$wiki_title = $wiki['title'];
			$background="";
			$wiki_short = $wiki['short'];
	  		if($wiki['moderate'])
			    $background="#6EFF6E";
			if(wiki_user_in_group($mybb->user, $allowed)) {
		//	if($PL->is_member($allowed)) {
				eval("\$additional = \"".$templates->get("wiki_category_table_mod")."\";");
			}
       		if(!$wiki['is_hidden']||($wiki['uid']==$mybb->user['uid']||$mybb->usergroup['cancp']||$mybb->user['ismoderator']))
				eval("\$wiki_table .= \"".$templates->get("wiki_category_table")."\";");
		}

		if(wiki_user_in_group($mybb->user, $allowed)) {
//		if($PL->is_member($allowed)) {
			eval("\$wiki_header = \"".$templates->get("wiki_panel_category")."\";");
			$submit = "<center><input type=\"submit\" value=\"{$lang->wiki_save_order}\" /></center>";
			eval("\$additional = \"".$templates->get("wiki_category_mod")."\";");
		}
		eval("\$showwiki = \"".$templates->get("wiki_category")."\";");
	}elseif(isset($mybb->input['wid'])&&$mybb->input['wid']!="") {
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
		$query = $db->simple_select("wiki", "id, cid, title, link, text, uid, username, date, is_hidden, is_closed, awaiting_moderation", "id='{$id}'");
		$wiki=$db->fetch_array($query);
		if($wiki['awaiting_moderation']&&!wiki_user_in_group($mybb->user, $allowedmod))
//		if($wiki['awaiting_moderation']&&!$PL->is_member($allowedmod))
		    error_no_permission();
		if($wiki['is_hidden']) {
		    if($wiki['uid']==$mybb->user['uid']||wiki_user_in_group($mybb->user, $allowedmod))
//		    if($wiki['uid']==$mybb->user['uid']||$PL->is_member($allowedmod))
				eval("\$wiki['hidden'] = \"".$templates->get("wiki_header_hidden")."\";");
			else
				error($lang->wiki_hidden);
		}

     	if(wiki_user_in_group($mybb->user, $allowedmod)) {
//     	if($PL->is_member($allowedmod)) {
			$query = $db->simple_select("wiki_versions", "*", "wid='{$id}'");
			$vnumber=$db->num_rows($query);
			$wiki_versions = wiki_get_versions($id);
			if($vnumber!=0)
				eval("\$versions = \"".$templates->get("wiki_panel_versions")."\";");
			if($wiki['awaiting_moderation'])
				eval("\$unlock = \"".$templates->get("wiki_panel_unlock")."\";");
		}

		$cid=intval($wiki['cid']);
     	if(wiki_user_in_group($mybb->user, $allowed)) {
//     	if($PL->is_member($allowed)) {
			if(!$wiki['is_closed'])
				eval("\$wiki_header = \"".$templates->get("wiki_panel_text")."\";");
			else {
			    if($wiki['uid']==$mybb->user['uid']||wiki_user_in_group($mybb->user, $allowedmod))
//			    if($wiki['uid']==$mybb->user['uid']||$PL->is_member($allowedmod))
					eval("\$wiki_header = \"".$templates->get("wiki_panel_text")."\";");
			}
		}

		$wiki_title = $wiki['title'];
		$wiki_text = $parser->parse_message($wiki['text'], $parser_options);
		$test = $db->simple_select("wiki_cats", "id, title", "id='{$cid}'");
		$category=$db->fetch_array($test);
		$uid=intval($wiki['uid']);
		$query = $db->simple_select("users", "uid, username, postnum, avatar, avatardimensions, usergroup, additionalgroups, displaygroup, usertitle, lastactive, lastvisit, invisible, away", "uid='{$uid}'");
		$user=$db->fetch_array($query);
		$user_header = createHeader($user, $wiki);
		add_breadcrumb($category['title'], wiki_get_category($category['id']));
		add_breadcrumb($wiki_title, wiki_get_article($id));
		eval("\$showwiki = \"".$templates->get("wiki_text")."\";");
	} else {
		$query = $db->simple_select("wiki_cats", "id, title, Sort", "", array("order_by"=>"Sort"));
		$wiki_table="";
		while($t=$db->fetch_array($query)) {
			$cid=intval($t['id']);
			$category_title = '<a href="'.$settings['bburl'].'/'.wiki_get_category($cid).'">'.$t['title'].'</a>';
			$category_number = $db->num_rows($db->simple_select("wiki", "id", "cid='{$cid}'"));
			if(wiki_user_in_group($mybb->user, $allowed)) {
		//	if($PL->is_member($allowed)) {
				$category_sort = $t['Sort'];
				eval("\$additional = \"".$templates->get("wiki_table_mod")."\";");
			}
			eval("\$wiki_table .= \"".$templates->get("wiki_table")."\";");
		}

		if(wiki_user_in_group($mybb->user, $allowedmod)) {
//		if($PL->is_member($allowedmod)) {
			$trash_query=$db->simple_select("wiki_trash", "*", "", array("limit"=>"5", "order_by"=>"date", "order_dir"=>"desc"));
			while($entry=$db->fetch_array($trash_query)) {
				$trash=@unserialize($entry['entry']);
				$trash_cid = intval($trash['cid']);
				$query=$db->simple_select("wiki_cats", "title AS category", "id='{$trash_cid}'");
				$trash_cat = $db->fetch_array($query);
				if($trash_cat&&$trash_cat!="")
					$trash=array_merge($trash, $trash_cat);
				else
					$trash['category'] = $lang->wiki_trash_unknown_cat;
				$trash['deleteddate']=date($mybb->settings['dateformat'], $entry['date'])." ".date($mybb->settings['timeformat'], $entry['date']);
				$entry_from = intval($entry['from']);
				$query=$db->simple_select("users", "username AS deletedfrom", "uid='{$entry_from}'");
				$trash=array_merge($trash, $db->fetch_array($query));
				$trash['id']=$entry['id'];
				eval("\$wiki_trash_table .= \"".$templates->get("wiki_trash_table_element")."\";");
			}
			$wiki_trash = WIKI_TRASH;
			eval("\$wiki_trash= \"".$templates->get("wiki_trash_table")."\";");
		}
		$wiki_new = WIKI_NEW;
		if(wiki_user_in_group($mybb->user, $allowed)) {
//		if($PL->is_member($allowed)) {
			if($mybb->settings['wiki_stype']=="full") {
			    $full_checked = "checked=\"checked\"";
			    $title_checked = "";
			} else {
			    $full_checked = "";
			    $title_checked = "checked=\"checked\"";
			}
			eval("\$search = \"".$templates->get("wiki_search")."\";");
			eval("\$wiki_header = \"".$templates->get("wiki_panel")."\";");
			$submit = "<center><input type=\"submit\" value=\"{$lang->wiki_save_order}\" /></center>";
			eval("\$additional = \"".$templates->get("wiki_mod")."\";");
		}
		eval("\$showwiki = \"".$templates->get("wiki")."\";");
	}
	output_page($showwiki);
}
?>