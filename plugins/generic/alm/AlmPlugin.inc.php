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

DEFINE('ALM_API_URL', 'http://pkp-alm.lib.sfu.ca/api/v3/articles/');

class AlmPlugin extends GenericPlugin {
    /** @var $_article Article */
    var $_article;

	/** @var $cache FileCache */
    var $cache;

    /** @var $apiKey string */
    var $_apiKey;


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

		$application =& Application::getApplication();
		$request =& $application->getRequest();
		$router =& $request->getRouter();
		$context = $router->getContext($request);

		if ($success && $context) {
			$apiKey = $this->getSetting($context->getId(), 'apiKey');
			if ($apiKey) {
				$this->_apiKey = $apiKey;
				HookRegistry::register('TemplateManager::display',array(&$this, 'templateManagerCallback'));
			}
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
		return Locale::translate('plugins.generic.alm.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.generic.alm.description');
	}

	/**
	* @see GenericPlugin::getManagementVerbs()
	*/
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', Locale::translate('plugins.generic.alm.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * @see GenericPlugin::manage()
	 */
	function manage($verb, $args, &$message) {
		if (!parent::manage($verb, $args, $message)) return false;
		switch ($verb) {
			case 'settings':
				$journal =& Request::getJournal();

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));

				$this->import('SettingsForm');
				$form = new SettingsForm($this, $journal->getId());

				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return false;
					} else {
						$form->display();
					}
				} else {
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
			return false;
		}
	}

    /**
     * @param $hookName
     * @param $params
     */
    function templateManagerCallback($hookName, $params) {
        if ($this->getEnabled()) {
      		$templateMgr = &$params[0];
	        $template = $params[1];
	        if ($template == 'article/article.tpl') {
	            $additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');
	            $d3import = '<script language="javascript" type="text/javascript" src="'.Request::getBaseUrl() .
	                                DIRECTORY_SEPARATOR . $this->getPluginPath() .  DIRECTORY_SEPARATOR .
			                        'js' . DIRECTORY_SEPARATOR . 'd3.v3.min.js"></script>';
		        $tooltipImport = '<script language="javascript" type="text/javascript" src="'.Request::getBaseUrl() .
		                            DIRECTORY_SEPARATOR . $this->getPluginPath() .  DIRECTORY_SEPARATOR .
				                    'js' . DIRECTORY_SEPARATOR . 'bootstrap.tooltip.min.js"></script>';
	            $templateMgr->assign('additionalHeadData', $additionalHeadData . "\n". $d3import . "\n" . $tooltipImport);

				$templateMgr->addStyleSheet(Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() .
										DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'bootstrap.tooltip.min.css');
		        $templateMgr->addStyleSheet(Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() .
		                                DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'almviz.css');

				// For inserting the metrics into the page
				$templateMgr->register_outputfilter(array('AlmPlugin', 'almOutputFilter'));

		        // For grabbing the TimedView statistics
                $this->import('TimedViewsAlmDAO');
                $timedViewsDao = new TimedViewsAlmDAO();
                DAORegistry::registerDAO('TimedViewsAlmDAO', $timedViewsDao);
	        }
        }
    }

    /**
     * @param $hookName
     * @param $params
     * @return bool
     */
    function almOutputFilter($output, &$smarty) {
	    $whereToPlace = '<div id="alm"></div>';

	    if (strpos($output, $whereToPlace) === false) {
		    return $output;
	    }
	    // prevent an infinite loop
	    $smarty->unregister_outputfilter('AlmPlugin_almOutputFilter');

		$article =& $smarty->get_template_vars('article');
        assert(is_a($article, 'Article'));

        $articleId = $article->getId();

	    $almPlugin =& PluginRegistry::getPlugin('generic', 'AlmPlugin');

        $cacheManager =& CacheManager::getManager();
        $cache  =& $cacheManager->getCache('alm', $articleId, array(&$almPlugin, '_cacheMiss'));
        // If the cache is older than a 1 day in first 30 days, or a week in first 6 months, or older than a month
		$daysSincePublication = floor((time() - strtotime('2013-04-20')) / (60 * 60 * 24));
		if ($daysSincePublication <= 30) {
			$daysToStale = 1;
		} elseif ( $daysSincePublication <= 180 ) {
			$daysToStale = 7;
		} else {
			$daysToStale = 29;
		}

        if (time() - $cache->getCacheTime() > 60 * 60 * 24 * $daysToStale) {
            $cache->flush();
        }
		$resultJson = $cache->getContents();

	    // Pull in from the TimedView plugin
	    $timedViewsAlmDao =& DAORegistry::getDAO('TimedViewsAlmDAO'); /* @var $timedViewsAlmDao TimedViewsAlmDAO */
	    $timedViewJson = $timedViewsAlmDao->getGalleyViewCounts($articleId);


		if ($resultJson) {
			$smarty->assign('resultJson', $resultJson);
			$smarty->assign('timedViewsJson', $timedViewJson);
			$metricsHTML = $smarty->fetch($almPlugin ->getTemplatePath() . 'output.tpl');

			$output = str_replace($whereToPlace, $metricsHTML, $output);
		}
	    return $output;
	}

    /**
     * @param $cache
     * @param $articleId
     * @return JSON
     */
    function _cacheMiss(&$cache) {
	    $articleId = $cache->getCacheId();
	    $articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
	    $article =& $articleDao->getArticle($articleId);

		// Construct the parameters to send to the web service
		$searchParams = array(
			'info' => 'history',
		);

		// Call the web service (URL defined at top of this file)
//		$resultJson =& $this->callWebService(ALM_API_URL . 'info:doi/' . $article->getStoredDOI(), $searchParams);
        $resultJson =& $this->callWebService(ALM_API_URL . 'info:doi/' . '10.3402/fnr.v52i0.1811' , $searchParams);

        if (!$resultJson) $resultJson = false;

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
		if (!is_array($params)) {
			$params = array();
		}

		$params['api_key'] = $this->_apiKey;
		$webServiceRequest = new WebServiceRequest($url, $params, $method, '5');

		// Configure and call the web service
		$webService = new WebService();
		$result =& $webService->call($webServiceRequest);

		return $result;
	}
}
?>
