<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 1.9
 * @package BreezingForms
 * @copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024 by XDA+GIL
 * @license Released under the terms of the GNU General Public License
 **/
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Language\Text;


/**
 * These constants are necessary unless BreezingForms still needs __little__ portions of legacy code 
 * e.g. _MOS_ALLOWHTML => can't replace that with JInputFilter so easy, because ff_getParam() roughly depends on it.
 */

/**
 * Legacy define, _ISO define not used anymore. All output is forced as utf-8.
 * @deprecated	As of version 1.5
 */
if (!defined('_ISO'))
    define('_ISO', 'charset=utf-8');

/**
 * Legacy constant, use _JEXEC instead
 * @deprecated	As of version 1.5
 */
if (!defined('_VALID_MOS'))
    define('_VALID_MOS', 1);

/**
 * Legacy constant, use _JEXEC instead
 * @deprecated	As of version 1.5
 */
if (!defined('_MOS_MAMBO_INCLUDED'))
    define('_MOS_MAMBO_INCLUDED', 1);

/**
 * Legacy constant, use DATE_FORMAT_LC instead
 * @deprecated	As of version 1.5
 */
if (!defined('_DATE_FORMAT_LC'))
    DEFINE('_DATE_FORMAT_LC', Text::_('DATE_FORMAT_LC1')); //Uses PHP's strftime Command Format

/**
 * Legacy constant, use DATE_FORMAT_LC2 instead
 * @deprecated	As of version 1.5
 */
if (!defined('_DATE_FORMAT_LC2'))
    DEFINE('_DATE_FORMAT_LC2', Text::_('DATE_FORMAT_LC2'));

/**
 * Legacy constant, use InputFilter instead
 * @deprecated	As of version 1.5
 */
if (!defined('_MOS_NOTRIM'))
    DEFINE("_MOS_NOTRIM", 0x0001);

/**
 * Legacy constant, use InputFilter instead
 * @deprecated	As of version 1.5
 */
if (!defined('_MOS_ALLOWHTML'))
    DEFINE("_MOS_ALLOWHTML", 0x0002);

/**
 * Legacy constant, use InputFilter instead
 * @deprecated	As of version 1.5
 */
if (!defined('_MOS_ALLOWRAW'))
    DEFINE("_MOS_ALLOWRAW", 0x0004);