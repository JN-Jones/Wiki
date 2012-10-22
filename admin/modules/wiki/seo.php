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
$page->add_breadcrumb_item($lang->wiki_seo, "index.php?module=".MODULE."-seo");

$page->output_header($lang->wiki_seo);

echo "OK";

$page->output_footer();
?>