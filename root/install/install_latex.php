<?php
/**
*
* @package install
* @version $Id$
* @copyright (c) 2008 Andreas Fischer (bantu@phpbb.com)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();

// Report all errors, except notices
error_reporting(E_ALL ^ E_NOTICE);

/**
* Permission check
*/ 
if (!$auth->acl_get('a_bbcode'))
{
	trigger_error('NOT_AUTHORISED');
}

/**
* Setup configuration values
*/
$config_defaults = array(
	'latex_method'				=> 'remote',
	'latex_images_path'			=> 'images/latex',
);

foreach ($config_defaults as $config_value => $default)
{
	if (!isset($config[$config_value]))
	{
		set_config($config_value, $default);
	}
}

/**
* Handle BBcode insertion
*/
$bbcode_tag = 'LaTeX';

// Check if BBcode already exists.
$sql = 'SELECT 1 as test
	FROM ' . BBCODES_TABLE . "
	WHERE LOWER(bbcode_tag) = '" . $db->sql_escape(strtolower($bbcode_tag)) . "'";
$result = $db->sql_query($sql);
$info = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

if ($info['test'] === '1')
{
	$user->add_lang('acp/posting');
	trigger_error('BBCODE_INVALID_TAG_NAME', E_USER_WARNING);
}

// Get max_bbcode_id - borrowed from acp_bbcodes
$sql = 'SELECT MAX(bbcode_id) as max_bbcode_id
	FROM ' . BBCODES_TABLE;
$result = $db->sql_query($sql);
$row = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

if ($row)
{
	$bbcode_id = $row['max_bbcode_id'] + 1;

	// Make sure it is greater than the core bbcode ids...
	if ($bbcode_id <= NUM_CORE_BBCODES)
	{
		$bbcode_id = NUM_CORE_BBCODES + 1;
	}
}
else
{
	$bbcode_id = NUM_CORE_BBCODES + 1;
}

if ($bbcode_id > 1511)
{
	$user->add_lang('acp/posting');
	trigger_error('TOO_MANY_BBCODES', E_USER_WARNING);
}

// First pass replace for {TEXT} - borrowed from acp_bbcodes
$first_pass_replace = "str_replace(array(\"\\r\\n\", '\\\"', '\\'', '(', ')'), array(\"\\n\", '\"', '&#39;', '&#40;', '&#41;'), trim('\$1'))";

$sql_ary = array(
	// Basics
	'bbcode_id'					=> $bbcode_id,
	'bbcode_tag'				=> $bbcode_tag,
	'bbcode_match'				=> "[$bbcode_tag]{TEXT}[/$bbcode_tag]",
	'bbcode_tpl'				=> '',

	// Posting
	'display_on_posting'		=> false,
	'bbcode_helpline'			=> '',

	// First pass
	'first_pass_match'			=> "!\[$bbcode_tag\](.*?)\[/$bbcode_tag\]!ise",
	'first_pass_replace'		=> "'[$bbcode_tag:\$uid]'.$first_pass_replace.'[/$bbcode_tag:\$uid]'",

	// Second pass
	'second_pass_match'			=> "!\[$bbcode_tag:\$uid\](.*?)\[/$bbcode_tag:\$uid\]!ise",
	'second_pass_replace'		=> "phpbb_latex_bbcode::second_pass('\$1')",
);

// Actually insert the BBcode. Add log entry.
$db->sql_query('INSERT INTO ' . BBCODES_TABLE . $db->sql_build_array('INSERT', $sql_ary));
add_log('admin', 'LOG_BBCODE_ADD', $bbcode_tag);

// Purge cache
$cache->purge();

?>