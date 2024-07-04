<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Utility;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Akeeba\Component\AdminTools\Administrator\Helper\TemplateEmails;
use DateTimeZone;
use Exception;
use Joomla\Application\ApplicationInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

class BlockedRequestHandler implements DatabaseAwareInterface
{
	use DatabaseAwareTrait;

	/**
	 * Plugin parameters
	 *
	 * @var   Registry
	 * @since 7.0.0
	 */
	protected $pluginParams = null;

	/**
	 * WAF parameters
	 *
	 * @var   Storage
	 * @since 7.0.0
	 */
	protected $wafParams = null;

	/**
	 * Component parameters
	 *
	 * @var   Registry
	 * @since 7.0.0
	 */
	protected $cParams = null;

	private ApplicationInterface $application;

	public function __construct(Registry $pluginParams, Storage $wafParams, Registry $cParams)
	{
		$this->pluginParams = $pluginParams;
		$this->wafParams    = $wafParams;
		$this->cParams      = $cParams;
	}

	/**
	 * @param   string     $templateKey  The template key to send, e.g. 'com_admintools.blockedrequest'
	 * @param   User|null  $user         The user to send the email to. NULL for the currently logged in user.
	 * @param   array      $data         Associative array for tag/variable replacement in the email template.
	 *
	 * @return bool
	 */
	public function sendEmail(string $templateKey, ?User $user = null, array $data = []): bool
	{
		// Do not send emails in the Core version
		if (!defined('ADMINTOOLS_PRO') || !ADMINTOOLS_PRO)
		{
			return true;
		}

		$app  = $this->getApplication();
		$user = $user ?: $app->getIdentity();

		$data = $this->getEmailVariables(
			array_merge(
				[
					'USERNAME' => $user->username,
					'FULLNAME' => $user->name,
				], $data
			)
		);

		try
		{
			TemplateEmails::updateTemplate($templateKey);

			return TemplateEmails::sendMail($templateKey, $data, $user);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Logs security exceptions and processes the IP auto-ban for this IP but does NOT block the request.
	 *
	 * This is used when the request needs to be redirected (e.g. admin secret URL parameter), or when we are only
	 * logging potential problems (e.g. failed login).
	 *
	 * @param   string  $reason                    Block reason code
	 * @param   string  $extraLogInformation       Extra information to be written to the text log file
	 * @param   string  $extraLogTableInformation  Extra information to be written to the extradata field of the log
	 *
	 * @return  bool
	 */
	public function logWithoutBlocking($reason, $extraLogInformation = '', $extraLogTableInformation = '')
	{
		if ($this->wafParams->getValue('tsrenable', 0))
		{
			$this->processAutoBan($reason);
		}

		return $this->logBlockedRequest($reason, $extraLogInformation, $extraLogTableInformation);
	}

	/**
	 * Blocks the request, logs it and processes the IP auto-ban.
	 *
	 * This is the full request blocking experience, triggered when we need to immediately abort the request in progress
	 * to prevent a security issue from affecting the application.
	 *
	 * @param   string  $reason                    Block reason code
	 * @param   string  $message                   The message to be shown to the user
	 * @param   string  $extraLogInformation       Extra information to be written to the text log file
	 * @param   string  $extraLogTableInformation  Extra information to be written to the extradata field of the log
	 *                                             table (useful for JSON format)
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function blockRequest(
		$reason = 'other', $message = '', $extraLogInformation = '', $extraLogTableInformation = ''
	)
	{
		if (empty($message))
		{
			$customMessage = $this->wafParams->getValue('custom403msg', '');
			$message       = trim($customMessage) ?: 'PLG_ADMINTOOLS_MSG_BLOCKED';
		}

		if (!$this->logWithoutBlocking($reason, $extraLogInformation, $extraLogTableInformation))
		{
			return;
		}

		// Merge the default translation with the current translation
		/** @var CMSApplication $app */
		$app = $this->getApplication();

		if ((Text::_('PLG_ADMINTOOLS_MSG_BLOCKED') == 'PLG_ADMINTOOLS_MSG_BLOCKED')
		    && ($message == 'PLG_ADMINTOOLS_MSG_BLOCKED'))
		{
			$message = "Access Denied";
		}
		else
		{
			$message = Text::_($message);
		}

		$message = RescueUrl::processRescueInfoInMessage($message);

		// Show the 403 message
		$use403View = $this->wafParams->getValue('use403view', 0);
		$isFrontend = $app->isClient('site');
		$isApi      = $app->isClient('api');

		if ($isApi)
		{
			@ob_end_clean();

			header('HTTP/1.1 403 Access Denied');

			echo $message;

			$app->close();
		}

		if (!$use403View || !$isFrontend)
		{
			// Using Joomla!'s error page
			$app->input->set('template', null);
			$app->input->set('layout', null);

			throw new Exception($message, 403);
		}

		// Using a view
		$session = $app->getSession();

		if (!$session->get('com_admintools.block', false))
		{
			// This is inside an if-block so that we don't end up in an infinite redirection loop
			$session->set('com_admintools.block', true);
			$session->set('com_admintools.message', $message);

			if ($app->isClient('site') || $app->isClient('administrator'))
			{
				$session->close();
			}

			$app->redirect(Uri::base(), 307);
		}
	}

	/**
	 * Checks if the Rescue URL is being accessed.
	 *
	 * This only applies when IP autoban is enabled and this is an administrator access.
	 *
	 * @return  void
	 */
	public function checkRescueURL()
	{
		$autoban = $this->wafParams->getValue('tsrenable', 0);

		if (!$autoban)
		{
			return;
		}

		// If IP auto-ban is enabled we need to check for a Rescue URL
		RescueUrl::processRescueURL($this);
	}

	public function setApplication(ApplicationInterface $application): void
	{
		$this->application = $application;
	}

	private function getApplication(): ApplicationInterface
	{
		return $this->application;
	}

	/**
	 * Sends a security exception email. Respecting the email throttling settings.
	 *
	 * @param   string     $reason     The blocked request reason
	 * @param   User|null  $recipient  The recipient user. NULL for currently logged in user.
	 * @param   array      $data       Associative array for tag/variable replacement in the email template.
	 *
	 * @return  bool  True on succesfully sent email.
	 */
	private function sendSecurityExceptionEmail(string $reason, ?User $recipient = null, array $data = [])
	{
		if (!$this->isSendingAllowedByEmailThrottling())
		{
			return false;
		}

		return $this->sendEmail(
			'com_admintools.blockedrequest',
			$recipient,
			[
				'REASON' => $reason,
			]
		);
	}

	/**
	 * Get the variables we can use in emails as an associative list (variable => value).
	 *
	 * @param   array  $customVariables  An array of custom variables to add to the return.
	 *
	 * @return  array
	 */
	private function getEmailVariables($customVariables = [])
	{
		$app      = $this->getApplication();
		$siteName = $app->get('sitename');

		$cParams        = ComponentHelper::getParams('com_admintools');
		$email_timezone = $cParams->get('email_timezone', 'AKEEBA/DEFAULT');
		$app            = $this->getApplication();
		$user_tz        = $app->getIdentity()->get('timezone', $app->get('offset', 'GMT'));

		try
		{
			$timezone = new DateTimeZone($user_tz);
		}
		catch (Exception $e)
		{
			$timezone = null;
		}

		if (!empty($email_timezone) && ($email_timezone != 'AKEEBA/DEFAULT'))
		{
			try
			{
				$forcedTimezone = new DateTimeZone($email_timezone);
				$timezone       = $forcedTimezone;
			}
			catch (Exception $e)
			{
				// Just in case someone puts an invalid timezone in there (you can never be too paranoid).
			}
		}

		$date = clone Factory::getDate();
		$date->setTimezone($timezone ?: new DateTimeZone('GMT'));

		$ip = Filter::getIp();

		if ((strpos($ip, '::') === 0) && (strstr($ip, '.') !== false))
		{
			$ip = substr($ip, strrpos($ip, ':') + 1);
		}

		$currentUser = $app->getIdentity();

		if ($currentUser->guest)
		{
			$currentUser = 'Guest';
		}
		else
		{
			$currentUser = $currentUser->username . ' (' . $currentUser->name . ' <' . $currentUser->email . '>)';
		}

		$ipLookupURL = 'https://' . $this->wafParams->getValue('iplookup', 'ip-lookup.net/index.php?ip={ip}');
		$ipLookupURL = str_replace('{ip}', $ip, $ipLookupURL);
		$uri         = Uri::getInstance();
		$url         = $uri->toString(['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment']);

		return array_merge(
			[
				'USER'     => $currentUser,
				'SITENAME' => $siteName,
				'DATE'     => ($date)->format('Y-m-d H:i:s T', true),
				'IP'       => $ip,
				'URL'      => $url,
				'LOOKUP'   => $ipLookupURL,
				'UA'       => $_SERVER['HTTP_USER_AGENT'],
			], $customVariables
		);
	}

	/**
	 * Logs security exceptions
	 *
	 * @param   string  $reason                    Block reason code
	 * @param   string  $extraLogInformation       Extra information to be written to the text log file
	 * @param   string  $extraLogTableInformation  Extra information to be written to the extradata field of the log
	 *                                             table (useful for JSON format)
	 *
	 * @return bool
	 */
	private function logBlockedRequest($reason, $extraLogInformation = '', $extraLogTableInformation = '')
	{
		$ip = $this->getVisitorIPAddress();

		// No point continuing if I cannot get the visitor's IP address
		if ($ip === false)
		{
			return false;
		}

		// Make sure this IP is not in the Administrator Exclusive Allow IP List
		if ($this->isIPInAdminWhitelist())
		{
			return false;
		}

		// Make sure this IP is not in the Site IP Allow List
		if ($this->isIPInAllowList())
		{
			return false;
		}

		// Make sure this IP is not in the "Do not block these IPs" list
		if ($this->isSafeIP())
		{
			return true;
		}

		// Make sure this IP doesn't resolve to a whitelisted domain
		if ($this->isWhitelistedDomain($ip))
		{
			return true;
		}

		// Get the human-readable blocking reason
		$txtReason = $this->getBlockingReasonHumanReadable($reason, $extraLogTableInformation);

		// Get the email tokens, also used for logging
		$tokens = $this->getEmailVariables(
			[
				'REASON' => $txtReason,
			]
		);

		// Log the security exception to file and the database, if necessary
		$this->logSecurityException($reason, $extraLogInformation, $extraLogTableInformation, $txtReason, $tokens);

		// Email the security exception, if necessary
		$this->emailSecurityException($reason, $tokens);

		return true;
	}

	/**
	 * Checks if an IP address should be automatically banned for raising too many security exceptions over a predefined
	 * time period.
	 *
	 * @param   string  $reason  The reason of the ban
	 *
	 * @return  void
	 */
	private function processAutoBan($reason = 'other')
	{
		// The Core version does not support auto-banning IP addresses
		if (!defined('ADMINTOOLS_PRO') || !ADMINTOOLS_PRO)
		{
			return;
		}

		// We need to be able to get our own IP, right?
		if (!function_exists('inet_pton'))
		{
			return;
		}

		// Get the IP
		$ip = Filter::getIp();

		// No point continuing if we can't get an address, right?
		if (empty($ip) || ($ip == '0.0.0.0'))
		{
			return;
		}

		// Check for repeat offenses
		/** @var DatabaseDriver $db */
		$db           = $this->getDatabase();
		$strikes      = $this->wafParams->getValue('tsrstrikes', 3);
		$numfreq      = $this->wafParams->getValue('tsrnumfreq', 1);
		$frequency    = $this->wafParams->getValue('tsrfrequency', 'hour');
		$mindatestamp = 0;

		switch ($frequency)
		{
			case 'second':
				break;

			case 'minute':
				$numfreq *= 60;
				break;

			case 'hour':
				$numfreq *= 3600;
				break;

			case 'day':
				$numfreq *= 86400;
				break;

			case 'ever':
				$mindatestamp = 946706400; // January 1st, 2000
				break;
		}

		$jNow = clone Factory::getDate();

		if ($mindatestamp == 0)
		{
			$mindatestamp = $jNow->toUnix() - $numfreq;
		}

		$jMinDate = clone Factory::getDate($mindatestamp);
		$minDate  = $jMinDate->toSql();

		$sql = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__admintools_log'))
			->where($db->qn('logdate') . ' >= ' . $db->q($minDate))
			->where($db->qn('ip') . ' = ' . $db->q($ip));
		$db->setQuery($sql);
		try
		{
			$numOffenses = $db->loadResult();
		}
		catch (Exception $e)
		{
			$numOffenses = 0;
		}

		if ($numOffenses < $strikes)
		{
			return;
		}

		// Block the IP
		$myIP = @inet_pton($ip);

		if ($myIP === false)
		{
			return;
		}

		$myIP = inet_ntop($myIP);

		$until     = $jNow->toUnix();
		$numfreq   = $this->wafParams->getValue('tsrbannum', 1);
		$frequency = $this->wafParams->getValue('tsrbanfrequency', 'hour');

		switch ($frequency)
		{
			case 'second':
				$until += $numfreq;
				break;

			case 'minute':
				$numfreq *= 60;
				$until   += $numfreq;
				break;

			case 'hour':
				$numfreq *= 3600;
				$until   += $numfreq;
				break;

			case 'day':
				$numfreq *= 86400;
				$until   += $numfreq;
				break;

			case 'ever':
				$until = 2145938400; // January 1st, 2038 (mind you, UNIX epoch runs out on January 19, 2038!)
				break;
		}

		$jMinDate = clone Factory::getDate($until);
		$minDate  = $jMinDate->toSql();

		$record = (object) [
			'ip'     => $myIP,
			'reason' => $reason,
			'until'  => $minDate,
		];

		// If I'm here it means that we have to ban the user. Let's see if this is a simple autoban or
		// we have to issue a permaban as a result of several attacks
		if ($this->wafParams->getValue('permaban', 0))
		{
			// Ok I have to check the number of autoban
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__admintools_ipautobanhistory'))
				->where($db->qn('ip') . ' = ' . $db->q($myIP));

			try
			{
				$bans = $db->setQuery($query)->loadResult();
			}
			catch (Exception $e)
			{
				$bans = 0;
			}

			$limit = (int) $this->wafParams->getValue('permabannum', 0);

			if ($limit && ($bans >= $limit))
			{
				$block = (object) [
					'ip'          => $myIP,
					'description' => 'IP automatically blocked after being banned automatically ' . $bans . ' times',
				];

				try
				{
					$db->insertObject('#__admintools_ipblock', $block);
					Cache::resetCache('ipblock');
				}
				catch (Exception $e)
				{
					// This should never happen, however let's prevent a white page if anything goes wrong
				}
			}
		}

		try
		{
			$db->insertObject('#__admintools_ipautoban', $record);
			Cache::resetCache('ipautoban');
		}
		catch (Exception $e)
		{
			// If the IP was already blocked and I have to block it again, I'll have to update the current record
			$db->updateObject('#__admintools_ipautoban', $record, 'ip');
			Cache::resetCache('ipautoban');
		}

		// Send an optional email
		if ($this->wafParams->getValue('emailafteripautoban', ''))
		{
			// Load the component's administrator translation files
			$jlang = $this->getApplication()->getLanguage();
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
			$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

			$substitutions = $this->getEmailVariables(
				[
					'REASON' => $reason,
					'UNTIL'  => $minDate,
				]
			);

			// Send the email
			try
			{
				$recipients = explode(',', $this->wafParams->getValue('emailafteripautoban', ''));
				$recipients = array_map('trim', $recipients);

				foreach ($recipients as $recipient)
				{
					if (empty($recipient))
					{
						continue;
					}

					$recipientUser           = new User();
					$recipientUser->username = $recipient;
					$recipientUser->name     = $recipient;
					$recipientUser->email    = $recipient;
					$data                    = array_merge(RescueUrl::getRescueInformation($recipient), $substitutions);

					$this->sendEmail('com_admintools.ipautoban', $recipientUser, $data);
				}
			}
			catch (Exception $e)
			{
				// Joomla! 3.5 and later throw an exception when crap happens instead of suppressing it and returning false
			}
		}
	}

	/**
	 * Get the visitor IP address. Return false if we cannot get an IP address or if we get 0.0.0.0 (broken IP
	 * forwarding).
	 *
	 * @return  bool|string
	 */
	private function getVisitorIPAddress()
	{
		// Get our IP address
		$ip = Filter::getIp();

		if ((strpos($ip, '::') === 0) && (strstr($ip, '.') !== false))
		{
			$ip = substr($ip, strrpos($ip, ':') + 1);
		}

		// No point continuing if we can't get an address, right?
		if (empty($ip) || ($ip == '0.0.0.0'))
		{
			return false;
		}

		return $ip;
	}

	/**
	 * Is the IP address in the "Never block these IPs" (safe IPs) list?
	 *
	 * @return  bool
	 */
	private function isSafeIP()
	{
		$safeIPs = $this->wafParams->getValue('neverblockips', '') ?: [];

		if (is_string($safeIPs))
		{
			$safeIPs = array_map('trim', explode(',', $safeIPs));
		}

		$safeIPs = array_map(
			function ($x) {
				return is_array($x) ? $x[0] : $x;
			}, is_array($safeIPs) ? $safeIPs : []
		);

		return !empty($safeIPs) && Filter::IPinList($safeIPs) ? true : false;
	}

	/**
	 * Is the IP address in the Administrator IP Whitelist?
	 *
	 * @return  bool
	 */
	private function isIPInAdminWhitelist(): bool
	{
		if ($this->wafParams->getValue('ipwl', 0) != 1)
		{
			return false;
		}

		$ipTable = Cache::getCache('adminiplist');

		if (!empty($ipTable) && Filter::IPinList($ipTable))
		{
			return true;
		}

		return false;
	}

	/**
	 * Is the IP address in the Site IP Allow List?
	 *
	 * @return  bool
	 *
	 * @since   7.2.4
	 */
	private function isIPInAllowList(): bool
	{
		$ipTable = Cache::getCache('ipallow');

		if (!empty($ipTable) && Filter::IPinList($ipTable))
		{
			return true;
		}

		return false;
	}

	/**
	 * Does the IP address resolve to one of the whitelisted domain names?
	 *
	 * @param   string  $ip
	 *
	 * @return  bool
	 */
	private function isWhitelistedDomain($ip)
	{
		static $whitelistDomains = null;

		if (is_null($whitelistDomains))
		{
			$whitelistDomains = $this->wafParams->getValue('whitelist_domains', []);

			if (is_string($whitelistDomains))
			{
				$whitelistDomains = array_map('trim', explode(',', $whitelistDomains));
			}

			$whitelistDomains = array_map(function($x) {
				return is_array($x) ? $x[0] : $x;
			}, is_array($whitelistDomains) ? $whitelistDomains : []);
		}

		if (!empty($whitelistDomains))
		{
			$remote_domain = @gethostbyaddr($ip);

			if (empty($remote_domain))
			{
				return false;
			}

			foreach ($whitelistDomains as $domain)
			{
				$domain = trim($domain);

				if (strrpos($remote_domain, $domain) === strlen($remote_domain) - strlen($domain))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the blocking reason in a human readable format
	 *
	 * @param   string  $reason
	 * @param   string  $extraLogTableInformation
	 *
	 * @return  string
	 */
	private function getBlockingReasonHumanReadable($reason, $extraLogTableInformation)
	{
		// Load the component's administrator translation files
		$jlang = $this->getApplication()->getLanguage();
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_admintools', JPATH_ADMINISTRATOR, null, true);

		// Get the reason in human readable format
		$txtReason = Text::_('COM_ADMINTOOLS_LOG_LBL_REASON_' . strtoupper($reason));

		if (empty($extraLogTableInformation))
		{
			return $txtReason;
		}

		// Get extra information
		[$logReason,] = explode('|', $extraLogTableInformation);

		return $txtReason . " ($logReason)";
	}

	/**
	 * Write a security exception to the log, as long as logging is enabled and the $reason is not one of the
	 * $reasons_nolog ones
	 *
	 * @param   string  $reason
	 * @param   string  $extraLogInformation
	 * @param   string  $extraLogTableInformation
	 * @param   string  $txtReason
	 * @param   array   $tokens
	 *
	 * @return  void
	 */
	private function logSecurityException($reason, $extraLogInformation, $extraLogTableInformation, $txtReason, $tokens)
	{
		$reasonsNoLog = $this->wafParams->getValue('reasons_nolog', []);

		// Handle legacy data
		if (is_string($reasonsNoLog))
		{
			$reasonsNoLog = explode(',', $reasonsNoLog);
		}

		$reasonsNoLog = is_array($reasonsNoLog) ? $reasonsNoLog : [];

		// If it's a no-log reason let's get outta here
		if (!$this->wafParams->getValue('logbreaches', 0) || in_array($reason, $reasonsNoLog))
		{
			return;
		}

		// Log to file
		$this->logSecurityExceptionToFile($reason, $extraLogInformation, $txtReason, $tokens);

		// Log to the database table
		$this->logSecurityExceptionToDatabase($reason, $extraLogTableInformation, $tokens);
	}

	/**
	 * Log a security exception to our log file
	 *
	 * @param   string  $reason
	 * @param   string  $extraLogInformation
	 * @param   string  $txtReason
	 * @param   array   $tokens
	 */
	private function logSecurityExceptionToFile($reason, $extraLogInformation, $txtReason, $tokens)
	{
		// Write to the log file only if we're told to
		if (!$this->wafParams->getValue('logfile', 0))
		{
			return;
		}

		// Get the log filename
		$logpath = $this->getApplication()->get('log_path');
		$fname   = $logpath . DIRECTORY_SEPARATOR . 'admintools_blocked.php';

		// -- Check the file size. If it's over 1Mb, archive and start a new log.
		if (@file_exists($fname))
		{
			$fsize = filesize($fname);

			if ($fsize > 1048756)
			{
				$altFile = substr($fname, 0, -4) . '.1.php';

				if (@file_exists($altFile))
				{
					unlink($altFile);
				}

				@copy($fname, $altFile);
				@unlink($fname);
			}
		}

		// If the main log file does not exist yet create a new one.
		if (!file_exists($fname))
		{
			$content = <<< END
php
/**
 * =====================================================================================================================
 * Admin Tools debug log file
 * =====================================================================================================================
 *
 * This file contains a dump of the requests which were blocked by Admin Tools. By definition, this file does contain
 * a lot of "hacking signatures" since this is what the Admin Tools component is designed to stop and this is the file
 * logging all these hacking attempts.
 *
 * You can disable the creation of this file by going to Components, Admin Tools, Web Application Firewall, Configure
 * WAF and setting the "Keep a debug log file" option to NO. This is the recommended setting. You should only set this
 * option to YES if you are troubleshooting an issue (Admin Tools is blocking access to your site).
 *
 * Some hosts will mistakenly report this file as suspicious or hacked. As a result they might issue an automated
 * warning and / or block access to your site. Should that happen please ask your host to look in this file and read
 * this header. This file is SAFE since the only executable statement is die() below which prevents the file from being
 * executed at all. If your host does not understand that this file is safe or does not know how to add an exception in
 * their automated scanner to exempt Joomla's log files (all files under this directory) from being flagged as hacked /
 * suspicious we strongly recommend going to a different host that understands how PHP works. It will be safer for you
 * as well. 
 */
 
die();
END;
			$content = "?$content?";
			$content .= ">\n\n";
			file_put_contents($fname, '<' . $content);
		}

		// -- Log the exception
		$fp = @fopen($fname, 'a');

		if ($fp === false)
		{
			return;
		}

		fwrite($fp, str_repeat('-', 79) . PHP_EOL);
		fwrite($fp, "Blocking reason: " . $reason . PHP_EOL . str_repeat('-', 79) . PHP_EOL);
		fwrite($fp, "Reason     : " . $txtReason . PHP_EOL);
		fwrite($fp, 'Timestamp  : ' . gmdate('Y-m-d H:i:s') . " GMT" . PHP_EOL);
		fwrite($fp, 'Local time : ' . $tokens['[DATE]'] . " " . PHP_EOL);
		fwrite($fp, 'URL        : ' . $tokens['[URL]'] . PHP_EOL);
		fwrite($fp, 'User       : ' . $tokens['[USER]'] . PHP_EOL);
		fwrite($fp, 'IP         : ' . $tokens['[IP]'] . PHP_EOL);
		fwrite($fp, 'UA         : ' . $tokens['[UA]'] . PHP_EOL);

		if (!empty($extraLogInformation))
		{
			fwrite($fp, $extraLogInformation . PHP_EOL);
		}

		fwrite($fp, PHP_EOL . PHP_EOL);
		fclose($fp);
	}

	/**
	 * Log a security exception to the database table
	 *
	 * @param   string  $reason
	 * @param   string  $extraLogInformation
	 * @param   array   $tokens
	 *
	 *
	 * @since version
	 */
	private function logSecurityExceptionToDatabase($reason, $extraLogTableInformation, $tokens)
	{
		try
		{
			/** @var DatabaseDriver $db */
			$db   = $this->getDatabase();
			$date = clone Factory::getDate();
			$url  = $tokens['URL'];

			if (strlen($url) > 10240)
			{
				$url = substr($url, 0, 10240);
			}

			$logEntry = (object) [
				'logdate'   => $date->toSql(),
				'ip'        => $tokens['IP'],
				'url'       => $url,
				'reason'    => $reason,
				'extradata' => $extraLogTableInformation,
			];

			$db->insertObject('#__admintools_log', $logEntry);
		}
		catch (Exception $e)
		{
			// Do nothing if the query fails
		}
	}

	/**
	 * Sends information about the security exception by email
	 *
	 * @param   string  $reason
	 * @param   array   $tokens
	 *
	 * @return  bool
	 */
	private function emailSecurityException($reason, $tokens)
	{
		$emailOnException = $this->wafParams->getValue('emailbreaches', '');
		$reasonsNoEmail   = $this->wafParams->getValue('reasons_noemail', '') ?: [];
		$reasonsNoEmail   = is_string($reasonsNoEmail) ? explode(',', $reasonsNoEmail) : $reasonsNoEmail;

		if (empty($emailOnException) || in_array($reason, $reasonsNoEmail))
		{
			return true;
		}

		// Send the email
		try
		{
			$recipients = explode(',', $emailOnException);
			$recipients = array_map('trim', $recipients);

			foreach ($recipients as $recipient)
			{
				if (empty($recipient))
				{
					continue;
				}

				if (empty($recipient))
				{
					continue;
				}

				$recipientUser           = new User();
				$recipientUser->username = $recipient;
				$recipientUser->name     = $recipient;
				$recipientUser->email    = $recipient;
				$tokens                  = array_merge($tokens, RescueUrl::getRescueInformation($recipient));

				$this->sendSecurityExceptionEmail($reason, $recipientUser, $tokens);
			}
		}
		catch (Exception $e)
		{
		}

		return true;
	}

	/**
	 * Is sending an email allowed by the email throttling feature?
	 *
	 * @return  bool
	 *
	 * @since   7.2.2
	 */
	private function isSendingAllowedByEmailThrottling(): bool
	{
		$cParams = ComponentHelper::getParams('com_admintools');

		//  If the throttling feature is disabled allow sending the email.
		if ($cParams->get('email_throttle', 1) != 1)
		{
			return true;
		}

		// Get the frequency limit options
		$maxAllowedEmails = $cParams->get('email_num', 5);
		$timePeriod       = $cParams->get('email_numfreq', 15);
		$timeUOM          = $cParams->get('email_freq', 'minutes');

		switch ($timeUOM)
		{
			case 'seconds':
				$earliestDate = Factory::getDate()->sub(new \DateInterval('PT' . $timePeriod . 'S'));
				break;

			case 'minutes':
				$earliestDate = Factory::getDate()->sub(new \DateInterval('PT' . $timePeriod . 'M'));
				break;

			case 'hours':
				$earliestDate = Factory::getDate()->sub(new \DateInterval('PT' . $timePeriod . 'H'));
				break;

			case 'days':
				$earliestDate = Factory::getDate()->sub(new \DateInterval('P' . $timePeriod . 'D'));
				break;

			case 'ever':
			default:
				$earliestDate = Factory::getDate('2000-01-01 00:00:00');
				break;
		}

		$reasonsNoLog = $this->wafParams->getValue('reasons_nolog', []) ?: [];
		$reasonsNoLog = is_array($reasonsNoLog)
			? $reasonsNoLog
			: array_map('trim', @explode(',', $reasonsNoLog));

		/** @var DatabaseDriver $db */
		$db      = $this->getDatabase();
		$logDate = $earliestDate->toSql();
		$sql     = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__admintools_log'))
			->where($db->qn('logdate') . ' >= :logDate')
			->bind(':logDate', $logDate);

		// Apply the where clause only if we have excluded any reason from logging
		if (!empty($reasonsNoLog))
		{
			$sql->whereNotIn($db->qn('reason'), $reasonsNoLog, ParameterType::STRING);
		}

		$db->setQuery($sql);

		try
		{
			$numOffenses = $db->loadResult() ?: 0;
		}
		catch (Exception $e)
		{
			$numOffenses = 0;
		}

		return $numOffenses <= $maxAllowedEmails;
	}
}