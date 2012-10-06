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
$page->add_breadcrumb_item($lang->wiki_update, "index.php?module=".MODULE."-update");

$page->output_header($lang->wiki_update);

$active_hooks = $plugins->hooks;
require_once MYBB_ROOT."inc/plugins/wiki.php";
$plugininfo = wiki_info();
$plugins->hooks = $active_hooks;
$wiki_uploaded = $plugininfo['version'];
$wiki_installed = $PL->cache_read("wiki_version");

if($mybb->input['action']=="do_update") {
	if($mybb->input['cancel_update']) {
		flash_message($lang->wiki_canceled, 'error');
		admin_redirect("index.php?module=".MODULE."-index");
	}
	if($mybb->input['do_update']) {
		if($PL->version<8) {
			flash_message($lang->wiki_pl_old, "error");
        	admin_redirect("index.php?module=".MODULE."-update");
		}
		wiki_settings();
		wiki_templates();
		wiki_update($wiki_installed, $wiki_uploaded);
		flash_message($lang->wiki_success, 'success');
		admin_redirect("index.php?module=".MODULE."-index");
	}
	flash_message($lang->wiki_error, 'error');
	admin_redirect("index.php?module=".MODULE."-index");
} else {
	if(version_compare($wiki_uploaded, $wiki_installed, ">")) {
		$lang->wiki_update_submit_desc = $lang->sprintf($lang->wiki_update_submit_desc, $wiki_installed, $wiki_uploaded);
		$form = new Form("index.php?module=".MODULE."-update&action=do_update", "post");
		$form_container = new FormContainer($lang->wiki_update);
	
		$form_container->output_row($lang->wiki_update_submit, $lang->wiki_update_submit_desc);
		$form_container->end();
	
		$buttons[] = $form->generate_submit_button($lang->wiki_update_submit, array("name"=>"do_update"));
		$buttons[] = $form->generate_submit_button($lang->cancel, array("name"=>"cancel_update"));
		$form->output_submit_wrapper($buttons);
		$form->end();
	} else {
		$form = new Form("index.php?module=".MODULE."-index", "post");
		$form_container = new FormContainer($lang->wiki_update);
	
		$form_container->output_row($lang->wiki_update_no, $lang->wiki_update_no_desc);
		$form_container->end();
	
		$buttons[] = $form->generate_submit_button($lang->wiki_update_ok);
		$form->output_submit_wrapper($buttons);
		$form->end();	
	}
}
$page->output_footer();
?>