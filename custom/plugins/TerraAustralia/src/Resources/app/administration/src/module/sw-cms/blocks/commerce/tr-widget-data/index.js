import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-widget-data',
    label: 'global.cms.blocks.tr-widget-data.label',
    category: 'commerce',
    component: 'sw-cms-block-tr-widget-data',
    previewComponent: 'tr-widget-data-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        data: 'tr-e-widget-data'
    }
});
