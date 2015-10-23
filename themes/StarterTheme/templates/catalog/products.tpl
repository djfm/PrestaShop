{if $products|count}
  <section id="products">
    <h1>{l s='Products'}</h1>

    {block name="filters"}
      {include './_partials/navigation.tpl' query=$query}
    {/block}

    {block name="sort_options"}
      {include './_partials/sort_options.tpl' sort_options=$sort_options}
    {/block}

    <div class="products">
      {foreach from=$products item="product"}
        {block name="product_miniature"}
          {include './product-miniature.tpl' product=$product}
        {/block}
      {/foreach}
    </div>

    {block name="pagination"}
      {include './_partials/pagination.tpl' pagination=$pagination}
    {/block}
  </section>
{/if}
