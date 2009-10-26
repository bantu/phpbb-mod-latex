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
	/**
	* New config array
	*
	* @var	array[string]
	*/
	protected $new_config = array();

	/**
	* Array of existing latex methods
	*
	* @var	array[string]
	*/
	protected $latex_methods;

	/**
	* Main method
	*
	* @return	void
	*/
	public function main($id, $mode)
	{
		global $db, $user, $auth, $cache, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;

		// Setup language 
		$user->add_lang(array('acp/posting', 'mods/latex'));

		// Submit button pushed?
		$submit = (isset($_POST['submit'])) ? true : false;

		// Add form key
		$form_key = 'acp_bbcode_latex';
		add_form_key($form_key);

		// Init configuration values if this is the first time ...
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

		// Check if a BBcode installed and method supported
		$bbcode_installed = ($this->bbcode_exists($config['latex_bbcode_tag'])) ? true : false;
		$method_supported = ($bbcode_installed && $this->method_supported($config['latex_method'])) ? true : false;

		// Variables for page output
		$display_vars = array(
			'title'	=> 'ACP_LATEX_BBCODE',
			'vars'	=> array(
				'legend1' => 'ACP_LATEX_SETTINGS',
				'latex_images_path' => array(
					'lang' => 'IMAGES_DIR',
					'validate' => 'wpath',
					'type' => 'text:25:100',
					'explain' => true,
				),
				'latex_method' => array(
					'lang' => 'LATEX_METHOD',
					'validate' => 'string',
					'type' => 'custom',
					'method' => 'select_methods',
					'explain' => true,
				),
			),
		);

		if (!$bbcode_installed)
		{
			// Insert BBcode installer at the top
			$vars = array(
				'legend3' => 'ACP_LATEX_INSTALL',
				'latex_bbcode_tag' => array(
					'lang' => 'BBCODE_NAME',
					'validate' => 'string',
					'type' => 'text:10:10',
					'explain' => true,
				),
			);

			$display_vars['vars'] = array_merge($vars, $display_vars['vars']);
			unset($vars);
		}

		$this->new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		$error = array();

		// We validate the complete config if whished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		// Some additional error checks
		if ($submit)
		{
			// Form key check
			if (!check_form_key($form_key))
			{
				$error[] = $user->lang['FORM_INVALID'];
			}

			// Moan if newly selected method is unsupported
			if (isset($cfg_array['latex_method']) && !$this->method_supported($cfg_array['latex_method']))
			{
				$error[] = $user->lang['LATEX_METHOD_NOT_SUPPORTED'];
			}

			// Install BBcode
			if (isset($cfg_array['latex_bbcode_tag']) && isset($display_vars['vars']['latex_bbcode_tag']))
			{
				// Check if BBcode already exists.
				if ($this->bbcode_exists($cfg_array['latex_bbcode_tag']))
				{
					$error[] = $user->lang['BBCODE_INVALID_TAG_NAME'];
				}
				else
				{
					$this->insert_bbcode($cfg_array['latex_bbcode_tag']);
				}
			}
		}

		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// Generally show a warning if BBcode is not installed
		if (!$bbcode_installed)
		{
			$error[] = $user->lang['LATEX_BBCODE_NOT_INSTALLED'];
		}
		else if (!$method_supported)
		{
			// Show warning if bbcode is installed but method unsupported.
			$error[] = $user->lang['LATEX_METHOD_NOT_SUPPORTED'];
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
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars,
				));

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
	*
	* @return	void
	*/
	private function insert_bbcode($bbcode_tag)
	{
		global $db, $user;

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
	*
	* @return	bool
	*/
	private function bbcode_exists($bbcode_tag)
	{
		static $hard_coded;
		global $db;

		$lower = strtolower($bbcode_tag);

		$sql = 'SELECT 1 as test
			FROM ' . BBCODES_TABLE . "
			WHERE LOWER(bbcode_tag) = '" . $db->sql_escape($lower) . "'";
		$result = $db->sql_query($sql);
		$info = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if ($info['test'] === '1')
		{
			return true;
		}

		// Make sure the user didn't pick a "bad" name for the BBCode tag. - From acp_bbcodes.php
		if (!is_array($hard_coded))
		{
			$hard_coded = array('code', 'quote', 'quote=', 'attachment', 'attachment=', 'b', 'i', 'url', 'url=', 'img', 'size', 'size=', 'color', 'color=', 'u', 'list', 'list=', 'email', 'email=', 'flash', 'flash=');
		}

		if (in_array($lower, $hard_coded))
		{
			return true;
		}

		return false;
	}

	/**
	* Get available LaTeX methods
	*
	* @return	array[string]
	*/
	private function get_methods()
	{
		global $phpbb_root_path, $phpEx;

		$path = $phpbb_root_path . 'includes/latex/';
		$methods = array();

		$str_match = 'latex_';
		$len_match = strlen($str_match);
		$len_phpex = strlen('.' . $phpEx);

		$handle = opendir($path);
		while (($entry = readdir($handle)) !== false)
		{
			// Make sure $entry begins with $str_match
			if (strpos($entry, $str_match) !== 0)
			{
				continue;
			}

			// latex_$method.$phpEx
			$method = substr($entry, $len_match, -$len_phpex);
			if (empty($method))
			{
				continue;
			}

			$file = $path . $entry;
			if (!file_exists($file))
			{
				continue;
			}

			$class = 'phpbb_latex_bbcode_' . $method;
			if (!class_exists($class))
			{
				include($file);
			}

			// Not the best way ...
			$obj = new $class();

			$methods[$method] = array(
				'name'		=> ucfirst($method),
				'supported' => $obj->is_supported(),
			);

			unset($obj);
		}
		closedir($handle);

		return $methods;
	}

	/**
	* Returns whether a method is supported or not
	*
	* @return	bool
	*/
	protected function method_supported($method)
	{
		if (empty($method))
		{
			return false;
		}

		if (!isset($this->latex_methods))
		{
			$this->latex_methods = $this->get_methods();
		}

		if (isset($this->latex_methods[$method]) && $this->latex_methods[$method]['supported'])
		{
			return true;
		}

		return false;
	}

	/**
	* Radio button for available Latex methods
	*
	* @return	string
	*/
	public function select_methods($value, $key)
	{
		$html = '';
		$name = 'config[latex_method]';

		if (!isset($this->latex_methods))
		{
			$this->latex_methods = $this->get_methods();
		}

		foreach ($this->latex_methods as $method => $details)
		{
			$selected = ($details['supported'] && $value == $method) ? ' checked="checked"' : '';
			$disabled = (!$details['supported']) ? ' disabled="disabled"' : '';

			$html .= '<label><input type="radio" name="' . $name . '" value="' . $method . '"' . $selected . $disabled . ' class="radio" /> ' . $details['name'] . '</label>';
		}
		
		return $html;
	}
}
