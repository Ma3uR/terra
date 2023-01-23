// import Vue from "vue";
import './services/webmp.service';

import './module/extentions/webmasteri-advanced-configuration';
import './module/extentions/webmasterei-plugin-config';
import './module/extentions/webmasterei-system-config';
import './module/extentions/sw-settings-index';
import './module/webmasterei-settings';

// Snippets import
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);
