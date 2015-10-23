import $ from 'jquery';

import prestashop from 'prestashop';
import EventEmitter from 'events';

// "inherit" EventEmitter
for (var i in EventEmitter.prototype) {
    prestashop[i] = EventEmitter.prototype[i];
}

import './setup-rivets';
import './checkout';
import './product-navigation';

$(document).ready(() => {
    $('.ps-shown-by-js').show();
    $('.ps-hidden-by-js').hide();
});
