import template from './tr-e-products-config.html.twig';
import './tr-e-products-config.scss';

const { Component, Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;

Component.register('tr-e-products-config', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            productCollection: null,
            productStream: null,
            tempProductIds: [],
            tempStreamId: null,
            defaultSorting: {}
        };
    },
    
    watch: {
        defaultSorting() {
            if (Object.keys(this.defaultSorting).length === 0) {
                this.element.config.defaultSorting.value = '';
            } else {
                this.element.config.defaultSorting.value = this.defaultSorting.key;
            }
        }
    },

    computed: {
        productRepository() {
            return this.repositoryFactory.create('product');
        },

        productStreamRepository() {
            return this.repositoryFactory.create('product_stream');
        },
        
        productSortingRepository() {
            return this.repositoryFactory.create('product_sorting');
        },

        products() {
            if (this.element.data && this.element.data.products && this.element.data.products.length > 0) {
                return this.element.data.products;
            }

            return null;
        },

        productMediaFilter() {
            const criteria = new Criteria(1, 50);
            criteria.addAssociation('cover');
            criteria.addAssociation('options.group');

            return criteria;
        },

        productMultiSelectContext() {
            const context = Object.assign({}, Shopware.Context.api);
            context.inheritance = true;

            return context;
        },

        productAssignmentTypes() {
            return [{
                label: this.$tc('global.cms.elements.tr-products.config.productAssignmentTypeOptions.manual'),
                value: 'static'
            }, {
                label: this.$tc('global.cms.elements.tr-products.config.productAssignmentTypeOptions.productStream'),
                value: 'product_stream'
            }];
        },
        
        allProductSortingsCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.equals('locked', false));
            criteria.addSorting(Criteria.sort('priority', 'desc'));

            return criteria;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-products');

            this.productCollection = new EntityCollection('/product', 'product', Shopware.Context.api);

            if (this.element.config.products.value.length <= 0) {
                return;
            }

            if (this.element.config.products.source === 'product_stream') {
                this.loadProductStream();
            } else {
                const criteria = new Criteria(1, 50);
                criteria.addAssociation('cover');
                criteria.addAssociation('options.group');
                criteria.setIds(this.element.config.products.value);

                this.productRepository
                    .search(criteria, Object.assign({}, Shopware.Context.api, { inheritance: true }))
                    .then((result) => {
                        this.productCollection = result;
                    });
            }
            
            this.initDefaultSorting();
        },
        
        initDefaultSorting() {
            const defaultSortingKey = this.element.config.defaultSorting.value;
            if (defaultSortingKey !== '') {
                const criteria = new Criteria();

                criteria.addFilter(Criteria.equals('key', defaultSortingKey));

                this.productSortingRepository.search(criteria, Shopware.Context.api)
                    .then(response => {
                        this.defaultSorting = response.first();
                    });
            }
        },

        onChangeAssignmentType(type) {
            if (type === 'product_stream') {
                this.tempProductIds = this.element.config.products.value;
                this.element.config.products.value = this.tempStreamId;
            } else {
                this.tempStreamId = this.element.config.products.value;
                this.element.config.products.value = this.tempProductIds;
            }
        },

        loadProductStream() {
            this.productStreamRepository
                .get(this.element.config.products.value, Shopware.Context.api, new Criteria())
                .then((result) => {
                    this.productStream = result;
                });
        },

        onChangeProductStream(streamId) {
            if (streamId === null) {
                this.productStream = null;
                return;
            }

            this.loadProductStream();
        },

        onProductsChange() {
            this.element.config.products.value = this.productCollection.getIds();
            
            this.$set(this.element.data, 'products', this.productCollection);
        },
        
        onDefaultSortingChange(entity, defaultSorting) {
            if (!defaultSorting) {
                this.defaultSorting = {};
                return;
            }

            this.defaultSorting = defaultSorting;
        }
    }
});
