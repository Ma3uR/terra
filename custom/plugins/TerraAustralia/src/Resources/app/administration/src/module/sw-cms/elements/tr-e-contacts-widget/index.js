import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-contacts-widget',
    label: 'global.cms.elements.tr-contacts-widget.label',
    component: 'tr-e-contacts-widget-component',
    configComponent: 'tr-e-contacts-widget-config',
    previewComponent: 'tr-e-contacts-widget-preview',
    defaultConfig: {
        title: {
            source: 'static',
            value: ''
        },
        phone: {
            source: 'static',
            value: ''
        },
        email: {
            source: 'static',
            value: ''
        },
        background: {
            source: 'static',
            value: null,
            entity: {
                name: 'media'
            }
        },
        mode: {
            source: 'static',
            value: 'widget_link'
        },
        linkTo: {
            source: 'static',
            value: null
        },
        targetType: {
            source: 'static',
            value: 'contacts'
        }
    }
});
