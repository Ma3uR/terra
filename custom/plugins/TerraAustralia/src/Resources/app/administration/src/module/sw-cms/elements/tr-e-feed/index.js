import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-feed',
    label: 'global.cms.elements.tr-feed.label',
    component: 'tr-e-feed-component',
    configComponent: 'tr-e-feed-config',
    previewComponent: 'tr-e-feed-preview',
    defaultConfig: {
        media: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'media'
            }
        },
        caption: {
            source: 'static',
            value: null
        }
    }
});
