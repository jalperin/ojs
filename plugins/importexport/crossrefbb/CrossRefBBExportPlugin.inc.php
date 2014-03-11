<?php

/**
 * @file plugins/importexport/crossrefbb/CrossRefBBExportPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefBBExportPlugin
 * @ingroup plugins_importexport_crossrefbb
 *
 * @brief CrossRef export/registration plugin.
 */


if (!class_exists('DOIExportPlugin')) { // Bug #7848
	import('plugins.importexport.crossrefbb.classes.DOIExportPlugin');
}

// DataCite API
define('CROSSREFBB_API_RESPONSE_OK', 200);
//define('CROSSREFBB_API_URL', 'http://doi.crossref.org/servlet/deposit');
define('CROSSREFBB_API_URL_DEV', 'http://test.crossref.org/servlet/deposit');

// Test DOI prefix
define('CROSSREFBB_API_TESTPREFIX', '10.1234');

class CrossRefBBExportPlugin extends DOIExportPlugin {

	//
	// Constructor
	//
	function CrossRefBBExportPlugin() {
		parent::DOIExportPlugin();
	}


	//
	// Implement template methods from ImportExportPlugin
	//
	/**
	 * @see ImportExportPlugin::getName()
	 */
	function getName() {
		return 'CrossRefBBExportPlugin';
	}

	/**
	 * @see ImportExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.crossrefBB.displayName');
	}

	/**
	 * @see ImportExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.crossrefBB.description');
	}


	//
	// Implement template methods from DOIExportPlugin
	//
	/**
	 * @see DOIExportPlugin::getPluginId()
	 */
	function getPluginId() {
		return 'crossrefBB';
	}

	/**
	 * @see DOIExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'CrossRefBBSettingsForm';
	}

	/**
	 * @see DOIExportPlugin::getAllObjectTypes()
	 */
	function getAllObjectTypes() {
		return array(
			'issue' => DOI_EXPORT_ISSUES,
			'article' => DOI_EXPORT_ARTICLES
		);
	}

	/**
	 * Display a list of issues for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function displayIssueList(&$templateMgr, &$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published issues.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$this->registerDaoHook('IssueDAO');
		$issueIterator =& $issueDao->getPublishedIssues($journal->getId(), Handler::getRangeInfo('issues'));

		// Filter only issues that contain an article that have a DOI assigned.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$issues = array();
		$numArticles = array();
		while ($issue =& $issueIterator->next()) {
			$issueArticles =& $publishedArticleDao->getPublishedArticles($issue->getId());
			$issueArticlesNo = 0;
			foreach ($issueArticles as $issueArticle) {
				if ($issueArticle->getPubId('doi')) {
					if (!in_array($issue, $issues)) $issues[] = $issue;
					$issueArticlesNo++;
				}
			}
			$numArticles[$issue->getId()] = $issueArticlesNo;
		}

		// Instantiate issue iterator.
		import('lib.pkp.classes.core.ArrayItemIterator');
		$rangeInfo = Handler::getRangeInfo('articles');
		$iterator = new ArrayItemIterator($issues, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the issue template.
		$templateMgr->assign_by_ref('issues', $iterator);
		$templateMgr->assign('numArticles', $numArticles);
		$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
	}

	/**
	 * @see DOIExportPlugin::displayAllUnregisteredObjects()
	 */
	function displayAllUnregisteredObjects(&$templateMgr, &$journal) {
		// Prepare information specific to this plug-in.
		$this->setBreadcrumbs(array(), true);
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION));

		// Prepare and display the template.
		$templateMgr->assign_by_ref('articles', $this->_getUnregisteredArticles($journal));
		$templateMgr->display($this->getTemplatePath() . 'all.tpl');
	}

	/**
	 * The selected issue can be exported if it contains an article that has a DOI,
	 * and the articles containing a DOI also have a date published.
	 * The selected article can be exported if it has a DOI and a date published.
	 * @param $foundObject Issue|PublishedArticle
	 * @param $errors array
	 * @return array|boolean
	*/
	function canBeExported($foundObject, &$errors) {
		return true;
		if (is_a($foundObject, 'Issue')) {
			$export = false;
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$issueArticles =& $publishedArticleDao->getPublishedArticles($foundObject->getId());
			foreach ($issueArticles as $issueArticle) {
				if (!is_null($issueArticle->getPubId('doi'))) {
					$export = true;
					if (is_null($issueArticle->getDatePublished())) {
						$errors[] = array('plugins.importexport.crossrefbb.export.error.articleDatePublishedMissing', $issueArticle->getId());
						return false;
					}
				}
			}
			return $export;
		}
		if (is_a($foundObject, 'PublishedArticle')) {
			if (is_null($foundObject->getDatePublished())) {
				$errors[] = array('plugins.importexport.crossrefbb.export.error.articleDatePublishedMissing', $foundObject->getId());
				return false;
			}
			return parent::canBeExported($foundObject, $errors);
		}
	}

	/**
	 * @see DOIExportPlugin::generateExportFiles()
	 */
	function generateExportFiles(&$request, $exportType, &$objects, $targetPath, &$journal, &$errors) {
		// Additional locale file.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));

		$this->import('classes.CrossRefBBExportDom');
		$dom = new CrossRefBBExportDom($request, $this, $journal, $this->getCache());
		$doc =& $dom->generate($objects);
		if ($doc === false) {
			$errors =& $dom->getErrors();
			return false;
		}

		// Write the result to the target file.
		$exportFileName = $this->getTargetFileName($targetPath, $exportType);
		file_put_contents($exportFileName, XMLCustomWriter::getXML($doc));
		$generatedFiles = array($exportFileName => &$objects);
		return $generatedFiles;
	}

	/**
	 * @see DOIExportPlugin::registerDoi()
	 */
	function registerDoi(&$request, &$journal, &$objects, $file) {
        // Prepare HTTP session.
        $curlCh = curl_init ();
        curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlCh, CURLOPT_POST, true);

        $username = $this->getSetting($journal->getId(), 'username');
        $password = $this->getSetting($journal->getId(), 'password');

        // Transmit XML data.
        assert(is_readable($file));
        /*
                if ($this->isTestMode($request)) {
                    curl_setopt($curlCh, CURLOPT_URL, CROSSREFBB_API_URL_DEV . '?operation=doMDUpload&login_id='.$username.'&login_passwd='.$password);
                } else {
                    curl_setopt($curlCh, CURLOPT_URL, CROSSREFBB_API_URL . '?operation=doMDUpload&login_id='.$username.'&login_passwd='.$password);
                }
        */
        curl_setopt($curlCh, CURLOPT_URL, CROSSREFBB_API_URL_DEV . '?operation=doMDUpload&login_id='.$username.'&login_passwd='.$password);
        curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($curlCh, CURLOPT_POSTFIELDS, array('fname' => '@/'.realpath($file)));

        $result = true;
        $response = curl_exec($curlCh);
        if ($response === false) {
            $result = array(array('plugins.importexport.common.register.error.mdsError', 'No response from server.'));
        } else {
            $status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
            if ($status != CROSSREFBB_API_RESPONSE_OK) {
                $result = array(array('plugins.importexport.common.register.error.mdsError', "$status - $response"));
            }
        }

        curl_close($curlCh);

        // FIXME: move this functionality elsewhere
        if ($result === true) {
            // Mark all objects as registered.
            foreach($objects as $object) {
                $this->markRegistered($request, $object, CROSSREFBB_API_TESTPREFIX);
            }
        }

        return $result;
    }

    /**
     * @see AcronPlugin::parseCronTab()
     */
    function callbackParseCronTab($hookName, $args) {
        $taskFilesPath =& $args[0];
        $taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';

        return false;
    }
}

?>
