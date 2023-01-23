import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria();
criteria.addAssociation('cover');

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-products',
    label: 'global.cms.elements.tr-products.label',
    component: 'tr-e-products-component',
    configComponent: 'tr-e-products-config',
    previewComponent: 'sw-cms-el-preview-tr-e-products',
    defaultConfig: {
        products: {
            source: 'static',
            value: [],
            required: true,
            entity: {
                name: 'product',
                criteria: criteria
            }
        },
        title: {
            source: 'static',
            value: ''
        },
        defaultSorting: {
            source: 'static',
            value: ''
        },
        productStreamLimit: {
            source: 'static',
            value: 8
        }
    },
    collect: function collect(elem) {
        const context = Object.assign(
            {},
            Shopware.Context.api,
            { inheritance: true }
        );

        const criteriaList = {};

        Object.keys(elem.config).forEach((configKey) => {
            if (elem.config[configKey].source === 'mapped') {
                return;
            }

            if (elem.config[configKey].source === 'product_stream') {
                return;
            }

            const entity = elem.config[configKey].entity;

            if (entity && elem.config[configKey].value) {
                const entityKey = entity.name;
                const entityData = {
                    value: [...elem.config[configKey].value],
                    key: configKey,
                    searchCriteria: entity.criteria ? entity.criteria : new Criteria(),
                    ...entity
                };

                entityData.searchCriteria.setIds(entityData.value);
                entityData.context = context;

                criteriaList[`entity-${entityKey}`] = entityData;
            }
        });

        return criteriaList;
    }
});
