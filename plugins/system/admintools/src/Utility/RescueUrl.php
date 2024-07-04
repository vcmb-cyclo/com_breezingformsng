<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Utility;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;
use RuntimeException;
use Throwable;

class RescueUrl
{
	/**
	 * This string is used in the 'series' column of #__user_keys to signify an Admin Tools Rescue URL token.
	 */
	public const series = 'com_admintools_rescue';

	/**
	 * Caches the results of isRescueMode() for faster reference during the same page load.
	 *
	 * @var   null|bool
	 */
	private static $isRescueMode = null;

	/**
	 * Checks if the current request is trying to enable Rescue Mode. If so, we will create a new rescue token and
	 * store
	 * the relevant information in the database.
	 *
	 * This feature is only available on the backend of the site. The reasoning is that if you can access the backend
	 * of
	 * your site you can unblock yourself and fix whatever was blocking you in the first place.
	 *
	 * @param   BlockedRequestHandler  $exceptionsHandler  The Admin Tools exceptions handler, used to find email
	 *                                                     templates
	 *
	 * @return  void
	 */
	public static function processRescueURL(BlockedRequestHandler $exceptionsHandler)
	{
		// Is the feature enabled?
		if (!self::isRescueModeEnabled())
		{
			return;
		}

		$app = Factory::getApplication();

		if (!$app->isClient('administrator'))
		{
			return;
		}

		// Do I have an email address?
		$email = trim($app->input->get('admintools_rescue', '', 'raw') ?: '');

		if (empty($email))
		{
			return;
		}

		if ($email == 'you@example.com')
		{
			echo Text::sprintf('PLG_ADMINTOOLS_LBL_RESCUEURL_ERR_INVALIDADDRESS', $email);

			$app->close(0);
		}

		// Does the email belong to a Super User?
		$userId = self::isSuperUserByEmail($email);

		if (!$userId)
		{
			return;
		}

		// Create a new random token, 96 characters long (that's about 160 bits of randomness)
		$token = UserHelper::genRandomPassword(96);

		// Check if #__user_keys has another token with series == 'com_admintools_rescue' and delete it
		self::removeOldRescueTokens();

		// Save new #__user_keys record with invalid = 0 (unused; we'll change that to -1 when we use it)
		$browser = Browser::getInstance();
		$user    = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userId);
		$ip      = Filter::getIp();

		self::saveRescueToken($user->username, $token, 0, time(), $browser->getAgentString(), $ip);

		// Send email
		self::sendRescueURLEmail($user, $token, $exceptionsHandler);

		// Close application with a message that the email has been sent
		echo Text::_('PLG_ADMINTOOLS_LBL_RESCUEURL_SENTMSG');

		$app->close(0);
	}

	/**
	 * Are we in Rescue Mode?
	 *
	 * This happens in two cases. Either this request includes a valid Rescue Mode token OR we had already provided it
	 * in a previous request and the Rescue Mode information is now in the session.
	 *
	 * @return  bool
	 */
	public static function isRescueMode()
	{
		// Check the static cache first
		if (is_bool(self::$isRescueMode))
		{
			return self::$isRescueMode;
		}

		self::$isRescueMode = false;

		// Is the feature enabled?
		if (!self::isRescueModeEnabled())
		{
			return false;
		}

		$app     = Factory::getApplication();
		$session = $app->getSession();

		// Is this the backend of the site? (Rescue mode is only valid in the backend)
		if (!$app->isClient('administrator'))
		{
			return false;
		}

		// Do we have a token?
		$token    = $app->input->getCmd('admintools_rescue_token', '');
		$username = null;

		// In case there is a token we need to process it.
		if (!empty($token))
		{
			self::removeExpiredRescueTokens();

			$username = empty($token) ? null : self::getUsernameFromRescueToken($token);
		}

		// In case of a valid token I have to set a few things in the session
		if (!empty($username))
		{
			$session->set('com_admintools.rescue_timestamp', time());
			$session->set('com_admintools.rescue_username', $username);
		}

		// Is the timestamp saved in the session within the time limit?
		$expiresOn = (int) $session->get('com_admintools.rescue_timestamp', 0)
			+ (self::getTimeout() * 60);

		if (time() > $expiresOn)
		{
			return false;
		}

		// We must be guest OR the username must match the one in the token.
		$currentUser = $app->getIdentity();
		$username    = Factory::getApplication()->getSession()->get('com_admintools.rescue_username', '');

		if (!empty($currentUser) && !$currentUser->guest && ($currentUser->username != $username))
		{
			return false;
		}

		// All checks passed, this is Rescue Mode
		self::$isRescueMode = true;

		return true;
	}

	public static function getRescueInformation($email = 'you@example.com'): array
	{
		$ret = [
			'RESCUEINFO'         => '',
			'RESCUE_TRIGGER_URL' => '#',
		];

		// If the feature is disabled we will not show any rescue information
		if (!self::isRescueModeEnabled())
		{
			return $ret;
		}

		$ret = [
			'RESCUEINFO'         => Text::sprintf('PLG_ADMINTOOLS_MSG_BLOCKED_RESCUEINFO', $email),
			'RESCUE_TRIGGER_URL' => rtrim(Uri::root(), '/') . '/administrator/index.php?admintools_rescue=',
		];

		return $ret;
	}

	/**
	 * @param           $message
	 * @param   string  $email
	 *
	 * @deprecated
	 */
	public static function processRescueInfoInMessage($message, $email = 'you@example.com')
	{
		/**
		 * Replace the new {RESCUEINFO} and {RESCUE_TRIGGER_URL} with square bracket versions.
		 *
		 * Admin Tools 7 and later uses the Joomla email template manager which makes use of curly braces. Older
		 * versions used our own email template manager which used square brackets. The best way to have backwards and
		 * forwards compatibility is to convert the curly braces to square ones and keep the replacement code the same.
		 *
		 * Note: yes, users need to write new email templates. However, the [RESCUEINFO] / {RESCUEINFO} literal is
		 * replaced with the contents of the *language string* PLG_ADMINTOOLS_MSG_BLOCKED_RESCUEINFO. This contains the
		 * {RESCUE_TRIGGER_URL} / [RESCUE_TRIGGER_URL] literal. While Admin Tools 7 uses the curly braces version, any
		 * language override from earlier versions OR third party language files could still be using the square
		 * brackets one. Therefore we really do need to have everything normalised to one format for the code below to
		 * work reliably.
		 */
		$message = str_ireplace([
			'{RESCUEINFO}',
			'{RESCUE_TRIGGER_URL}'
		], [
			'[RESCUEINFO]',
			'[RESCUE_TRIGGER_URL]'
		], $message);

		// Nothing to replace? Don't bother proceeding.
		if (strpos($message, '[RESCUEINFO]') === false)
		{
			return $message;
		}

		// Step 1. Replace [RESCUEINFO] with the language string, if the feature is enabled.
		$message = str_replace('[RESCUEINFO]',
			self::isRescueModeEnabled() ? Text::sprintf('PLG_ADMINTOOLS_MSG_BLOCKED_RESCUEINFO', $email) : '',
			$message);

		// Replace curly braces again, they could have been added from the language string
		$message = str_ireplace([
			'{RESCUEINFO}',
			'{RESCUE_TRIGGER_URL}'
		], [
			'[RESCUEINFO]',
			'[RESCUE_TRIGGER_URL]'
		], $message);

		// Step 2. Replace [RESCUE_TRIGGER_URL] with the trigger URL for rescue mode
		if (strpos($message, '[RESCUE_TRIGGER_URL]') !== false)
		{
			return str_replace('[RESCUE_TRIGGER_URL]',
				rtrim(Uri::root(), '/') . '/administrator/index.php?admintools_rescue=',
				$message);
		}

		return $message;
	}

	/**
	 * Is the Rescue Mode feature enabled in the plugin?
	 *
	 * @return  bool
	 */
	private static function isRescueModeEnabled()
	{
		$params = self::getPluginParams();

		return (bool) $params->get('rescueurl', 1);
	}

	/**
	 * Get the rescue mode timeout in minutes. Must be at least one minute.
	 *
	 * @return  int
	 */
	private static function getTimeout()
	{
		$params  = self::getPluginParams();
		$timeout = (int) $params->get('rescueduration', 15);

		if ($timeout <= 0)
		{
			$timeout = 15;
		}

		return $timeout;
	}

	/**
	 * Get the plugin parameters.
	 *
	 * @return Registry
	 */
	private static function getPluginParams(): Registry
	{
		// On Joomla! 5 we can't use PluginHelper::getPlugin when the plugin is disabled. MAXIMUM EFFORT!
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where(
				[
					$db->quoteName('type') . ' = ' . $db->quote('plugin'),
					$db->quoteName('folder') . ' = ' . $db->quote('system'),
					$db->quoteName('element') . ' = ' . $db->quote('admintools'),
				]
			);

		try
		{
			$params = $db->setQuery($query)->loadResult() ?: null;
		}
		catch (Throwable $e)
		{
			$params = null;
		}

		return new Registry($params ?: '{}');
	}

	/**
	 * Does the user exist, not blocked and have the core.admin (Super User) privilege?
	 *
	 * @param   string  $email  The email to check for
	 *
	 * @return  bool|int
	 */
	private static function isSuperUserByEmail($email)
	{
		$db     = Factory::getContainer()->get(DatabaseInterface::class);
		$query  = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__users'))
			->where($db->qn('email') . ' = ' . $db->q($email))
			->where($db->qn('block') . ' = ' . $db->q(0));
		$userID = $db->setQuery($query)->loadResult();

		if (empty($userID))
		{
			return false;
		}

		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($userID);

		if (!$user->authorise('core.admin'))
		{
			return false;
		}

		return $userID;
	}

	/**
	 * Check if #__user_keys has another token with series == 'com_admintools_rescue' and delete it
	 *
	 * @return  void
	 */
	private static function removeOldRescueTokens()
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->delete('#__user_keys')
			->where($db->qn('series') . ' = ' . $db->q(self::series));
		$db->setQuery($query)->execute();
	}

	/**
	 * Save a login token
	 *
	 * @param   string  $username    The username this cookie belongs to.
	 * @param   string  $token       The token to assign to this cookie. The token is stored hashed to prevent
	 *                               side-channel attacks.
	 * @param   int     $invalid     We use this as a status flag. 0 when the token is unused, 1 after it's been used.
	 * @param   string  $time        The timestamp this cookie was created on.
	 * @param   string  $user_agent  The user agent of the user's browser.
	 * @param   string  $ip          The IP address of the user
	 *
	 * @return  void
	 */
	private static function saveRescueToken($username, $token, $invalid, $time, $user_agent, $ip)
	{
		// Create a combined entry for the User Agent string and IP address
		$combined = json_encode([
			'ua' => $user_agent,
			'ip' => $ip,
		]);

		$db = Factory::getContainer()->get(DatabaseInterface::class);
		$o  = (object) [
			'id'       => null,
			'user_id'  => $username,
			'token'    => UserHelper::hashPassword($token),
			'series'   => self::series,
			'invalid'  => $invalid,
			'time'     => $time,
			'uastring' => $combined,
		];

		if (!$db->insertObject('#__user_keys', $o, 'id'))
		{
			throw new RuntimeException('Could not save token');
		}
	}

	private static function getUsernameFromRescueToken($token, $ua = null, $ip = null)
	{
		// Make sure we have a UA string and an IP address
		if (is_null($ua))
		{
			$browser = Browser::getInstance();
			$ua      = $browser->getAgentString();
		}

		if (is_null($ip))
		{
			$ip = Filter::getIp();
		}

		// Create a combined entry for the User Agent string and IP address
		$combined = json_encode([
			'ua' => $ua,
			'ip' => $ip,
		]);

		// Get the cutoff time for tokens
		$rescueDuration   = self::getTimeout() * 60;
		$now              = time();
		$nowMinusDuration = $now - $rescueDuration;

		// Load all non-expired Admin Tools tokens
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__user_keys'))
			->where($db->qn('series') . ' = ' . $db->q(self::series))
			->where($db->qn('time') . ' > ' . $db->q($nowMinusDuration))
			->where($db->qn('uastring') . ' = ' . $db->q($combined));

		$entries = $db->setQuery($query)->loadObjectList();

		// No entry? No user.
		if (empty($entries))
		{
			return null;
		}

		// Loop all entries until we find a matching token
		foreach ($entries as $entry)
		{
			// FYI: Clean text passwords are always truncated to 72 chars. So shorten tokens will always validate
			// https://stackoverflow.com/a/28951717/485241
			if (!UserHelper::verifyPassword($token, $entry->token))
			{
				continue;
			}

			// Mark token as used
			$entry->invalid = 1;
			$db->updateObject('#__user_keys', $entry, 'id');

			return $entry->user_id;
		}

		// If we're here there was no matching token.
		return null;
	}

	/**
	 * Removes all expired Admin Tools tokens
	 *
	 * @return  void
	 */
	private static function removeExpiredRescueTokens()
	{
		$db         = Factory::getContainer()->get(DatabaseInterface::class);
		$expiration = time() - 60 * self::getTimeout();

		$query = $db->getQuery(true)
			->delete('#__user_keys')
			->where($db->qn('series') . ' = ' . $db->q(self::series))
			->where($db->quoteName('time') . ' < ' . $db->quote($expiration));
		$db->setQuery($query)->execute();
	}

	/**
	 * Send an email with the Rescue URL to the user
	 *
	 * @param   User                   $user               The user requesting the Rescue URL
	 * @param   string                 $token              The Rescue URL token already saved in the database
	 * @param   BlockedRequestHandler  $exceptionsHandler  The exceptions handler, used to fetch email templates
	 *
	 * @return  void
	 */
	private static function sendRescueURLEmail(User $user, $token, BlockedRequestHandler $exceptionsHandler)
	{
		// Load the component's administrator translation files
		$jlang = Factory::getApplication()->getLanguage();
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

		// Get the reason in human readable format
		$txtReason = Text::_('PLG_ADMINTOOLS_LBL_RESCUEURL');

		// Get the backend Rescue URL
		$url = rtrim(Uri::root(), '/') . '/administrator/index.php?admintools_rescue_token=' . $token;

		try
		{
			$tokens = [
				'REASON'    => $txtReason,
				'RESCUEURL' => $url,
				'USER'      => $user->username,
			];

			$exceptionsHandler->sendEmail('com_admintools.rescueurl', $user, $tokens);
		}
		catch (Exception $e)
		{
		}
	}
}