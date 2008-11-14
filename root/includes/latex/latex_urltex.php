<?php
/**
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (c) 2008 Andreas Fischer (bantu@phpbb.com)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* LaTeX BBcode for phpBB 3.0.x using remote 
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
	private $extension = 'gif';

	/**
	* Primitive method to download a file from a specified url.
	*
	* @var $source_url		string		source url
	* @var $destination		string		filename of local file
	*
	* @return	void
	*/
	static function download_file($source_url, $destination)
	{
		$fp = fopen($destination, 'w');
		fwrite($fp, file_get_contents($source_url));
		fclose($fp);

		print_r(get_headers($source_url));
	}

	/**
	* @return mixed		false if error, else relative url string
	*/
	function latex_text_to_image($text)
	{
		global $phpbb_root_path, $config;

		$method = $config['latex_method'];

		$extension = latex_method_to_extension($method);
		if ($extension === false)
		{
			return false;
		}

		$hash = latex_hash($text);

		$store_path	= $phpbb_root_path . $config['latex_images_path'];
		$local_file	= $store_path . '/' . $hash . '.' . $extension;

		if (file_exists($local_file))
		{
			return $local_file;
		}

		// The image does not exist yet, we need to create it or get it ...
		if ($method == 'mimetex')
		{
			// Check if mimetex location is an url.
			$url_info	= parse_url($config['latex_mimetex_location']);
			$url_valid	= (isset($url_info['scheme']) && $url_info['scheme'] == 'http') ? true : false;

			if ($url_valid)
			{
				$source_url	= $config['latex_mimetex_location'] . '?' . rawurlencode($text);

				latex_get_file($source_url, $local_file);
			}
		}

		return $local_file;
	}
}

?>