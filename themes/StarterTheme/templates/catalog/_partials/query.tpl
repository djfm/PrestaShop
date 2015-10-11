{if $query.type == null}
  {foreach from=$query.facets item="facet"}
    {include './query.tpl' query=$facet}
  {/foreach}
{else}
  <div>
    {if $query.type == 'CategoryFilter'}
      <p>{l s='Category'}</p>
    {else}
      {* StarterTheme TODO: We definitely need a better way to manage translations, don't want to worry about it for the POC *}
      <p>{l s='Filter'}</p>
    {/if}
    {foreach from=$query.choices item="choice"}
      <label><input name="{$choice.name|escape:'javascript'}" value="{$choice.value|escape:'javascript'}" type="checkbox" {if $choice.enabled}checked{/if}> {$choice.label}</label>
    {/foreach}
  </div>
{/if}
