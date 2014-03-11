<?php

/**
 * @file plugins/generic/alm/ArticleInfoSender.php
 *
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleInfoSender
 * @ingroup plugins_generic_alm
 *
 * @brief Scheduled task to send article information to the ALM server.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('lib.pkp.classes.core.JSONManager');


class ArticleInfoSender extends ScheduledTask {

	/** @var $_plugin AlmPlugin */
	var $_plugin;

	/** @var $_depositUrl string */
	var $_depositUrl;

	/**
	 * Constructor.
	 * @param $argv array task arguments
	 */
	function ArticleInfoSender($args) {
		PluginRegistry::loadCategory('generic');
		$plugin =& PluginRegistry::getPlugin('generic', 'CrossRefBBExportPlugin'); /* @var $plugin AlmPlugin */
		$this->_plugin =& $plugin;

		if (is_a($plugin, 'CrossRefBBExportPlugin')) {
			$plugin->addLocaleData();
		}

		parent::ScheduledTask($args);
	}

	/**
	 * @see ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.generic.alm.senderTask.name');
	}

	/**
	 * @see FileLoader::execute()
	 */
	function execute() {
		if (!$this->_plugin) return false;

		if (!$this->_depositUrl) {
			$this->notify(SCHEDULED_TASK_MESSAGE_TYPE_ERROR, __('plugins.generic.alm.senderTask.error.noDepositUrl'));
			return false;
		}

		$plugin = $this->_plugin;

		$journals = $this->_getJournals();
        $request =& Application::getRequest();

		foreach ($journals as $journal) {
            $unregisteredArticles = $plugin->_getUnregisteredArticles($journal);

            $unregisteredArticlesIds = array();
            foreach ($unregisteredArticles as $article) {
                array_push($unregisteredArticlesIds, $article->getId());
            }
            $exportSpec = array(DOI_EXPORT_ARTICLES => $unregisteredArticlesIds);

            $result = $plugin->registerObjects($request, $exportSpec, $journal);
		}
	}

	/**
	 * Get all journals that meet the requirements to have
	 * their articles DOIs sent to Crossref .
	 * @return array
	 */
	function _getJournals() {
		$plugin =& $this->_plugin;
		$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journalFactory =& $journalDao->getJournals(true);

		$journals = array();
		while($journal =& $journalFactory->next()) {
			$journalId = $journal->getId();
			if (!$plugin->getSetting($journalId, 'enabled') && !$plugin->getSetting($journalId, 'automaticRegistration')) continue;

			$doiPrefix = null;
			$pubIdPlugins =& PluginRegistry::loadCategory('pubIds', true, $journalId);
			if (isset($pubIdPlugins['DOIPubIdPlugin'])) {
				$doiPubIdPlugin =& $pubIdPlugins['DOIPubIdPlugin'];
				$doiPrefix = $doiPubIdPlugin->getSetting($journalId, 'doiPrefix');
			}

			if ($doiPrefix) {
				$journals[] =& $journal;
			} else {
				$this->notify(SCHEDULED_TASK_MESSAGE_TYPE_WARNING,
					__('plugins.generic.alm.senderTask.warning.noDOIprefix', array('path' => $journal->getPath())));
			}
		}

		return $journals;
	}
}
?>
