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
	* The file extension of the generated image
	*
	* @var	string
	*/
	protected $image_extension = 'gif';

	/**
	* Temporary path where operations are performed
	*
	* @var	string
	*/
	protected $tmp_path;

	/**
	* Font size (used by latex)
	*
	* @var	int
	*/
	protected $fontsize = 10;

	/**
	* Formular density (used by imagemagick)
	*
	* @var	int
	*/
	protected $density = 120;

	/**
	* Constructor
	*/
	public function __construct()
	{
		global $phpbb_root_path;

		$this->tmp_path = $phpbb_root_path . '/cache';

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
		$functions = array('exec', 'fopen', 'fwrite');
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
	* Creates the LaTeX image
	*
	* @return	bool		false on error, true on success
	*/
	protected function create_image()
	{
		$cwd = getcwd();
		chdir($this->tmp_path);

		$status = $this->create_image_helper();
		$this->clean_up();

		chdir($cwd);

		return $status;
	}

	/**
	* Helper method for $this->create_image()
	*
	* @return	bool		false on error
	*/
	private function create_image_helper()
	{
		// Write .tex
		$fp = fopen($this->hash . '.tex', 'wb');
		$status = fwrite($fp, wrap_text($this->text));
		fclose($fp);

		if (!file_exists($this->hash . '.tex'))
		{
			return false;
		}

		// Convert .tex to .dvi
		exec($this->latex_location . ' --interaction=nonstopmode ' . $this->hash . '.tex');

		if (!file_exists($this->hash . '.dvi'))
		{
			return false;
		}

		// Convert .dvi to .ps
		exec($this->dvips_location . ' -E ' . $this->hash . '.dvi' . ' -o ' . $this->hash . '.ps');

		if (!file_exists($this->hash . '.ps'))
		{
			return false;
		}

		// Convert .ps to image
		exec($this->convert_location . ' -density ' . $this->density . ' -trim -transparent "#FFFFFF"' . $this->hash . '.ps ' . $this->get_image_location());

		if (!file_exists($this->get_image_location()))
		{
			return false;
		}

		return true;
	}

	/**
	* Deletes all temporary files in $this->images_path
	*
	* @return void
	*/
	protected static function wrap_text($text) {
		$out = '';

		$out .= '\documentclass[' . $this->fontsize . "pt]{article}\n";
		$out .= "\usepackage[latin1]{inputenc}\n";
		$out .= "\usepackage{amsmath}\n";
		$out .= "\usepackage{amsfonts}\n";
		$out .= "\usepackage{amssymb}\n";
		$out .= "\pagestyle{empty}\n";
		$out .= "\begin{document}\n";
		$out .= '$' . $text . "$\n";
		$out .= "\end{document}\n";

		return $out;
	}

	/**
	* Deletes all temporary files in $this->images_path
	*
	* @return void
	*/
	public function clean_up()
	{
		$handle = opendir($this->tmp_path);

		while (($entry = readdir($handle)) !== false)
		{
			$file = $this->tmp_path . '/' . $entry;

			// Files only. Ignore hidden files.
			if (!is_file($file) || strpos($entry, '.') === 0)
			{
				continue;
			}

			foreach (array('tex', 'dvi', 'ps', 'log', 'aux')) as $extension)
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