const { Criteria, EntityCollection } = Shopware.Data;

export default {
    namespaced: true,

    state() {
        return {
            category: null,
            customFieldSets: [],
            widgetData: {
                source: 'static',
                products: [],
                productStream: null,
                tempProductIds: [],
                tempStreamId: null,
                defaultSorting: 'name-asc',
                productCollection: new EntityCollection('/product', 'product', Shopware.Context.api)
            }
        };
    },

    mutations: {
        setActiveCategory(state, { category }) {
            state.category = category;
        },

        setCustomFieldSets(state, newCustomFieldSets) {
            state.customFieldSets = newCustomFieldSets;
        },
        
        setWidgetData(state, data) {
            state.widgetData = data;
        },
    },

    actions: {
        setActiveCategory({ commit }, payload) {
            commit('setActiveCategory', payload);
        },

        loadActiveCategory({ commit }, { repository, id, apiContext }) {
            const criteria = new Criteria();

            criteria.getAssociation('seoUrls')
                .addFilter(Criteria.equals('isCanonical', true));

            criteria.addAssociation('tags')
                .addAssociation('media')
                .addAssociation('navigationSalesChannels')
                .addAssociation('serviceSalesChannels')
                .addAssociation('footerSalesChannels');


            return repository.get(id, apiContext, criteria).then((category) => {
                commit('setActiveCategory', { category });
            });
        },
        
        loadWidgetData({ commit }, { repository, id, apiContext }) {
            const criteria = new Criteria();
                
            criteria.addFilter(Criteria.equals('categoryId', id));
            
            return repository.search(criteria, apiContext).then((widget) => {
                
                let payload = {
                    source: 'static',
                    products: [],
                    productStream: null,
                    tempProductIds: [],
                    tempStreamId: null,
                    defaultSorting: 'name-asc',
                    productCollection: new EntityCollection('/product', 'product', Shopware.Context.api)
                };
                
                if(widget && widget.total > 0) {
                    const first = widget.first();
                    
                    if(first) {
                        const src = JSON.parse(first.source);
                        
                        payload.source = src.source;
                        payload.defaultSorting = src.sorting;
                        payload.products = src.products;
                        
                        if( payload.source === 'product_stream' ) {
                            payload.productStream = src.products;
                        }
                    }
                }
                
                commit('setWidgetData', payload);
            });
        },
        
        updateWidgetData({ commit }, { repository, id, apiContext, widget }) {
            commit('setWidgetData', widget);
        }
    }
};
