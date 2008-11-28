<?php
/**
*
* @package acp
* @version $Id$
* @copyright (c) 2008 Andreas Fischer <bantu@phpbb.com>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @package module_install
*/
class acp_bbcode_latex_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_bbcode_latex',
			'title'		=> 'ACP_BBCODE_LATEX',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'general'		=> array('title' => 'ACP_BBCODE_LATEX', 'auth' => 'acl_a_bbcode', 'cat' => array('ACP_MESSAGES')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>