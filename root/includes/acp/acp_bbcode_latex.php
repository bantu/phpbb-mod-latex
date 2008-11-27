<?php
/**
*
* @package acp
* @version $Id$
* @copyright (c) 2008 Andreas Fischer <bantu@phpbb.com>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* @package acp
*/
class acp_bbcode_latex
{
	var $u_action;
	var $new_config = array();

	function main($id, $mode)
	{
		global $db, $user, $auth, $cache, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		$user->add_lang('acp/posting');
		$user->add_lang('mods/latex/common');

		$submit = (isset($_POST['submit'])) ? true : false;

		$form_key = 'acp_bbcode_latex';
		add_form_key($form_key);

		// Init configuration values
		$config_defaults = array(
			'latex_bbcode_tag'			=> 'LaTeX',
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

		switch ($mode)
		{
			case 'install':
				$bbcode_tag = request_var('bbcode_tag', 'LaTeX');

				$this->insert_bbcode($bbcode_tag);
			break;

			case 'settings':
				$display_vars = array(
					'title'	=> 'ACP_LATEX_BBCODE',
					'vars'	=> array(
						'legend1'			=> 'ACP_SETTINGS',
						'latex_images_path'		=> array('lang' => 'UPLOAD_DIR', 'validate' => 'wpath', 'type' => 'text:25:100', 'explain' => true),
				));
			break;

			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			break;
		}

		$this->new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		$error = array();

		// We validate the complete config if whished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
		}

		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		foreach ($display_vars['vars'] as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
			{
				continue;
			}

			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

			if ($submit)
			{
				set_config($config_name, $config_value);
			}
		}

		if ($submit)
		{
			add_log('admin', 'LOG_CONFIG_SETTINGS');

			trigger_error($user->lang['CONFIG_UPDATED'] . adm_back_link($this->u_action));
		}

		$this->tpl_name = 'acp_board';
		$this->page_title = $display_vars['title'];

		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang[$display_vars['title']],
			'L_TITLE_EXPLAIN'	=> $user->lang[$display_vars['title'] . '_EXPLAIN'],

			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),

			'U_ACTION'			=> $this->u_action,
		));

		// Output relevant page
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}
			
			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);
			
			if (empty($content))
			{
				continue;
			}
			
			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> $content,
			));

			unset($display_vars['vars'][$config_key]);
		}
	}

	/**
	* Insert LaTeX bbcode into phpBB
	*/
	function insert_bbcode($bbcode_tag)
	{
		global $db, $user;

		// Check if BBcode already exists.
		if ($this->bbcode_exists($bbcode_tag))
		{
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
			'display_on_posting'		=> true,
			'bbcode_helpline'			=> isset($user->lang['LATEX_BBCODE_HELPLINE']) ? sprintf($user->lang['LATEX_BBCODE_HELPLINE'], $bbcode_tag) : '',

			// First pass
			'first_pass_match'			=> "!\[$bbcode_tag\](.*?)\[/$bbcode_tag\]!ise",
			'first_pass_replace'		=> "'[$bbcode_tag:\$uid]'.$first_pass_replace.'[/$bbcode_tag:\$uid]'",

			// Second pass
			'second_pass_match'			=> "!\[$bbcode_tag:\$uid\](.*?)\[/$bbcode_tag:\$uid\]!ise",
			'second_pass_replace'		=> "phpbb_latex_bbcode::second_pass('\$1')",
		);

		$db->sql_query('INSERT INTO ' . BBCODES_TABLE . $db->sql_build_array('INSERT', $sql_ary));

		set_config('latex_bbcode_tag', $bbcode_tag);

		add_log('admin', 'LOG_BBCODE_ADD', $bbcode_tag);
	}

	/**
	* Checks if specified BBcode exists
	*/
	function bbcode_exists($bbcode_tag)
	{
		global $db;

		$sql = 'SELECT 1 as test
			FROM ' . BBCODES_TABLE . "
			WHERE LOWER(bbcode_tag) = '" . $db->sql_escape(strtolower($bbcode_tag)) . "'";
		$result = $db->sql_query($sql);
		$info = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if ($info['test'] === '1')
		{
			return true;
		}

		return false;
	}

	/**
	* Get available LaTeX methods
	*/
	function get_methods()
	{
		global $phpbb_root_path, $phpEx;

		$methods = array();

		$str_match = 'latex';
		$neg_match = $str_match . '.' . $phpEx;
		$len_match = strlen($str_match) + 1;
		$len_phpex = strlen($phpEx) + 1;

		$handle = opendir($phpbb_root_path . 'includes/latex');
		while (($entry = readdir($handle)) !== false)
		{
			if ($entry == $neg_match)
			{
				continue;
			}

			if (strpos($entry, $str_match) !== 0)
			{
				continue;
			}

			$methods[] = substr($entry, $len_match, -$len_phpex);
		}
		closedir($handle);

		return $methods;
	}
}

?>