import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-widget',
    label: 'global.cms.elements.tr-widget.label',
    component: 'tr-e-widget-component',
    configComponent: 'tr-e-widget-config',
    previewComponent: 'tr-e-widget-preview',
    
    defaultConfig: {
        target: {
            source: 'static',
            value: null,
            required: true
        },
        targetType: {
            source: 'static',
            value: 'category'
        },
        mode: {
            source: 'static',
            value: 'standart'
        }
    }
});
