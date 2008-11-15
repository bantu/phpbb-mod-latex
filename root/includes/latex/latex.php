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
* General Requirements:
*	PHP 5
*	phpBB 3.0.x
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

abstract class phpbb_latex_bbcode
{
	/**
	* LaTeX text/formular
	*
	* @var	string
	*/
	public $text;

	/**
	* Hash of $text
	*
	* @var	string
	*/
	protected $hash;

	/**
	* Parsed string after second pass
	*
	* @var	string
	*/
	public $parsed_data;

	/**
	* Relative location to the generated latex image
	*
	* @var	string
	*/
	protected $image_location;

	/**
	* The file extension of our image
	*
	* @var	string
	*/
	protected $image_extension;

	/**
	* The path where images are stored/cached
	*
	* @var	string
	*/
	protected $image_store_path;

	/**
	* Returns an instance of the bbcode method object
	*
	* @return	object		latex bbcode parser object
	*/
	static function get_instance()
	{
		global $config, $phpbb_root_path, $phpEx, $user;

		//set_config('latex_method', 'remote_mathtex');

		// Setup language here ...
		//$user->add_lang('mods/latex/common');

		if (!isset($config['latex_method']))
		{
			trigger_error('LATEX_NOT_INSTALLED');
		}

		$file = $phpbb_root_path . 'includes/latex/latex_' . $config['latex_method'] . '.' . $phpEx;

		if (!file_exists($file))
		{
			trigger_error('LATEX_METHOD_NOT_INSTALLED');
		}

		$class = __CLASS__ . '_' . $config['latex_method'];

		if (!class_exists($class))
		{
			include($file);
		}

		return new $class();
	}

	/**
	* Second pass for latex bbcode
	*
	* @param	string $text	text
	* @return	string			output after second pass
	*/
	static function second_pass($text)
	{
		static $parser = null;

		if (is_null($parser))
		{
			$parser = self::get_instance();
		}

		$parser->text = $text;
		$parser->parse();

		unset($parser->text);

		return $parser->parsed_data;
	}

	/**
	* Hash function for latex text
	*
	* @param	string $text	text
	* @return	string			hash
	*/
	static function hash($text)
	{
		return md5($text);
	}

	/**
	* Constructor
	*/
	function __construct()
	{
		$this->setup_store_path();
		$this->setup_image_location();
	}

	/**
	* Main function
	*/
	abstract function parse();

	/**
	* The BBcode template and replacements
	*/
	function apply_bbcode_template()
	{
		$tpl = '<img src="$1" alt="$2" style="vertical-align: middle;" />';

		$search = array('$1', '$2');
		$replace = array($this->image_location, $this->text);

		$this->parsed_data = str_replace($search, $replace, $tpl);
	}

	/**
	* Setup image storage path
	*/
	function setup_store_path()
	{
		global $config, $phpbb_root_path;

		if (!isset($config['latex_images_path']))
		{
			trigger_error('LATEX_NOT_INSTALLED');
		}

		$path = $phpbb_root_path . $config['latex_images_path'];

		if (!is_writable($path))
		{
			trigger_error('LATEX_IMAGES_PATH NOT_WRITABLE');
		}

		$this->image_store_path = $path;
	}

	/**
	* Setup local image location
	*/
	function setup_image_location()
	{
		$this->image_location = $this->image_store_path . '/' . $this->hash . '.' . $this->image_extension;
	}

	/**
	* Delete all $this->image_extension files in $this->images_path
	*/
	function purge_cache()
	{
		$handle = opendir($this->image_store_path);

		while (($entry = readdir($handle)) !== false)
		{
			$file = $this->image_store_path . '/' . $entry;

			// Files only. Ignore hidden files.
			if (!is_file($file) || strpos($entry, '.') === 0)
			{
				continue;
			}

			if (substr($entry, -strlen($this->image_extension)) == $this->image_extension)
			{
				unlink($file);
			}
		}

		closedir($handle);
	}
}

?>