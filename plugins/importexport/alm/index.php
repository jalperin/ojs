<?php

/**
 * @defgroup plugins_importexport_alm
 */

/**
 * @file plugins/importexport/alm/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_alm
 * @brief Wrapper for Alm export plugin.
 *
 */

// $Id$


require_once('AlmExportPlugin.inc.php');

return new AlmExportPlugin();

?>
