<?php
/**
*
* common [Deutsch - Du]
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
	'LATEX_BBCODE_HELPLINE'				=> 'LaTeX-Formel: [%1$s]LaTeX-Formel[%1$s]',

	'LATEX_IMAGES_PATH_NOT_READABLE'	=> 'Im angegebenen Pfad für LaTeX-Grafiken kann nicht gelesen werden.',
	'LATEX_IMAGES_PATH_NOT_WRITABLE'	=> 'Der angegebene Pfad für LaTeX-Grafiken ist nicht beschreibbar.',
	'LATEX_METHOD_NOT_INSTALLED'		=> 'Die konfigurierte LaTeX-Methode ist nicht verfügbar oder nicht installiert.',
	'LATEX_METHOD_NOT_SUPPORTED'		=> 'Die konfigurierte LaTeX-Methode wird von der aktuellen PHP-Konfiguration nicht unterstützt.',
	'LATEX_NOT_INSTALLED'				=> 'LaTeX ist nicht vollständig oder nicht korrekt installiert.',
));

?>