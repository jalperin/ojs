<?php

/**
 * @file plugins/generic/alm/AlmPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AlmPlugin
 * @ingroup plugins_generic_alm
 *
 * @brief Alm plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.webservice.WebService');

DEFINE('ALM_API_URL', 'http://alm.publicknowledgeproject.org/api/v3/articles/');
define('ALM_CACHE_DAYS', 14);

class AlmPlugin extends GenericPlugin {
    /** @var $_article Article */
    var $_article;

	/** @var $cache FileCache */
    var $cache;


	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;
		$this->addLocaleData();
		if ($success) {
            HookRegistry::register('TemplateManager::display',array(&$this, 'addJs'));
			// Insert Alm page tag to article footer
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'AlmPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.alm.displayName');
	}

	function getDescription() {
		return __('plugins.generic.alm.description');
	}

    /**
     * @param $hookName
     * @param $params
     */
    function addJs($hookName, $params) {
        if ($this->getEnabled()) {
      		$templateMgr = &$params[0];
            $additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');
            $d3import = '<script language="javascript" type="text/javascript" src="'.Request::getBaseUrl() .
                    DIRECTORY_SEPARATOR . $this->getPluginPath() .  '/d3.v2.min.js"></script>';
            $templateMgr->assign('additionalHeadData', $additionalHeadData . "\n". $d3import);
        }
    }

    /**
     * @param $hookName
     * @param $params
     * @return bool
     */
    function insertFooter($hookName, $params) {

		if ($this->getEnabled()) {
            $templateMgr = &$params[1];
			$output = &$params[2];

			$article =& $templateMgr->get_template_vars('article');

            // Set the article for later usage
            $this->_article =& $article;

            assert(is_a($article, 'Article'));
            $articleId = $article->getId();

            $cacheManager =& CacheManager::getManager();
            $cache  =& $cacheManager->getCache('alm', $articleId, array(&$this, '_cacheMiss'));
            // If the cache is older than a 1 day in first 30 days, or older than a couple of days, flush it
            if (time() - $cache->getCacheTime() > 60 * 60 * 24 * ALM_CACHE_DAYS) {
                $cache->flush();
            }
			$resultJson = $cache->getContents();
            $templateMgr->assign('resultJson', $resultJson);
            $templateMgr->display($this->getTemplatePath() . 'output.tpl');
		}
		return false;
	}

    /**
     * @param $cache
     * @param $articleId
     * @return JSON
     */
    function _cacheMiss(&$cache, $articleId) {
		// Construct the parameters to send to the web service
		$searchParams = array(
			'info' => 'detail',
		);

		// Call the web service (URL defined at top of this file)
//		$resultJson =& $this->callWebService(ALM_API_URL . 'info:doi/' . $this->_article->getStoredDOI(), $searchParams);
        $resultJson =& $this->callWebService(ALM_API_URL . 'info:doi/' . '10.3402/fnr.v52i0.1811' , $searchParams);

		$cache->setEntireCache($resultJson);
		return $resultJson;
	}


	/**
	 * Call web service with the given parameters
	 * @param $params array GET or POST parameters
	 * @return JSON or null in case of error
	 */
	function &callWebService($url, &$params, $method = 'GET') {
		// Create a request
		$webServiceRequest = new WebServiceRequest($url, $params, $method);

		// Configure and call the web service
		$webService = new WebService();
		$result =& $webService->call($webServiceRequest);

		return $result;
	}
}
?>
