import './component';
import './config';
import './preview';

Shopware.Service('cmsService').registerCmsElement({
    name: 'tr-e-slider',
    label: 'global.cms.elements.tr-slider.label',
    component: 'tr-e-slider-component',
    configComponent: 'tr-e-slider-config',
    previewComponent: 'tr-e-slider-preview',
    defaultConfig: {
        sliderItems: {
            source: 'static',
            value: [],
            required: true,
            entity: {
                name: 'media'
            }
        },
    },
    enrich: function enrich(elem, data) {
        if (Object.keys(data).length < 1) {
            return;
        }

        Object.keys(elem.config).forEach((configKey) => {
            const entity = elem.config[configKey].entity;

            if (!entity) {
                return;
            }

            const entityKey = entity.name;
            if (!data[`entity-${entityKey}`]) {
                return;
            }

            elem.data[configKey] = [];
            elem.config[configKey].value.forEach((sliderItem) => {
                elem.data[configKey].push({
                    newTab: sliderItem.newTab,
                    url: sliderItem.url,
                    media: data[`entity-${entityKey}`].get(sliderItem.mediaId),
                    label: sliderItem.label,
                    title: sliderItem.title,
                    btnName: sliderItem.btnName
                });
            });
        });
    }
});
