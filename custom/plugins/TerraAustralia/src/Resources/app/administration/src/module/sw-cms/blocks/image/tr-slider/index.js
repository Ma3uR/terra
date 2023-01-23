import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-slider',
    label: 'global.cms.blocks.tr-slider.label',
    category: 'image',
    component: 'tr-slider-component',
    previewComponent: 'tr-slider-preview',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'full_width'
    },
    slots: {
        slider: 'tr-e-slider'
    }
});
