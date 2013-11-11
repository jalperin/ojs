<?php

/**
 * @defgroup plugins_importexport_crossrefbb
 */

/**
 * @file plugins/importexport/crossrefbb/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_crossrefbb
 *
 * @brief Wrapper for the CrossRef export plugin.
 */


require_once('CrossRefBBExportPlugin.inc.php');

return new CrossRefBBExportPlugin();

?>
