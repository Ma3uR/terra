import template from './tr-e-widget-config.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;

Shopware.Component.register('tr-e-widget-config', {
    template,
    
    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },
    
    computed: {
        getLabelProperty() {
            let r = 'name';
            
            switch (this.element.config.targetType.value) {
                case 'category':
                    r = 'name';
                    break;
                default:
                    r = 'name';
                    break;
            }
            return r;
        },
        
        getEntity() {
            return this.element.config.targetType.value;
        }
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-widget');
        }
    }
});
