<?php
/**
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (c) 2008 Andreas Fischer <bantu@phpbb.com>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* LaTeX BBcode for phpBB 3.0.x using remote service
*
* This is the cheapest and easiest way to get LaTeX integrated into your forums.
*	The latex formula will get sent to a remote server hosting a mimetext or mathtex (prefered) installation.
*	The remote host will render it for us and return a gif or png image.
*	The returned image will be stored on the webspace.
*
* Requirements:
*	allow_url_fopen allowed
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
	* @var	array[int][string]
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
	* @var	array[string][string]
	*/
	protected $supported_formats = array(
		'image/gif' => 'gif',
		'image/png' => 'png',
	);

	/**
	* Main render function
	*
	* @return	void
	*/
	public function render()
	{
		$this->hash = self::hash($this->text);

		if ($this->guess_image_location())
		{
			// No need to do anything.
			return;
		}
		// Implicit else. Need to download image.

		// Setup path for writing.
		$this->setup_store_path(true);

		// Download image.
		$this->download_image();
	}

	/**
	* Method that tells us whether the current 
	* php setup supports this latex method or not 
	*
	* @return	bool
	*/
	public static function is_supported()
	{
		if ((@ini_get('allow_url_fopen') != '1' && strtolower(@ini_get('allow_url_fopen')) !== 'on'))
		{
			return false;
		}

		$functions = array('file_get_contents', 'fopen', 'fwrite');
		foreach ($functions as $function)
		{
			if (!function_exists($function) || !is_callable($function))
			{
				return false;
			}
		}

		return true;
	}

	/**
	* Download images from remote service
	*
	* @return	bool		false on error, true on success
	*/
	protected function download_image()
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

			$fp = fopen($this->get_image_location(), 'wb');
			fwrite($fp, $file);
			fclose($fp);

			return true;
		}

		return false;
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
}

?>