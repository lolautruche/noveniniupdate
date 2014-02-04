{if not(is_set($selected_env))}
{def $selected_env = "" }
{/if}
{include uri="design:settings/settings_validation.tpl"}
{include uri="design:noveniniupdate/no_left_menu.tpl"}

<script type="text/javascript">
function confirmUpdateEnv(curEnv) {ldelim}
	if (confirm("{'Update the environment with current XML content?'|i18n('extension/noveniniupdate/view')}")) {ldelim}
		return true;
	{rdelim} else {ldelim}
		return false;
	{rdelim}
{rdelim}
</script>

<form method="post" action={"noveniniupdate/view"|ezurl}>

	{* DESIGN: Header START *}
	<div class="box-header">
		<div class="box-tc">
			<div class="box-ml">
				<div class="box-mr">
					<div class="box-tl">
						<div class="box-tr">
							<h1 class="context-title">{'Select the environment'|i18n('extension/noveniniupdate/view')}</h1>

							{* DESIGN: Mainline *}<div class="header-mainline"></div>

							{* DESIGN: Header END *}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	{* DESIGN: Content START *}
	<div class="box-ml">
		<div class="box-mr">
			<div class="box-content">
				<div class="context-attributes">
					{if is_set($error_message)}
					<h2>Error</h2>
					<div class="object" style="color:red; font-weight:bold;"><p><ul><li>{$error_message|wash}</li></ul></p></div>
					{/if}
					
					{if is_set($confirm_label)}
					<div class="object"><p style="color:red;"><strong>{$confirm_label}</strong></p></div>
					{/if}					
					
					{if $envs|count}
					<div class="object">
						<p>{'Please select the ini files environment to edit'|i18n('extension/noveniniupdate/view')}</p>
						<br />
					    <div class="element">
					        <label>{'Select environment to view'|i18n('extension/noveniniupdate/view')}:</label>
					        <select name="selectedEnvironment">
								{foreach $envs as $key => $value}
									<option value="{$key}"{if or($key|eq($selected_env), $key|eq($current_env))} selected="selected"{/if}>{$value}</option>
									{if eq($key, $selected_env)}{def $selected_label = $value}{/if}
								{/foreach}
					        </select>
					    </div>
					</div>
					{/if}
				</div>
			{* DESIGN: Content END *}
			</div>
		</div>
	</div>
	
	{if $envs|count}
	<div class="controlbar">
		{* DESIGN: Control bar START *}
		<div class="box-bc">
			<div class="box-ml">
				<div class="box-mr">
					<div class="box-tc">
						<div class="box-bl">
							<div class="box-br">
								<div class="block">
								    <input class="button" type="submit" name="DoListButton" value="{'Select'|i18n('extension/noveniniupdate/view')}" />
								</div>
							{* DESIGN: Control bar END *}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	{/if}
</form>

{if $tabs|count}
<form method="post" action={"noveniniupdate/view"|ezurl}>

	{* DESIGN: Header START *}
	<div class="box-header">
		<div class="box-tc">
			<div class="box-ml">
				<div class="box-mr">
					<div class="box-tl">
						<div class="box-tr">
							<h1 class="context-title">{'Settings for %environment environment'|i18n('extension/noveniniupdate/view',, hash( '%environment', $selected_label))}</h1>
							{* DESIGN: subline *}<div class="header-subline"></div>

							{* DESIGN: Header END *}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	{* DESIGN: Content START *}
	<div class="box-ml">
		<div class="box-mr">
			<div class="box-content">
				<div class="context-attributes">
					<p>{'Be careful, this is the current XML content in '|i18n('extension/noveniniupdate/view')} {ezini( 'XmlSettings', 'XmlContent', 'noveniniupdate.ini' )}</p>
			    	<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
			    		<tr>
							<th colspan="2" width="50%">{'File path and comment'|i18n('extension/noveniniupdate/view')}</th>
					        <th class="tight">{'Block'|i18n( 'extension/noveniniupdate/view' )}</th>
					        <th class="tight">{'Data type'|i18n( 'extension/noveniniupdate/view' )}</th>
					        <th class="tight">{'Variable name'|i18n( 'extension/noveniniupdate/view' )}</th>
					        <th class="tight">{'Value'|i18n( 'extension/noveniniupdate/view' )}</th>			        
					        <th class="tight">&nbsp;</th>
					    </tr>
					    {def 
					    	$i = 1
					    	$cpt_global = 1
					    }
					    {foreach $tabs as $tab sequence array('bgdark', 'bglight') as $seq}
			    		<tr valign="top" class="{$seq}">
				            <td colspan="7">
				                <strong>{$tab['path']}</strong><br />{$tab['comment']}
				            </td>
				       	</tr>
					       	{set 
					       		$i = 1
					       		$cpt_global = 1
					       	}
				       		{foreach $tab.lines as $t}
				       		{*if gt( $i, 2 )*}
				       		<tr valign="top" class="{$seq}">
								<td colspan="2">		            
				        			&nbsp;
				    			</td>
				            	<td width="1">[{$t['block']}]</td>
					            <td width="1">{$t['type']}</td>
					            <td>{$t['name']}</td>
					            <td align="left" width="1">{$t['value']|nl2br}</td>
					            <td width="1">
						            {* Disable the edit button because the edit function is useless compared to the built-in INI edit interface. *}
						            {*<a href={concat('noveniniupdate/edit/(env)/', $selected_env, '/(path)/', $tab['path'], '/(line)/', $cpt_global)|ezurl}>
		                        		<img src={"edit.gif"|ezimage} alt="{'Edit'|i18n('extension/noveniniupdate/view')}" />
		                        	</a>*}
					            </td>
					        </tr>
					        {set $cpt_global = $cpt_global|inc}
					        {*/if*}
					        {*set $i = $i|inc*}
					    	{/foreach}
				    	{/foreach}
					</table>
					
				{* Cluster Mode settings *}
				{if $cluster_params}
					<h3>{'Cluster mode settings for selected environment "%env"'|i18n('extension/noveniniupdate/view', '', hash('%env', $selected_env))}</h3>
					<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
			    		<tr>
							<th>{'Cluster param name'|i18n('extension/noveniniupdate/view')}</th>
					        <th>{'Cluster param value'|i18n( 'extension/noveniniupdate/view' )}</th>
					    </tr>
					{foreach $cluster_params as $param sequence array('bgdark', 'bglight') as $seq}
					    <tr valign="top" class="{$seq}">
					        <td>{$param.name}</td>
					        <td>{$param.value}</td>
					    </tr>
					{/foreach}
					</table>
				{/if}
				
				{* Config PHP settings *}
				{if $config_params}
					<h3>{'Config.php settings for selected environment "%env"'|i18n('extension/noveniniupdate/view', '', hash('%env', $selected_env))}</h3>
					<table class="list" width="100%" cellspacing="0" cellpadding="0" border="0">
			    		<tr>
							<th>{'Config param name'|i18n('extension/noveniniupdate/view')}</th>
					        <th>{'Config param value'|i18n( 'extension/noveniniupdate/view' )}</th>
					    </tr>
					{foreach $config_params as $param sequence array('bgdark', 'bglight') as $seq}
					    <tr valign="top" class="{$seq}">
					        <td>{$param.name}</td>
					        <td>{$param.value}</td>
					    </tr>
					{/foreach}
					</table>
				{/if}
				</div>
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
									<label for="doBackup">{'Do a backup before switching'|i18n('extension/noveniniupdate/view')}</label>
									<input type="checkbox" name="doBackup" id="doBackup" checked="checked">
								</div>
								<div class="block">
									<input class="button" type="submit" name="updateenvbutton" onclick="return confirmUpdateEnv('{$selected_label}');" value="{'Update environment with current XML content'|i18n('extension/noveniniupdate/view')}" />
								</div>
							{* DESIGN: Control bar END *}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<input type="hidden" name="selectedEnvironment" value="{$selected_env}" />

</form>
{/if}