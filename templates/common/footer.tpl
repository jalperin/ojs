{**
 * footer.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site footer.
 *
 * $Id$
 *}
{if $displayCreativeCommons}
	<br /><br />
	<a rel="license" target="_new" href="http://creativecommons.org/licenses/by/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by/3.0/80x15.png" /></a>
	<br />
	This <span xmlns:dc="http://purl.org/dc/elements/1.1/" href="http://purl.org/dc/dcmitype/Text" rel="dc:type">work</span> is licensed under a <a target="_new" rel="license" href="http://creativecommons.org/licenses/by/3.0/">Creative Commons Attribution 3.0 License</a>.
{/if}
{if $pageFooter}
<br /><br />
{$pageFooter}
{else}{strip}{* Include the ISSN as a default footer, if available. *}
	{if $currentJournal->getSetting('onlineIssn')}{assign var="issn" value=$currentJournal->getSetting('onlineIssn')}
	{elseif $currentJournal->getSetting('printIssn')}{assign var="issn" value=$currentJournal->getSetting('printIssn')}
	{elseif $currentJournal->getSetting('issn')}{assign var="issn" value=$currentJournal->getSetting('issn')}
	{/if}
	{if $issn}<br/><br/>
	        {translate key="journal.issn"}:&nbsp;{$issn|escape}
	{/if}
{/strip}{/if}
{call_hook name="Templates::Common::Footer::PageFooter"}
</div><!-- content -->
</div><!-- main -->
</div><!-- body -->

{get_debug_info}
{if $enableDebugStats}
<div id="footer">
	<div id="footerContent">
		<div class="debugStats">
		{translate key="debug.executionTime"}: {$debugExecutionTime|string_format:"%.4f"}s<br />
		{translate key="debug.databaseQueries"}: {$debugNumDatabaseQueries|escape}<br/>
		{translate key="debug.memoryUsage"}: {$debugMemoryUsage|escape}<br/>
		{if $debugNotes}
			<strong>{translate key="debug.notes"}</strong><br/>
			{foreach from=$debugNotes item=note}
				{translate key=$note[0] params=$note[1]}<br/>
			{/foreach}
		{/if}
		</div>
	</div><!-- footerContent -->
</div><!-- footer -->
{/if}

</div><!-- container -->
</body>
</html>
