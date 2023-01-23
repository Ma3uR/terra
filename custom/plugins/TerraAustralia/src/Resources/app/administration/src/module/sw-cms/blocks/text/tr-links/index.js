import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-links',
    label: 'global.cms.blocks.tr-links.label',
    category: 'text',
    component: 'sw-cms-block-tr-links',
    previewComponent: 'tr-links-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        links: 'tr-e-links'
    }
});
