<?php

/**
 * @file plugins/importexport/alm/AlmExportPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AlmExportPlugin
 * @ingroup plugins_importexport_alm
 *
 * @brief ALM metadata export plugin
 */

import('classes.plugins.ImportExportPlugin');

class AlmExportPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'AlmExportPlugin';
	}

	function getDisplayName() {
		return __('plugins.importexport.alm.displayName');
	}

	function getDescription() {
		return __('plugins.importexport.alm.description');
	}

	function display(&$args) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args);

		$journal =& Request::getJournal();

		switch (array_shift($args)) {
            //
            // Command Line Only
            //
            default:
				$this->setBreadcrumbs();
				$templateMgr->assign_by_ref('journal', $journal);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	function exportArticles(&$articles, $outputFile = null) {
		if (!isset($articles) || count($articles) == 0) return false;

		$output = '';
		foreach ($articles as $article) {
			$doi = $article->getStoredDOI();
			$publishedDate = date('Y-m-d', strtotime($article->getDatePublished()));
			$title = $article->getLocalizedTitle();
			if ($doi && $publishedDate && $title)
				$output .= "$doi $publishedDate $title\n";
		}


		// dump out the results
		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'w'))===false) return false;
			fwrite($h, $output);
			fclose($h);
		} else {
			header("Content-Type: text/plain");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"alm.txt\"");
			echo $output;
		}
		return true;
	}

	function exportIssues(&$issues, $outputFile = null) {
		$this->import('AlmExportDom');

		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');

		$articles = array();
		foreach ($issues as $issue) {
			foreach ($sectionDao->getSectionsForIssue($issue->getId()) as $section) {
				foreach ($publishedArticleDao->getPublishedArticlesBySectionId($section->getId(), $issue->getId()) as $article) {
					$articles[] =& $article;
				}
			}
		}

		return $this->exportArticles($articles, $outputFile);
	}

	/**
	 * Execute import/export tasks using the command-line interface.
	 * @param $args Parameters to the plugin
	 */
	function executeCLI($scriptName, &$args) {
		$outFile = array_shift($args);
		$journalPath = array_shift($args);

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');

		$journal =& $journalDao->getJournalByPath($journalPath);

		if (!$journal) {
			if ($journalPath != '') {
				echo __('plugins.importexport.alm.cliError') . "\n";
				echo __('plugins.importexport.alm.error.unknownJournal', array('journalPath' => $journalPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		} else {
			$doiPrefix = $journal->getSetting('doiPrefix');
			if (!$doiPrefix) {
				echo __('plugins.importexport.crossref.errors.noDOIprefix') . "\n";
				return;
			}
		}

		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */

		if ($outFile != '') switch (array_shift($args)) {
            case 'all':
                $articles =& $publishedArticleDao->getPublishedArticlesByJournalId($journal->getId());
                $articles =& $articles->toArray();
                if (!$this->exportArticles($articles, $outFile)) {
                    echo __('plugins.importexport.alm.cliError') . "\n";
                    echo __('plugins.importexport.alm.export.error.couldNotWrite', array('fileName' => $outFile)) . "\n\n";
                }

                $this->updateSetting($journal->getId(), 'lastExport', Core::getCurrentDate(), 'date');
                return;
            case 'sinceLast':
	            $lastExport = $this->getSetting($journal->getId(), 'lastExport');

                $articles =& $publishedArticleDao->getPublishedArticlesByJournalId($journal->getId(), null, true);
                $articlesSinceLast = array();
				while ($article =& $articles->next() ){
					if (strtotime($article->getDatePublished()) > $lastExport) {
						$articlesSinceLast[] = $article;
					} else {
						break;
					}
					unset($article);
				}

                if (!$this->exportArticles($articlesSinceLast, $outFile)) {
                    echo __('plugins.importexport.alm.cliError') . "\n";
                    echo __('plugins.importexport.alm.export.error.couldNotWrite', array('fileName' => $outFile)) . "\n\n";
                }
                $this->updateSetting($journal->getId(), 'lastExport', Core::getCurrentDate(), 'date');
                return;
			case 'articles':
				$articles = array();
				foreach ($args as $articleId) {
					$article =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);
					if ($article)
						$articles[] =& $article;
				}

				if (!$this->exportArticles($articles, $outFile)) {
					echo __('plugins.importexport.alm.cliError') . "\n";
					echo __('plugins.importexport.alm.export.error.couldNotWrite', array('fileName' => $outFile)) . "\n\n";
				}
				return;
			case 'issue':
				$issueId = array_shift($args);
				$issue =& $issueDao->getIssueByBestIssueId($issueId, $journal->getId());
				if ($issue == null) {
					echo __('plugins.importexport.alm.cliError') . "\n";
					echo __('plugins.importexport.alm.export.error.issueNotFound', array('issueId' => $issueId)) . "\n\n";
					return;
				}
				$issues = array($issue);
				if (!$this->exportIssues($issues, $outFile)) {
					echo __('plugins.importexport.alm.cliError') . "\n";
					echo __('plugins.importexport.alm.export.error.couldNotWrite', array('fileName' => $outFile)) . "\n\n";
				}
				return;
		}
		$this->usage($scriptName);

	}

	/**
	 * Display the command-line usage information
	 */
	function usage($scriptName) {
		echo __('plugins.importexport.alm.cliUsage', array(
			'scriptName' => $scriptName,
			'pluginName' => $this->getName()
		)) . "\n";
	}
}

?>
