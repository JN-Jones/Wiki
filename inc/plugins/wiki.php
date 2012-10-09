<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}
if(!$pluginlist)
    $pluginlist = $cache->read("plugins");

$plugins->add_hook("global_start", "wiki_init");
$plugins->add_hook("fetch_wol_activity_end", "wiki_wol_activity");
$plugins->add_hook("build_friendly_wol_location_end", "wiki_wol_location");
$plugins->add_hook("parse_message", "wiki_mycode");
$plugins->add_hook("parse_message_quote", "wiki_mycode");
$plugins->add_hook("parse_message_end", "wiki_autolink");
if(is_array($pluginlist['active']) && in_array("myplugins", $pluginlist['active'])) {
	$plugins->add_hook("myplugins_actions", "wiki_myplugins_actions");
	$plugins->add_hook("myplugins_permission", "wiki_myplugins_admin_permissions");
}

function wiki_info()
{
	return array(
		"name"			=> "Wiki",
		"description"	=> "Adds a wiki to the Forum",
		"website"		=> "http://jonesboard.tk/",
		"author"		=> "Jones",
		"authorsite"	=> "http://jonesboard.tk/",
		"version"		=> "1.2.1",
		"guid" 			=> "0b842d4741fc27e460013732dd5d6d52",
		"compatibility" => "16*"
	);
}

function wiki_install()
{
	global $db, $lang, $PL;
	$plugininfo = wiki_info();
	$lang->load("wiki");
    if(!file_exists(PLUGINLIBRARY))
    {
        flash_message($lang->wiki_pl_missing, "error");
        admin_redirect("index.php?module=config-plugins");
    }
    $PL or require_once PLUGINLIBRARY;

    if($PL->version < 8)
    {
        flash_message($lang->wiki_pl_old, "error");
        admin_redirect("index.php?module=config-plugins");
    }

	wiki_settings(true);
	wiki_templates(true);

	$col = $db->build_create_table_collation();
	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`cid` int(11) NOT NULL,
				`title` varchar(50) DEFAULT NULL,
				`link` varchar(100) DEFAULT NULL,
				`short` varchar(200) DEFAULT NULL,
				`text` text, `uid` int(10),
				`username` varchar(80),
				`date` bigint(30),
				`is_hidden` boolean,
				`is_closed` boolean,
				`awaiting_moderation` boolean,
				`Sort` int NOT NULL default '0',
	PRIMARY KEY (`id`) ) ENGINE=MyISAM {$col}");

	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki_cats` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`pid` int(11) NOT NULL default '-1',
				`title` varchar(50) DEFAULT NULL,
				`Sort` int NOT NULL default '0',
	PRIMARY KEY (`id`) ) ENGINE=MyISAM {$col}");

	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki_trash` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`entry` text,
				`from` int(10) NOT NULL,
				`date` bigint(30) NOT NULL,
	PRIMARY KEY (`id`) ) ENGINE=MyISAM {$col}");

	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki_versions` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`wid` int(11) NOT NULL DEFAULT '-1',
				`tid` int(11) NOT NULL DEFAULT '-1',
				`entry` text,
	PRIMARY KEY (`id`) ) ENGINE=MyISAM {$col}");

	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki_permissions` (
		`gid` int(11) NOT NULL,
		`can_view` boolean NOT NULL DEFAULT '1',
		`can_create` boolean NOT NULL DEFAULT '1',
		`can_edit` boolean NOT NULL DEFAULT '0',
		`can_search` boolean NOT NULL DEFAULT '1',
		`can_version_view` boolean NOT NULL DEFAULT '0',
		`can_version_restore` boolean NOT NULL DEFAULT '0',
		`can_version_delete` boolean NOT NULL DEFAULT '0',
		`can_version_diff` boolean NOT NULL DEFAULT '0',
		`can_trash_view` boolean NOT NULL DEFAULT '0',
		`can_trash_restore` boolean NOT NULL DEFAULT '0',
		`can_trash_delete` boolean NOT NULL DEFAULT '0',
		`can_edit_closed` boolean NOT NULL DEFAULT '0',
		`can_view_hidden` boolean NOT NULL DEFAULT '0',
		`can_edit_sort` boolean NOT NULL DEFAULT '0',
		`can_unlock` boolean NOT NULL DEFAULT '0',
	PRIMARY KEY (`gid`) ) ENGINE=MyISAM {$col}");

	$db->query('INSERT INTO '.TABLE_PREFIX.'wiki_permissions
			(gid, can_view, can_create, can_edit, can_search, can_version_view, can_version_restore, can_version_delete, can_version_diff, can_trash_view, can_trash_restore, can_trash_delete, can_edit_closed, can_view_hidden, can_edit_sort, can_unlock)
		VALUES
			(1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
			(2, 1, 1, 1, 1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0),
			(3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
			(4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
			(5, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
			(6, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
			(7, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
		');

	$PL->cache_update("wiki_version", $plugininfo['version']);
	$PL->cache_update("wiki_pl_version", "8");
//	wiki_cache_update("permissions");
}

function wiki_is_installed()
{
	global $db;
	return $db->table_exists("wiki");
}

function wiki_uninstall()
{
    global $PL, $db;
    $PL or require_once PLUGINLIBRARY;

    $PL->settings_delete("wiki");
    $PL->templates_delete("wiki");

	$db->drop_table("wiki");
	$db->drop_table("wiki_cats");
	$db->drop_table("wiki_trash");
	$db->drop_table("wiki_versions");
	$db->drop_table("wiki_permissions");

	$PL->cache_delete("wiki_version");
	$PL->cache_delete("wiki_pl_version");
	$PL->cache_delete("wiki_articles");
	$PL->cache_delete("wiki_categories");
	$PL->cache_delete("wiki_versions");
	$PL->cache_delete("wiki_trash");
	$PL->cache_delete("wiki_permissions");
}

function wiki_activate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#".preg_quote('<li><a href="{$mybb->settings[\'bburl\']}/search.php"><img src="{$theme[\'imgdir\']}/toplinks/search.gif" alt="" title="" />{$lang->toplinks_search}</a></li>')."#i", '<li><a href="{$mybb->settings[\'bburl\']}/search.php"><img src="{$theme[\'imgdir\']}/toplinks/search.gif" alt="" title="" />{$lang->toplinks_search}</a></li><li><a href="{$mybb->settings[\'bburl\']}/{$wiki_link}"><img src="{$theme[\'imgdir\']}/toplinks/wiki.gif" alt="" title="" />{$lang->wiki}</a></li>');
	find_replace_templatesets("footer", "#".preg_quote('<!-- End powered by -->')."#i", '{$wiki_copyright}<!-- End powered by -->');
}

function wiki_deactivate()
{
	require MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#".preg_quote('<li><a href="{$mybb->settings[\'bburl\']}/{$wiki_link}"><img src="{$theme[\'imgdir\']}/toplinks/wiki.gif" alt="" title="" />{$lang->wiki}</a></li>')."#i", "", 0);
	find_replace_templatesets("footer", "#".preg_quote('{$wiki_copyright}')."#i", "", 0);
}

function wiki_myplugins_actions($actions)
{
	global $page, $lang, $info, $db;
	$lang->load("wiki");

	$own_actions = array(
		'wiki-index' => array('active' => 'wiki-index', 'file' => '../wiki/home.php'),
		'wiki-article' => array('active' => 'wiki-article', 'file' => '../wiki/article.php'),
		'wiki-permissions' => array('active' => 'wiki-permissions', 'file' => '../wiki/permissions.php'),
		'wiki-import' => array('active' => 'wiki-import', 'file' => '../wiki/import.php'),
		'wiki-cache' => array('active' => 'wiki-cache', 'file' => '../wiki/cache.php'),
		'wiki-update' => array('active' => 'wiki-update', 'file' => '../wiki/update.php')
	);
	$actions = array_merge($actions, $own_actions);

	$query = $db->simple_select("settinggroups", "gid", "name='Wiki'");
    $g = $db->fetch_array($query);

	$sub_menu = array();
	$sub_menu['5'] = array("id" => "wiki-index", "title" => $lang->wiki_index, "link" => "index.php?module=myplugins-wiki-index");
	$sub_menu['10'] = array("id" => "wiki-article", "title" => $lang->wiki_article, "link" => "index.php?module=myplugins-wiki-article");
	$sub_menu['15'] = array("id" => "wiki-permissions", "title" => $lang->wiki_permissions, "link" => "index.php?module=myplugins-wiki-permissions");
	$sub_menu['20'] = array("id" => "wiki-option", "title" => $lang->wiki_option, "link" => "index.php?module=config-settings&action=change&gid=".$g['gid']);
	$sub_menu['25'] = array("id" => "wiki-import", "title" => $lang->wiki_import, "link" => "index.php?module=myplugins-wiki-import");
	$sub_menu['30'] = array("id" => "wiki-cache", "title" => $lang->wiki_cache, "link" => "index.php?module=myplugins-wiki-cache");
	$sub_menu['35'] = array("id" => "wiki-update", "title" => $lang->wiki_update, "link" => "index.php?module=myplugins-wiki-update");

	$sidebar = new SidebarItem($lang->wiki);
	$sidebar->add_menu_items($sub_menu, $actions[$info]['active']);

	$page->sidebar .= $sidebar->get_markup();

	return $actions;
}

function wiki_myplugins_admin_permissions($admin_permissions)
{
	global $lang;

	$lang->load("wiki");

	$own_admin_permissions = array(
		"index"	=> $lang->wiki_permission_index,
		"article"	=> $lang->wiki_permission_article,
		"permissions"	=> $lang->wiki_permission_permissions,
		"import"	=> $lang->wiki_permission_import,
		"cache"	=> $lang->wiki_permission_cache,
		"update"	=> $lang->wiki_permission_update
	);
	$admin_permissions = array_merge($admin_permissions, $own_admin_permissions);

	return $admin_permissions;
}

function wiki_wol_activity($user_activity)
{
    $split_loc = explode(".php", $user_activity['location']);
    if($split_loc[0] == $user['location']) {
        $filename = '';
    } else {
        $filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
    }
    global $parameters;

    switch ($filename)
    {
		case 'wiki':
            $user_activity['activity'] = "wiki";
			if($mybb->settings['seourls'] == "yes" || ($mybb->settings['seourls'] == "auto" && $_SERVER['SEO_SUPPORT'] == 1)) {
	            if(substr($user_activity['location'], 0, 13) == "wiki-article-")
					$user_activity['wiki']['entry'] = substr($user_activity['location'], 14, -5);
	            if(substr($user_activity['location'], 0, 14) == "wiki-category-")
		            $user_activity['wiki']['cat'] = substr($user_activity['location'], 15, -5);
			} else {
				if(!$parameters['action']) {
		            $user_activity['wiki']['entry'] = (int)$parameters['wid'];
		            $user_activity['wiki']['cat'] = (int)$parameters['cid'];
				}
			}
            if($parameters['action'] == "category_add")
                $user_activity['wiki']['cat_add'] = true;
            elseif($parameters['action'] == "category_edit")
                $user_activity['wiki']['cat_edit'] = (int)$parameters['cid'];
            elseif($parameters['action'] == "category_delete")
                $user_activity['wiki']['cat_delete'] = (int)$parameters['cid'];
            elseif($parameters['action'] == "article_add")
                $user_activity['wiki']['entry_add'] = true;
            elseif($parameters['action'] == "article_edit")
                $user_activity['wiki']['entry_edit'] = (int)$parameters['wid'];
            elseif($parameters['action'] == "article_delete")
                $user_activity['wiki']['entry_delete'] = (int)$parameters['wid'];
            elseif($parameters['action'] == "search")
                $user_activity['wiki']['search'] = true;
			break;
    }
    return $user_activity;
}

function wiki_wol_location($array)
{
	global $lang, $settings, $wiki_link;
    switch ($array['user_activity']['activity'])
    {
        case 'wiki':
//        	echo "<pre>"; var_dump($array['user_activity']['wiki']); echo "</pre>";
        	if($array['user_activity']['wiki']['entry']) {
	        	$id=$array['user_activity']['wiki']['entry'];
				$wiki = wiki_cache_load("articles", $id);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_entry, wiki_get_article($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['cat']) {
	        	$id=$array['user_activity']['wiki']['cat'];
				$wiki = wiki_cache_load("categories", $id);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_cat, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['cat_add'])
				$array['location_name'] = $lang->wiki_wol_cat_add;
			elseif($array['user_activity']['wiki']['cat_edit']) {
	        	$id=$array['user_activity']['wiki']['cat_edit'];
				$wiki = wiki_cache_load("categories", $id);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_cat_edit, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['cat_delete']) {
	        	$id=$array['user_activity']['wiki']['cat_delete'];
				$wiki = wiki_cache_load("categories", $id);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_cat_delete, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['entry_add'])
				$array['location_name'] = $lang->wiki_wol_add;
			elseif($array['user_activity']['wiki']['entry_edit']) {
	        	$id=$array['user_activity']['wiki']['entry_edit'];
				$wiki = wiki_cache_load("articles", $id);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_edit, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['entry_delete']) {
	        	$id=$array['user_activity']['wiki']['entry_delete'];
				$wiki = wiki_cache_load("articles", $id);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_delete, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['search'])
				$array['location_name'] = $lang->sprintf($lang->wiki_wol_search, $wiki_link);
			else
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol, $wiki_link);
            break;
    }
    return $array;
}

function wiki_mycode($message)
{
	global $mybb;
	if($mybb->settings['wiki_mycode']=="1"||$mybb->settings['wiki_mycode']=="both") {
		$message = preg_replace_callback("#\[\[([0-9]*):(.*?)\]\]#si", "wiki_mycode_createID", $message);
		$message = preg_replace_callback("#\[\[([a-zA-Z0-9\s]*):(.*?)\]\]#si", "wiki_mycode_create", $message);
		$message = preg_replace_callback("#\[\[(.*?)\]\]#si", "wiki_mycode_create", $message);
	}
	if($mybb->settings['wiki_mycode']=="2"||$mybb->settings['wiki_mycode']=="both") {
		$message = preg_replace_callback("#\[wiki=([0-9]*)\](.*?)\[/wiki\]#si", "wiki_mycode_createID", $message);
		$message = preg_replace_callback("#\[wiki=([a-zA-Z0-9\s]*)\](.*?)\[/wiki\]#si", "wiki_mycode_create", $message);
		$message = preg_replace_callback("#\[wiki\](.*?)\[/wiki\]#si", "wiki_mycode_create", $message);
	}
	return $message;
}

function wiki_mycode_createID(array $match)
{
	global $settings;
	$id=(int)$match[1]; $name=$match[2];
	$wiki = wiki_cache_load("articles", $id);
	if($wiki['link'])
	    return '<a rel="nofollow" href="'.$wiki['link'].'" target="_blank" title="'.$wiki['short'].'">'.$name.'</a>';
	elseif($wiki['text'])
		return '<a href="'.$settings['bburl'].'/'.wiki_get_article($id).'" title="'.$wiki['short'].'">'.$name.'</a>';
	else
		return $name;
}

function wiki_mycode_create(array $match)
{
	global $settings;
	$name = $match[1];
	$show = $match[2];
	if(!isset($show))
	    $show=$match[1];
	$found = array();
	$articles = wiki_cache_load("articles");
	if($articles) {
		foreach($articles as $article) {
			if($article['title'] == $name)
			    $found[] = $article;
		}
		if(sizeOf($found) == 0)
		    return $name;
		$wiki = $found[0];
		if($wiki['link'])
		    return '<a rel="nofollow" href="'.$wiki['link'].'" target="_blank" title="'.$wiki['short'].'">'.$show.'</a>';
		elseif($wiki['text'])
			return '<a href="'.$settings['bburl'].'/'.wiki_get_article($wiki['id']).'" title="'.$wiki['short'].'">'.$show.'</a>';
	}
	return $show;
}

function wiki_autolink($message)
{
	global $mybb;
	if($mybb->settings['wiki_autolink']=="0")
	    return $message;
	$articles = wiki_cache_load("articles");
	if($articles) {
		foreach($articles as $wiki) {
	    	if($wiki['link'])
			    $link = '<a rel="nofollow" href="'.$wiki['link'].'" target="_blank">'.$wiki['title'].'</a>';
			elseif($wiki['text'])
				$link = '<a href="'.$settings['bburl'].'/'.wiki_get_article($wiki['id']).'">'.$wiki['title'].'</a>';
			else
				$link = $wiki['title'];
			$message=str_replace(" ".$wiki['title']." ", " ".$link." ", $message);
		}
	}
	return $message;
}


function wiki_is_allowed($action)
{
	global $mybb;

	if(isset($mybb->user['wiki_permissions'][$action]))
		return $mybb->user['wiki_permissions'][$action];

	return false;
}

function wiki_init()
{
	global $lang, $wiki_copyright, $mybb, $templates, $wiki_link;
	$lang->load("wiki");
	if($mybb->settings['wiki_copy'])
		eval("\$wiki_copyright = \"".$templates->get("wiki_copy")."\";");

	if($mybb->settings['seourls'] == "yes" || ($mybb->settings['seourls'] == "auto" && $_SERVER['SEO_SUPPORT'] == 1))
	{
		define('WIKI', "wiki.html");
		define('WIKI_CATEGORY', "wiki-category-{cid}.html");
		define('WIKI_ARTICLE', "wiki-article-{wid}.html");
		define('WIKI_VERSIONS', "wiki-article-{wid}-versions.html");
		define('WIKI_VERSION', "wiki-version-{vid}.html");
		define('WIKI_TRASH', "wiki-trash.html");
		define('WIKI_NEW', "wiki-new.html");
	} else {
		define('WIKI', "wiki.php");
		define('WIKI_CATEGORY', "wiki.php?cid={cid}");
		define('WIKI_ARTICLE', "wiki.php?wid={wid}");
		define('WIKI_VERSIONS', "wiki.php?action=versions&wid={wid}");
		define('WIKI_VERSION', "wiki.php?action=show_version&vid={vid}");
		define('WIKI_TRASH', "wiki.php?action=trash");
		define('WIKI_NEW', "wiki.php?action=new");
	}
	$wiki_link = WIKI;


	if($mybb->user['additionalgroups'] != "")
		$groups = explode(",", $mybb->user['additionalgroups']);
	$groups[] = $mybb->user['usergroup'];

	$perms = wiki_cache_load("permissions");

	$permissions = array();

	foreach ($perms as $row) {
		if(!in_array($row['gid'], $groups))
		    continue;
		foreach($row as $key => $value)
		{
			if($key == 'gid')
				continue;

			$value = (bool)$value;

			if($value)
				$permissions[$key] = $value;
		}
	}

	$mybb->user['wiki_permissions'] = $permissions;
}

function wiki_get_category($cid)
{
	$link = str_replace("{cid}", $cid, WIKI_CATEGORY);
	return htmlspecialchars_uni($link);
}

function wiki_get_article($wid)
{
	$link = str_replace("{wid}", $wid, WIKI_ARTICLE);
	return htmlspecialchars_uni($link);
}

function wiki_get_versions($wid)
{
	$link = str_replace("{wid}", $wid, WIKI_VERSIONS);
	return htmlspecialchars_uni($link);
}

function wiki_get_version($vid)
{
	$link = str_replace("{vid}", $vid, WIKI_VERSION);
	return htmlspecialchars_uni($link);
}

function wiki_cache_update($action, $data = false)
{
	global $PL, $db;
    $PL or require_once PLUGINLIBRARY;
	if(!$data) {
		if($action == "articles")
		    $query = $db->simple_select("wiki");
		elseif($action == "categories")
		    $query = $db->simple_select("wiki_cats");
		elseif($action == "versions")
		    $query = $db->simple_select("wiki_versions");
		elseif($action == "trash")
		    $query = $db->simple_select("wiki_trash");
		elseif($action == "permissions") {
		    $query = $db->simple_select("wiki_permissions");
			while($rdata = $db->fetch_array($query))
			    $data[$rdata['gid']] = $rdata;
		} else
			return false;
		if($action != "permissions") {
			while($rdata = $db->fetch_array($query))
			    $data[$rdata['id']] = $rdata;
		}
	}
	if(!is_array($data)) {
		$PL->cache_update("wiki_".$action, array(false));
		return false;
	}

	$PL->cache_update("wiki_".$action, $data);
	return true;
}

function wiki_cache_load($action, $id = false)
{
	global $PL;
    $PL or require_once PLUGINLIBRARY;

	$content = $PL->cache_read("wiki_".$action);
	if(!is_array($content)) {
	    wiki_cache_update($action);
		$content = $PL->cache_read("wiki_".$action);
	}
   	if(sizeOf($content) == 1 && $content[0] === false)
	    return false;
	if(!$id)
		return $content;
	return $content[$id];
}

function wiki_sort_sort($a, $b)
{
	if($a['Sort'] == $b['Sort'])
	    return 1;
	return ($a['Sort'] < $b['Sort']) ? 0 : 2;
}

function wiki_sort_title($a, $b)
{
	return strcoll($a['title'], $b['title']);
}

function wiki_sort_date($a, $b)
{
	if($a['date'] == $b['date'])
	    return 1;
	return ($a['date'] < $b['date']) ? 0 : 2;
}

function wiki_sort_versions_date($a, $b)
{
	$e1 = @unserialize($a['entry']);
	$e2 = @unserialize($b['entry']);

    if($e1['date'] == $e2['date'])
	    return 1;
	return ($e1['date'] < $e2['date']) ? 0 : 2;
}

function wiki_sort_search_date($a, $b)
{
	if($a['type'] == "articles")
	    $d1 = $a['date'];
	elseif($a['type'] == "category")
	    $d1 = 0;
	elseif($a['type'] == "versions" || $a['type'] == "trash")
		$d1 = $a['entry']['date'];

	if($b['type'] == "articles")
	    $d2 = $b['date'];
	elseif($b['type'] == "category")
	    $d2 = 0;
	elseif($b['type'] == "versions" || $b['type'] == "trash")
		$d2 = $b['entry']['date'];

	if($d1 == $d2)
	    return 1;
	return ($d1 > $d2) ? 0 : 2;
}

function wiki_sort_search_title($a, $b)
{
	if($a['type'] == "articles" || $a['type'] == "category")
	    $t1 = $a['title'];
	elseif($a['type'] == "versions" || $a['type'] == "trash")
		$t1 = $a['entry']['title'];

	if($b['type'] == "articles" || $b['type'] == "category")
	    $t2 = $b['title'];
	elseif($b['type'] == "versions" || $b['type'] == "trash")
		$t2 = $b['entry']['title'];

	return strcoll($t1, $t2);
}

function wiki_create_navy($cat, $article = false, $categories = false)
{
	if(!$categories)
	    $categories = wiki_cache_load("categories");
	if($article)
	    $cat = $categories[$cat['cid']];
   	if(!is_array($cat))
	    $cat = $categories[$cat];

	$parent = true;
	$cats = array();
	while($parent)
	{
		$parent = false;
		$cats[] = $cat;
		if($cat['pid'] != -1) {
		    $cat = $categories[$cat['pid']];
		    $parent = true;
		}
	}
	$cats = array_reverse($cats);

	foreach($cats as $cat)
	{
		add_breadcrumb($cat['title'], wiki_get_category($cat['id']));
	}
}

/*
Paul's Simple Diff Algorithm v 0.1
(C) Paul Butler 2007 <http://www.paulbutler.org/>
May be used and distributed under the zlib/libpng license. */

function diff($old, $new){
	foreach($old as $oindex => $ovalue){
		$nkeys = array_keys($new, $ovalue);
			foreach($nkeys as $nindex){
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
			$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen){
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}


function getsort($url, $name="sort")
{
	global $mybb, $lang, $templates, $theme;

	if(!$mybb->settings['wiki_own_sort'])
	    return;

	if(!$mybb->input[$name])
		$mybb->input[$name] = "normal";

	$selected[$mybb->input[$name]] = " selected=selected";

	eval("\$sort = \"".$templates->get("wiki_sortoptions")."\";");
	return $sort;
}

function createHeader($user, $wiki, $showbuttons=true)
{
	global $mybb, $lang, $templates, $groupscache, $theme, $allowed, $allowedmod, $PL, $settings;
	$date=$lang->sprintf($lang->wiki_written, date($mybb->settings['dateformat'], $wiki['date']), date($mybb->settings['timeformat'], $wiki['date']));
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
	if(!empty($usergroup['image']))
	{
		if(!empty($mybb->user['language']))
		{
			$language = $mybb->user['language'];
		}
		else
		{
			$language = $mybb->settings['bblanguage'];
		}
		$usergroup['image'] = str_replace("{lang}", $language, $usergroup['image']);
		$usergroup['image'] = str_replace("{theme}", $theme['imgdir'], $usergroup['image']);
		eval("\$user['groupimage'] = \"".$templates->get("postbit_groupimage")."\";");
	}
	$post['profilelink_plain'] = get_profile_link($user['uid']);
	$user['username_formatted'] = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
	$user['profilelink'] = build_profile_link($user['username_formatted'], $user['uid']);

	if(trim($user['usertitle']) != "")
	{
		$hascustomtitle = 1;
	}

	if($usergroup['usertitle'] != "" && !$hascustomtitle)
	{
		$user['usertitle'] = $usergroup['usertitle'];
	}
	elseif(is_array($titlescache) && !$usergroup['usertitle'])
	{
		reset($titlescache);
		foreach($titlescache as $key => $titleinfo)
		{
			if($user['postnum'] >= $key)
			{
				if(!$hascustomtitle)
				{
					$user['usertitle'] = $titleinfo['title'];
				}
				$user['stars'] = $titleinfo['stars'];
				$user['starimage'] = $titleinfo['starimage'];
				break;
			}
		}
	}

	if($usergroup['stars'])
	{
		$user['stars'] = $usergroup['stars'];
	}

	if(!$user['starimage'])
	{
		$user['starimage'] = $usergroup['starimage'];
	}

	if($user['starimage'] && $user['stars'])
	{
		// Only display stars if we have an image to use...
		$user['starimage'] = str_replace("{theme}", $theme['imgdir'], $user['starimage']);

		for($i = 0; $i < $user['stars']; ++$i)
		{
			$user['userstars'] .= "<img src=\"".$user['starimage']."\" border=\"0\" alt=\"*\" />";
		}

		$user['userstars'] .= "<br />";
	}
	if($user['avatar'] != "" && ($mybb->user['showavatars'] != 0 || !$mybb->user['uid']))
	{
		$user['avatar'] = htmlspecialchars_uni($user['avatar']);
		$avatar_dimensions = explode("|", $user['avatardimensions']);

		if($avatar_dimensions[0] && $avatar_dimensions[1])
		{
			list($max_width, $max_height) = explode("x", my_strtolower($mybb->settings['postmaxavatarsize']));
		 	if($avatar_dimensions[0] > $max_width || $avatar_dimensions[1] > $max_height)
			{
				require_once MYBB_ROOT."inc/functions_image.php";
				$scaled_dimensions = scale_image($avatar_dimensions[0], $avatar_dimensions[1], $max_width, $max_height);
				$avatar_width_height = "width=\"{$scaled_dimensions['width']}\" height=\"{$scaled_dimensions['height']}\"";
			}
			else
			{
				$avatar_width_height = "width=\"{$avatar_dimensions[0]}\" height=\"{$avatar_dimensions[1]}\"";
			}
		}
		$post['avatar']=$user['avatar'];
		eval("\$user['useravatar'] = \"".$templates->get("postbit_avatar")."\";");
		$user['avatar_padding'] = "padding-right: 10px;";
	}
	else
	{
		$user['useravatar'] = "";
	}
	// Determine the status to show for the user (Online/Offline/Away)
	$timecut = TIME_NOW - $mybb->settings['wolcutoff'];
	if($user['lastactive'] > $timecut && ($user['invisible'] != 1 || $mybb->usergroup['canviewwolinvis'] == 1) && $user['lastvisit'] != $user['lastactive'])
	{
		eval("\$user['onlinestatus'] = \"".$templates->get("postbit_online")."\";");
	}
	else
	{
		if($user['away'] == 1 && $mybb->settings['allowaway'] != 0)
		{
			eval("\$user['onlinestatus'] = \"".$templates->get("postbit_away")."\";");
		}
		else
		{
			eval("\$user['onlinestatus'] = \"".$templates->get("postbit_offline")."\";");
		}
	}
	if($showbuttons && wiki_is_allowed("can_edit")) {
		if(!$wiki['is_closed'] || wiki_is_allowed("can_edit_closed") || $wiki['uid']==$mybb->user['uid'])
			eval("\$user['edit'] = \"".$templates->get("wiki_header_edit")."\";");
	}
	eval("\$user_header = \"".$templates->get("wiki_header")."\";");
	return $user_header;
}

function wiki_update($installed, $uploaded)
{
	global $PL, $db;
	$up = false;

	$PL->cache_update("wiki_version", $uploaded);
	$PL->cache_update("wiki_pl_version", "8");
	if(version_compare($installed, "1.1 Beta 1 Dev 10", "<")) {
		$db->add_column('wiki', 'Sort', "int NOT NULL default '0'");
		$db->add_column('wiki_cats', 'Sort', "int NOT NULL default '0'");
	}
	if(version_compare($installed, "1.2 Beta 1 Dev 1", "<")) {
		$db->query('CREATE TABLE '.TABLE_PREFIX.'wiki_permissions (
			gid int(11) NOT NULL PRIMARY KEY,
			can_view boolean NOT NULL DEFAULT 1,
			can_create boolean NOT NULL DEFAULT 1,
			can_edit boolean NOT NULL DEFAULT 0,
			can_search boolean NOT NULL DEFAULT 1,
			can_version_view boolean NOT NULL DEFAULT 0,
			can_version_restore boolean NOT NULL DEFAULT 0,
			can_version_delete boolean NOT NULL DEFAULT 0,
			can_trash_view boolean NOT NULL DEFAULT 0,
			can_trash_restore boolean NOT NULL DEFAULT 0,
			can_trash_delete boolean NOT NULL DEFAULT 0,
			can_edit_closed boolean NOT NULL DEFAULT 0,
			can_view_hidden boolean NOT NULL DEFAULT 0,
			can_edit_sort boolean NOT NULL DEFAULT 0
		)');

		$db->query('INSERT INTO '.TABLE_PREFIX.'wiki_permissions
				(gid, can_view, can_create, can_edit, can_search, can_version_view, can_version_restore, can_version_delete, can_trash_view, can_trash_restore, can_trash_delete, can_edit_closed, can_view_hidden, can_edit_sort)
			VALUES
				(1, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
				(2, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0),
				(3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				(4, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				(5, 1, 1, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
				(6, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
				(7, 1, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0)
			');
	}
	if(version_compare($installed, "1.2 Beta 1 Dev 2", "<")) {
	    $db->add_column('wiki_permissions', 'can_unlock', "boolean NOT NULL DEFAULT '0'");
		$db->update_query("wiki_permissions", array("can_unlock" => 1), "gid='3' OR gid='4' OR gid='6'");
	}
	if(version_compare($installed, "1.2 Beta 2 Dev 1", "<")) {
	    $db->add_column('wiki_versions', 'tid', "int(11) NOT NULL DEFAULT '-1' AFTER wid");
	    $db->modify_column('wiki_versions', 'wid', "int(11) NOT NULL DEFAULT '-1'");
	}
	if(version_compare($installed, "1.2 Beta 3 Dev 1", "<")) {
	    $db->add_column('wiki_cats', 'pid', "int(11) NOT NULL DEFAULT '-1' AFTER id");
	}
	if(version_compare($installed, "1.2 Beta 3 Dev 3", "<")) {
	    $db->add_column('wiki_permissions', 'can_version_diff', "boolean NOT NULL DEFAULT '0' AFTER can_version_delete");
		$db->update_query("wiki_permissions", array("can_version_diff" => 1), "gid='2' OR gid='3' OR gid='4' OR gid='6'");
	}

	if($up)
	    wiki_cache_update("permissions");
}

function wiki_settings($install=false)
{
    global $PL;
	$PL->settings("wiki",
	  	"Wiki",
	  	"Settings for the \"Wiki\" Plugin",
	  	array(
	      	"moderate_new" => array(
	          	"title" => "Moderate new entrys?",
	          	"description" => "",
		        "optionscode" => "yesno",
		        "value" => "no",
	          ),
	      	"own_sort" => array(
	          	"title" => "Allow own order?",
	          	"description" => "Otherwise just the order you saved is available",
		        "optionscode" => "yesno",
		        "value" => "yes",
	          ),
	      	"stype" => array(
	          	"title" => "Search Type",
	          	"description" => "Which search type should be standard in your forum?",
		        "optionscode" => "select
full=Fulltext
title=Title",
		        "value" => "full",
	          ),
	      	"mycode" => array(
	          	"title" => "MyCode",
	          	"description" => "Which MyCode want you use?",
		        "optionscode" => "select
none=None
1=[[]]
2=[wiki][/wiki]
both=Both",
		        "value" => "both",
	          ),
	      	"autolink" => array(
	          	"title" => "Link Article automatic",
	          	"description" => "Link automatical Articles in Posts? Attention: If you have more than one article with the same name or an article with a name like \"and\" this function can make mistakes",
		        "optionscode" => "yesno",
		        "value" => "no",
	          ),
	      	"copy" => array(
	          	"title" => "Show Copyright?",
	          	"description" => "It isn't necessary but it would be nice if you display it",
		        "optionscode" => "yesno",
		        "value" => "no",
	          ),
		)
    );
}

function wiki_templates($install=false)
{
    global $PL;
    $PL->templates("wiki",
                   "Wiki",
                   array(
    				/* Hauptseite (Kategorien & Mülleimer) */
                       "" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki}</title>
	{\$headerinclude}
	<style type=\"text/css\">
	.wiki_panel {
		background: #efefef;
		color: #000000;
		font-size: 11px;
		border: 1px solid #D4D4D4;
		padding: 8px;
	}
	</style>
</head>
<body>
{\$header}
{\$wiki_header}
{\$wiki_category}
{\$wiki_trash}
{\$footer}
</body>
</html>",
				/* Zusätzliche Spalten für Moderatoren */
					   "sort" => "
		<td class=\"tcat\" width=\"5%\" >
			<span class=\"smalltext\"><strong>{\$lang->order}</strong></span>
		</td>",
						"control" => "
		<td class=\"tcat\" width=\"5%\" colspan=\"2\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_control}</strong></span>
		</td>",
				/* Kategorie Tabelle */
						"table" => "
<div style=\"float: right;\">{\$multipage}</div>
<form action=\"wiki.php\" method=\"post\">
<input type=\"hidden\" name=\"action\" value=\"do_save_order\" />
<input type=\"hidden\" name=\"order\" value=\"category\" />
<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />

<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"5\"><strong>{\$lang->wiki}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\" width=\"45%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_category}</strong></span>
		</td>
		<td class=\"tcat\" width=\"45%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_number}</strong></span>
		</td>
		{\$additional['sort']}
		{\$additional['control']}
	</tr>
	{\$wiki_table}
</table>
<br />
<div style=\"float: right;\">{\$multipage}</div>
{\$submit}
</form>
<div style=\"float: left; margin-top: -20px;\">{\$sort}</div>
<br />",
				/* Elemente für Kategorie Hauptseite */
                       "table_element" => "
<tr>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$category_title}</strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$category_number}</strong></span>
	</td>
	{\$additional['sort']}
	{\$additional['control']}
</tr>",
				/* Zusätzliche Elemente für Moderatoren */
					   "table_sort" => "
	<td class=\"trow1\">
		<span class=\"smalltext\"><input type=\"text\" name=\"disporder[{\$cid}]\" value=\"{\$category_sort}\" class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" /></span>
	</td>",
						"table_control" => "
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=category_edit&cid={\$cid}\"><img src=\"{\$settings['bburl']}/images/wiki_edit.gif\" alt=\"{\$lang->wiki_edit}\" title=\"{\$lang->wiki_edit}\" /></a></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=category_delete&cid={\$cid}\"><img src=\"{\$settings['bburl']}/images/wiki_delete.gif\" alt=\"{\$lang->wiki_delete}\" title=\"{\$lang->wiki_delete}\" /></a></span>
	</td>",
				/* Kategorie, Auflistung der Artikel */
                       "category" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki} - {\$category['title']}</title>
	{\$headerinclude}
	<style type=\"text/css\">
	.wiki_panel {
		background: #efefef;
		color: #000000;
		font-size: 11px;
		border: 1px solid #D4D4D4;
		padding: 8px;
	}
	</style>
</head>
<body>
{\$header}
{\$wiki_header}
{\$wiki_category}
<div style=\"float: right;\">{\$amultipage}</div>
<form action=\"wiki.php\" method=\"post\">
<input type=\"hidden\" name=\"action\" value=\"do_save_order\" />
<input type=\"hidden\" name=\"order\" value=\"article\" />
<input type=\"hidden\" name=\"cat\" value=\"{\$category['id']}\" />
<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />

<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"5\"><strong>{\$lang->wiki}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\" width=\"45%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_title}</strong></span>
		</td>
		<td class=\"tcat\" width=\"45%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_short}</strong></span>
		</td>
		{\$additional['sort']}
		{\$additional['control']}
	</tr>
	{\$wiki_table}
</table>
<br />
<div style=\"float: right;\">{\$amultipage}</div>
{\$submit}
</form>
<div style=\"float: left; margin-top: -20px;\">{\$asort}</div>
{\$footer}
</body>
</html>",
				/* Zusätzliche Spalten für Moderatoren */
					   "category_sort" => "
		<td class=\"tcat\" width=\"5%\" >
			<span class=\"smalltext\"><strong>{\$lang->order}</strong></span>
		</td>",
						"category_control" => "
		<td class=\"tcat\" width=\"5%\" colspan=\"2\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_control}</strong></span>
		</td>",
				/* Elemente für Artikelauflistung, Kategore */
                       "category_table" => "
<tr>
	<td style=\"background: {\$background};\" class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$wiki_title}</strong></span>
	</td>
	<td style=\"background: {\$background};\" class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$wiki_short}</strong></span>
	</td>
	{\$additional['sort']}
	{\$additional['control']}
</tr>",
				/* Zusätzliche Elemente für Moderatoren */
					   "category_table_sort" => "
	<td style=\"background: {\$background};\" class=\"trow1\">
		<span class=\"smalltext\"><input type=\"text\" name=\"disporder[{\$wiki['id']}]\" value=\"{\$wiki['Sort']}\" class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" /></span>
	</td>",
						"category_table_control" => "
	<td style=\"background: {\$background};\" class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_edit&wid={\$wiki['id']}\"><img src=\"{\$settings['bburl']}/images/wiki_edit.gif\" alt=\"{\$lang->wiki_edit}\" title=\"{\$lang->wiki_edit}\" /></a></span>
	</td>
	<td style=\"background: {\$background};\" class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_delete&wid={\$wiki['id']}\"><img src=\"{\$settings['bburl']}/images/wiki_delete.gif\" alt=\"{\$lang->wiki_delete}\" title=\"{\$lang->wiki_delete}\" /></a></span>
	</td>",
				/* Sortierungsoptionen */
						"sortoptions" => "
<form action=\"{\$url}\" method=\"post\">
<select name=\"{\$name}\">
	<option value=\"normal\"{\$selected['normal']}>{\$lang->wiki_sort_normal}</option>
	<option value=\"title\"{\$selected['title']}>{\$lang->wiki_sort_title}</option>
	<option value=\"date\"{\$selected['date']}>{\$lang->wiki_sort_date}</option>
</select>
<input type=\"submit\" value=\"{\$lang->wiki_sort}\" />
</form>",
				/* Anzeige eines Artikels */
                       "text" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki} - {\$wiki_title}</title>
	{\$headerinclude}
	<style type=\"text/css\">
	.wiki_panel {
		background: #efefef;
		color: #000000;
		font-size: 11px;
		border: 1px solid #D4D4D4;
		padding: 8px;
	}
	</style>
</head>
<body>
{\$header}
{\$wiki_header}
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" align=\"center\"><strong>{\$wiki_title}</strong></td>
	</tr>
	{\$user_header}
	<tr>
		<td class=\"trow1\">{\$wiki_text}</td>
	</tr>
</table>
{\$footer}
</body>
</html>",
				/* Anzeige der User Informationen */
                       "header" => "
		<tr class=\"tcat\">
			<td>{\$date}</td>
		</tr>
		<tr>
			<td class=\"trow1\">
				<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"width: 100%;\">
					<tr>
						<td class=\"post_avatar\" width=\"1\" style=\"{\$user['avatar_padding']}\">
							{\$user['useravatar']}
						</td>
						<td class=\"post_author\">
							<strong><span class=\"largetext\">{\$user['profilelink']}</span></strong> {\$user['onlinestatus']}<br />
							<span class=\"smalltext\">
								{\$user['usertitle']}<br />
								{\$user['userstars']}
								{\$user['groupimage']}
							</span>
						</td>
						{\$user['edit']}
					</tr>
					{\$wiki['hidden']}
				</table>
			</td>
		</tr>",
				/* Bearbeiten/Löschen Link für User Infos */
                       "header_edit" => "
<td class=\"smalltext post_author_info\" width=\"165\">
	<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_edit&wid={\$wiki['id']}\"><img src=\"{\$settings['bburl']}/images/wiki_edit.gif\" alt=\"{\$lang->wiki_edit}\" title=\"{\$lang->wiki_edit}\" />{\$lang->wiki_edit}</a><br />
	<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_delete&wid={\$wiki['id']}\"><img src=\"{\$settings['bburl']}/images/wiki_delete.gif\" alt=\"{\$lang->wiki_delete}\" title=\"{\$lang->wiki_delete}\" />{\$lang->wiki_delete}</a>
</td>",
				/* Hinweis auf verborgen für User Infos */
                       "header_hidden" => "
<tr>
	<td colspan=\"3\">{\$lang->wiki_hidden}</td>
</tr>",
				/* Suchmaske */
					   "search" => "
<form action=\"wiki.php\" method=\"post\">
<input type=\"hidden\" name=\"action\" value=\"search\" />
<input type=\"hidden\" name=\"short\" value=\"true\" />
<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
<input type=\"text\" name=\"searchString\" value=\"{\$searchString}\" class=\"text_input\" />
<input type=\"submit\" value=\"{\$lang->search}\" />
</form>",
				/* Anzeige von Suchergebnissen */
                       "search_results" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki} - {\$lang->search}</title>
	{\$headerinclude}
</head>
<body>
{\$header}
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" align=\"center\" colspan=\"3\"><strong>{\$lang->wiki} - {\$lang->search}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\" align=\"center\"><strong>{\$lang->wiki_title}</strong></td>
		<td class=\"tcat\" align=\"center\"><strong>{\$lang->wiki_category}</strong></td>
		<td class=\"tcat\" align=\"center\"><strong>{\$lang->search_other}</strong></td>
	</tr>
	{\$searchResults}
</table>
<br />
<form action=\"wiki.php\" method=\"post\">
<input type=\"hidden\" name=\"action\" value=\"search\" />
<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" align=\"center\" colspan=\"2\"><strong>{\$lang->search_options}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\">{\$lang->search_string}</td>
		<td class=\"tcat\">{\$lang->search_type}</td>
	</tr>
	<tr>
		<td class=\"trow1\"><input type=\"text\" name=\"searchString\" value=\"{\$searchString}\" class=\"text_input\" /></td>
		<td class=\"trow1\">
			<input type=\"radio\" name=\"type\" value=\"full\" {\$full_checked}/>{\$lang->search_full}
			<input type=\"radio\" name=\"type\" value=\"title\" {\$title_checked}/>{\$lang->search_title}
		</td>
	</tr>
	<tr>
		<td class=\"tcat\">{\$lang->search_where}</td>
		<td class=\"tcat\">{\$lang->search_cats}</td>
	</tr>
	<tr>
		<td class=\"trow1\">
			<select name=\"where[]\" multiple=\"multiple\">
				{\$wopt['articles']}
				{\$wopt['category']}
				{\$wopt['versions']}
				{\$wopt['trash']}
			</select>
		</td>
		<td class=\"trow1\"><select name=\"cats[]\" multiple=\"multiple\">{\$cats}</select></td>
	</tr>
	<tr>
		<td class=\"tcat\">{\$lang->search_date}</td>
		<td class=\"tcat\">{\$lang->search_sort}</td>
	</tr>
	<tr>
		<td class=\"trow1\">
			<input type=\"radio\" name=\"date\" value=\"all\" {\$all_checked}/>{\$lang->search_date_all}<br />
			<input type=\"radio\" name=\"date\" value=\"day\" {\$day_checked}/>{\$lang->search_date_day}<br />
			<input type=\"radio\" name=\"date\" value=\"week\" {\$week_checked}/>{\$lang->search_date_week}<br />
			<input type=\"radio\" name=\"date\" value=\"month\" {\$month_checked}/>{\$lang->search_date_month}<br />
			<input type=\"radio\" name=\"date\" value=\"other\" {\$other_checked}/>{\$lang->search_date_other}<br />
			<select name=\"day1\">
				<option value=\"\">&nbsp;</option>
				{\$days1}
			</select>.
			<select name=\"month1\">
				<option value=\"\">&nbsp;</option>
				<option value=\"1\" {\$month1['1']}>{\$lang->month_1}</option>
				<option value=\"2\" {\$month1['2']}>{\$lang->month_2}</option>
				<option value=\"3\" {\$month1['3']}>{\$lang->month_3}</option>
				<option value=\"4\" {\$month1['4']}>{\$lang->month_4}</option>
				<option value=\"5\" {\$month1['5']}>{\$lang->month_5}</option>
				<option value=\"6\" {\$month1['6']}>{\$lang->month_6}</option>
				<option value=\"7\" {\$month1['7']}>{\$lang->month_7}</option>
				<option value=\"8\" {\$month1['8']}>{\$lang->month_8}</option>
				<option value=\"9\" {\$month1['9']}>{\$lang->month_9}</option>
				<option value=\"10\" {\$month1['10']}>{\$lang->month_10}</option>
				<option value=\"11\" {\$month1['11']}>{\$lang->month_11}</option>
				<option value=\"12\" {\$month1['12']}>{\$lang->month_12}</option>
			</select>.
			<input type=\"text\" class=\"textbox\" size=\"4\" maxlength=\"4\" name=\"year1\" value=\"{\$year1}\" />
			-
			<select name=\"day2\">
				<option value=\"\">&nbsp;</option>
				{\$days2}
			</select>.
			<select name=\"month2\">
				<option value=\"\">&nbsp;</option>
				<option value=\"1\" {\$month2['1']}>{\$lang->month_1}</option>
				<option value=\"2\" {\$month2['2']}>{\$lang->month_2}</option>
				<option value=\"3\" {\$month2['3']}>{\$lang->month_3}</option>
				<option value=\"4\" {\$month2['4']}>{\$lang->month_4}</option>
				<option value=\"5\" {\$month2['5']}>{\$lang->month_5}</option>
				<option value=\"6\" {\$month2['6']}>{\$lang->month_6}</option>
				<option value=\"7\" {\$month2['7']}>{\$lang->month_7}</option>
				<option value=\"8\" {\$month2['8']}>{\$lang->month_8}</option>
				<option value=\"9\" {\$month2['9']}>{\$lang->month_9}</option>
				<option value=\"10\" {\$month2['10']}>{\$lang->month_10}</option>
				<option value=\"11\" {\$month2['11']}>{\$lang->month_11}</option>
				<option value=\"12\" {\$month2['12']}>{\$lang->month_12}</option>
			</select>.
			<input type=\"text\" class=\"textbox\" size=\"4\" maxlength=\"4\" name=\"year2\" value=\"{\$year2}\" />
		</td>
		<td class=\"trow1\">
			<input type=\"radio\" name=\"sort\" value=\"date\" {\$date_checked}/>{\$lang->wiki_date}<br />
			<input type=\"radio\" name=\"sort\" value=\"title\" {\$stitle_checked}/>{\$lang->wiki_title}
			<br /><br />
			<input type=\"radio\" name=\"dir\" value=\"asc\" {\$asc_checked}/>{\$lang->search_asc}<br />
			<input type=\"radio\" name=\"dir\" value=\"desc\" {\$desc_checked}/>{\$lang->search_desc}
		</td>
	</tr>
</table>
<center><input type=\"submit\" value=\"{\$lang->search}\" /></center>
</form>
{\$footer}
</body>
</html>",
				/* Keine Suchergebnisse */
                       "search_results_no" => "
    <tr>
		<td colspan=\"3\"><center>{\$lang->search_no_result}</center></td>
	</tr>",
				/* Suchergebnisse */
                       "search_results_table" => "
	<tr>
		<td class=\"trow1\">{\$rtitle}</td>
		<td class=\"trow1\">{\$rcategory}</td>
		<td class=\"trow1\">{\$rother}</td>
	</tr>",
				/* Panel für Hauptseite */
                       "panel" => "
<div class=\"wiki_panel\">
{\$category_add}
<a style=\"float: right;\" href=\"{\$mybb->settings['bburl']}/{\$wiki_new}\" title=\"{\$lang->wiki_nav_new}\">{\$lang->wiki_nav_new}</a>
<span style=\"float: right; padding-right: 5px;\">{\$search}</span>
<br />
</div>
<br />",
				/* Panel für Artikelauflistung */
                       "panel_category" => "
<div class=\"wiki_panel\">
{\$category_add}
{\$article_add}
{\$article_edit}
{\$article_delete}
<br />
</div>
<br />",
				/* Panel für Artikelanzeige */
                       "panel_text" => "
<div class=\"wiki_panel\">
{\$article_add}
{\$article_edit}
{\$article_delete}
{\$versions}
{\$unlock}
<br />
</div>
<br />",
				/* Panel für Versionen (falls vorhanden) */
                       "panel_versions" => "
| <a href=\"{\$mybb->settings['bburl']}/{\$wiki_versions}\" title=\"{\$lang->wiki_versions}\">{\$lang->wiki_versions} ({\$vnumber})</a>",
				/* Panel für Freischaltung von Artikeln */
                       "panel_unlock" => "
| <a href=\"{\$mybb->settings['bburl']}/wiki.php?action=unlock&wid={\$id}\" title=\"{\$lang->wiki_unlock}\">{\$lang->wiki_unlock}</a>",
				/* Hinzufügen von Artikeln */
                       "add" => "
<html>
<head>
	<title>{\$mybb->settings['bbname']} - {\$lang->wiki_add}</title>
	{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			<td valign=\"top\">
				{\$errors}
				<form action=\"wiki.php\" method=\"post\">
				<input type=\"hidden\" name=\"action\" value=\"do_article_add\" />
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />

				<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
					<tr>
						<td class=\"thead\" align=\"center\" colspan=\"2\">
							<strong>{\$lang->wiki_add}</strong>
						</td>
					</tr>


					<tr>
						<td class=\"trow1\"><strong>{\$lang->wiki_title}:</strong></td>
						<td class=\"trow1\"><input type=\"text\" class=\"textbox\" name=\"wiki_title\" value=\"{\$title}\" /></td>
					</tr>
					<tr>
						<td class=\"trow2\"><strong>{\$lang->wiki_category}:</strong></td>
						<td class=\"trow2\"><select name=\"wiki_cat\">{\$wiki_cats}</select></td>
					</tr>
					<tr>
						<td class=\"trow1\"><strong>{\$lang->wiki_link}:</strong></td>
						<td class=\"trow1\"><input type=\"text\" class=\"textbox\" name=\"wiki_link\" value=\"{\$link}\" /></td>
					</tr>
					<tr>
						<td class=\"trow2\"><strong>{\$lang->wiki_short}:</strong></td>
						<td class=\"trow2\"><input type=\"text\" class=\"textbox\" name=\"wiki_short\" value=\"{\$short}\" /></td>
					</tr>
					<tr>
						<td class=\"trow1\"><strong>{\$lang->wiki_text}:</strong></td>
						<td class=\"trow1\"><textarea name=\"wiki_text\" id=\"message\" rows=\"20\" cols=\"70\">{\$message}</textarea>{\$codebuttons}</td>
					</tr>
					<tr>
						<td class=\"trow2\"><strong>{\$lang->wiki_options}:</strong></td>
						<td class=\"trow2\"><input type=\"checkbox\" name=\"hide\" class=\"ckeckbox\" {\$hide}/>{\$lang->wiki_hide}<br />
						<input type=\"checkbox\" name=\"close\" class=\"ckeckbox\" {\$close}/>{\$lang->wiki_close}</td>
					</tr>
					<tr>
						<td class=\"trow1\"></td>
						<td class=\"trow1\"><input type=\"submit\" value=\"{\$lang->wiki_add}\" /></td>
					</tr>
					</form>
				</table>
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>",
				/* Bearbeiten von Artikeln */
                       "edit" => "
<html>
<head>
	<title>{\$mybb->settings['bbname']} - {\$lang->wiki_edit}</title>
	{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			<td valign=\"top\">
				{\$errors}
				<form action=\"wiki.php\" method=\"post\">
				<input type=\"hidden\" name=\"action\" value=\"do_article_edit\" />
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
				<input type=\"hidden\" name=\"wid\" value=\"{\$wid}\" />

				<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
					<tr>
						<td class=\"thead\" align=\"center\" colspan=\"2\">
							<strong>{\$lang->wiki_edit}</strong>
						</td>
					</tr>


					<tr>
						<td class=\"trow1\"><strong>{\$lang->wiki_title}:</strong></td>
						<td class=\"trow1\"><input type=\"text\" class=\"textbox\" name=\"wiki_title\" value=\"{\$wiki['title']}\" /></td>
					</tr>
					<tr>
						<td class=\"trow2\"><strong>{\$lang->wiki_category}:</strong></td>
						<td class=\"trow2\"><select name=\"wiki_cat\">{\$wiki_cats}</select></td>
					</tr>
					<tr>
						<td class=\"trow1\"><strong>{\$lang->wiki_link}:</strong></td>
						<td class=\"trow1\"><input type=\"text\" class=\"textbox\" name=\"wiki_link\" value=\"{\$wiki['link']}\" /></td>
					</tr>
					<tr>
						<td class=\"trow2\"><strong>{\$lang->wiki_short}:</strong></td>
						<td class=\"trow2\"><input type=\"text\" class=\"textbox\" name=\"wiki_short\" value=\"{\$wiki['short']}\" /></td>
					</tr>
					<tr>
						<td class=\"trow1\"><strong>{\$lang->wiki_text}:</strong></td>
						<td class=\"trow1\"><textarea name=\"wiki_text\" id=\"message\" rows=\"20\" cols=\"70\">{\$wiki['text']}</textarea>{\$codebuttons}</td>
					</tr>
					<tr>
						<td class=\"trow2\"><strong>{\$lang->wiki_options}:</strong></td>
						<td class=\"trow2\"><input type=\"checkbox\" name=\"hide\" class=\"ckeckbox\" {\$hidden_checked}/>{\$lang->wiki_hide}<br />
						<input type=\"checkbox\" name=\"close\" class=\"ckeckbox\" {\$closed_checked}/>{\$lang->wiki_close}</td>
					</tr>
					<tr>
						<td class=\"trow1\"></td>
						<td class=\"trow1\"><input type=\"submit\" value=\"{\$lang->wiki_edit}\" /></td>
					</tr>
					</form>
				</table>
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>",
				/* Löschen von Artikeln */
                       "delete" => "
<html>
<head>
	<title>{\$mybb->settings['bbname']} - {\$lang->wiki_delete}</title>
	{\$headerinclude}
</head>
<body>
	{\$header}
	<form action=\"wiki.php\" method=\"post\" enctype=\"multipart/form-data\">
	<input type=\"hidden\" name=\"action\" value=\"do_article_delete\" />
	<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
	<input type=\"hidden\" name=\"wid\" value=\"{\$wid}\" />
		<table width=\"100%\" border=\"0\" align=\"center\">
			<tr>
				<td valign=\"top\">
					<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
						<tr>
							<td class=\"thead\" colspan=\"2\">
								<strong>{\$wiki['title']} - {\$lang->wiki_delete}</strong>
							</td>
						</tr>
						<tr>
							<td class=\"trow1\" colspan=\"2\" align=\"center\">
								{\$lang->confirm_delete_wiki}
							</td>
						</tr>
					</table>
					<br />
					<div align=\"center\">
						<input type=\"submit\" class=\"button\" name=\"submit\" value=\"{\$lang->wiki_delete}\" />
					</div>
					</form>
				</td>
			</tr>
		</table>
	</form>
	{\$footer}
</body>
</html>",
				/* Hinzufügen von Kategorien */
                       "category_add" => "
<html>
<head>
	<title>{\$mybb->settings['bbname']} - {\$lang->wiki_category_add}</title>
	{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			<td valign=\"top\">
				{\$errors}
				<form action=\"wiki.php\" method=\"post\">
				<input type=\"hidden\" name=\"action\" value=\"do_category_add\" />
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />

				<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
					<tr>
						<td class=\"thead\" align=\"center\" colspan=\"2\">
							<strong>{\$lang->wiki_category_add}</strong>
						</td>
					</tr>


					<tr>
						<td class=\"trow1\"><strong>{\$lang->wiki_name}:</strong></td>
						<td class=\"trow1\"><input type=\"text\" class=\"textbox\" name=\"wiki_name\" /></td>
					</tr>
					<tr>
						<td class=\"trow2\"><strong>{\$lang->wiki_category}:</strong></td>
						<td class=\"trow2\"><select name=\"wiki_cat\">{\$wiki_cats}</select></td>
					</tr>
					<tr>
						<td class=\"trow2\"></td>
						<td class=\"trow2\"><input type=\"submit\" value=\"{\$lang->wiki_category_add}\" /></td>
					</tr>
					</form>
				</table>
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>",
				/* Bearbeiten von Kategorien */
                       "category_edit" => "
<html>
<head>
	<title>{\$mybb->settings['bbname']} - {\$lang->wiki_category_edit}</title>
	{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			<td valign=\"top\">
				{\$errors}
				<form action=\"wiki.php\" method=\"post\">
				<input type=\"hidden\" name=\"action\" value=\"do_category_edit\" />
				<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
				<input type=\"hidden\" name=\"cid\" value=\"{\$cid}\" />

				<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
					<tr>
						<td class=\"thead\" align=\"center\" colspan=\"2\">
							<strong>{\$lang->wiki_category_edit}</strong>
						</td>
					</tr>


					<tr>
						<td class=\"trow1\"><strong>{\$lang->wiki_name}:</strong></td>
						<td class=\"trow1\"><input type=\"text\" class=\"textbox\" name=\"wiki_name\" value=\"{\$wiki['title']}\" /></td>
					</tr>
					<tr>
						<td class=\"trow2\"><strong>{\$lang->wiki_category}:</strong></td>
						<td class=\"trow2\"><select name=\"wiki_cat\">{\$wiki_cats}</select></td>
					</tr>
					<tr>
						<td class=\"trow2\"></td>
						<td class=\"trow2\"><input type=\"submit\" value=\"{\$lang->wiki_category_edit}\" /></td>
					</tr>
					</form>
				</table>
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>",
				/* Löschen von Beiträgen */
                       "category_delete" => "
<html>
<head>
	<title>{\$mybb->settings['bbname']} - {\$lang->wiki_category_delete}</title>
	{\$headerinclude}
</head>
<body>
	{\$header}
	<form action=\"wiki.php\" method=\"post\" enctype=\"multipart/form-data\">
	<input type=\"hidden\" name=\"action\" value=\"do_category_delete\" />
	<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
	<input type=\"hidden\" name=\"cid\" value=\"{\$cid}\" />
		<table width=\"100%\" border=\"0\" align=\"center\">
			<tr>
				<td valign=\"top\">
					<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
						<tr>
							<td class=\"thead\" colspan=\"2\">
								<strong>{\$wiki['title']} - {\$lang->wiki_category_delete}</strong>
							</td>
						</tr>
						<tr>
							<td class=\"trow1\" colspan=\"2\" align=\"center\">
								{\$lang->confirm_delete_category}
							</td>
						</tr>
					</table>
					<br />
					<div align=\"center\">
						<input type=\"submit\" class=\"button\" name=\"submit\" value=\"{\$lang->wiki_category_delete}\" />
					</div>
					</form>
				</td>
			</tr>
		</table>
	</form>
	{\$footer}
</body>
</html>",
				/* Mülleimer - Blanko */
                       "trash" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki_trash}</title>
	{\$headerinclude}
</head>
<body>
{\$header}
{\$errors}
<div style=\"float: right;\">{\$multipage}</div>
{\$wiki_trash_table}
<div style=\"float: right;\">{\$multipage}</div>
{\$footer}
</body>
</html>",
				/* Mülleimer - Tabelle */
                       "trash_table" => "
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"6\"><strong><a href=\"{\$settings['bburl']}/{\$wiki_trash}\" title=\"{\$lang->wiki_trash}\">{\$lang->wiki_trash}</a></strong></td>
	</tr>
	<tr>
		<td class=\"tcat\" width=\"33%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_title}</strong></span>
		</td>
		<td class=\"tcat\" width=\"33%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_category}</strong></span>
		</td>
		<td class=\"tcat\" width=\"8%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_trash_deletedon}</strong></span>
		</td>
		<td class=\"tcat\" width=\"8%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_trash_deletedfrom}</strong></span>
		</td>
		{\$restore}
		{\$delete}
	</tr>
	{\$wiki_trash_table}
</table>",
						"trash_table_restore" => "
		<td class=\"tcat\" width=\"8%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_restore}</strong></span>
		</td>",
						"trash_table_delete" => "
		<td class=\"tcat\" width=\"10%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_trash_delete}</strong></span>
		</td>",
				/* Mülleimer - Tabellenelemt */
                       "trash_table_element" => "
<tr>
	<td class=\"trow1\">
		<span class=\"smalltext\">{\$trash['title']}</span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\">{\$trash['category']}</strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\">{\$trash['deleteddate']}</strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\">{\$trash['deletedfrom']}</strong></span>
	</td>
	{\$restore}
	{\$delete}
</tr>",
						"trash_table_element_restore" => "
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"wiki.php?action=restore&wid={\$trash['id']}\">{\$lang->wiki_restore}</a></strong></span>
	</td>",
						"trash_table_element_delete" => "
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"wiki.php?action=trash_delete&wid={\$trash['id']}\">{\$lang->wiki_trash_delete}</a></strong></span>
	</td>",
				/* Anzeige verschiedener Versionen */
                       "versions" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki_versions}</title>
	{\$headerinclude}
</head>
<body>
{\$header}
{\$errors}
{\$diffs}
<div style=\"float: right;\">{\$multipage}</div>
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"6\"><strong>{\$lang->wiki_versions_of} {\$awiki['title']}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\" width=\"45%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_date}</strong></span>
		</td>
		<td class=\"tcat\" width=\"45%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_title}</strong></span>
		</td>
		<td class=\"tcat\" width=\"5%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_short}</strong></span>
		</td>
		<td class=\"tcat\" width=\"5%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_user}</strong></span>
		</td>
		{\$restore}
		{\$delete}
	</tr>
	{\$wiki_table}
</table>
<div style=\"float: right;\">{\$multipage}</div>
{\$footer}
</body>
</html>",
				/* Auswahl für Diff */
						"versions_diff_panel" => "
<form action=\"wiki.php\" method=\"post\">
<input type=\"hidden\" name=\"action\" value=\"version_diff\" />
<input type=\"hidden\" name=\"wid\" value=\"{\$wid}\" />
<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"4\"><strong>{\$lang->wiki_versions_diff}</strong></td>
	</tr>
	<tr>
		<td class=\"trow1\" width=\"15%\"><strong>{\$lang->wiki_version} 1</strong></td>
		<td class=\"trow1\" width=\"35%\"><select name=\"version1\">{\$versions1}</select></td>
		<td class=\"trow1\" width=\"15%\"><strong>{\$lang->wiki_version} 2</strong></td>
		<td class=\"trow1\" width=\"35%\"><select name=\"version2\">{\$versions2}</select></td>
	</tr>
	<tr>
		<td class=\"trow1\" colspan=\"4\" style=\"text-align: center;\">
				<input type=\"submit\" class=\"button\" name=\"submit\" value=\"{\$lang->wiki_versions_diff_submit}\" />
		</td>
	</tr>
</table>
</form><br /><br />",
						"versions_restore" => "
		<td class=\"tcat\" width=\"5%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_restore}</strong></span>
		</td>",
						"versions_delete" => "
		<td class=\"tcat\" width=\"5%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_version_delete}</strong></span>
		</td>",
				/* Elemente für Versions Tabelle */
                       "versions_table" => "
<tr>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong><a href=\"{\$settings['bburl']}/{\$wiki_version}\">{\$wiki['date']}</a></strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$wiki['title']}</strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$wiki['short']}</strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$wiki['user']}</strong></span>
	</td>
	{\$restore}
	{\$delete}
</tr>",
						"versions_table_restore" => "
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=restore_version&vid={\$vid}\">{\$lang->wiki_restore}</a></span>
	</td>",
						"versions_table_delete" => "
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=delete_version&vid={\$vid}\">{\$lang->wiki_version_delete}</a></span>
	</td>",
				/* Anzeige verschiedener Versionen */
                       "versions_diff" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki_versions_diff}</title>
	{\$headerinclude}
	<style type=\"text/css\">
		.wiki-diff-deleted {
			text-decoration: line-through;
			background-color: #ffaaaa;
		}

		.wiki-diff-inserted {
			background-color: #aaffaa;
		}
	</style>
</head>
<body>
{\$header}
{\$diffs}
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"6\"><strong>{\$lang->wiki_versions_diff_between}</strong></td>
	</tr>
	<tr>
		<td class=\"trow1\">
			{\$diff}
		</td>
	</tr>
</table>
{\$footer}
</body>
</html>",
    				/* Neue und Aktualisierte Artikel */
                       "new" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki_nav_new}</title>
	{\$headerinclude}
</head>
<body>
{\$header}
<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
	<tr>
		<td class=\"thead\" colspan=\"4\"><strong>{\$lang->wiki_new_article}</strong></td>
	</tr>
	<tr>
		<td class=\"tcat\" width=\"35%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_title}</strong></span>
		</td>
		<td class=\"tcat\" width=\"40%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_short}</strong></span>
		</td>
		<td class=\"tcat\" width=\"15%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_user}</strong></span>
		</td>
		<td class=\"tcat\" width=\"10%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_date}</strong></span>
		</td>
	</tr>
	{\$article_table}
</table>
{\$footer}
</body>
</html>",
				/* Elemente für Neue & Aktualisierte */
                       "new_element" => "
<tr>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong><a href=\"{\$settings['bburl']}/{\$wiki_article}\">{\$wiki['title']}</a></strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$wiki['short']}</strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$wiki['user']}</strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$wiki['date']}</strong></span>
	</td>
</tr>",
				/* Copyright Hinweis */
                       "copy" => "
{\$lang->wiki_copy} <a href=\"http://jonesboard.tk/\">Jones</a>",
                       )
        );
}
?>