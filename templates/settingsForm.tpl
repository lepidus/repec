{**
 * plugins/generic/repec/templates/settingsForm.tpl
 *
 * RePEc/ReDIF plugin settings form.
 *}
<script>
	$(function() {ldelim}
		$('#repecSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="repecSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="repecSettingsFormNotification"}

	<div id="description">{translate key="plugins.generic.repec.settings.description"}</div>

	{if $repecBaseUrl}
		<p>
			<strong>{translate key="plugins.generic.repec.settings.publicUrl"}</strong>
			<a href="{$repecBaseUrl|escape}" target="_blank" rel="noopener">{$repecBaseUrl|escape}</a>
		</p>
	{/if}

	{fbvFormArea id="repecArchiveSettings"}
		{fbvFormSection title="plugins.generic.repec.settings.archive"}
			{fbvElement type="text" id="archiveCode" value=$archiveCode required=true label="plugins.generic.repec.settings.archiveCode" maxlength="3"}
			{fbvElement type="text" id="archiveName" value=$archiveName required=true label="plugins.generic.repec.settings.archiveName"}
			{fbvElement type="textarea" id="archiveDescription" value=$archiveDescription label="plugins.generic.repec.settings.archiveDescription"}
		{/fbvFormSection}

		{fbvFormSection title="plugins.generic.repec.settings.series"}
			{fbvElement type="text" id="seriesCode" value=$seriesCode required=true label="plugins.generic.repec.settings.seriesCode" maxlength="6"}
			{fbvElement type="text" id="seriesName" value=$seriesName required=true label="plugins.generic.repec.settings.seriesName"}
			{fbvElement type="text" id="providerName" value=$providerName required=true label="plugins.generic.repec.settings.providerName"}
			{fbvElement type="text" id="providerHomepage" value=$providerHomepage required=true label="plugins.generic.repec.settings.providerHomepage"}
			{fbvElement type="text" id="providerInstitution" value=$providerInstitution label="plugins.generic.repec.settings.providerInstitution"}
		{/fbvFormSection}

		{fbvFormSection title="plugins.generic.repec.settings.maintainer"}
			{fbvElement type="text" id="maintainerName" value=$maintainerName required=true label="plugins.generic.repec.settings.maintainerName"}
			{fbvElement type="text" id="maintainerEmail" value=$maintainerEmail required=true label="plugins.generic.repec.settings.maintainerEmail"}
		{/fbvFormSection}

		{fbvFormButtons submitText="common.save"}
	{/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
