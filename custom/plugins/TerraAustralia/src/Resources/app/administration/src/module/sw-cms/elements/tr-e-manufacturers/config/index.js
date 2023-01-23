import template from './tr-e-manufacturers-config.html.twig';
import './tr-e-manufacturers-config.scss';

const { Component, Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;

Component.register('tr-e-manufacturers-config', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            manufacturersCollection: null,
        };
    },

    computed: {
        manufacturersRepository() {
            return this.repositoryFactory.create('product_manufacturer');
        },

        manufacturers() {
            if (this.element.data && this.element.data.manufacturers && this.element.data.manufacturers.length > 0) {
                return this.element.data.manufacturers;
            }

            return null;
        },

        manufacturersFilter() {
            const criteria = new Criteria(1, 100);
            criteria.addAssociation('media');
            
            return criteria;
        },

        manufacturersMultiSelectContext() {
            const context = Object.assign({}, Shopware.Context.api);
            context.inheritance = true;

            return context;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-manufacturers');

            this.manufacturersCollection = new EntityCollection('/product-manufacturer', 'product_manufacturer', Shopware.Context.api);

            if (this.element.config.manufacturers.value.length > 0) {
                const criteria = new Criteria(1, 100);
                criteria.addAssociation('media');
                criteria.setIds(this.element.config.manufacturers.value);

                this.manufacturersRepository.search(criteria, Object.assign({}, Shopware.Context.api, { inheritance: true }))
                    .then(result => {
                        this.manufacturersCollection = result;
                    });
            }
        },
        
        onManufacturersChange() {
            this.element.config.manufacturers.value = this.manufacturersCollection.getIds();

            this.$set(this.element.data, 'manufacturers', this.manufacturersCollection);
        }
    }
});
