import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-listing-headline',
    label: 'global.cms.blocks.tr-listing-headline.label',
    category: 'text',
    component: 'sw-cms-block-tr-listing-headline',
    previewComponent: 'tr-listing-headline-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        links: 'tr-e-links',
        text: 'text'
    }
});
