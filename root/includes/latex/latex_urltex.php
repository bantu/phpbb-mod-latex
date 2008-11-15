<?php
/**
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (c) 2008 Andreas Fischer (bantu@phpbb.com)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* LaTeX BBcode for phpBB 3.0.x using remote service
*
* This is the cheapest and easiest way to get LaTeX integrated into your forums.
*	The latex formula will get sent to a remote host hosting a mimetext or mathtex (prefered) installation.
*	The remote host will render it and return a gif or png image.
*	That image will be stored on your webspace and will be named by a hash so you can access it later on.
*
* Requirements:
*	function file_get_contents() enabled
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class phpbb_latex_bbcode_urltex extends phpbb_latex_bbcode
{
	/**
	* The extension appended to downloaded image files
	*
	* @var	string
	*/
	protected $image_extension = 'gif';

	/**
	* Remote location to get images from
	*
	* @var	string
	*/
	protected $remote_location;

	/**
	* Constructor
	*/
	function __construct()
	{
		parent::__construct();

		$this->setup_remote_location();
	}

	/**
	* Main function
	*/
	function parse()
	{
		$this->hash = self::hash($this->text);

		$this->setup_image_location();

		if (!file_exists($this->image_location))
		{
			$this->download_image();
		}

		$this->apply_bbcode_template();
	}

	/**
	* Primitive method to download a file
	*/
	function download_image()
	{
		$url = $this->remote_location . '?' . rawurlencode($this->text);

		$fp = fopen($this->image_location, 'w');
		fwrite($fp, file_get_contents($url));
		fclose($fp);

		//print_r(get_headers($url));
	}

	/**
	* Setup remote parser location
	*/
	function setup_remote_location()
	{
		global $config;

		if (empty($config['latex_mimetex_location']))
		{
			trigger_error('LATEX_REMOTE_LOCATION_UNSPECIFIED');
		}

		$this->remote_location = $config['latex_mimetex_location'];
	}

	/**
	* Setup local image location
	*/
	function setup_image_location()
	{
		$this->image_location = $this->image_store_path . '/' . $this->hash . '.' . $this->image_extension;
	}
}

?>