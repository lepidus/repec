{**
 * plugins/generic/repec/templates/settingsForm.tpl
 *
 * RePEc/ReDIF plugin settings form.
 *}
<script>
	$(function() {ldelim}
		$('#repecSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
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

	#repecSettings #legacyHandlesJson {
		box-sizing: border-box;
		font-family: monospace;
		min-height: 10rem;
		width: 100%;
	}
</style>

<form class="pkp_form" id="repecSettingsForm" method="post" enctype="multipart/form-data" action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
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
		{if $isManagedByGlobalArchive}
			<p>{translate key="plugins.generic.repec.settings.managedByGlobalArchive" archiveCode=$globalArchiveCode}</p>
		{else}
			{fbvFormSection title="plugins.generic.repec.settings.requiredData"}
				<div class="repecSettingsFormField">
					{fbvElement type="text" id="archiveCode" value=$archiveCode required=true label="plugins.generic.repec.settings.archiveCode" maxlength="3"}
				</div>
				{if !$isGlobalContext}
					<div class="repecSettingsFormField">
						{fbvElement type="text" id="seriesCode" value=$seriesCode required=true label="plugins.generic.repec.settings.seriesCode" description="plugins.generic.repec.settings.seriesCodeDescription" maxlength="6"}
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
								</div>
							{/if}
						</div>
					{/foreach}
				{/fbvFormSection}
			{/if}
		{/if}

		{if !$isGlobalContext}
			{fbvFormSection title="plugins.generic.repec.settings.legacyHandles"}
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
			{/fbvFormSection}
		{/if}

		{if !$isManagedByGlobalArchive || !$isGlobalContext}
			{fbvFormButtons submitText="common.save"}
		{/if}
	{/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
