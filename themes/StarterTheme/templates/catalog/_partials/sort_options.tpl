<div id="navigation-sort-options">
  {foreach from=$sort_options item=option}
    <button
      type  = "submit"
      form  = "navigation"
      name  = "sort_option"
      value = "{$option.serialized|escape:'javascript'}"
    >
      {$option.label}
    </button>
  {/foreach}
</div>
