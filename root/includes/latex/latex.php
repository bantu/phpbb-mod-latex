<?php
/**
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (c) 2008 Andreas Fischer <bantu@phpbb.com>
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
	* The file extension of the generated image
	*
	* @var	string
	*/
	protected $image_extension;

	/**
	* The path where images are stored or cached
	*
	* @var	string
	*/
	protected $image_store_path;

	/**
	* Array of supported formats
	*
	* @var	array
	*/
	protected $supported_formats;

	/**
	* Returns an instance of the bbcode method object
	*
	* @return	object		latex bbcode parser object
	*/
	public static function get_instance()
	{
		global $config, $phpbb_root_path, $phpEx, $user;

		// Setup language here ...
		$user->add_lang('mods/latex/common');

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
	public static function second_pass($text)
	{
		static $renderer = null;

		if (is_null($renderer))
		{
			$renderer = self::get_instance();
		}

		$renderer->text = $text;
		$renderer->render();
		$result = $renderer->get_result();

		unset($renderer->text);

		return $result;
	}

	/**
	* Hash function for latex text
	*
	* @param	string $text	text
	* @return	string			hash
	*/
	public static function hash($text)
	{
		return md5($text);
	}

	/**
	* Constructor
	*/
	public function __construct()
	{
		// Setup path for reading.
		$this->setup_store_path();
	}

	/**
	* Main render function
	*
	* @return	void
	*/
	abstract public function render();

	/**
	* Method that tells us whether the current 
	* php setup supports this latex method or not 
	*
	* @return	bool
	*/
	abstract public static function is_supported();

	/**
	* Builds and returns the final result
	* Includes the BBcode template
	*
	* @return	string
	*/
	public function get_result()
	{
		$src = $this->get_image_location();
		$alt = $this->text;

		return '<img src="' . $src . '" alt="' . $alt . '" style="vertical-align: middle;" />';
	}

	/**
	* Get local image location
	*
	* @return	string		image location
	*/
	protected function get_image_location()
	{
		return $this->image_store_path . '/' . $this->hash . '.' . $this->image_extension;
	}

	/**
	* Guess image location
	*
	* @return	bool		true on success
	*/
	protected function guess_image_location()
	{
		$ext = $this->image_extension;

		foreach ($this->supported_formats as $extension)
		{
			$this->image_extension = $extension;

			if (file_exists($this->get_image_location()))
			{
				return true;
			}
		}

		$this->image_extension = $ext;

		return false;
	}

	/**
	* Setup image storage path
	*
	* @return	void
	*/
	protected function setup_store_path($check_writeable = false)
	{
		if (empty($this->image_store_path))
		{
			global $config, $phpbb_root_path;

			if (!isset($config['latex_images_path']))
			{
				// No path specified
				trigger_error('LATEX_NOT_INSTALLED');
			}

			$this->image_store_path = $phpbb_root_path . $config['latex_images_path'];
			if (!is_readable($this->image_store_path))
			{
				// Path specified but not readable by php/webserver
				trigger_error('LATEX_IMAGES_PATH_NOT_READABLE');
			}
		}

		// Path specified and readable
		if ($check_writeable && !is_writable($this->image_store_path))
		{
			// Path not writable
			trigger_error('LATEX_IMAGES_PATH_NOT_WRITABLE');
		}
	}

	/**
	* Delete all image files in $this->image_store_path
	*
	* @return void
	*/
	public function purge_cache()
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

			foreach ($this->supported_formats as $extension)
			{
				if (substr($entry, -strlen($extension)) == $extension)
				{
					unlink($file);
				}
			}
		}

		closedir($handle);
	}
}

?>