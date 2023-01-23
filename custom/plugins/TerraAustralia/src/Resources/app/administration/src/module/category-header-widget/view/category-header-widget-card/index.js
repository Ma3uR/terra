const { Component, Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;
const { mapState, mapPropertyErrors } = Shopware.Component.getComponentHelper();

import template from './category-header-widget.html.twig';

Component.override('sw-category-detail-base', {
    template,
    
    data() {
        return {
            productStreamFilter: null,
            productStreamInvalid: false,
            manualAssignedProductsCount: 0,
            defaultSorting: {}
        };
    },
    
    watch: {
        'category'(category) {
            
            if(category) {
                this.loadWidgetData(category.id);
            }
        },
        
        defaultSorting() {
            if(typeof this.defaultSorting === 'undefined' || ! this.defaultSorting){
                this.defaultSorting = {};
            }
            
            if (Object.keys(this.defaultSorting).length === 0) {
                this.widget.defaultSorting = '';
            } else {
                this.widget.defaultSorting = this.defaultSorting.key;
            }
            
            this.updateWidgetState();
        }
    },
    
    created() {
        this.createdComponent();
        
        if( this.category ) {
            this.loadWidgetData(this.category.id);
        }
    },
    
    computed: {
        widget() {
            return Shopware.State.get('swCategoryDetail').widgetData;
        },
        
        widgetProductRepository() {
            return this.repositoryFactory.create('product');
        },
        
        widgetRepository() {
            return this.repositoryFactory.create('tr_category_header_widget');
        },
        
        widgetProductStreamRepository() {
            return this.repositoryFactory.create('product_stream');
        },
        
        widgetProductAssignmentTypes() {
            return [{
                label: this.$tc('global.category-header-widget.productAssignmentTypeOptions.manual'),
                value: 'static'
            }, {
                label: this.$tc('global.category-header-widget.productAssignmentTypeOptions.productStream'),
                value: 'product_stream'
            }];
        },
        
        productSortingRepository() {
            return this.repositoryFactory.create('product_sorting');
        },
        
        allProductSortingsCriteria() {
            const criteria = new Criteria();

            criteria.addFilter(Criteria.equals('locked', false));
            criteria.addSorting(Criteria.sort('priority', 'desc'));

            return criteria;
        },
    },
    
    methods: {
        loadWidgetData(categoryId) {
            return Shopware.State.dispatch('swCategoryDetail/loadWidgetData', {
                repository: this.widgetRepository,
                apiContext: Shopware.Context.api,
                id: categoryId
            }).then(() => {
                this.initDefaultSorting();
                
                // load products
                if(this.widget.source === 'static' && this.widget.products.length > 0) {
                    const criteria = new Criteria();
                    criteria.setIds(this.widget.products);
                    
                    this.widgetProductRepository.search(criteria, Object.assign({}, Shopware.Context.api, { inheritance: true }))
                        .then((result) => {
                            this.widget.productCollection = result;
                        });
                }
            });
        },
        
        initDefaultSorting() {
            const defaultSortingKey = this.widget.defaultSorting;
            if (defaultSortingKey !== '') {
                const criteria = new Criteria();

                criteria.addFilter(Criteria.equals('key', defaultSortingKey));

                this.productSortingRepository.search(criteria, Shopware.Context.api)
                    .then(response => {
                        this.defaultSorting = response.first();
                    });
            }
        },
        
        updateWidgetState() {
            return Shopware.State.dispatch('swCategoryDetail/updateWidgetData', {
                        repository: this.widgetProductsRepository,
                        apiContext: Shopware.Context.api,
                        id: this.category.id,
                        widget: this.widget
                    }).then(() => {
                        // nothing
                    });
        },
        
        loadWidgetProductStream() {
            this.widgetProductStreamRepository
                .get(this.widget.products, Shopware.Context.api, new Criteria())
                .then((result) => {
                    this.widget.productStream = result;
                    
                    this.updateWidgetState();
                });
        },
        
        onWidgetChangeAssignmentType(type) {
            if (type === 'product_stream') {
                this.widget.tempProductIds = this.widget.products;
                this.widget.products = this.widget.tempStreamId;
            } else {
                this.widget.tempStreamId = this.widget.products;
                this.widget.products = this.widget.tempProductIds;
            }
        },
        
        onWidgetChangeProductStream(streamId) {
            if (streamId === null) {
                this.widget.productStream = null;
                return;
            }

            this.loadWidgetProductStream();
        },
        
        onWidgetProductsChange() {
            this.widget.products = this.widget.productCollection.getIds();

            this.updateWidgetState();
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
