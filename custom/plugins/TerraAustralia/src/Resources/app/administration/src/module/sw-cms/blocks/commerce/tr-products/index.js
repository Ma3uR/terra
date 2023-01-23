import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-products',
    label: 'global.cms.blocks.tr-products.label',
    category: 'commerce',
    component: 'sw-cms-block-tr-products',
    previewComponent: 'tr-products-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        productCollection: 'tr-e-products'
    }
});
