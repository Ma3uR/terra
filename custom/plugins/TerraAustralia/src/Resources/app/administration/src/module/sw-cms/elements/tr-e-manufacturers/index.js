import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-manufacturers',
    label: 'global.cms.elements.tr-manufacturers.label',
    component: 'tr-e-manufacturers-component',
    configComponent: 'tr-e-manufacturers-config',
    previewComponent: 'tr-e-manufacturers-preview',
    defaultConfig: {
        manufacturers: {
            source: 'static',
            value: [],
            required: true,
            entity: {
                name: 'product_manufacturer',
                criteria: criteria
            }
        },
        title: {
            source: 'static',
            value: null
        },
        displayTitle: {
            source: 'static',
            value: true
        }
        
    }
});
