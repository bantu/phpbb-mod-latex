<?php
/**
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (c) 2003 Benjamin Zeiss <zeiss@math.uni-goettingen.de>
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
* Additional Requirements:
*	PHP function exec() enabled
*	LaTeX binaries installed (latex, dvips)
*	ImageMagick installed (convert)
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
	protected $image_extension = 'png';

	/**
	* Temporary path where operations are performed
	*
	* @var	string
	*/
	protected $tmp_path;

	/**
	* Location of latex binary (latex)
	*
	* @var	string
	*/
	protected $latex_location;

	/**
	* Location of dvips binary (latex)
	*
	* @var	string
	*/
	protected $dvips_location;

	/**
	* Location of convert binary (imagemagick)
	*
	* @var	string
	*/
	protected $convert_location;

	/**
	* Font size (used by latex)
	*
	* @var	int
	*/
	protected $fontsize = 11;

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
		// Setup store path for reading
		$this->setup_store_path();

		if (!file_exists($this->image_store_path . $this->hash . '.' . $this->image_extension))
		{
			// Setup image storage path and temporary path.
			$this->setup_store_path(true);
			$this->setup_tmp_path();

			// Create image.
			$this->create_image();
		}
	}

	/**
	* Method that tells us whether the current 
	* php setup supports this latex method or not 
	*
	* @return	bool		false if unsupported
	*/
	public function is_supported()
	{
		$functions = array('exec', 'copy', 'fopen', 'fwrite');
		foreach ($functions as $function)
		{
			if (!function_exists($function) || !is_callable($function))
			{
				return false;
			}
		}
		// exec() is available

		// @TODO: This won't work on systems not supporting 'which'
		if (!$this->detect_binaries())
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
		$this->detect_binaries();

		$cwd = getcwd();
		chdir($this->tmp_path);

		// Write text to temporary .tex file
		$fp = fopen($this->hash . '.tex', 'wb');
		fwrite($fp, $this->wrap_text($this->text));
		fclose($fp);

		$cmds = array(
			// Convert .tex to .dvi
			array(
				'require'	=> $this->hash . '.tex',
				'exec'		=> $this->latex_location . ' --interaction=nonstopmode ' . $this->hash . '.tex',
			),
			// Convert .dvi to .ps
			array(
				'require'	=> $this->hash . '.dvi',
				'exec'		=> $this->dvips_location . ' -E ' . $this->hash . '.dvi' . ' -o ' . $this->hash . '.ps',
			),
			// Convert .ps to image
			array(
				'require'	=> $this->hash . '.ps',
				'exec'		=> $this->convert_location . ' -density ' . $this->density . ' -trim -transparent "#FFFFFF" ' . $this->hash . '.ps ' . $this->hash . '.' . $this->image_extension,
			),
			// Check if image exists
			array(
				'require'	=> $this->hash . '.' . $this->image_extension,
			),
		);

		$status = true;
		foreach ($cmds as $cmd)
		{
			if (!file_exists($cmd['require']))
			{
				$status = false;
				break;
			}

			if (!empty($cmd['exec']))
			{
				exec($cmd['exec']);
			}
		}

		unset($cmds);
		chdir($cwd);

		// Copy image to storage path
		if ($status)
		{
			$src = $this->tmp_path . $this->hash . '.' . $this->image_extension;
			$dst = $this->image_store_path . $this->hash . '.' . $this->image_extension;

			if (rename($src, $dst) === false)
			{
				copy($src, $dst);
			}
		}

		// Clean up temp path
		$this->clean_tmp_path();

		return $status;
	}

	/**
	* Deletes all temporary files in $this->images_path
	*
	* @return	string
	*/
	protected function wrap_text($text) {
		$out = '';

		$out .= '\documentclass[' . $this->fontsize . "pt]{article}\n";
		$out .= "\usepackage[utf8]{inputenc}\n";
		$out .= "\usepackage{amsmath}\n";
		$out .= "\usepackage{amsfonts}\n";
		$out .= "\usepackage{amssymb}\n";
		$out .= "\pagestyle{empty}\n";
		$out .= "\begin{document}\n";
		$out .= '$' . htmlspecialchars_decode($text) . "$\n";
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

			$this->tmp_path = $phpbb_root_path . 'cache/';
		}

		// Assume phpBB cache folder is writeable
	}

	/**
	* Deletes all temporary files $this->tmp_path
	*
	* @return	void
	*/
	protected function clean_tmp_path()
	{
		foreach (array('tex', 'dvi', 'ps', $this->image_extension, 'log', 'aux') as $ext)
		{
			$file = $this->tmp_path . $this->hash . '.' . $ext;

			if (file_exists($file))
			{
				unlink($file);
			}
		}
	}

	/**
	* Autodetects required binaries (unix systems only)
	*
	* @return	bool
	*/
	protected function detect_binaries()
	{
		foreach (array('latex', 'dvips', 'convert') as $binary)
		{
			$guess = exec("which $binary");

			if (!file_exists($guess))
			{
				return false;
			}

			$property = $binary . '_location';
			$this->$property = $guess;
		}

		return true;
	}
}

?>