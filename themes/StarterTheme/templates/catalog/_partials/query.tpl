{foreach from=$query item="facet" key="facetIndex"}
  <input type="hidden" name="facets[{$facetIndex}][identifier]" value="{$facet.identifier}">
  <section class="facet">
    <h1 class="h2">{$facet.name}</h1>
    {foreach from=$facet.filters item="filter"}
      <label>
        <input
          type  = "checkbox"
          name  = "{$filter.inputName nofilter}"
          value = {$filter.inputValue nofilter}
          {if $filter.enabled} checked {/if}
        > {$filter.label}
      </label>
    {/foreach}
  </section>
{/foreach}
