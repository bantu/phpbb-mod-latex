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
*	PHP 5 or higher
*
* Known Issues:
*	The LaTeX BBcode cannot easily be set to parse before every other BBcode (but it should)
*		See: http://www.phpbb.com/bugs/ascraeus/40215#post133075 for details
*		(This actually stops the MOD from being finished)
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
	* Hash method used to hash text
	* @link	hash()
	*
	* @var	string
	*/
	protected static $hash_method = 'md5';

	/**
	* LaTeX text/formular
	*
	* @var	string
	*/
	protected $text;

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
	public static function get_instance()
	{
		global $config, $phpbb_root_path, $phpEx, $user;

		// Setup language here ...
		$user->add_lang('mods/latex');

		if (!isset($config['latex_method']))
		{
			trigger_error('LATEX_NOT_INSTALLED', E_USER_ERROR);
		}

		$file = $phpbb_root_path . 'includes/latex/latex_' . $config['latex_method'] . '.' . $phpEx;
		if (!file_exists($file))
		{
			trigger_error('LATEX_METHOD_NOT_INSTALLED', E_USER_ERROR);
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

		$renderer->set_text($text);
		$renderer->render();

		return $renderer->get_result();
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
	* @return	bool		false if unsupported
	*/
	abstract public function is_supported();

	/**
	* Sets the text attribute and generates hash
	*
	* @param	string	$text
	* @return	void
	*/
	public function set_text($text)
	{
		$this->text = $text;
		$this->hash = hash(self::$hash_method, $text);
	}

	/**
	* Builds and returns the final result
	* Includes the BBcode template
	*
	* @return	string
	*/
	public function get_result()
	{
		$src = $this->image_store_path . $this->hash . '.' . $this->image_extension;
		$alt = htmlspecialchars($this->text);

		return '<img src="' . $src . '" alt="' . $alt . '" style="vertical-align: middle;" />';
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
				trigger_error('LATEX_NOT_INSTALLED', E_USER_ERROR);
			}

			$this->image_store_path = $phpbb_root_path . $config['latex_images_path'];

			// Add / if necessary
			if (substr($this->image_store_path, -1) !== '/')
			{
				$this->image_store_path .= '/';
			}

			if (!is_readable($this->image_store_path))
			{
				// Path specified but not readable by php/webserver
				trigger_error('LATEX_IMAGES_PATH_NOT_READABLE', E_USER_ERROR);
			}
		}

		// Path specified and readable
		if ($check_writeable && !is_writable($this->image_store_path))
		{
			// Path not writable
			trigger_error('LATEX_IMAGES_PATH_NOT_WRITABLE', E_USER_ERROR);
		}
	}

	/**
	* Deletes all files in $this->image_store_path
	*
	* @return	 void
	*/
	public function purge_image_cache()
	{
		$handle = opendir($this->image_store_path);

		while (($entry = readdir($handle)) !== false)
		{
			$file = $this->image_store_path . $entry;

			// Files only. Ignore hidden files.
			if (!is_file($file) || strpos($entry, '.') === 0)
			{
				continue;
			}

			// Skip index file.
			if (strpos($entry, 'index') === 0)
			{
				continue;
			}

			unlink($file);
		}

		closedir($handle);
	}
}
