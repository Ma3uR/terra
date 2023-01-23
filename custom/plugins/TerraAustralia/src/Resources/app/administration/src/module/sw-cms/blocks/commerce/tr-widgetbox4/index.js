import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-widgetbox4',
    label: 'global.cms.blocks.tr-widgetbox4.label',
    category: 'commerce',
    component: 'sw-cms-block-tr-widgetbox4',
    previewComponent: 'tr-widgetbox4-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        box1: 'tr-e-widget',
        box2: 'tr-e-widget',
        box3: 'tr-e-widget',
        box4: 'tr-e-widget'
    }
});
