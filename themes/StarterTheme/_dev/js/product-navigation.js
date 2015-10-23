import $ from 'jquery';

function replaceProducts (html) {
  $('#products').replaceWith(html);
  $('.ps-hidden-by-js').hide();
}

function refreshProducts (extraParams) {
  extraParams  = extraParams || '';
  const params = $('#navigation').serialize() + extraParams;
  const url    = window.location.href.split("?")[0];
  $.post(url, params, null, 'json').then(resp => {
    replaceProducts(resp.products);
    window.history.pushState({products: resp.products}, undefined, resp.query_url);
    window.onpopstate = function (e) {
      if (e.state && e.state.products) {
        replaceProducts(e.state.products);
      }
    };
  });
}

$(document).ready(function () {
  $('body').on('change', '#navigation input', function () {
    refreshProducts();
  });

  $('body').on('click', '#products button[name="page"]', function (event) {
    event.preventDefault();
    const page = $(event.target).attr('value');
    refreshProducts('&page=' + page);
  });

    $('body').on('click', '#products button[name="sort_option"]', function (event) {
      event.preventDefault();
      const option = $(event.target).attr('value');
      refreshProducts('&sort_option=' + option);
    });
});
