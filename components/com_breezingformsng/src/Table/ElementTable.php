<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Table;

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2008-2020 by Markus Bopp
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

final class ElementTable extends Table
{
	/** @var int|string|null */
	public $id = null;     // general parameters
	/** @var int|string|null */
	public $form = null;     // form id
	/** @var int|string|null */
	public $page = null;     // page number
	/** @var int|string|null */
	public $ordering = null;     // ordering index
	/** @var int|string|null */
	public $published = null;     // publish status
	/** @var string|null */
	public $name = null;     // identifier
	/** @var string|null */
	public $title = null;     // fancy name
	/** @var string|null */
	public $type = null;     // element type
/*
-----------------------------------------Element Parameter Cross Reference-------------------------------------------
Element             logging posx posy width height flag1    flag2    data1   data2     data3  script1 script2 script3
---------------------------------------------------------------------------------------------------------------------
Static Text/HTML    -       px%  px%  px%   px%    -        -        value   -         -      -       -       -
Rectangle           -       px%  px%  px%   px%    -        -        border  bckg.col. -      -       -       -
Image               -       px%  px%  px%   px%    -        -        img.url alt.text  -      -       -       -
Tooltip             -       px%  px%  -     -      type     -        img.url text      -      -       -       -
Regular Button      -       px%  px%  -     -      -        disabled -       caption   -      -       action  -
Graphic Button      -       px%  px%  -     -      capt.pos disabled img.url caption   -      -       action  -
Icon                -       px%  px%  -     -      capt.pos border   img.url caption   img.f2 -       action  -
Checkbox            yes     px%  px%  -     -      checked  disabled value   label     -      init    action  valid.
Radio Button        yes     px%  px%  -     -      checked  disabled value   label     -      init    action  valid.
Select List         yes     px%  px%  px    px     multiple disabled size    options   -      init    action  valid.
Query List          yes     px%  px%  px%   m.rows dsp.hdr  dsp.ckbx setting query     cols   -       -       -
Text                yes     px%  px%  szpx  maxlen password dis/rdo  value   -         -      init    action  valid.
Textarea            yes     px%  px%  szpx  colpx  -        dis/rdo  value   -         -      init    action  valid.
File Upload         yes     px%  px%  size  limit  -        disabled dir     types     -      init    action  valid.
Hidden Input        yes     -    -     -     -     -        -        value   -         -      init    -       valid.
---------------------------------------------------------------------------------------------------------------------

Query List Settings: border / cellspacing / cellpadding / <tr(h)>class / <tr(1)>class / <tr(2)>class
*/
	/** @var string|null */
	public $class1 = null;     // css class for <div>
	/** @var string|null */
	public $class2 = null;     // css class for <element>

	/** @var int|string|null */
	public $logging = null;     // element is logged in email/database no/yes

	/** @var int|string|null */
	public $posx = null;     // horizontal position in px or %
	/** @var int|string|null */
	public $posxmode = null;     // 0-px 1-%
	/** @var int|string|null */
	public $posy = null;     // vertical position in px or %
	/** @var int|string|null */
	public $posymode = null;     // 0-px 1-%
	/** @var int|string|null */
	public $width = null;     // width in % or px
	/** @var int|string|null */
	public $widthmode = null;     // 0-px 1-%
	/** @var int|string|null */
	public $height = null;     // height in px
	/** @var int|string|null */
	public $heightmode = null;     // 0-fixed px 1-auto 2-automax

	/** @var int|string|null */
	public $flag1 = null;     // element specific data, see xref
	/** @var int|string|null */
	public $flag2 = null;
	/** @var string|null */
	public $data1 = null;
	/** @var string|null */
	public $data2 = null;
	/** @var string|null */
	public $data3 = null;

	/** @var int|string|null */
	public $script1cond = null;     // init script
	/** @var int|string|null */
	public $script1flag1 = null;     // condition 1 = on form entry no/yes
	/** @var int|string|null */
	public $script1flag2 = null;     // condition 2 = on page entry
	/** @var int|string|null */
	public $script1id = null;     // script id
	/** @var string|null */
	public $script1code = null;     // custom code

	/** @var int|string|null */
	public $script2cond = null;     // action script
	/** @var int|string|null */
	public $script2flag1 = null;     // action 1 = Click
	/** @var int|string|null */
	public $script2flag2 = null;     // action 2 = Blur
	/** @var int|string|null */
	public $script2flag3 = null;     // action 3 = Change
	/** @var int|string|null */
	public $script2flag4 = null;     // action 4 = Focus
	/** @var int|string|null */
	public $script2flag5 = null;     // action 5 = Select
	/** @var int|string|null */
	public $script2id = null;     // script id
	/** @var string|null */
	public $script2code = null;     // custom code

	/** @var int|string|null */
	public $script3cond = null;     // validation script
	/** @var int|string|null */
	public $script3id = null;     // script id
	/** @var string|null */
	public $script3msg = null;     // message
	/** @var string|null */
	public $script3code = null;     // custom code

	/** @var int|string|null */
	public $mailback = null;
	/** @var string|null */
	public $mailbackfile = null;

	public function __construct(DatabaseInterface $db)
	{
		parent::__construct('#__facileforms_elements', 'id', $db);
	} // constructor

	public function load($id = null, $reset = null)
	{
		return parent::load($id, $reset ?? true);
	}

} // class ElementTable

