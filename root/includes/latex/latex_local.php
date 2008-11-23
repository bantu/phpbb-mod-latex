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
	* Main render function
	*
	* @return	void
	*/
	public function render()
	{
		$this->hash = self::hash($this->text);

		if (!file_exists($this->get_image_location()))
		{
			$this->setup_tmp_path();

			// Create image.
			$this->create_image();
		}
	}

	/**
	* Method that tells us whether the current 
	* php setup supports this latex method or not 
	*
	* @return	bool
	*/
	public static function is_supported()
	{
		$functions = array('exec', 'copy', 'fopen', 'fwrite');
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

		// Create image in temporary folder
		$status = $this->create_image_helper();

		chdir($cwd);

		// Copy image to images path
		$src = $this->tmp_path . '/' . $this->hash . '.' . $this->image_extension;
		$dst = $this->get_image_location());

		if (rename($src, $dst) === false)
		{
			copy($src, $dst);
		}

		// Clean up tmp path
		$this->clean_tmp_path();

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
		$status = fwrite($fp, self::wrap_text($this->text));
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
		exec($this->convert_location . ' -density ' . $this->density . ' -trim -transparent "#FFFFFF"' . $this->hash . '.ps ' . $this->hash . '.' . $this->image_extension);

		if (!file_exists($this->hash . '.' . $this->image_extension))
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
	* Setup temporary path
	*
	* @return	void
	*/
	protected function setup_tmp_path()
	{
		if (empty($this->tmp_path))
		{
			global $phpbb_root_path;

			$this->tmp_path = $phpbb_root_path . '/cache';
		}

		// Assume phpBB cache folder is writeable
	}

	/**
	* Deletes all temporary files $this->tmp_path
	*
	* @return void
	*/
	protected function clean_tmp_path()
	{
		foreach (array('tex', 'dvi', 'ps', $this->image_extension, 'log', 'aux') as $ext)
		{
			$file = $this->tmp_path . '/' . $this->hash . '.' . $ext;

			if (file_exists($file))
			{
				unlink($file);
			}
		}
	}
}

?>