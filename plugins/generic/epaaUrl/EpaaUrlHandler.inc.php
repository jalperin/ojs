<?php

/**
 * @file EpaaUrlHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.epaaUrl
 * @class EpaaUrlHandler
 *
 * Find the content and display the appropriate page
 *
 */

import('handler.Handler');

class EpaaUrlHandler extends Handler {

	function redirect (&$args, &$request) {
		$v = $request->getUserVar('v');
		$n = $request->getUserVar('n');
		if ( !is_numeric($v) || !is_numeric($n) ) {
			$request->redirect(null, 'issue', 'current');
		}

		$publishedArticleDAO =& DAORegistry::getDAO('PublishedArticleDAO');

		$result =& $publishedArticleDAO->retrieve(
			'SELECT pa.article_id
				FROM published_articles pa JOIN articles a ON pa.article_id = a.article_id JOIN issues i ON pa.issue_id = i.issue_id
			JOIN issue_settings s ON (i.issue_id = s.issue_id)
			WHERE s.setting_name = ? AND setting_value = ?
				AND a.pages = ?',
			array('pub-id::publisher-id', $v, $n)
		);

		$articleId = isset($result->fields[0]) ? $result->fields[0] : false;
		$result->Close();

		if ( $articleId ) {
			$request->redirect(null, 'article', 'view', $articleId);
		} else {
			$request->redirect(null, 'issue', 'current');
		}

	}
}

?>
