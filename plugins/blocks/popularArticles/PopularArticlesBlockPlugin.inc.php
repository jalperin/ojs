<?php

/**
 * @file PopularArticlesBlockPlugin.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Edited by Andreas Ihrig
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PopularArticlesBlockPlugin
 * @ingroup plugins_blocks_user
 *
 * @brief Class for Popular Article Block plugin
 */


import('lib.pkp.classes.plugins.BlockPlugin');

class PopularArticlesBlockPlugin extends BlockPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			$this->addLocaleData();

			$this->import('PopularArticlesDAO');
			$PopularArticlesDAO =& new PopularArticlesDAO();
			$ret =& DAORegistry::registerDAO('PopularArticlesDAO', $PopularArticlesDAO);
		}
		return $success;
	}

	function getContents(&$templateMgr) {
		$journal =& Request::getJournal();
		if (!$journal) return '';

		$PopularArticlesDAO =& DAORegistry::getDAO('PopularArticlesDAO');
		$templateMgr->assign('popularArticles', $PopularArticlesDAO->getPopularArticles($journal->getJournalId(), $this->getQuantity()));

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$templateMgr->assign_by_ref('journalDao', $journalDao);

		return parent::getContents($templateMgr);
	}

	/**
	 * Determine whether the plugin is enabled. Overrides parent so that
	 * the plugin will be displayed during install.
	 */
	function getEnabled() {
		if (!Config::getVar('general', 'installed')) return true;
		return parent::getEnabled();
	}

	/**
	 * Install default settings on system install.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the block context. Overrides parent so that the plugin will be
	 * displayed during install.
	 * @return int
	 */
	function getBlockContext() {
		if (!Config::getVar('general', 'installed')) return BLOCK_CONTEXT_RIGHT_SIDEBAR;
		return parent::getBlockContext();
	}

	/**
	 * Determine the plugin sequence. Overrides parent so that
	 * the plugin will be displayed during install.
	 */
	function getSeq() {
		if (!Config::getVar('general', 'installed')) return 1;
		return parent::getSeq();
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_LEFT_SIDEBAR, BLOCK_CONTEXT_RIGHT_SIDEBAR, BLOCK_CONTEXT_HOMEPAGE);
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'PopularArticlesBlockPlugin';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return Locale::translate('plugins.block.popularArticles.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return Locale::translate('plugins.block.popularArticles.description');
	}

	function getQuantity() {
		return 7;
	}
}

?>
