import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-manufacturers',
    label: 'global.cms.blocks.tr-manufacturers.label',
    category: 'commerce',
    component: 'sw-cms-block-tr-manufacturers',
    previewComponent: 'tr-manufacturers-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        manufacturers: 'tr-e-manufacturers'
    }
});
