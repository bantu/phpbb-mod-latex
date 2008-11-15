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

if (!class_exists('phpbb_latex_bbcode_remote'))
{
	include($phpbb_root_path . 'includes/latex/latex.' . $phpEx);
}

class phpbb_latex_bbcode_remote extends phpbb_latex_bbcode
{
	/**
	* Array of public service servers
	*
	* @var	array
	*/
	public $services;

	/**
	* Additional custom server
	*
	* @var	mixed	string or array
	*/
	public $custom_service;

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
	* Download images from remote service
	*/
	function download_image()
	{
		static $services;

		if (!is_array($services))
		{
			$services = $this->get_remote_services();
		}

		foreach ($services as $service)
		{
			if (file_exists($this->image_location))
			{
				break;
			}

			$url = $service . '?' . rawurlencode($this->text);

			self::download($url, $this->image_location);
		}
	}

	/**
	* Primitive method to download a file
	*/
	static function download($from, $to)
	{
		$fp = fopen($to, 'w');
		fwrite($fp, file_get_contents($from));
		fclose($fp);
	}

	/**
	* Get remote services
	*/
	function get_remote_services()
	{
		$services = $this->services;

		if (!empty($this->custom_service))
		{
			$custom_service = $this->custom_service;

			if (!is_array($custom_service))
			{
				$custom_service = array($custom_service);
			}

			$services = array_merge($custom_service, $services);
		}

		return $services;
	}
}

?>