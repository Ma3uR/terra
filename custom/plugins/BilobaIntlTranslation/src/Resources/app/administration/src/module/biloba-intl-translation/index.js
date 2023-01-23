import './page/biloba-intl-translation-plugin-configuration';
import './page/biloba-intl-translation-log-list';
import './page/biloba-intl-translation-log-detail';

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';
import esES from './snippet/es-ES';
import frFR from './snippet/fr-FR';
import itIT from './snippet/it-IT';
import nlNL from './snippet/nl-NL';
import plPL from './snippet/pl-PL';
import ptPT from './snippet/pt-PT';

Shopware.Module.register('biloba-intl-translation', {
    color: '#002445',
    icon: 'default-shopping-paper-bag-product',
    title: 'Biloba Translation Pro',
    description: 'Manage bundles here.',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
        'es-ES': esES,
        'frFR': frFR,
        'itIT': itIT,
        'nlNL': nlNL,
        'plPL': plPL,
        'ptPT': ptPT
    },

    navigation: [{
        label: 'biloba-intl-translation.general.mainMenuItemGeneral',
        color: '#ff68b4',
        path: 'biloba.intl.translation.log.list',
        icon: 'default-symbol-content',
        position: 100,
        parent: 'sw-content'
    }],
    // SW routes are for getting information from the migration, so we need always list, detail or create
    routes: {
        'log.list': {
            // we give our component a suffix according to the above
            component: 'biloba-intl-translation-log-list',
            path: 'log/list'
        },
        'log.detail': {
            component: 'biloba-intl-translation-log-detail',
            path: 'log/detail/:id',
            meta: {
                parentPath: 'biloba.intl.translation.log.list'
            }
        },
    },
});