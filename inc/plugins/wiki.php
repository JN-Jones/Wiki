<?php
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
if(!defined("PLUGINLIBRARY"))
{
    define("PLUGINLIBRARY", MYBB_ROOT."inc/plugins/pluginlibrary.php");
}

$plugins->add_hook("global_start", "wiki_init");
$plugins->add_hook("fetch_wol_activity_end", "wiki_wol_activity");
$plugins->add_hook("build_friendly_wol_location_end", "wiki_wol_location");
$plugins->add_hook("parse_message", "wiki_mycode");
$plugins->add_hook("parse_message_quote", "wiki_mycode");
$plugins->add_hook("parse_message_end", "wiki_autolink");

function wiki_info()
{
	return array(
		"name"			=> "Wiki",
		"description"	=> "Adds a wiki to the Forum",
		"website"		=> "http://www.mybbdemo.tk/forum-12.html",
		"author"		=> "Jones",
		"authorsite"	=> "http://www.mybbdemo.tk/",
		"version"		=> "1.1.2",
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
	wiki_templates(trie);

	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki` ( `id` int(11) NOT NULL AUTO_INCREMENT, `cid` int(11) NOT NULL, `title` varchar(50) DEFAULT NULL, `link` varchar(100) DEFAULT NULL, `short` varchar(200) DEFAULT NULL, `text` text, `uid` int(10), `username` varchar(80), `date` bigint(30), `is_hidden` boolean, `is_closed` boolean, `awaiting_moderation` boolean, `Sort` int NOT NULL default '0', PRIMARY KEY (`id`) ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1");
	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki_cats` ( `id` int(11) NOT NULL AUTO_INCREMENT, `title` varchar(50) DEFAULT NULL, `Sort` int NOT NULL default '0', PRIMARY KEY (`id`) ) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1");
	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki_trash` ( `id` int(11) NOT NULL AUTO_INCREMENT, `entry` text, `from` int(10) NOT NULL, `date` bigint(30) NOT NULL, PRIMARY KEY (`id`) ) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1");
	$db->query("CREATE TABLE `".TABLE_PREFIX."wiki_versions` ( `id` int(11) NOT NULL AUTO_INCREMENT, `wid` int(11) NOT NULL, `entry` text, PRIMARY KEY (`id`) ) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1");

	$PL->cache_update("wiki_version", $plugininfo['version']);
	$PL->cache_update("wiki_pl_version", "8");
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

	$PL->cache_delete("wiki_version");
	$PL->cache_delete("wiki_pl_version");
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
		            $user_activity['wiki']['entry'] = intval($parameters['wid']);
		            $user_activity['wiki']['cat'] = intval($parameters['cid']);
				}
			}
            if($parameters['action'] == "category_add")
                $user_activity['wiki']['cat_add'] = true;
            elseif($parameters['action'] == "category_edit")
                $user_activity['wiki']['cat_edit'] = intval($parameters['cid']);
            elseif($parameters['action'] == "category_delete")
                $user_activity['wiki']['cat_delete'] = intval($parameters['cid']);
            elseif($parameters['action'] == "article_add")
                $user_activity['wiki']['entry_add'] = true;
            elseif($parameters['action'] == "article_edit")
                $user_activity['wiki']['entry_edit'] = intval($parameters['wid']);
            elseif($parameters['action'] == "article_delete")
                $user_activity['wiki']['entry_delete'] = intval($parameters['wid']);
            elseif($parameters['action'] == "search")
                $user_activity['wiki']['search'] = true;
			break;
    }
    return $user_activity;
}

function wiki_wol_location($array)
{
	global $db, $lang, $settings, $wiki_link;
    switch ($array['user_activity']['activity'])
    {
        case 'wiki':
//        	echo "<pre>"; var_dump($array['user_activity']['wiki']); echo "</pre>";
        	if($array['user_activity']['wiki']['entry']) {
	        	$id=$array['user_activity']['wiki']['entry'];
				$test = $db->simple_select("wiki", "title, id", "id='{$id}'");
				$wiki=$db->fetch_array($test);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_entry, wiki_get_article($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['cat']) {
	        	$id=$array['user_activity']['wiki']['cat'];
				$test = $db->simple_select("wiki_cats", "title, id", "id='{$id}'");
				$wiki=$db->fetch_array($test);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_cat, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['cat_add'])
				$array['location_name'] = $lang->wiki_wol_cat_add;
			elseif($array['user_activity']['wiki']['cat_edit']) {
	        	$id=$array['user_activity']['wiki']['cat_edit'];
				$test = $db->simple_select("wiki_cats", "title, id", "id='{$id}'");
				$wiki=$db->fetch_array($test);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_cat_edit, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['cat_delete']) {
	        	$id=$array['user_activity']['wiki']['cat_delete'];
				$test = $db->simple_select("wiki_cats", "title, id", "id='{$id}'");
				$wiki=$db->fetch_array($test);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_cat_delete, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['entry_add'])
				$array['location_name'] = $lang->wiki_wol_add;
			elseif($array['user_activity']['wiki']['entry_edit']) {
	        	$id=$array['user_activity']['wiki']['entry_edit'];
				$test = $db->simple_select("wiki", "title, id", "id='{$id}'");
				$wiki=$db->fetch_array($test);
	            $array['location_name'] = $lang->sprintf($lang->wiki_wol_edit, wiki_get_category($wiki['id']), $wiki['title']);
			} elseif($array['user_activity']['wiki']['entry_delete']) {
	        	$id=$array['user_activity']['wiki']['entry_delete'];
				$test = $db->simple_select("wiki", "title, id", "id='{$id}'");
				$wiki=$db->fetch_array($test);
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
		$message = preg_replace_callback("#\[\[([0-9]):(.*?)\]\]#si", "wiki_mycode_createID", $message);
		$message = preg_replace_callback("#\[\[([a-zA-Z0-9\s]*):(.*?)\]\]#si", "wiki_mycode_create", $message);
		$message = preg_replace_callback("#\[\[(.*?)\]\]#si", "wiki_mycode_create", $message);
	}
	if($mybb->settings['wiki_mycode']=="2"||$mybb->settings['wiki_mycode']=="both") {
		$message = preg_replace_callback("#\[wiki=([0-9])\](.*?)\[/wiki\]#si", "wiki_mycode_createID", $message);
		$message = preg_replace_callback("#\[wiki=([a-zA-Z0-9\s]*)\](.*?)\[/wiki\]#si", "wiki_mycode_create", $message);
		$message = preg_replace_callback("#\[wiki\](.*?)\[/wiki\]#si", "wiki_mycode_create", $message);
	}
	return $message;
}

function wiki_mycode_createID(array $match)
{
	global $db, $settings;
	$id=intval($match[1]); $name=$match[2];
	$query=$db->simple_select("wiki", "text, link, short", "id='{$id}'");
	$wiki=$db->fetch_array($query);
	if($wiki['link'])
	    return '<a rel="nofollow" href="'.$wiki['link'].'" target="_blank" title="'.$wiki['short'].'">'.$name.'</a>';
	elseif($wiki['text'])
		return '<a href="'.$settings['bburl'].'/'.wiki_get_article($id).'" title="'.$wiki['short'].'">'.$name.'</a>';
	else
		return $name;
}

function wiki_mycode_create(array $match)
{
	global $db, $settings;
	$name=$db->escape_string($match[1]);
	$show=$match[2];
	if(!isset($show))
	    $show=$match[1];
	$query=$db->simple_select("wiki", "id", "title='{$name}'", array('limit'=>'0,1'));
	if($db->num_rows($query)==0)
	    return $name;
	$id=$db->fetch_array($query); $id=$id['id'];
	$query=$db->simple_select("wiki", "text, link, short", "id='{$id}'");
	$wiki=$db->fetch_array($query);
	if($wiki['link'])
	    return '<a rel="nofollow" href="'.$wiki['link'].'" target="_blank" title="'.$wiki['short'].'">'.$show.'</a>';
	elseif($wiki['text'])
		return '<a href="'.$settings['bburl'].'/'.wiki_get_article($id).'" title="'.$wiki['short'].'">'.$show.'</a>';
	else
		return $show;

}

function wiki_autolink($message)
{
	global $db, $mybb;
	if($mybb->settings['wiki_autolink']=="0")
	    return $message;
	$query=$db->simple_select("wiki", "id, title, text, link, short");
	while($wiki=$db->fetch_array($query)) {
		if($wiki['link'])
		    $link = '<a rel="nofollow" href="'.$wiki['link'].'" target="_blank">'.$wiki['title'].'</a>';
		elseif($wiki['text'])
			$link = '<a href="'.$settings['bburl'].'/'.wiki_get_article($wiki['id']).'">'.$wiki['title'].'</a>';
		else
			$link = $wiki['title'];
		$message=str_replace(" ".$wiki['title']." ", " ".$link." ", $message);
	}
	return $message;
}


function wiki_user_in_group($user, $allowedgroups)
{
	if(sizeof($allowedgroups)==1 && $allowedgroups[0]==0)
	    return true;
	$groups = array();
	$agroups = explode(',', $user['additionalgroups']);
	array_push($groups, $user['usergroup']);
	for($i=0; $i<sizeof($agroups); $i++) {
		array_push($groups, $agroups[$i]);
	}
	$in = false;
	foreach ($groups as $group) {
		if(in_array($group, $allowedgroups)) {
		   $in = true;
		}
	}
	return $in;
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
	if(wiki_user_in_group($mybb->user, $allowed) && $showbuttons) {
//	if($PL->is_member($allowed) && $showbuttons) {
			if(!$wiki['is_closed'])
				eval("\$user['edit'] = \"".$templates->get("wiki_header_edit")."\";");
			else {
			    if($wiki['uid']==$mybb->user['uid']||wiki_user_in_group($mybb->user, $allowedmod))
//			    if($wiki['uid']==$mybb->user['uid']||$PL->is_member($allowedmod))
					eval("\$user['edit'] = \"".$templates->get("wiki_header_edit")."\";");
			}
	}
	eval("\$user_header = \"".$templates->get("wiki_header")."\";");
	return $user_header;
}

function wiki_update($installed, $uploaded)
{
	global $PL, $db;
	$PL->cache_update("wiki_version", $uploaded);
	$PL->cache_update("wiki_pl_version", "8");
	if(version_compare($installed, "1.1 Beta 1 Dev 10", "<")) {
		$db->add_column('wiki', 'Sort', "int NOT NULL default '0'");
		$db->add_column('wiki_cats', 'Sort', "int NOT NULL default '0'");
	}
}

function wiki_settings($install=false)
{
    global $PL;
	$PL->settings("wiki",
	  	"Wiki",
	  	"Settings for the \"Wiki\" Plugin",
	  	array(
	      	"allowedgroups" => array(
	          	"title" => "Allowed Usergroups",
	          	"description" => "The Groups which can visit the wiki. Choose 0 for every Group. Seperate groups with ,",
		        "optionscode" => "text",
		        "value" => "0",
	          ),
	      	"write_allowedgroups" => array(
	          	"title" => "Allowed Manager Groups",
	          	"description" => "The Groups which can add/change/delete the wiki entries. Choose 0 for every group. Seperate groups with ,",
		        "optionscode" => "text",
		        "value" => "2,3,4,5,6",
	          ),
	      	"mod_allowedgroups" => array(
	          	"title" => "Allowed Moderator Groups",
	          	"description" => "The Groups which manage the Trash and Version History. Choose 0 for every group. Seperate groups with ,",
		        "optionscode" => "text",
		        "value" => "3,4,6",
	          ),
	      	"moderate_new" => array(
	          	"title" => "Moderate new entrys?",
	          	"description" => "",
		        "optionscode" => "yesno",
		        "value" => "no",
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
	          	"description" => "Link automatical Articles in Posts? Attention: This is just a BETA Function",
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
</head>
<body>
{\$header}
{\$wiki_header}
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
		{\$additional}
	</tr>
	{\$wiki_table}
</table>
{\$submit}
</form>
{\$wiki_trash}
{\$footer}
</body>
</html>",
				/* Zusätzliche Spalten für Moderatoren */
					   "mod" => "
		<td class=\"tcat\" width=\"5%\" >
			<span class=\"smalltext\"><strong>{\$lang->order}</strong></span>
		</td>
		<td class=\"tcat\" width=\"5%\" colspan=\"2\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_control}</strong></span>
		</td>",
				/* Elemente für Kategorie Hauptseite */
                       "table" => "
<tr>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$category_title}</strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><strong>{\$category_number}</strong></span>
	</td>
	{\$additional}
</tr>",
				/* Zusätzliche Elemente für Moderatoren */
					   "table_mod" => "
	<td class=\"trow1\">
		<span class=\"smalltext\"><input type=\"text\" name=\"disporder[{\$cid}]\" value=\"{\$category_sort}\" class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" /></span>
	</td>
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
</head>
<body>
{\$header}
{\$wiki_header}
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
		{\$additional}
	</tr>
	{\$wiki_table}
</table>
{\$submit}
</form>
{\$footer}
</body>
</html>",
				/* Zusätzliche Spalten für Moderatoren */
					   "category_mod" => "
		<td class=\"tcat\" width=\"5%\" >
			<span class=\"smalltext\"><strong>{\$lang->order}</strong></span>
		</td>
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
	{\$additional}
</tr>",
				/* Zusätzliche Elemente für Moderatoren */
					   "category_table_mod" => "
	<td style=\"background: {\$background};\" class=\"trow1\">
		<span class=\"smalltext\"><input type=\"text\" name=\"disporder[{\$wiki['id']}]\" value=\"{\$wiki['Sort']}\" class=\"text_input align_center\" style=\"width: 80%; font-weight: bold;\" /></span>
	</td>
	<td style=\"background: {\$background};\" class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_edit&wid={\$wiki['id']}\"><img src=\"{\$settings['bburl']}/images/wiki_edit.gif\" alt=\"{\$lang->wiki_edit}\" title=\"{\$lang->wiki_edit}\" /></a></span>
	</td>
	<td style=\"background: {\$background};\" class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_delete&wid={\$wiki['id']}\"><img src=\"{\$settings['bburl']}/images/wiki_delete.gif\" alt=\"{\$lang->wiki_delete}\" title=\"{\$lang->wiki_delete}\" /></a></span>
	</td>",
				/* Anzeige eines Artikels */
                       "text" => "
<html>
<head>
	<title>{\$settings['bbname']} - {\$lang->wiki} - {\$wiki_title}</title>
	{\$headerinclude}
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
<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />
<input type=\"text\" name=\"searchString\" value=\"{\$searchString}\" class=\"text_input\" />
<input type=\"submit\" value=\"{\$lang->search}\" />
<input type=\"radio\" name=\"type\" value=\"full\" {\$full_checked}/>{\$lang->search_full}
<input type=\"radio\" name=\"type\" value=\"title\" {\$title_checked}/>{\$lang->search_title}
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
		<td class=\"tcat\" align=\"center\"><strong>{\$lang->wiki_short}</strong></td>
	</tr>
	{\$searchResults}
	<tr>
		<td colspan=\"3\"><center>{\$search}</center></td>
	</tr>
</table>
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
		<td class=\"trow1\">{\$result['title']}</td>
		<td class=\"trow1\">{\$result['category']}</td>
		<td class=\"trow1\">{\$result['short']}</td>
	</tr>",
				/* Panel für Hauptseite */
                       "panel" => "
<div style=\"background: #efefef; color: #000000; font-size: 11px; border: 1px solid #D4D4D4; padding: 8px;\">
<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=category_add\" title=\"{\$lang->wiki_nav_category_add}\">{\$lang->wiki_nav_category_add}</a>
<a style=\"float: right;\" href=\"{\$mybb->settings['bburl']}/{\$wiki_new}\" title=\"{\$lang->wiki_nav_new}\">{\$lang->wiki_nav_new}</a>
<span style=\"float: right; padding-right: 5px;\">{\$search}</span>
<br />
</div>
<br />",
				/* Panel für Artikelauflistung */
                       "panel_category" => "
<div style=\"background: #efefef; color: #000000; font-size: 11px; border: 1px solid #D4D4D4; padding: 8px;\">
<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_add&cid={\$wiki_cid}\" title=\"{\$lang->wiki_nav_add}\">{\$lang->wiki_nav_add}</a> |
<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=category_edit&cid={\$wiki_cid}\" title=\"{\$lang->wiki_nav_category_edit}\">{\$lang->wiki_nav_category_edit}</a> |
<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=category_delete&cid={\$wiki_cid}\" title=\"{\$lang->wiki_nav_category_delete}\">{\$lang->wiki_nav_category_delete}</a>
<br />
</div>
<br />",
				/* Panel für Artikelanzeige */
                       "panel_text" => "
<div style=\"background: #efefef; color: #000000; font-size: 11px; border: 1px solid #D4D4D4; padding: 8px;\">
<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_add&cid={\$cid}\" title=\"{\$lang->wiki_nav_add}\">{\$lang->wiki_nav_add}</a> |
<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_edit&wid={\$id}\" title=\"{\$lang->wiki_nav_edit}\">{\$lang->wiki_nav_edit}</a> |
<a href=\"{\$mybb->settings['bburl']}/wiki.php?action=article_delete&wid={\$id}\" title=\"{\$lang->wiki_nav_delete}\">{\$lang->wiki_nav_delete}</a>
{\$versions}{\$unlock}
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
				<input type=\"hidden\" name=\"wid\" value=\"{\$wiki_id}\" />

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
	<input type=\"hidden\" name=\"wid\" value=\"{\$wiki_id}\" />
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
				<input type=\"hidden\" name=\"cid\" value=\"{\$wiki_id}\" />

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
	<input type=\"hidden\" name=\"cid\" value=\"{\$wiki_id}\" />
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
{\$wiki_trash_table}
{\$footer}
</body>
</html>",
				/* Mülleimer - Tabelle */
                       "trash_table" => "
<br />
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
		<td class=\"tcat\" width=\"8%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_restore}</strong></span>
		</td>
		<td class=\"tcat\" width=\"10%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_trash_delete}</strong></span>
		</td>
	</tr>
	{\$wiki_trash_table}
</table>",
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
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"wiki.php?action=restore&wid={\$trash['id']}\">{\$lang->wiki_restore}</a></strong></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"wiki.php?action=trash_delete&wid={\$trash['id']}\">{\$lang->wiki_trash_delete}</a></strong></span>
	</td>
</tr>",
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
		<td class=\"tcat\" width=\"5%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_restore}</strong></span>
		</td>
		<td class=\"tcat\" width=\"5%\">
			<span class=\"smalltext\"><strong>{\$lang->wiki_version_delete}</strong></span>
		</td>
	</tr>
	{\$wiki_table}
</table>
{\$footer}
</body>
</html>",
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
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=restore_version&vid={\$vid}\">{\$lang->wiki_restore}</a></span>
	</td>
	<td class=\"trow1\">
		<span class=\"smalltext\"><a href=\"{\$mybb->settings['bburl']}/wiki.php?action=delete_version&vid={\$vid}\">{\$lang->wiki_version_delete}</a></span>
	</td>
</tr>",
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
{\$lang->wiki_copy} <a href=\"http://mybbdemo.tk/\">Jones</a>",
                       )
        );
}
?>