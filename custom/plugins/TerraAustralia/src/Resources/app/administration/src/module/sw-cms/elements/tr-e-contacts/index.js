import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-contacts',
    label: 'global.cms.elements.tr-contacts.label',
    component: 'tr-e-contacts-component',
    configComponent: 'tr-e-contacts-config',
    previewComponent: 'tr-e-contacts-preview',
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
            //required: true,
            entity: {
                name: 'media'
            }
        },
        mode: {
            source: 'static',
            value: 'form'
        },
        linkTo: {
            source: 'static',
            value: null
        }
    }
});
