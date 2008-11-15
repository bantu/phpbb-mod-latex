<?php
/**
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (c) 2008 Andreas Fischer (bantu@phpbb.com)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* LaTeX BBcode for phpBB 3.0.x using remote MathTex
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
	include($phpbb_root_path . 'includes/latex/latex_remote.' . $phpEx);
}

class phpbb_latex_bbcode_remote_mathtex extends phpbb_latex_bbcode_remote
{
	/**
	* The extension appended to downloaded image files
	*
	* @var	string
	*/
	public $image_extension = 'gif';

	/**
	* Array of public mathtex services as per
	* http://www.forkosh.dreamhost.com/source_mathtex.html
	*
	* @var	array
	*/
	public $services = array(
		'http://www.forkosh.dreamhost.com/mathtex.cgi',
		'http://www.cyberroadie.org/cgi-bin/mathtex.cgi',
		'http://www.problem-solving.be/cgi-bin/mathtex.cgi',
	);

	/**
	* Constructor
	*/
	function __construct()
	{
		parent::__construct();

		global $config;

		if (!empty($config['latex_remote_mathtex_service']))
		{
			$this->custom_service = $config['latex_remote_mathtex_service'];
		}
	}
}

?>