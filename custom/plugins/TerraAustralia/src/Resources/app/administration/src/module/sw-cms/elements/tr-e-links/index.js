import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-links',
    label: 'global.cms.elements.tr-links.label',
    component: 'tr-e-links-component',
    configComponent: 'tr-e-links-config',
    previewComponent: 'tr-e-links-preview',
    defaultConfig: {
        links: {
            source: 'static',
            value: [],
            required: true
        },
        
        cover: {
            source: 'static',
            value: null,
            entity: {
                name: 'media'
            }
        }
    }
});
