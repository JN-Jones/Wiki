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
$page->add_breadcrumb_item($lang->wiki_cache, "index.php?module=".MODULE."-cache");

$page->output_header($lang->wiki_cache);

/*
if($mybb->input['action'] == "test") {
	$articles = wiki_cache_load("articles");
	echo "<pre>Unsortiert:<br />";
	var_dump($articles);
	echo "Sortiert:<br />";
	uasort($articles, "wiki_sort_date");
	var_dump($articles);
	echo "</pre>";
	exit();
} //*/
if($mybb->input['action'] == "view") {
	$wcache = wiki_cache_load($mybb->input['cache']);
	if(!$wcache) {
		$lang->wiki_cache_empty = $lang->sprintf($lang->wiki_cache_empty, $mybb->input['cache']);
		flash_message($lang->wiki_cache_empty, 'error');
		admin_redirect("index.php?module=".MODULE."-cache");
	}
	$cachecontents = print_r($wcache, true);
	$table = new Table;
	$table->construct_cell("<pre>\n{$cachecontents}\n</pre>");
	$table->construct_row();
	$table->output($lang->wiki_cache.": {$mybb->input['cache']}");

	$page->output_footer();
} elseif($mybb->input['action'] == "reload") {
	wiki_cache_update($mybb->input['cache']);

	$lang->wiki_cache_reload = $lang->sprintf($lang->wiki_cache_reload, $mybb->input['cache']);
	flash_message($lang->wiki_cache_reload, 'success');
	admin_redirect("index.php?module=".MODULE."-cache");
} else {
	$wcache['articles'] = wiki_cache_load("articles");
	$wcache['categories'] = wiki_cache_load("categories");
	$wcache['versions'] = wiki_cache_load("versions");
	$wcache['trash'] = wiki_cache_load("trash");
	$wcache['permissions'] = wiki_cache_load("permissions");

	foreach($wcache as $key => $value) {
		if(is_array($value))
			$number[$key] = sizeOf($value);
		else
			$number[$key] = 0;
	}

	$table = new Table;
	$table->construct_header($lang->wiki_cache);
	$table->construct_header($lang->wiki_elements);
	$table->construct_header($lang->wiki_reload);

	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=view&amp;cache=articles\">{$lang->wiki_articles}</a>");
	$table->construct_cell($number['articles']);
	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=reload&amp;cache=articles\">{$lang->wiki_reload}</a>");
	$table->construct_row();

	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=view&amp;cache=categories\">{$lang->wiki_categories}</a>");
	$table->construct_cell($number['categories']);
	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=reload&amp;cache=categories\">{$lang->wiki_reload}</a>");
	$table->construct_row();

	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=view&amp;cache=versions\">{$lang->wiki_versions}</a>");
	$table->construct_cell($number['versions']);
	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=reload&amp;cache=versions\">{$lang->wiki_reload}</a>");
	$table->construct_row();

	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=view&amp;cache=trash\">{$lang->wiki_trash}</a>");
	$table->construct_cell($number['trash']);
	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=reload&amp;cache=trash\">{$lang->wiki_reload}</a>");
	$table->construct_row();

	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=view&amp;cache=permissions\">{$lang->wiki_permissions}</a>");
	$table->construct_cell($number['permissions']);
	$table->construct_cell("<a href=\"index.php?module=".MODULE."-cache&amp;action=reload&amp;cache=permissions\">{$lang->wiki_reload}</a>");
	$table->construct_row();

	$table->output($lang->wiki_cache);
}
$page->output_footer();
?>