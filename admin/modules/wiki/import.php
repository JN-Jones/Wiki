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

$page->add_breadcrumb_item($lang->wiki, "index.php?module=wiki");
$page->add_breadcrumb_item($lang->wiki_import, "index.php?module=wiki-import");

$page->output_header($lang->wiki_import);

if($mybb->input['action']=="do_import") {
	if(!$_FILES['import_file']||$_FILES['import_file']=="") {
		flash_message($lang->wiki_import_no_file, 'error');
		admin_redirect("index.php?module=wiki-import");
	}
	if($_FILES['import_file']['error']>0||!is_uploaded_file($_FILES['import_file']['tmp_name'])) {
		flash_message($lang->wiki_import_error_file, 'error');
		admin_redirect("index.php?module=wiki-import");
	}
	$contents = @file_get_contents($_FILES['import_file']['tmp_name']);
	@unlink($_FILES['import_file']['tmp_name']);
	if(!trim($contents))
	{
		flash_message($lang->wiki_import_error_file, 'error');
		admin_redirect("index.php?module=wiki-import");
	}
	$error = "";
	$data = $PL->xml_import($contents, $error);
	if(!$data) {
//		echo "<pre>"; var_dump($error); echo "</pre>";
 		flash_message($lang->wiki_import_invalid_file, 'error');
		admin_redirect("index.php?module=wiki-import");
	}
//	echo "0:<br /><pre>"; var_dump($data); echo "</pre>";
	$counter = array("cat"=>0, "art"=>0, "trash"=>0, "version"=>0);

	if($data['cats']) {
		$catid = array();
		foreach($data['cats'] as $cat) {
			$oldid=$cat['id'];
			unset($cat['id']);
			$catid[$oldid] = $db->insert_query('wiki_cats', $cat);
			$counter['cat']++;
		}
		wiki_cache_update("categories");
	}
	if($data['art']) {
		$artid = array();
		$newcat = wiki_cache_load("categories");
		array_splice($newcat, 1);
		$usednewcat = false;
		foreach($data['art'] as $art) {
			$changed = false;
			$oldid=$art['id'];
			unset($art['id']);
			if($catid) {
				if(array_key_exists($art['cid'], $catid)) {
					$changed = true;
					$art['cid'] = $catid[$art['cid']];
				}
			}
			if(!$changed) {
				$art['cid'] = $newcat['id'];
				$usednewcat = true;
			}
			$artid[$oldid] = $db->insert_query('wiki', $art);
			$counter['art']++;
		}
		wiki_cache_update("articles");
	}
	if($data['trash']) {
		foreach($data['trash'] as $trash) {
			unset($trash['id']);
			$db->insert_query('wiki_trash', $trash);
			$counter['trash']++;
		}
		wiki_cache_update("trash");
	}
	if($data['versions']&&$artid) {
		foreach($data['versions'] as $version) {
			unset($version['id']);
			$entry = @unserialize($version['entry']);
			$entry['id'] = $artid[$version['wid']];
			$version['wid'] = $artid[$version['wid']];
			$version['entry'] = serialize($entry);
			$db->insert_query('wiki_versions', $version);
			$counter['version']++;
		}
		wiki_cache_update("versions");
	}
	$lang->wiki_import_success = $lang->sprintf($lang->wiki_import_success, $counter['art'], $counter['cat'], $counter['trash'], $counter['version']);
	if($usednewcat) {
		$lang->wiki_import_success .= $lang->sprintf($lang->wiki_import_success_new_cat, $newcat['title']);
	}
	flash_message($lang->wiki_import_success, 'success');
	admin_redirect("index.php?module=wiki-article");
} else {
	$form = new Form("index.php?module=wiki-import&action=do_import", "post", "", 1);
	$form_container = new FormContainer($lang->wiki_import);

	$file = $form->generate_file_upload_box("import_file", array("id"=>"import_file"));
	$form_container->output_row($lang->wiki_import, $lang->wiki_import_desc, $file);
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->wiki_import);
	$buttons[] = $form->generate_reset_button($lang->reset);
	$form->output_submit_wrapper($buttons);
	$form->end();
}
$page->output_footer();
?>