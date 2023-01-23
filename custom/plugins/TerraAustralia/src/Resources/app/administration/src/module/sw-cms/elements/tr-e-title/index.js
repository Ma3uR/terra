import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-title',
    label: 'global.cms.elements.tr-title.label',
    component: 'tr-e-title-component',
    configComponent: 'tr-e-title-config',
    previewComponent: 'tr-e-title-preview',
    defaultConfig: {
        title: {
            source: 'static',
            value: '(Title) Lorem ipsum dolor sit amet'
        }
    }
});
