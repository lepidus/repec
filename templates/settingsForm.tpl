{**
 * plugins/generic/repec/templates/settingsForm.tpl
 *
 * RePEc/ReDIF plugin settings form.
 *}
<script>
	$(function() {ldelim}
		var $form = $('#repecSettingsForm');
		var articleHandlePatternLocked = $form.data('article-handle-pattern-locked') === 1;
		var articleHandlePatternInitiallySaved = $.trim($('#articleHandlePattern').val() || '') !== '';
		var confirmArticleHandlePattern = {translate|json_encode key="plugins.generic.repec.settings.articleHandlePatternConfirm"};
		var confirmRemoveIndividualSettings = {translate|json_encode key="plugins.generic.repec.settings.removeIndividualSettingsConfirm"};

		function buildArticleHandlePreview() {ldelim}
			var archiveCode = $.trim($('#archiveCode').val() || '').toLowerCase() || 'aaa';
			var seriesCode = $.trim($('#seriesCode').val() || '').toLowerCase() || 'series';
			var pattern = $.trim($('#articleHandlePattern').val() || '') || {$defaultArticleHandlePattern|json_encode};
			var suffix = pattern
				.replace(/%v/g, '35')
				.replace(/%Y/g, '1995')
				.replace(/%i/g, '3')
				.replace(/%a/g, '59960');
			$('#articleHandlePatternPreview').text('RePEc:' + archiveCode + ':' + seriesCode + ':' + suffix);
		{rdelim}

		$form.on('submit', function(event) {ldelim}
			var pattern = $.trim($('#articleHandlePattern').val() || '');
			if ($('#removeIndividualRepecSettings').is(':checked') && !confirm(confirmRemoveIndividualSettings)) {ldelim}
				event.preventDefault();
				event.stopImmediatePropagation();
				return false;
			{rdelim}
			if (!articleHandlePatternLocked && !articleHandlePatternInitiallySaved && pattern !== '') {ldelim}
				buildArticleHandlePreview();
				if (!confirm(confirmArticleHandlePattern.replace('__PREVIEW__', $('#articleHandlePatternPreview').text()))) {ldelim}
					event.preventDefault();
					event.stopImmediatePropagation();
					return false;
				{rdelim}
			{rdelim}
		{rdelim});

		$form.pkpHandler('$.pkp.controllers.form.AjaxFormHandler');

		$('.repecGenerateSeriesCode').on('click', function() {ldelim}
			var targetName = $(this).data('targetName');
			var suggestion = $(this).data('suggestion');
			var $target = $form.find(':input').filter(function() {ldelim}
				return $(this).attr('name') === targetName;
			{rdelim}).first();
			if ($target.length && suggestion) {ldelim}
				$target.val(suggestion).trigger('change');
			{rdelim}
		{rdelim});

		$('#archiveCode, #seriesCode, #articleHandlePattern').on('keyup change', buildArticleHandlePreview);
		buildArticleHandlePreview();

		$('#legacyHandlesFile').on('change', function() {ldelim}
			var file = this.files && this.files[0];
			if (!file) {ldelim}
				$('#legacyHandlesJson').val('');
				return;
			{rdelim}
			var reader = new FileReader();
			reader.onload = function(event) {ldelim}
				$('#legacyHandlesJson').val(event.target.result);
			{rdelim};
			reader.readAsText(file);
		{rdelim});
	{rdelim});
</script>
<style>
	#repecSettings .repecSettingsFormField {
		margin-bottom: 1.1rem;
	}

	#repecSettings .repecSettingsFormField input {
		margin-bottom: 0.25rem;
	}

	#repecSettings .repecSettingsSupportEmailNotice {
		color: #555;
		font-style: italic;
		margin: -0.5rem 0 1.1rem;
	}

	#repecSettings legend,
	#repecSettings .section h3,
	#repecSettings .section_title {
		font-size: 1rem;
		line-height: 1.35;
	}

	#repecSettings .repecGlobalJournal {
		border-bottom: 1px solid #ddd;
		margin-bottom: 1rem;
		padding-bottom: 1rem;
	}

	#repecSettings .repecGlobalJournalCode {
		margin-left: 1.5rem;
		max-width: 16rem;
	}

	#repecSettings .repecSeriesCodeActions {
		margin-top: 0.25rem;
	}

	#repecSettings .repecAdvancedSettings {
		margin-top: 1rem;
	}

	#repecSettings .repecAdvancedWarning {
		border-left: 4px solid #b00020;
		margin: 1rem 0;
		padding: 0.5rem 0.75rem;
	}

	#repecSettings .repecAdvancedWarning strong {
		color: #b00020;
	}

	#repecSettings .repecHandlePreview {
		background: #f5f5f5;
		border: 1px solid #ddd;
		box-sizing: border-box;
		font-family: monospace;
		margin: 0.25rem 0 1rem;
		padding: 0.5rem;
		word-break: break-all;
	}

	#repecSettings #legacyHandlesJson {
		box-sizing: border-box;
		font-family: monospace;
		min-height: 10rem;
		width: 100%;
	}
</style>

<form class="pkp_form" id="repecSettingsForm" method="post" enctype="multipart/form-data" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}" data-article-handle-pattern-locked="{if $articleHandlePatternLocked}1{else}0{/if}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="repecSettingsFormNotification"}

	<div id="description">{translate key="plugins.generic.repec.settings.description"}</div>

	{if $repecBaseUrl}
		<p>
			<strong>{translate key="plugins.generic.repec.settings.publicUrl"}</strong>
			<a href="{$repecBaseUrl|escape}" target="_blank" rel="noopener">{$repecBaseUrl|escape}</a>
		</p>
	{/if}

	{fbvFormArea id="repecSettings"}
		<p class="repecSettingsSupportEmailNotice">
			{translate key="plugins.generic.repec.settings.repecScopeNotice"}
			<a href="https://ideas.repec.org/stepbystep.html" target="_blank" rel="noopener">https://ideas.repec.org/stepbystep.html</a>
		</p>

		{if $isManagedByGlobalArchive}
			<p>{translate key="plugins.generic.repec.settings.managedByGlobalArchive" archiveCode=$globalArchiveCode}</p>
		{else}
			{fbvFormSection title="plugins.generic.repec.settings.requiredData"}
				<div class="repecSettingsFormField">
					{fbvElement type="text" id="archiveCode" value=$archiveCode required=true label="plugins.generic.repec.settings.archiveCode" description="plugins.generic.repec.settings.archiveCodeDescription" maxlength="3"}
				</div>
				{if !$isGlobalContext}
					<div class="repecSettingsFormField">
						{fbvElement type="text" id="seriesCode" value=$seriesCode required=true label="plugins.generic.repec.settings.seriesCode" description="plugins.generic.repec.settings.seriesCodeDescription" maxlength="6"}
						{if $suggestedSeriesCode}
							<div class="repecSeriesCodeActions">
								<button type="button" class="pkp_button repecGenerateSeriesCode" data-target-name="seriesCode" data-suggestion="{$suggestedSeriesCode|escape}">
									{translate key="plugins.generic.repec.settings.generateSeriesCode"}
								</button>
							</div>
						{/if}
					</div>
				{/if}
				<div class="repecSettingsFormField">
					{if $isGlobalContext}
						{fbvElement type="text" id="maintainerEmail" value=$maintainerEmail required=true label="plugins.generic.repec.settings.maintainerEmail" description="plugins.generic.repec.settings.maintainerEmailGlobalDescription"}
					{else}
						{fbvElement type="text" id="maintainerEmail" value=$maintainerEmail label="plugins.generic.repec.settings.maintainerEmail" description="plugins.generic.repec.settings.maintainerEmailDescription"}
					{/if}
				</div>
				{if $supportEmailInUse}
					<p class="repecSettingsSupportEmailNotice">
						{translate key="plugins.generic.repec.settings.supportEmailInUse" email=$supportEmailInUse}
					</p>
				{/if}
			{/fbvFormSection}

			{if $isGlobalContext}
				{fbvFormSection title="plugins.generic.repec.settings.globalJournals"}
					<p>{translate key="plugins.generic.repec.settings.globalJournalsDescription"}</p>
					{foreach from=$globalJournalOptions item=journal}
						<div class="repecGlobalJournal">
							<label>
								<input type="checkbox" name="globalJournalIds[]" value="{$journal.id|escape}"{if $journal.selected} checked="checked"{/if}{if $journal.disabled} disabled="disabled"{/if}>
								{$journal.name|escape} ({$journal.path|escape})
							</label>
							{if $journal.disabled}
								<p class="repecSettingsSupportEmailNotice">{translate key="plugins.generic.repec.settings.globalJournalUnavailable"}</p>
							{else}
								<div class="repecGlobalJournalCode">
									<label for="globalSeriesCodes-{$journal.id|escape}">
										{translate key="plugins.generic.repec.settings.seriesCode"}
									</label>
									<input type="text" id="globalSeriesCodes-{$journal.id|escape}" name="globalSeriesCodes[{$journal.id|escape}]" value="{$journal.seriesCode|escape}" maxlength="6">
									{if $journal.suggestedSeriesCode}
										<div class="repecSeriesCodeActions">
											<button type="button" class="pkp_button repecGenerateSeriesCode" data-target-name="globalSeriesCodes[{$journal.id|escape}]" data-suggestion="{$journal.suggestedSeriesCode|escape}">
												{translate key="plugins.generic.repec.settings.generateSeriesCode"}
											</button>
										</div>
									{/if}
								</div>
							{/if}
						</div>
					{/foreach}
				{/fbvFormSection}
			{/if}
		{/if}

		{if !$isGlobalContext}
			<details class="repecAdvancedSettings">
				<summary>{translate key="plugins.generic.repec.settings.advanced"}</summary>
				<p class="repecAdvancedWarning">
					<strong>{translate key="plugins.generic.repec.settings.advancedWarningLabel"}</strong>
					{translate key="plugins.generic.repec.settings.advancedWarning"}
				</p>
				{fbvFormSection}
					<div class="repecSettingsFormField">
						<label for="articleHandlePattern">{translate key="plugins.generic.repec.settings.articleHandlePattern"}</label>
						{if $articleHandlePatternLocked}
							<input type="text" id="articleHandlePattern" name="articleHandlePattern" value="{$articleHandlePattern|escape}" readonly="readonly">
							<p>{translate key="plugins.generic.repec.settings.articleHandlePatternLockedDescription"}</p>
						{else}
							<input type="text" id="articleHandlePattern" name="articleHandlePattern" value="{$articleHandlePattern|escape}">
							<p>{translate key="plugins.generic.repec.settings.articleHandlePatternDescription"}</p>
						{/if}
					</div>
					<label>{translate key="plugins.generic.repec.settings.articleHandlePatternPreview"}</label>
					<div class="repecHandlePreview" id="articleHandlePatternPreview">{$articleHandlePatternPreview|escape}</div>
					<p class="repecSettingsSupportEmailNotice">{translate key="plugins.generic.repec.settings.articleHandlePatternWarning"}</p>

					<h3>{translate key="plugins.generic.repec.settings.legacyHandles"}</h3>
					<p>{translate key="plugins.generic.repec.settings.legacyHandlesDescription"}</p>
					<p>{translate key="plugins.generic.repec.settings.legacyHandlesCount" count=$legacyHandlesCount}</p>
					{if $legacyHandlesDownloadUrl}
						<p>
							<a href="{$legacyHandlesDownloadUrl|escape}">{translate key="plugins.generic.repec.settings.legacyHandlesDownload"}</a>
						</p>
					{/if}
					<div class="repecSettingsFormField">
						<label for="legacyHandlesFile">{translate key="plugins.generic.repec.settings.legacyHandlesFile"}</label>
						<input type="file" id="legacyHandlesFile" name="legacyHandlesFile" accept="application/json,.json">
					</div>
					<div class="repecSettingsFormField">
						<label for="legacyHandlesJson">{translate key="plugins.generic.repec.settings.legacyHandlesJson"}</label>
						<textarea id="legacyHandlesJson" name="legacyHandlesJson"></textarea>
					</div>
					{if !$isManagedByGlobalArchive}
						<div class="repecSettingsFormField">
							<label>
								<input type="checkbox" id="removeIndividualRepecSettings" name="removeIndividualRepecSettings" value="1"{if !$hasIndividualRepecSettingsToRemove} disabled="disabled"{/if}>
								{translate key="plugins.generic.repec.settings.removeIndividualSettings"}
							</label>
						</div>
					{/if}
				{/fbvFormSection}
			</details>
		{/if}

		{if !$isManagedByGlobalArchive || !$isGlobalContext}
			{fbvFormButtons submitText="common.save"}
		{/if}
	{/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
