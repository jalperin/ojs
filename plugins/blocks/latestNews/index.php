<?php

/**
 * @defgroup plugins_blocks_popularArtilces
 */

/**
 * @file plugins/blocks/recentArticles/index.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_blocks_popularArtilces
 * @brief Wrapper for LatestNewsBlockPlugin block plugin.
 *
 */


require_once('LatestNewsBlockPlugin.inc.php');

return new LatestNewsBlockPlugin();

?>