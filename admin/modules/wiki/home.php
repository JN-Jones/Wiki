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

$page->add_breadcrumb_item($lang->wiki, "index.php?module=".MODULE."-index");

$page->output_header($lang->wiki);


$success=true;
$active_hooks = $plugins->hooks;
require_once MYBB_ROOT."inc/plugins/wiki.php";
$plugininfo = wiki_info();
$plugininfo['guid'] = trim($plugininfo['guid']);
$plinfo = pluginlibrary_info();
$plinfo['guid'] = trim($plinfo['guid']);
$plugins->hooks = $active_hooks;

$url = "http://mods.mybb.com/version_check.php?info[]=".$plugininfo['guid']."&info[]=".$plinfo['guid'];

require_once MYBB_ROOT."inc/class_xml.php";
$contents = fetch_remote_file($url);

if(!$contents)
{
	$success = false;
	$message = $lang->error_vcheck_communications_problem;
}

if($success) {
	$parser = new XMLParser($contents);
	$tree = $parser->get_tree();
	
	if(array_key_exists('error', $tree['plugins']))
	{
		switch($tree['plugins'][0]['error'])
		{
			case "1":
				$error_msg = $lang->error_no_input;
				break;
			case "2":
				$error_msg = $lang->error_no_pids;
				break;
			default:
				$error_msg = "";
		}

		$success=false;
		$message=$lang->error_communication_problem.$error_msg;
	}
}
if($success) {
	if(!array_key_exists('plugin', $tree['plugins']))
	{
		$success = false;
	}
}
if($success) {	
	if(array_key_exists("tag", $tree['plugins']['plugin']))
	{
		$only_plugin = $tree['plugins']['plugin'];
		unset($tree['plugins']['plugin']);
		$tree['plugins']['plugin'][0] = $only_plugin;
	}

	foreach($tree['plugins']['plugin'] as $plugin)
	{
		if($plugin['attributes']['guid']==$plinfo['guid'])
		    $pl_newest = $plugin['version']['value'];
		elseif($plugin['attributes']['guid']==$plugininfo['guid'])
			$wiki_newest = $plugin['version']['value'];
	}
}
$wiki_uploaded = $plugininfo['version'];
$wiki_installed = $PL->cache_read("wiki_version");
$pl_bw = $PL->cache_read("wiki_pl_version");

if(version_compare($wiki_uploaded, $wiki_installed, ">"))
	$page->output_alert($lang->sprintf($lang->wiki_update_message,$wiki_uploaded,$wiki_installed));
elseif(version_compare($wiki_uploaded, $wiki_installed, "<"))
	$page->output_error($lang->sprintf($lang->wiki_version_error,$wiki_installed,$wiki_uploaded));
if(version_compare($wiki_installed, $wiki_newest, "<"))
	$page->output_alert($lang->sprintf($lang->wiki_version_update,$wiki_installed,$wiki_newest));
/* This was just fun ;)
elseif(version_compare($wiki_uploaded, $wiki_newest, ">"))
	$page->output_alert("Bist du Entwickler oder Tester? Wenn nicht hast du was falsch gemacht.");
*/
if(version_compare($PL->version, $pl_bw, "<"))
	$page->output_error($lang->sprintf($lang->wiki_pl_version_error,$PL->version,$pl_bw));


$table = new Table;
$table->construct_cell("<b>$lang->wiki_installed</b>", array('width' => '50%'));
$table->construct_cell($wiki_installed, array('width' => '50%'));
$table->construct_row();
$table->construct_cell("<b>$lang->wiki_uploaded</b>", array('width' => '50%'));
$table->construct_cell($wiki_uploaded, array('width' => '50%'));
$table->construct_row();
$table->construct_cell("<b>$lang->wiki_newest</b>", array('width' => '50%'));
$table->construct_cell($wiki_newest, array('width' => '50%'));
$table->construct_row();
$table->output($lang->wiki_info);

$table = new Table;
$table->construct_cell("<b>$lang->wiki_installed</b>", array('width' => '50%'));
$table->construct_cell($PL->version, array('width' => '50%'));
$table->construct_row();
$table->construct_cell("<b>$lang->wiki_newest</b>", array('width' => '50%'));
$table->construct_cell($pl_newest, array('width' => '50%'));
$table->construct_row();
$table->construct_cell("<b>$lang->wiki_buildwith</b>", array('width' => '50%'));
$table->construct_cell($pl_bw, array('width' => '50%'));
$table->construct_row();
$table->output($lang->wiki_pl_info);

$wcache['articles'] = wiki_cache_load("articles");
$wcache['categories'] = wiki_cache_load("categories");
$wcache['versions'] = wiki_cache_load("versions");
$wcache['trash'] = wiki_cache_load("trash");

foreach($wcache as $key => $value) {
	if(is_array($value))
		$number[$key] = sizeOf($value);
	else
		$number[$key] = 0;
}

$table = new Table;
$table->construct_cell("<b>$lang->wiki_num_art</b>", array('width' => '25%'));
$table->construct_cell($number['articles'], array('width' => '25%'));
$table->construct_cell("<b>$lang->wiki_num_cat</b>", array('width' => '25%'));
$table->construct_cell($number['categories'], array('width' => '25%'));
$table->construct_row();
$table->construct_cell("<b>$lang->wiki_num_trash</b>", array('width' => '25%'));
$table->construct_cell($number['trash'], array('width' => '25%'));
$table->construct_cell("<b>$lang->wiki_num_vers</b>", array('width' => '25%'));
$table->construct_cell($number['versions']+$number['articles'], array('width' => '25%'));
$table->construct_row();
$table->output($lang->wiki_stat);

$page->output_footer();
?>