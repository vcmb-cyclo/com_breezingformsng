<?php die() ?>
Admin Tools 7.5.4
================================================================================
+ Improved support for Joomla! 5.1's backend colour schemes
+ You can now choose the action for an invalid administrator secret URL parameter
+ Components sidebar menu item to open the appropriate server config maker for your site
~ Remove Itemid from Suspicious Core Parameters, it has its own feature (ItemidShield)
~ Re-arrange order of execution to process IP blocks before request blocking features

Admin Tools 7.5.3
================================================================================
~ Allow empty Itemid even when Suspicious Core Parameters feature is enabled
# [MEDIUM] Server configuration maker: Fixed fatal error when web servers different than Apache are used
# [MEDIUM] Joomla does not return the plugin ID when it's disabled, leading to broken links in the UI

Admin Tools 7.5.2
================================================================================
! Joomla-recommended .htaccess code was breaking the site

Admin Tools 7.5.1
================================================================================
! Detecting PHP handlers can break if there is no .htaccess file yet
! Error in version.php breaks the control panel interface

Admin Tools 7.5.0
================================================================================
+ Improved support for Joomla! 5.1 dark mode
+ Detect and import PHP version directives into the .htaccess Maker
~ Reintroduce old value format workarounds for people being late to upgrading from Joomla! 3.x
# [LOW] Admin Tools Core showed an (unsupported) URL Redirection menu item
# [LOW] Some numeric Configure WAF options did not have their limits enforced
# [LOW] Double Gzip/Brotli compression for some core Joomla! files when both compression algorithms are supported

Admin Tools 7.4.9
================================================================================
+ Optional description field on “Never block these IPs” and “Never blocked domains”
# [MEDIUM] Suspicious Core Parameter always applied the cmd filter, leading to false positives

Admin Tools 7.4.8
================================================================================
~ Added more options to the not log and not email for the reasons options

Admin Tools 7.4.7.1
================================================================================
! CSS compilation error

Admin Tools 7.4.7
================================================================================
+ WAF Exceptions now work with the Block Suspicious URL Parameters feature
+ Updated environment stats collection code
~ Even better workaround for the Joomla! Database Maintenance page bug
# [HIGH] Running the Auto-import configuration scheduled task from CLI results in PHP error
# [MEDIUM] Could not turn off Block Suspicious URL Parameters

Admin Tools 7.4.6
================================================================================
~ Workaround for the Joomla! bug making it erroneously claim in the Maintenance: Database page that Admin Tools' database tables are not up-to-date when they actually are.

Admin Tools 7.4.5
================================================================================
+ Block Suspicious Core Parameters feature
~ The reason in emails reporting a blocked IP was always reported as blocked IP which wasn't useful
~ Move Unblock My IP into Security when not showing the Graphs panel to balance the display
# [HIGH] The “Add persistent offenders to the IP Disallow List” did not work due to a typo
# [LOW] Missing translations when using Joomla Scheduled Tasks
# [LOW] Missing language string
# [LOW] The additional context of blocked requests was not shown in the Blocked Requests Log page
# [LOW] PHP 8.3 deprecated notice in ComponentParameters service (no functional issue)
# [LOW] PHP deprecated notice about implicit float to integer conversion on PHP File Change Scanner (no functional issue)

Admin Tools 7.4.4
================================================================================
+ Support bCrypt encryption for Administrator Password Protection on Apache 2.4+
# [HIGH] NginX Conf Maker: Backend protection would make backend unavailable in newer NginX versions
# [LOW] URL Redirections appears in the Core version, even though it won't do anything; removed
# [LOW] Htaccess Maker: Fixed PHP notices when a particular combination of options was used
# [LOW] HSTS option UI wouldn't let you turn it off

Admin Tools 7.4.3
================================================================================
+ Support for Joomla! 5 installations with a custom public folder
~ Joomla 5 Dark Mode workarounds

Admin Tools 7.4.2
================================================================================
# [HIGH] PHP File Change Scanner scans all files, regardless of the configured extensions

Admin Tools 7.4.1
================================================================================
# [LOW] Possible PHP error when updating this along other extensions using the same post-installation script

Admin Tools 7.4.0
================================================================================
+ Notice about Joomla 4 End of Service
+ Improve HSTS support with an option which adds "includeSubDomains; preload" to the header (gh-303)
+ PHP File Change Scanner: options to scan double extensions and case-insensitive extensions
~ Changed the plugins' namespace
~ Joomla 5 preparation: Use DatabaseInterface instead of DatabaseDriver
~ Joomla 5 preparation: Work around backwards incompatible changes in core plugin events
~ Joomla 5 preparation: Normalise plugin event calling
~ Joomla 5 preparation: Plugin event handlers clean-up
~ Joomla 5 preparation: Loading form data MUST NOT return a Table anymore
~ Joomla 5 preparation: PHP File Change Scanner would not work (type error)
# [LOW] Fixed deleting or unpublishing an entry in the WAF Deny List

Admin Tools 7.3.4
================================================================================
~ Block uninstallation of child extensions
# [LOW] opcache_invalidate may not invalidate a file
# [LOW] Wrong documentation link in the scheduling information page
# [LOW] Cannot save the password authentication when WebAuthn preference is enabled from the regular user edit page

Admin Tools 7.3.3
================================================================================
~ Workaround for CloudAccess and other hosts with broken Apache installations that don't understand the If directive
~ Only show warning to regenerate the server configuration file if it was generated by Admin Tools to begin with
# [MEDIUM] PHP File Change Scanner fails when the MySQL packet size is too small (e.g. 1MiB) when scanning big files (>400KiB) with Calculate File Diffs enabled
# [LOW] “Disable editing user properties for these user groups” does not let you select the Super Users group

Admin Tools 7.3.2
================================================================================
# [LOW] Fixed display issue on the Scheduling Information page

Admin Tools 7.3.1
================================================================================
# [HIGH] Quick Setup Wizard will still apply the wrong site root path to the .htaccess Maker

Admin Tools 7.3.0
================================================================================
+ IP Allow List
# [HIGH] Password-protect administrator can get in the way of www to non-www redirection
# [HIGH] Empty “Force HTTPS for these URLs” will cause any HTTP access to redirect to the HTTPS site's root
# [HIGH] Quick Setup Wizard will apply the wrong site root path to the .htaccess Maker
# [MEDIUM] Plugins not enabled on clean installation
# [MEDIUM] Configuration monitor was not executing
# [LOW] .htaccess Maker accidentally prevented the use of Authorization HTTP headers with Joomla
# [LOW] Saving WAF Configuration may fail if there are empty items in subform fields

Admin Tools 7.2.3
================================================================================
+ Block Joomla API exploitation in Joomla 4.0 to 4.2
# [LOW] Wrong label for Mark Safe / Unsafe in PHP File Change Scanner reports' toolbar
# [LOW] Automatic IP blocking notification email: wrong shortcode for IP lookup

Admin Tools 7.2.2
================================================================================
# [MEDIUM] Temporary Super User could fail on some sites due to an off-by-one error
# [MEDIUM] Email throttling options were not taken into account

Admin Tools 7.2.1
================================================================================
~ Joomla 4.2 changed the togglable inline help without documenting it (b/c break); worked around.
# [HIGH] Some Joomla Tasks are broken
# [HIGH] Importing settings could reply with "No active transaction" in some cases
# [LOW] Wrong grammatical case (nominative instead of genitive) in months in some languages e.g. Greek

Admin Tools 7.2.0
================================================================================
+ You can now choose User Notes category while saving the IP used during sign-up
- Removed PHP version warnings. This is now included in Joomla itself.
- Removed the PHP Easter Eggs option from the .htaccess Maker and similar features
~ Requires Joomla 4.2 or later
~ Requires PHP 7.4.0 or later
~ Password Protect Administrator: improve compatibility with Apache 2.2 and 2.4
~ Changed all Control Panel warnings to much more compact DETAILS elements
~ The “Exclusive Allow IP List” setting in Quick Setup is now Off by default
# [LOW] In some server configurations, the IP of the user was never saved, even if requested to

Admin Tools 7.1.11
================================================================================
+ Help buttons
~ Improved component menu with relevant quick actions
~ Save and Save & Close buttons are now separate, as per Joomla 4.2 UI guidelines
# [MEDIUM] Password-protect Administrator: Fixed disabling this feature from the web interface
# [LOW] Critical Files and Custom Critical Files may result in a PHP Warning if you upgraded from Joomla 3 but never visited and saved the WAF Configuration under Joomla 4

Admin Tools 7.1.10
================================================================================
~ Updated troubleshooting URLs to point to the correct Joomla 4–specific documentation page
# [LOW] PHP 8.1 deprecated notice when checking if FOF is still installed
# [LOW] Enabling the exception handler email and disabling sending emails in Global Configuration results in the wrong error being displayed in the Joomla frontend.

Admin Tools 7.1.9
================================================================================
- Removed the Enable IP Workarounds option
# [MEDIUM] NginX Configuration Maker: frontend protection would make the backend templates' static media fail to load
# [LOW] Custom Monitored Files changed are not reported and cause a PHP notice
# [LOW] Regression: Multiple email addresses were not allowed in Configure WAF options
# [LOW] Htaccess Maker: Fixed explicitly allowing CORS
# [LOW] NginX Conf Maker: Fixed explicitly allowing CORS
# [LOW] WebConfig Maker: Fixed explicitly allowing CORS
# [LOW] Possible PHP 8 exception if you have upgraded from Admin Tools 6 or earlier, have set up no logging for specific reasons and have not clicked on Save & Close in the Configure WAF page since upgrading to Admin Tools 7.

Admin Tools 7.1.8
================================================================================
# [HIGH] Running the PHP File Change Scanner with the frontend URL doesn't work

Admin Tools 7.1.7
================================================================================
+ Added .xsl to the allowed file types in front- and backend directories in the .htaccess Maker, NginX Conf Maker and web.config Maker
+ Add Show Inline Help support in component options for Joomla 4.1
# [LOW] Fixed displaying Rescue URL info inside message shown to blocked users
# [LOW] Rare concurrency issue saving critical files into the database could cause an exception during a page load

Admin Tools 7.1.6
================================================================================
+ Better message when Allowed Domains kicks in to help you figure out what went wrong and how to fix it
+ Automatically allow TinyMCE plugins when “Disable client-side risky behavior in frontend static content” is enabled.
~ Improved handling of empty multi-selection fields in the Configure WAF page
# [MEDIUM] Upgrading from Admin Tools 6 with non-empty Allowed Domains would break the migrated site
# [MEDIUM] Defining redirections with query parameters could result in the wrong redirection taking place
# [MEDIUM] Nginx Maker: Fixed fatal error under PHP 8 and "Reverse Proxy / Load Balancer IPs" are used

Admin Tools 7.1.5
================================================================================
# [HIGH] Many emails are not sent by the plugin
# [MEDIUM] Cannot ban/unban an IP in Blocked Requests page because of backwards incompatible changes in Joomla 4.1.1
# [LOW] Fixed choosing the correct option in "Browser cookie override for the administrator secret URL parameter"

Admin Tools 7.1.4
================================================================================
# [LOW] Blocked Requests Log: Filtering by reason was not working (gh-272)
# [LOW] A “Show Inline Help” button is shown in .htaccess Maker but there never was any inline help or tooltips for it.

Admin Tools 7.1.3
================================================================================
# [HIGH] Wrong base directory created by the Quick Start (gh-271)
# [HIGH] Core edition would block IPs when third party extensions were using the third party blocked requests feature of Admin Tools even though they cannot be managed in the Core edition
# [HIGH] Delete Inactive Users may make the site inaccessible due to a database error
# [LOW] Cannot delete Auto IP Blocking History entries
# [LOW] Missing language string COM_ADMINTOOLS_WAFEMAILTEMPLATE_REASON_IPAUTOBAN

Admin Tools 7.1.2
================================================================================
+ Temporary Super User passwords respect Joomla's password options (gh-265)
+ Support for Joomla 4.1's Inline Help in several pages of the component
+ Support for Joomla 4.1's Scheduled Tasks for the PHP File Change Scanner
+ Joomla Scheduled Tasks to replace the pseudo-scheduling in the system plugin
+ Set a persistent cookie for Administrator Secret URL Parameter (gh-268)
# [HIGH] Unhandled exception when you have turned off email sending in your site's Global Configuration
# [HIGH] Sometimes you get an immediate fatal error when a request is blocked

Admin Tools 7.1.1
================================================================================
+ Export/Import the list of blocked User Agents
# [LOW] Database update might fail on some servers

Admin Tools 7.1.0
================================================================================
+ Disable password reset for specific user groups
+ Server config makers: always allow access to HTML files used in TinyMCE plugins (used by Joomla's default TinyMCE editor)
~ Improved Quick Setup Wizard default settings for the .htaccess Maker
~ Quick Setup Wizard: Allowed domains resolving to IP 127.0.0.1 or ::1 will not be applied (automatically allowed anyway)
~ Aligned default option values visible in the Configure WAF page with what the WAF does if you do not save its configuration through the Configure WAF page
# [HIGH] The Rescue URL instructions did not work throughout the 7.0 release cycle
# [MEDIUM] The “Email PHP Exceptions to this address” feature did not do anything when enabled
# [MEDIUM] PHP warning when applying the Quick Setup Wizard when the option to create a .htaccess file is checked
# [LOW] The user actions log entries for some actions was badly formatted

Admin Tools 7.0.10
================================================================================
- Removed “Convert all links to HTTPS” feature; it has stopped making sense since circa 2015.
# [HIGH] Cannot edit component Options on PHP 8 if the site had Admin Tools 5.1.0 to 5.7.2 installed in the past
# [HIGH] Emails not being sent on security exception (gh-260)
# [MEDIUM] 404Shield: PHP 8 error when legacy data is stored in the configuration
# [LOW] PHP File Change Scanner: Marked As Safe filter not applied viewing scan results (ak-36399)

Admin Tools 7.0.9
================================================================================
# [HIGH] 404Shield wouldn't work reliably
# [LOW] Database warning in some update scenarios

Admin Tools 7.0.8
================================================================================
# [MEDIUM] Missing option “IP blocking of repeat offenders” could result in no IP auto–blocking

Admin Tools 7.0.7
================================================================================
~ Ensure the correct collation of all database tables and columns used by the extension
# [HIGH] PHP 8 error in NginXConfigMaker when legacy data is stored in the configuration
# [MEDIUM] Cannot delete Temporary Super Users
# [MEDIUM] Server protection: typo in the default value for field "Backend directories where file type exceptions are allowed"
# [LOW] Bootstrap 5.1.2 included in Joomla 4.0.4 broke the CSS for Control Panel icons
# [LOW] Blocked requests log: Fixed fatal error while banning or unbanning an IP

Admin Tools 7.0.6
================================================================================
~ Backend/Frontend file types allowed in selected directories are now case–insensitive
# [HIGH] Database error on blocked request if the Do Not Log Reasons list is empty
# [HIGH] PHP error if you leave “Do not send email notifications for these reasons” empty.
# [HIGH] PHP 8 error using the Quick Setup Wizard
# [HIGH] PHP 8 error in some features when legacy data is stored in the configuration
# [MEDIUM] Emails not sent for successful/failed admin long, critical files monitoring and super users monitoring
# [MEDIUM] Exception emails were missing information
# [MEDIUM] Feature "Change administrator login directory to" was missing

Admin Tools 7.0.5
================================================================================
+ Support improved Joomla Update in Joomla 4.0.4
~ Fixed padding in some modal dialogs
# [HIGH] Temporary Super Users still had a problem with a class name having the wrong case
# [HIGH] “Neutralise SVG execution” in NginX Conf Maker would cause a server error.
# [MEDIUM] Fatal error in Quick Start wizard on first installation if you choose to create a security–tightened .htaccess file
# [MEDIUM] Fix Permissions feature was stuck in a endless loop
# [LOW] Troubleshooting emails do not include troubleshooting links due to a typo
# [LOW] Features Bad Words Filtering and Quick Start reminder were always disabled
# [LOW] NginX Conf Maker doesn't show the right path in the message telling you how to enable the custom config file.
# [LOW] Check Temp and Log modal window didn't automatically close

Admin Tools 7.0.4
================================================================================
~ Improve language string
# [MEDIUM] Fixed fatal error while trying to access Temporary Super Users page
# [MEDIUM] Main Password feature wasn't blocking unprotected access
# [LOW] Wrong email subject for blocked requests
# [LOW] Default update server URL in the XML manifest is wrong
# [LOW] Links in the Statistics box below the Control Panel graphs result in an error

Admin Tools 7.0.3
================================================================================
~ Main Password: only show the views present in the Core or Pro version you have installed
~ Prevent accidental installation attempt on Joomla 3
# [HIGH] Admin Tools Core: Control Panel fails with PHP error
# [HIGH] Installation from scratch on Joomla 4 doesn't work due to a SQL error
# [MEDIUM] The Action Log plugin does not let you use any component action logged by it when enabled
# [MEDIUM] Quick Start: Allowed Domains option did not display at all and could not be applied
# [LOW] Htaccess Maker etc preview doesn't work
# [LOW] PHP Notice in Main Password's switch fields due to change made in Joomla 4.0 stable.
# [LOW] Downgrading from Pro to Core didn't work correctly
# [LOW] PHP Warning on first run of Pro version because the .htaccess Maker is not yet configured

Admin Tools 7.0.2
================================================================================
# [HIGH] Core version does not install due to an XML manifest typo
# [HIGH] Does not work on Windows on the latest Joomla 4 RC versions

Admin Tools 7.0.1
================================================================================
# [HIGH] Impossible to save the WAF configuration on some sites upgraded from 6.x which had some options empty.
# [MEDIUM] Extensions not enabled automatically on installation.

Admin Tools 7.0.0
================================================================================
+ Rewritten with Joomla 4 core MVC and Bootstrap 5 native styling
+ Option to disable password logins when WebAuthn is enabled for a user account (gh-248)