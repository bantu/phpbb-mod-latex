<?php
/**
*
* common [English]
*
* @package phpbb_latex_bbcode
* @version $Id$
* @copyright (c) 2005 phpBB Group, 2008 Andreas Fischer (bantu@phpbb.com)
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'ACP_LATEX_BBCODE'					=> 'LaTeX BBcode',
	'ACP_LATEX_BBCODE_EXPLAIN'			=> 'Latex BBcode EXPLAIN',

	'LATEX_BBCODE_HELPLINE'				=> 'Latex formula: [%1$s]Latex formula[%1$s]',
	'LATEX_BBCODE_NOT_INSTALLED'		=> 'Latex BBcode is currently not installed.',

	'LATEX_IMAGES_PATH_NOT_READABLE'	=> 'The specified path for Latex images is not readable.',
	'LATEX_IMAGES_PATH_NOT_WRITABLE'	=> 'The specified path for Latex images is not writeable.',
	'LATEX_METHOD_NOT_INSTALLED'		=> 'The selected Latex method is not installed or is missing.',
	'LATEX_METHOD_NOT_SUPPORTED'		=> 'The selected Latex method is not supported by your PHP setup.',
	'LATEX_NOT_INSTALLED'				=> 'Latex BBcode support is not installed or is not properly configured.',
));

?>