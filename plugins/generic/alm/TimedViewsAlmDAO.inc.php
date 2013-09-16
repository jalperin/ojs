<?php

/**
 * @file plugins/generic/timedView/TimedViewAlmDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TimedViewAlmDAO
 * @ingroup plugins_generic_alm
 *
 * @brief Pull out data from the Timed View
 */

import('lib.pkp.classes.db.DBRowIterator');

class TimedViewsAlmDAO extends DAO {
	/**
	 * Get the view count for each article's galleys.
	 * @param $journalId int
	 * @param $startDate int
	 * @param $endDate int
	 * @return array
	 */
	function getGalleyViewCounts($articleId, $label = null) {
		$params = array((int) $articleId);
		if ($label) { $params[] = $label; }

		$result =& $this->retrieve(
			'SELECT YEAR(date) as year, MONTH(date) as month, label, COUNT(tvl.galley_id) AS views
					FROM timed_views_log tvl
					LEFT JOIN article_galleys ag ON (tvl.galley_id = ag.galley_id)
					WHERE tvl.galley_id IS NOT NULL
						AND tvl.article_id = ? ' .
						(($label) ? 'AND label = ? ' : '') .
					'GROUP BY 1, 2, 3
					ORDER BY 1, 2',
			$params
		);

		return $this->_resultToAlmJson($result);
	}


	/**
	 * Turn the result into a JSON string
	 *
	 */
	function _resultToAlmJson(&$result) {
		$iterator =& new DBRowIterator($result);
		unset($result);

		$totalHtml = 0;
		$totalPdf = 0;
		$byYear = array();
		$byMonth = array();

		while ($item =& $iterator->next() ) {
			$label = strtolower($item['label']);
			$views = (int) $item['views'];
			switch($label) {
				case 'html':
					$totalHtml += $views;
					break;
				case 'pdf':
					$totalPdf += $views;
					break;
				default:
					// switch is considered a loop for purpuses of continue
					continue 2;
			}
			$year = $item['year'];
			$month = $item['month'];

			if (!isset($byYear[$year])) $byYear[$year] = array();
			if (!isset($byYear[$year][$label])) $byYear[$year][$label] = 0;
			$byYear[$year][$label] += $views;

			if (!isset($byMonth[$year . '-' . $month])) $byMonth[$year . '-' . $month] = array();
			if (!isset($byMonth[$year . '-' . $month][$label])) $byMonth[$year . '-' . $month][$label] = 0;

			$byMonth[$year . '-' . $month][$label] += $views;
			unset($item);
		}

		$json = '{"name": "pkpTimedViews", "display_name": "This journal",  "events_url": null, "metrics": {';
		$json .= '"pdf": ' . $totalPdf . ',
		            "html": ' . $totalHtml . ',
					"shares": null,
		            "groups": null,
		            "comments": null,
		            "likes": null,
		            "citations": 0,
		            "total": ' . ($totalHtml + $totalPdf) . '
		        },
		        "by_day": null,
		        "by_month": ';

		if (count($byMonth)) {
			$jsonMonths = array();
			foreach ($byMonth as $date => $labels) {
				$j = '';
				$date = explode('-', $date);
				$year = $date[0];
				$month = $date[1];
				$j .= '{
		            "year": ' . $year . ',
		            "month": ' . $month . ',
		            "shares": null,
		            "groups": null,
		            "comments": null,
		            "likes": null,
		            "citations": null,';
				$total = 0;
				foreach (array('html', 'pdf') as $label) {
					$views = isset($labels[$label])? (int) $labels[$label] : 0;
					$total += $views;
					$j .= '"' . $label . '": ' . $views . ', ';
				}
				$j .= '"total": ' . $total . '}';
				$jsonMonths[] = $j;
			}
			$json .= '[' . implode(',', $jsonMonths) . '], ';
		} else {
			$json .= 'null, ';
		}

		$json .= '"by_year": ';
		if (count($byYear)) {
			$jsonYears = array();
			foreach ($byYear as $year => $labels) {
				$j = '';
				$j .= '{
		            "year": ' . $year . ',
		            "shares": null,
		            "groups": null,
		            "comments": null,
		            "likes": null,
		            "citations": null,';
				$total = 0;
				foreach (array('html', 'pdf') as $label) {
					$views = isset($labels[$label])? (int) $labels[$label] : 0;
					$total += $views;
					$j .= '"' . $label . '": ' . $views . ', ';
				}
				$j .= '"total": ' . $total . '}';
				$jsonYears[] = $j;
			}
			$json .= '[' . implode(',', $jsonYears) . '] ';
		} else {
			$json .= 'null';
		}
		$json .= '}';

		return $json;
	}
}

?>
