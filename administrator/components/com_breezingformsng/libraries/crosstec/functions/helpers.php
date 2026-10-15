<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 * 
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\String\StringHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Mail\MailerInterface;

function bf_sanitizeFilename($fileName, $defaultIfEmpty = 'upload', $separator = '_', $lowerCase = true)
{
	// Gather file informations and store its extension
	$fileInfos = pathinfo($fileName);
	$fileExt = array_key_exists('extension', $fileInfos) ? '.' . strtolower($fileInfos['extension']) : '';

	// Removes accents

	if (function_exists('transliterator_transliterate')) {

		$fileName = @transliterator_transliterate('Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC; Lower();', $fileInfos['filename']);
		$fileName = OutputFilter::stringURLSafe($fileName);
		if (trim($fileName) == '') {

			$allowed = "/[^a-z0-9\\.\\-\\_]/i";
			$fileName = preg_replace($allowed, "_", $fileInfos['filename']);
		} else {
			// Removes all characters that are not separators, letters, numbers, dots or whitespaces
			$fileName = preg_replace("/[^ a-zA-Z" . preg_quote($separator) . "\d\.\s]/", '', $lowerCase ? strtolower($fileName) : $fileName);

			// Replaces all successive separators into a single one
			$fileName = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $fileName);

			// Trim beginning and ending seperators
			$fileName = trim($fileName, $separator);
		}
	} else {
		$allowed = "/[^a-z0-9\\.\\-\\_]/i";
		$fileName = preg_replace($allowed, "_", $fileInfos['filename']);
	}

	// If empty use the default string
	if (empty($fileName)) {
		$allowed = "/[^a-z0-9\\.\\-\\_]/i";
		$fileName = preg_replace($allowed, "_", $fileInfos['filename']);
	}

	if (empty($fileName)) {
		$fileName = $defaultIfEmpty;
	}

	return $fileName . $fileExt;
}

function bf_tooltipText($title = '', $content = '', $translate = 1, $escape = 1)
{
	// Return empty in no title or content is given.
	if ($title == '' && $content == '') {
		return '';
	}

	// Split title into title and content if the title contains '::' (old Mootools format).
	if ($content == '' && !(strpos($title, '::') === false)) {
		list($title, $content) = explode('::', $title, 2);
	}

	// Pass texts through the Text.
	if ($translate) {
		$title = Text::_($title);
		$content = Text::_($content);
	}

	// Escape the texts.
	if ($escape) {
		$title = str_replace('"', '&quot;', $title);
		$content = str_replace('"', '&quot;', $content);
	}

	// Return only the content if no title is given.
	if ($title == '') {
		return $content;
	}

	// Return only the title if title and text are the same.
	if ($title == $content) {
		return '<strong>' . $title . '</strong>';
	}

	// Return the formated sting combining the title and  content.
	if ($content != '') {
		return '<strong>' . $title . '</strong><br />' . $content;
	}

	// Return only the title.
	return $title;
}

function bf_stringURLUnicodeSlug($string)
{
	// Replace double byte whitespaces by single byte (East Asian languages)
	$str = preg_replace('/\xE3\x80\x80/', ' ', $string);


	// Remove any '-' from the string as they will be used as concatenator.
	// Would be great to let the spaces in but only Firefox is friendly with this

	$str = str_replace('-', ' ', $str);

	// Replace forbidden characters by whitespaces
	$str = preg_replace('#[:\#\*"@+=;!&\.%()\]\/\'\\\\|\[]#', "\x20", $str);

	// Delete all '?'
	$str = str_replace('?', '', $str);

	// Trim white spaces at beginning and end of alias and make lowercase
	$str = trim(StringHelper::strtolower($str));

	// Remove any duplicate whitespace and replace whitespaces by hyphens
	$str = preg_replace('#\x20+#', '-', $str);

	return $str;
}

function bf_cleanString($string)
{
	return str_replace(array('[', ']', '{', '}', '(', ')', '|'), array(
		'&#91;',
		'&#93;',
		'&#123;',
		'&#125;',
		'&#40;',
		'&#41;',
		'&#124;'
	), $string);
}

function bf_startsWith($haystack, $needle)
{
	$length = strlen($needle);

	return(substr($haystack, 0, $length) === $needle);
}

function bf_endsWith($haystack, $needle)
{
	$length = strlen($needle);
	if ($length == 0) {
		return true;
	}

	return(substr($haystack, -$length) === $needle);
}


/**
 * Mail creator as expected by former FacileForms code
 * This is a not really Legacy, so it stays like that
 *
 * @param string $from
 * @param string $fromname
 * @param string $subject
 * @param string $body
 *
 * @return MailerInterface
 */



function bf_getFieldSelectorList($form_id, $element_target_id)
{
	$db = Factory::getContainer()->get(DatabaseInterface::class);
	$formId = (int) $form_id;
	$db->setQuery($db->createQuery()->select($db->quoteName('name'))->from($db->quoteName('#__facileforms_elements'))->where($db->quoteName('form') . ' = :formId')->whereNotIn($db->quoteName('name'), ['bfFakeName','bfFakeName2','bfFakeName3','bfFakeName4','bfFakeName5','bfFakeName6'], \Joomla\Database\ParameterType::STRING)->order($db->quoteName('ordering'))->bind(':formId', $formId, \Joomla\Database\ParameterType::INTEGER));

	$rows = $db->loadColumn();
	$out = '<script type="text/javascript">
    function insertAtCursor_' . $element_target_id . '(myValue) {
var myField = document.getElementById("' . $element_target_id . '");
//IE support
if (document.selection) {
myField.focus();
sel = document.selection.createRange();
sel.text = myValue;
}
//MOZILLA/NETSCAPE support
else if (myField.selectionStart || myField.selectionStart == \'0\') {
var startPos = myField.selectionStart;
var endPos = myField.selectionEnd;
myField.value = myField.value.substring(0, startPos)
+ myValue
+ myField.value.substring(endPos, myField.value.length);
} else {
myField.value += myValue;
}
}

    </script>';
	if ($rows) {
		foreach ($rows as $row) {
			$out .= '<a href="javascript: insertAtCursor_' . $element_target_id . '(\'{' . $row . ':label}\');void(0);">{' . $row . ':label}</a><br/>';
			$out .= '<a href="javascript: insertAtCursor_' . $element_target_id . '(\'{' . $row . ':value}\');void(0);">{' . $row . ':value}</a><br/><br/>';
		}
	}

	return $out;
}

/*
   additional function for inserting fields to editor
*/
function bf_getFieldSelectorListEditor($form_id, $element_target_id)
{
	$db = Factory::getContainer()->get(DatabaseInterface::class);
	$formId = (int) $form_id;
	$db->setQuery($db->createQuery()->select($db->quoteName('name'))->from($db->quoteName('#__facileforms_elements'))->where($db->quoteName('form') . ' = :formId')->whereNotIn($db->quoteName('name'), ['bfFakeName','bfFakeName2','bfFakeName3','bfFakeName4','bfFakeName5','bfFakeName6'], \Joomla\Database\ParameterType::STRING)->order($db->quoteName('ordering'))->bind(':formId', $formId, \Joomla\Database\ParameterType::INTEGER));
	$rows = $db->loadColumn();
	$out = '<script type="text/javascript">
    function insertAtCursor_' . $element_target_id . '_Editor(myValue) {
        var content = Joomla.editors.instances[' . json_encode($element_target_id) . '].getValue();
        var splitPos = content.lastIndexOf("<");
        var combined = content.substring(0, splitPos) + myValue + content.substring(splitPos);
        jQuery("#' . $element_target_id . '_div iframe").contents().find("body").html(combined);
    }
    </script>';

	if ($rows) {
		foreach ($rows as $row) {
			$out .= '<a href="javascript: insertAtCursor_' . $element_target_id . '_Editor(\'{' . $row . ':label}\');void(0);">{' . $row . ':label}</a><br/>';
			$out .= '<a href="javascript: insertAtCursor_' . $element_target_id . '_Editor(\'{' . $row . ':value}\');void(0);">{' . $row . ':value}</a><br/><br/>';
		}
	}

	return $out;

}

function bf_getFieldSelectorListHTML($form_id, $editor, $element_target_id)
{
	$db = Factory::getContainer()->get(DatabaseInterface::class);
	$formId = (int) $form_id;
	$db->setQuery($db->createQuery()->select($db->quoteName('name'))->from($db->quoteName('#__facileforms_elements'))->where($db->quoteName('form') . ' = :formId')->whereNotIn($db->quoteName('name'), ['bfFakeName','bfFakeName2','bfFakeName3','bfFakeName4','bfFakeName5','bfFakeName6'], \Joomla\Database\ParameterType::STRING)->order($db->quoteName('ordering'))->bind(':formId', $formId, \Joomla\Database\ParameterType::INTEGER));
	$rows = $db->loadColumn();
	$out = '<script type="text/javascript">
    function insert_' . $element_target_id . 'HTML(myValue) {
        var content = ' . $editor->getContent($element_target_id) . ';
        ' . $editor->setContent($element_target_id, 'content+myValue') . ';
    }
    </script>';
	if ($rows) {
		foreach ($rows as $row) {
			$out .= '<a href="javascript: insert_' . $element_target_id . 'HTML(\'{' . $row . ':label}\');void(0);">{' . $row . ':label}</a><br/>';
			$out .= '<a href="javascript: insert_' . $element_target_id . 'HTML(\'{' . $row . ':value}\');void(0);">{' . $row . ':value}</a><br/><br/>';
		}
	}

	return $out;
}

// used if copy is disabled
function bf_copy($file1, $file2)
{
	$contentx = @file_get_contents($file1);
	$openedfile = @fopen($file2, "w");
	@fwrite($openedfile, $contentx);
	@fclose($openedfile);
	if ($contentx === false) {
		$status = false;
	} else {
		$status = true;
	}

	return $status;
}

function bf_createMail($from, $fromname, $subject, $body, $alt_sender = '')
{

	$_mailfrom = '';
	$_fromname = '';

	$_mailfrom = Factory::getApplication()->getConfig()->get('mailfrom', '');
	$_fromname = Factory::getApplication()->getConfig()->get('fromname', '');

	$mail = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

	/*
				try {

					$mail->setSender( array( $alt_sender ? $alt_sender : $_mailfrom, $fromname ? $fromname : $_fromname ) );

				} catch ( Exception $e ) {

				}*/

	$mail->setSubject($subject);
	$mail->setBody($body);

	$prev_from = $alt_sender ? $alt_sender : $_mailfrom;


	try {

		//$mail->SetFrom( $prev_from, $fromname ? $fromname : $_fromname );
		//$mail->SetFrom( $from ? $from : '', $fromname ? $fromname : '' );
		//$mail->setSender( array( $prev_from, $fromname ? $fromname : $_fromname ) );
		//$mail->setSender( array( $from ? $from : $prev_from, $fromname ? $fromname : $_fromname ) );
		$mail->setSender(array($prev_from, $fromname ? $fromname : $_fromname));

	} catch (Exception $e) {

	}

	try {

		if ($from && $from != $prev_from) {


			$newfrom = $from ? $from : $_mailfrom;
			$newfromname = $fromname ? $fromname : $_fromname;

			if (!empty($newfrom)) {

				$mail->addReplyTo($from, $fromname ? $fromname : $_fromname);
			}

		}

	} catch (Exception $e) {

	}

	return $mail;
}

function bf_sendNotificationBySession($session)
{

	$contents = Factory::getApplication()->getSession()->get($session, array());

	if (count($contents) != 0) {

		$from = $contents['from'];
		$fromname = $contents['fromname'];
		$recipient = $contents['recipients'];
		$subject = $contents['subject'];
		$body = $contents['body'];
		$attachment = $contents['attachment'];
		$html = $contents['isHtml'];
		$alt_sender = $contents['alt_sender'];

		if ((is_array($recipient) && count($recipient) != 0) || (!is_array($recipient) && $recipient != '')) {

			$mail = bf_createMail($from, $fromname, $subject, $body, $alt_sender);
			if (is_array($recipient)) {
				foreach ($recipient as $to) {
					$mail->addRecipient($to);
				}
			} else {
				$mail->addRecipient($recipient);
			}

			if ($attachment) {
				if (is_array($attachment)) {
					foreach ($attachment as $fname) {
						$mail->addAttachment($fname);
					}
				} else {
					$mail->addAttachment($attachment);
				}
			} // if

			if (isset($html)) {
				$mail->isHtml($html);
			}

			$mail->send();
		}
	}

	Factory::getApplication()->getSession()->set($session, array());
}

function bf_sendNotificationByPaymentCache($formId, $recordId, $type = 'admin')
{

	$contents = array();
	$sourcePath = JPATH_SITE . '/media/breezingforms/payment_cache/';
	if (@file_exists($sourcePath) && @is_readable($sourcePath) && @is_dir($sourcePath) && $handle = @opendir($sourcePath)) {
		while (false !== ($file = @readdir($handle))) {
			if ($file != "." && $file != "..") {
				$parts = explode('_', $file);
				if (count($parts) == 4) {
					if ($parts[0] == intval($formId) && $parts[1] == intval($recordId) && $parts[2] == $type) {
						$contents = unserialize(file_get_contents($sourcePath . $file));
						File::delete($sourcePath . $file);
						break;
					}
				}
			}
		}
		@closedir($handle);
	}

	if (count($contents) != 0) {

		$from = $contents['from'];
		$fromname = $contents['fromname'];
		$recipient = $contents['recipients'];
		$subject = $contents['subject'];
		$body = $contents['body'];
		$attachment = $contents['attachment'];
		$html = $contents['isHtml'];
		$alt_sender = $contents['alt_sender'];

		if ((is_array($recipient) && count($recipient) != 0) || (!is_array($recipient) && $recipient != '')) {

			$mail = bf_createMail($from, $fromname, $subject, $body, $alt_sender);
			if (is_array($recipient)) {
				foreach ($recipient as $to) {
					$mail->addRecipient($to);
				}
			} else {
				$mail->addRecipient($recipient);
			}

			if ($attachment) {
				if (is_array($attachment)) {
					foreach ($attachment as $fname) {
						$mail->addAttachment($fname);
					}
				} else {
					$mail->addAttachment($attachment);
				}
			} // if

			if (isset($html)) {
				$mail->isHtml($html);
			}

			$mail->send();
		}
	}
}

/**
 * The name says it all
 *
 * @param string $string
 *
 * @return boolean
 */
function bf_isUTF8(mixed $string): bool
{
	if (is_array($string)) {
		$enc = implode('', $string);

		return str_starts_with($enc, "\xEF\xBB\xBF");
	}

	return is_string($string) && preg_match('//u', $string) === 1;
}

/**
 * The classic recursive slash remover
 *
 * @param string $value raw
 *
 * @return string cleaned
 */
function bf_stripslashes_deep(mixed $value): mixed
{
	return $value;
}

function bf_is_email($email, $checkDNS = false)
{
	//      Check that $email is a valid address
	//              (http://tools.ietf.org/html/rfc3696)
	//              (http://tools.ietf.org/html/rfc2822)
	//              (http://tools.ietf.org/html/rfc5322#section-3.4.1)
	//              (http://tools.ietf.org/html/rfc5321#section-4.1.3)
	//              (http://tools.ietf.org/html/rfc4291#section-2.2)
	//              (http://tools.ietf.org/html/rfc1123#section-2.1)

	//      the upper limit on address lengths should normally be considered to be 256
	//              (http://www.rfc-editor.org/errata_search.php?rfc=3696)
	if (strlen($email) > 256) {
		return false;
	}   //      Too long

	//      Contemporary email addresses consist of a "local part" separated from
	//      a "domain part" (a fully-qualified domain name) by an at-sign ("@").
	//              (http://tools.ietf.org/html/rfc3696#section-3)
	$index = strrpos($email, '@');

	if ($index === false) {
		return false;
	}   //      No at-sign
	if ($index === 0) {
		return false;
	}   //      No local part
	if ($index > 64) {
		return false;
	}   //      Local part too long

	$localPart = substr($email, 0, $index);
	$domain = substr($email, $index + 1);
	$domainLength = strlen($domain);

	if ($domainLength === 0) {
		return false;
	}   //      No domain part
	if ($domainLength > 255) {
		return false;
	}   //      Domain part too long

	//      Let's check the local part for RFC compliance...
	//
	//      local-part      =       dot-atom / quoted-string / obs-local-part
	//      obs-local-part  =       word *("." word)
	//              (http://tools.ietf.org/html/rfc2822#section-3.4.1)
	if (preg_match('/^"(?:.)*"$/', $localPart) > 0) {
		$dotArray[] = $localPart;
	} else {
		$dotArray = explode('.', $localPart);
	}

	foreach ($dotArray as $localElement) {
		//      Period (".") may...appear, but may not be used to start or end the
		//      local part, nor may two or more consecutive periods appear.
		//              (http://tools.ietf.org/html/rfc3696#section-3)
		//
		//      A zero-length element implies a period at the beginning or end of the
		//      local part, or two periods together. Either way it's not allowed.
		if ($localElement === '') {
			return false;
		}   //      Dots in wrong place

		//      Each dot-delimited component can be an atom or a quoted string
		//      (because of the obs-local-part provision)
		if (preg_match('/^"(?:.)*"$/', $localElement) > 0) {
			//      Quoted-string tests:
			//
			//      Note that since quoted-pair
			//      is allowed in a quoted-string, the quote and backslash characters may
			//      appear in a quoted-string so long as they appear as a quoted-pair.
			//              (http://tools.ietf.org/html/rfc2822#section-3.2.5)
			$groupCount = preg_match_all('/(?:^"|"$|\\\\\\\\|\\\\")|(\\\\|")/', $localElement, $matches);
			array_multisort($matches[1], SORT_DESC);
			if ($matches[1][0] !== '') {
				return false;
			}   //      Unescaped quote or backslash character inside quoted string
			if (preg_match('/^"\\\\*"$/', $localElement) > 0) {
				return false;
			}   //      "" and "\" are slipping through - note: must tidy this up
		} else {
			//      Unquoted string tests:
			//
			//      Any ASCII graphic (printing) character other than the
			//      at-sign ("@"), backslash, double quote, comma, or square brackets may
			//      appear without quoting.  If any of that list of excluded characters
			//      are to appear, they must be quoted
			//              (http://tools.ietf.org/html/rfc3696#section-3)
			//
			$stripped = '';
			//      Any excluded characters? i.e. <space>, @, [, ], \, ", <comma>
			if (preg_match('/[ @\\[\\]\\\\",]/', $localElement) > 0) //      Check all excluded characters are escaped
			{
				$stripped = preg_replace('/\\\\[ @\\[\\]\\\\",]/', '', $localElement);
			}
			if (preg_match('/[ @\\[\\]\\\\",]/', $stripped) > 0) {
				return false;
			}   //      Unquoted excluded characters
		}
	}

	//      Now let's check the domain part...

	//      The domain name can also be replaced by an IP address in square brackets
	//              (http://tools.ietf.org/html/rfc3696#section-3)
	//              (http://tools.ietf.org/html/rfc5321#section-4.1.3)
	//              (http://tools.ietf.org/html/rfc4291#section-2.2)
	if (preg_match('/^\\[(.)+]$/', $domain) === 1) {
		//      It's an address-literal
		$addressLiteral = substr($domain, 1, $domainLength - 2);
		$matchesIP = array();

		//      Extract IPv4 part from the end of the address-literal (if there is one)
		if (preg_match('/\\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', $addressLiteral, $matchesIP) > 0) {
			$index = strrpos($addressLiteral, $matchesIP[0]);

			if ($index === 0) {
				//      Nothing there except a valid IPv4 address, so...
				return true;
			} else {
				//      Assume it's an attempt at a mixed address (IPv6 + IPv4)
				if ($addressLiteral[$index - 1] !== ':') {
					return false;
				}   //      Character preceding IPv4 address must be ':'
				if (substr($addressLiteral, 0, 5) !== 'IPv6:') {
					return false;
				}   //      RFC5321 section 4.1.3

				$IPv6 = substr($addressLiteral, 5, ($index === 7) ? 2 : $index - 6);
				$groupMax = 6;
			}
		} else {
			//      It must be an attempt at pure IPv6
			if (substr($addressLiteral, 0, 5) !== 'IPv6:') {
				return false;
			}   //      RFC5321 section 4.1.3
			$IPv6 = substr($addressLiteral, 5);
			$groupMax = 8;
		}

		$groupCount = preg_match_all('/^[0-9a-fA-F]{0,4}|\\:[0-9a-fA-F]{0,4}|(.)/', $IPv6, $matchesIP);
		$index = strpos($IPv6, '::');

		if ($index === false) {
			//      We need exactly the right number of groups
			if ($groupCount !== $groupMax) {
				return false;
			}   //      RFC5321 section 4.1.3
		} else {
			if ($index !== strrpos($IPv6, '::')) {
				return false;
			}   //      More than one '::'
			$groupMax = ($index === 0 || $index === (strlen($IPv6) - 2)) ? $groupMax : $groupMax - 1;
			if ($groupCount > $groupMax) {
				return false;
			}   //      Too many IPv6 groups in address
		}

		//      Check for unmatched characters
		array_multisort($matchesIP
		[1], SORT_DESC);
		if ($matchesIP[1][0] !== '') {
			return false;
		}   //      Illegal characters in address

		//      It's a valid IPv6 address, so...
		return true;
	} else {
		//      It's a domain name...

		//      The syntax of a legal Internet host name was specified in RFC-952
		//      One aspect of host name syntax is hereby changed: the
		//      restriction on the first character is relaxed to allow either a
		//      letter or a digit.
		//              (http://tools.ietf.org/html/rfc1123#section-2.1)
		//
		//      NB RFC 1123 updates RFC 1035, but this is not currently apparent from reading RFC 1035.
		//
		//      Most common applications, including email and the Web, will generally not permit...escaped strings
		//              (http://tools.ietf.org/html/rfc3696#section-2)
		//
		//      Characters outside the set of alphabetic characters, digits, and hyphen MUST NOT appear in domain name
		//      labels for SMTP clients or servers
		//              (http://tools.ietf.org/html/rfc5321#section-4.1.2)
		//
		//      RFC5321 precludes the use of a trailing dot in a domain name for SMTP purposes
		//              (http://tools.ietf.org/html/rfc5321#section-4.1.2)
		$matches = array();
		$groupCount = preg_match_all('/(?:[0-9a-zA-Z][0-9a-zA-Z-]{0,61}[0-9a-zA-Z]|[a-zA-Z])(?:\\.|$)|(.)/', $domain, $matches);
		$level = count($matches[0]);

		if ($level == 1) {
			return false;
		}   //      Mail host can't be a TLD

		$TLD = $matches[0][$level - 1];
		if (substr($TLD, strlen($TLD) - 1, 1) === '.') {
			return false;
		}   //      TLD can't end in a dot
		if (preg_match('/^[0-9]+$/', $TLD) > 0) {
			return false;
		}   //      TLD can't be all-numeric

		//      Check for unmatched characters
		array_multisort($matches[1], SORT_DESC);
		if ($matches[1][0] !== '') {
			return false;
		}   //      Illegal characters in domain, or label longer than 63 characters

		//      Check DNS?
		if ($checkDNS && function_exists('checkdnsrr')) {
			if (!(checkdnsrr($domain, 'A') || checkdnsrr($domain, 'MX'))) {
				return false;   //      Domain doesn't actually exist
			}
		}

		//      Eliminate all other factors, and the one which remains must be the truth.
		//              (Sherlock Holmes, The Sign of Four)
		return true;
	}
}
