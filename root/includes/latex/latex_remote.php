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

if (!class_exists('phpbb_latex_bbcode'))
{
	include($phpbb_root_path . 'includes/latex/latex.' . $phpEx);
}

class phpbb_latex_bbcode_remote extends phpbb_latex_bbcode
{
	/**
	* Array of public service servers as per
	* http://www.forkosh.dreamhost.com/source_mathtex.html
	*
	* @var	array
	*/
	protected $services = array(
		// MathTex
		'http://www.forkosh.dreamhost.com/mathtex.cgi',
		'http://www.cyberroadie.org/cgi-bin/mathtex.cgi',
		'http://www.problem-solving.be/cgi-bin/mathtex.cgi',

		// MimeTex
		'http://mitaub.sourceforge.net/cgi-bin/mimetex.cgi',
		'http://www.forkosh.dreamhost.com/mimetex.cgi',
	);

	/**
	* Supported formats
	*
	* @var	array
	*/
	protected $supported_formats = array(
		'image/gif' => 'gif',
		'image/png' => 'png',
	);

	/**
	* Main function
	*/
	function parse()
	{
		$this->hash = self::hash($this->text);

		$file_exists = false;
		foreach ($this->supported_formats as $extension)
		{
			$this->image_extension = $extension;

			if (file_exists($this->get_image_location()))
			{
				$file_exists = true;

				break;
			}
		}

		if (!$file_exists)
		{
			$this->download_image();
		}

		$this->apply_bbcode_template();
	}

	/**
	* Download images from remote service
	*
	* @return	bool		false on error, true on success
	*/
	function download_image()
	{
		foreach ($this->services as $service)
		{
			$url = $service . '?' . rawurlencode($this->text);
			$file = file_get_contents($url);
			$headers = get_headers($url, 1);

			if (empty($headers['Content-Type']))
			{
				continue;
			}

			$mime = $headers['Content-Type'];
			if (!isset($this->supported_formats[$mime]))
			{
				continue;
			}

			$this->image_extension = $this->supported_formats[$mime];

			$fp = fopen($this->get_image_location(), 'w');
			fwrite($fp, $file);
			fclose($fp);

			return true;
		}

		return false;
	}
}

?>