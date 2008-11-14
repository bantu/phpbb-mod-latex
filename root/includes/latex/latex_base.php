<?php
/**
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (c) 2008 Andreas Fischer (bantu@phpbb.com)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* LaTeX BBcode for phpBB 3.0.x
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class phpbb_latex_bbcode
{
	protected $bbcode_tpl;		// The BBcode template
	protected $images_path;		// The path where images are stored/cached.

	/**
	* Constructor
	*/
	function __construct()
	{
		global $config, $phpbb_root_path;

		// Setup images/cache path.
		if (!isset($config['latex_images_path']))
		{
			trigger_error('LATEX_BBCODE_NOT_INSTALLED');
		}

		$this->images_path = $phpbb_root_path . $config['latex_images_path'];

		if (!is_writable($this->images_path))
		{
			trigger_error('LATEX_BBCODE_IMAGES_PATH NOT_WRITABLE');
		}

		// Setup BBcode template
		$this->bbcode_tpl = '<img src="$1" alt="$2" />';
	}

	/**
	* Returns an instance of the bbcode method object
	*/
	static function get_instance()
	{
		global $config, $phpbb_root_path, $user;

		// Setup language here ...
		$user->add_lang('mods/latex/common');

		if (!isset($config['latex_method']))
		{
			// This is a fatal error.
			trigger_error('No latex method specified. It seems like you did not run the installer yet.');
		}

		$file = $phpbb_root_path . 'includes/latex/lasex_' . $config['latex_method'] . '.' . $phpEx;

		if (!file_exists($file))
		{
			trigger_error('LATEX_BBCODE_METHOD_NOT_INSTALLED');
		}

		$class = __CLASS__ . '_' . $config['latex_method'];

		if (!class_exists($class))
		{
			include($file);
		}

		return new $class();
	}

	//function clear_cache / purge cache

	/**
	* Second parse latex bbcode
	*/
	static function second_pass($text)
	{
		$parser = self::get_instance();
		
		
		$img = latex_text_to_image($text);
		//$tpl = $this->bbcode_tpl('latex');
		$tpl = self::bbcode_tpl();

		$search = array('$2', '$1');
		$replace = array($text, $img);

		return str_replace($search, $replace, $tpl);
	}

	/**
	* Hash function latex text is hashed with
	*
	* @var $text	string		text input
	*
	* @return	string			md5 hash
	*/
	static function hash($text)
	{
		return md5($text);
	}
}

?>