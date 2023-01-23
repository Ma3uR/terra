import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-widget-data',
    label: 'global.cms.elements.tr-widget-data.label',
    component: 'tr-e-widget-data-component',
    configComponent: 'tr-e-widget-data-config',
    previewComponent: 'tr-e-widget-data-preview',
    
    defaultConfig: {
        target: {
            source: 'static',
            value: null
        },
        targetType: {
            source: 'static',
            value: 'category'
        },
        /* Data widget can have multiple templates
         * mode manages it
         * supported types currently: seo
         * */
        mode: {
            source: 'static',
            value: 'seo'
        },
        settings: {
            source: 'static',
            value: {
                seo: {
                    sourceType: 'customFields', // source type inside source data, e.g. Category -> customFields
                    sourceFields: 'terra_listing_seotitle, terra_listing_seotext' // source fields from source type, e.g. Category -> customFields -> terra_listing_seotitle
                }
            }
        }
    }
});
