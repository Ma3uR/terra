import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-benefits',
    label: 'global.cms.blocks.tr-benefits.label',
    category: 'text-image',
    component: 'sw-cms-block-tr-benefits',
    previewComponent: 'tr-benefits-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        title: 'tr-e-title',
        feed1: 'tr-e-feed',
        feed2: 'tr-e-feed',
        feed3: 'tr-e-feed',
        feed4: 'tr-e-feed'
    }
});
