import './js/jquery-3.5.1.js';
import './js/select2.js';
import './js/slick.js';
import './js/script.js';

import ListingExtended from './plugins/listing-extended';

const PluginManager = window.PluginManager;

PluginManager.register('ListingExtended', ListingExtended, '[data-listing-extended]');
