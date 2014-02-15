{**
 * block.tpl
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Edited by Andreas Ihrig
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Popular Articles
 *
 * $Id$
 *}
<div class="block" id="sidebarPopularArticles">
	<span class="blockTitle">{translate key="plugins.block.popularArticles.displayName"}</span>
	{section name=article loop=$popularArticles}
	    {*{math equation="x+1" x=$smarty.section.article.index}*}&#187;&nbsp;<a href="{url page="article" op="view" path=$popularArticles[article]->getArticleId()}">{$popularArticles[article]->getArticleTitle()}</a><br />
	    <strong>{$popularArticles[article]->getViews()} {translate key="plugins.block.popularArticles.views"}</strong><br />
	{/section}
</div>

{*:{$article->getDatePublished()|date_format:$dateFormatShort}*}