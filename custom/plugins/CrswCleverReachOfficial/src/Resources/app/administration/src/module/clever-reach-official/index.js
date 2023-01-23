import './component/clever-reach-container';
import './component/clever-reach-content-window-wrapper';
import './component/clever-reach-iframe';
import './component/clever-reach-banner';
import './component/clever-reach-fonts';
import './page/clever-reach-router';
import './page/clever-reach-welcome';
import './page/clever-reach-initialSync';
import './page/clever-reach-refresh';
import './page/clever-reach-dashboard';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
import frFR from './snippet/fr-FR.json';
import esES from './snippet/es-ES.json';
import itIT from './snippet/it-IT.json';

Shopware.Module.register('clever-reach-official', {
    type: 'plugin',
    name: 'clever-reach.basic.label',
    title: 'clever-reach.basic.label',
    description: 'clever-reach.basic.description',
    color: '#EC6702',
    icon: 'default-communication-envelope',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
        'fr-FR': frFR,
        'es-ES': esES,
        'it-IT': itIT,
    },

    routes: {
        index: {
            component: 'clever-reach-router',
            path: ':page?'
        }
    },

    navigation: [{
        label: 'clever-reach.basic.label',
        color: '#EC6702',
        path: 'clever.reach.official.index',
        icon: 'default-communication-envelope',
        parent: 'sw-marketing'
    }]
});