<?php
/**
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (C) 2003 Benjamin Zeiss <zeiss@math.uni-goettingen.de>
* @copyright (c) 2008 Andreas Fischer <bantu@phpbb.com>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* LaTeX BBcode for phpBB 3.0.x using latex binaries
*	The idea is based on class.latexrender.php created by Benjamin Zeiss (see copyright).
*	latexrender version 0.8 was originally released under the GNU LGPL version 2.1.
*
* This is a quick and professional way to get LaTeX integrated into your forums:
*	Writes the LaTeX formula into a wrapped .tex file
*	Creates a .dvi file from the .tex file using latex
*	Converts the .dvi file to postscript using dvips
*	Trims and converts the .ps file to image using imagemagick
*	The generated image will be stored on the webspace.
*
* Requirements:
*	PHP function exec() enabled
*	LaTeX binaries installed (latex, dvips)
*	ImageMagick installed (convert, identify)
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

class phpbb_latex_bbcode_local extends phpbb_latex_bbcode
{
	/**
	* Array of commands that need to be executed to create image
	*
	* @var	array[string][string]
	*/
	protected $commands = array(
		
	);

	/**
	* Supported formats
	*
	* @var	array[int][string]
	*/
	protected $supported_formats = array('gif');

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->image_extension = $this->supported_formats[0];

		parent::__construct();
	}

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
		// Implicit else. Need to create image.

		// Setup path for writing.
		$this->setup_store_path(true);

		// Create image.
		$this->create_image();
	}

	/**
	* Method that tells us whether the current 
	* php setup supports this latex method or not 
	*
	* @return	bool
	*/
	public static function is_supported()
	{
		if (!function_exists('exec') || !is_callable('exec'))
		{
			return false;
		}

		return true;
	}

	/**
	* Creates the LaTeX image
	*
	* @return	bool		false on error, true on success
	*/
	protected function create_image()
	{
		return false;
	}
}

?>