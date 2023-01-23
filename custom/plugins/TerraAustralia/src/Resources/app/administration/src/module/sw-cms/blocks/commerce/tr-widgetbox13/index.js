import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-widgetbox13',
    label: 'global.cms.blocks.tr-widgetbox13.label',
    category: 'commerce',
    component: 'sw-cms-block-tr-widgetbox13',
    previewComponent: 'tr-widgetbox13-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        contacts: 'tr-e-contacts-widget',
        box1: 'tr-e-widget',
        box2: 'tr-e-widget',
        box3: 'tr-e-widget'
    }
});
