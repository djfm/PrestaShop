<div id="navigation-sort-options">
  {foreach from=$sort_options item=option}
    <button
      type  = "submit"
      form  = "navigation"
      name  = "sort_option"
      value = "{$option.serialized|escape:'javascript'}"
      {if $option.enabled} class = "current" {/if}
    >
      {$option.label}
    </button>
  {/foreach}
</div>
