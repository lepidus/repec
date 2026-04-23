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

	{fbvFormArea id="repecSettings"}
		{fbvFormSection title="plugins.generic.repec.settings.requiredData"}
			{fbvElement type="text" id="archiveCode" value=$archiveCode required=true label="plugins.generic.repec.settings.archiveCode" maxlength="3"}
			{fbvElement type="text" id="seriesCode" value=$seriesCode required=true label="plugins.generic.repec.settings.seriesCode" description="plugins.generic.repec.settings.seriesCodeDescription" maxlength="6"}
			{fbvElement type="text" id="maintainerEmail" value=$maintainerEmail label="plugins.generic.repec.settings.maintainerEmail" description="plugins.generic.repec.settings.maintainerEmailDescription"}
		{/fbvFormSection}

		{fbvFormButtons submitText="common.save"}
	{/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
