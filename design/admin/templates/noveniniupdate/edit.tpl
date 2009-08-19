{include uri="design:settings/settings_validation.tpl"}
{include uri="design:noveniniupdate/no_left_menu.tpl"}

{def $xmlDataTypes = ezini( 'XmlSettings', 'XmlType', 'noveniniupdate.ini' )}

<form method="post" action={"noveniniupdate/edit"|ezurl}>

	{* DESIGN: Header START *}
	<div class="box-header">
		<div class="box-tc">
			<div class="box-ml">
				<div class="box-mr">
					<div class="box-tl">
						<div class="box-tr">
							<h1 class="context-title">{'Edit INI setting'|i18n( 'extension/noveniniupdate/edit' )}</h1>

							{* DESIGN: Mainline *}<div class="header-mainline"></div>

						{* DESIGN: Header END *}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

{*$tab|attribute(show,2)*}

	{* DESIGN: Content START *}
	<div class="box-ml">
		<div class="box-mr">
			<div class="box-content">
				<div class="context-attributes">
					<div class="object">
					    <h2>{'Ini setting'|i18n( 'extension/noveniniupdate/edit' )}</h2>
					    <p><strong>{'Environment'|i18n( 'extension/noveniniupdate/edit' )}:</strong> {$tab['file']['label_env']}</p>
    					<p><strong>{'Ini File'|i18n( 'extension/noveniniupdate/edit' )}:</strong> {$tab['file']['path']}{if $tab['file']['comment']} ({$tab['file']['comment']}){/if}</p>
    					<p><strong>{'Block'|i18n( 'extension/noveniniupdate/edit' )}:</strong> {$tab['line']['block']}</p>    					
    					<p><strong>{'Datatype'|i18n( 'extension/noveniniupdate/edit' )}:</strong> {$xmlDataTypes[$tab['line']['type']]}</p>
    					<p><strong>{'Variable'|i18n( 'extension/noveniniupdate/edit' )}:</strong> {$tab['line']['name']}{if $tab['line']['comment']} ({$tab['line']['comment']}){/if}</p>

						<div class="break"></div>

					</div>
					
					<input type="hidden" name="LabelEnv" value="{$tab['file']['label_env']}" />
					<input type="hidden" name="Env" value="{$tab['file']['env']}" />					
					<input type="hidden" name="FilePath" value="{$tab['file']['path']}" />
					<input type="hidden" name="FileComment" value="{$tab['file']['comment']}" />
					<input type="hidden" name="Block" value="{$tab['line']['block']}" />
					<input type="hidden" name="SettingType" value="{$tab['line']['type']}" />
					<input type="hidden" name="SettingName" value="{$tab['line']['name']}" />
					<input type="hidden" name="LineComment" value="{$tab['line']['comment']}" />

					<div class="block">
					    <label>{'Setting value'|i18n( 'extension/noveniniupdate/edit' )}:</label>
						<textarea size="70" rows="10" class="box" name="Value">{$tab['line']['value']|wash}</textarea>
					</div>

				</div>
			{* DESIGN: Content END *}
			</div>
		</div>
	</div>

	<div class="controlbar">
		{* DESIGN: Control bar START *}
		<div class="box-bc">
			<div class="box-ml">
				<div class="box-mr">
					<div class="box-tc">
						<div class="box-bl">
							<div class="box-br">
								<div class="block">
								    <input class="button" type="submit" name="writesetting" value="{'Save'|i18n( 'extension/noveniniupdate/edit' )}" />
								    <input class="button" type="submit" name="cancelsetting" value="{'Cancel'|i18n( 'extension/noveniniupdate/edit' )}" />								
								</div>
							{* DESIGN: Control bar END *}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</form>
