{foreach from=$query item="facet" key="facetIndex"}
  <input type="hidden" name="facets[{$facetIndex}][identifier]" value="{$facet.identifier}">
  <input type="hidden" name="facets[{$facetIndex}][label]" value="{$facet.label}">
  <input type="hidden" name="facets[{$facetIndex}][condition]" value={$facet.condition nofilter}>
  <section class="facet {if $facet.hidden} hidden {/if}">
    <h1 class="h2">{$facet.label}</h1>
    {foreach from=$facet.filters item="filter"}
      <label class="filter">
        <input
          type  = "checkbox"
          name  = "{$filter.inputName nofilter}"
          value = {$filter.inputValue nofilter}
          {if $filter.enabled} checked {/if}
        > {$filter.label} <span class="magnitude">{$filter.magnitude}</span>
      </label>
    {/foreach}
  </section>
{/foreach}
