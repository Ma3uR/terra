const { Component, Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;
const { mapState, mapPropertyErrors } = Shopware.Component.getComponentHelper();
const { cloneDeep, merge } = Shopware.Utils.object;
const ShopwareError = Shopware.Classes.ShopwareError;
const type = Shopware.Utils.types;

import pageState from './state';

Component.override('sw-category-detail', {

    beforeCreate() {
        Shopware.State.registerModule('swCategoryDetail', pageState);
        Shopware.State.dispatch('cmsPageState/resetCmsPageState');
    },
    
    computed: {
        ...mapState('swCategoryDetail', [
            'widgetData',
            'category'
        ]),
        
        widgetRepository() {
            return this.repositoryFactory.create('tr_category_header_widget');
        },
        
        widgetProductRepository() {
            return this.repositoryFactory.create('product');
        },
    },
    
    methods: {
        
        saveCategoryHeaderWidget() {
            const payload = this.widgetRepository.create(Shopware.Context.api);
            
            payload.categoryId = this.category.id;
            payload.source = JSON.stringify({
                                sorting: this.widgetData.defaultSorting,
                                products: this.widgetData.products,
                                source: this.widgetData.source,
                            });
                            
            return this.widgetRepository.save(payload, Shopware.Context.api).then(() => {
                Shopware.State.dispatch('swCategoryDetail/loadWidgetData', {
                    repository: this.widgetRepository,
                    apiContext: Shopware.Context.api,
                    id: this.category.id
                }).then(() => {
                    // load products
                    if(this.widgetData.source === 'static' && this.widgetData.products.length > 0) {
                        const criteria = new Criteria();
                        criteria.setIds(this.widgetData.products);
                        
                        this.widgetProductRepository.search(criteria, Object.assign({}, Shopware.Context.api, { inheritance: true }))
                            .then((result) => {
                                this.widgetData.productCollection = result;
                            });
                    }
                });
            });
        },
        
        processCategoryHeaderWidget() {
            if(this.category.id) {
                const criteria = new Criteria();
                criteria.addFilter(Criteria.equals('categoryId', this.category.id));
                
                return this.widgetRepository.search(criteria, Shopware.Context.api).then((results) => {
                    if( results.getIds().length > 0 ) {
                            
                        return this.widgetRepository.syncDeleted(results.getIds(), Shopware.Context.api).then(() => {
                            this.saveCategoryHeaderWidget();
                        });
                    } else {
                        return this.saveCategoryHeaderWidget();
                    }
                });
            }
        },
        
        onSave() {
            this.processCategoryHeaderWidget();
            
            this.isSaveSuccessful = false;

            const pageOverrides = this.getCmsPageOverrides();

            if (type.isPlainObject(pageOverrides)) {
                this.category.slotConfig = cloneDeep(pageOverrides);
            }

            this.isLoading = true;
            this.updateSeoUrls().then(() => {
                return this.categoryRepository.save(this.category, Shopware.Context.api);
            }).then(() => {
                this.isSaveSuccessful = true;
                
                return this.setCategory();
                
            }).catch(() => {
                this.isLoading = false;

                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc(
                        'global.notification.notificationSaveErrorMessageRequiredFieldsInvalid'
                    )
                });
            });
        },
    }

});
