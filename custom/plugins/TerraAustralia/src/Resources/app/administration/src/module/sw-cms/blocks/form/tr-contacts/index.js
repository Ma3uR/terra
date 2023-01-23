import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'tr-contacts',
    label: 'global.cms.blocks.tr-contacts.label',
    category: 'form',
    component: 'sw-cms-block-tr-contacts',
    previewComponent: 'tr-contacts-preview',
    defaultConfig: {
        marginBottom: '0px',
        marginTop: '0px',
        marginLeft: '0px',
        marginRight: '0px',
        sizingMode: 'full_width'
    },
    slots: {
        content: 'tr-e-contacts'
    }
});
