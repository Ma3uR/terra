import template from './webmasterei-system-config.html.twig';

const { Component, Mixin } = Shopware;
const { object, types } = Shopware.Utils;
const { Criteria } = Shopware.Data;

Component.extend('webmasterei-system-config', 'sw-system-config', {
    name: 'webmasterei-system-config',

    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: ['systemConfigApiService', 'repositoryFactory'],

    data() {
        return {
            selectedDomains: []
        };
    },
    computed: {
        last() {
            return Object.keys(this.config).length-1;
        },
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },
        salesChannelCritera() {
            const criteria = new Criteria();
            criteria.addAssociation('domains');

            return criteria;
        }
    },
    methods: {
        customOnSalesChannelChanged(salesChannelId) {
            this.onSalesChannelChanged(salesChannelId);//call parent method
            this.salesChannelRepository
                .get(salesChannelId, Shopware.Context.api, this.salesChannelCritera)
                .then((salesChannel) => {
                    let currentDomains = salesChannel !== null && salesChannel.domains.length > 0 ? salesChannel.domains : [];
                    this.actualConfigData[salesChannelId]['WebmpGoogleReviewFeed.settings.domains'] = currentDomains;
                });
        },

        onElementInput(element, currentSalesChannelId, accessToken) {
            if (element.name === 'WebmpGoogleReviewFeed.settings.accessToken') {
                this.onAccessTokenInput(currentSalesChannelId, accessToken);
            }
        },

        onAccessTokenInput(currentSalesChannelId, accessToken) {
            if (currentSalesChannelId === null) {
                this.actualConfigData[currentSalesChannelId]['WebmpGoogleReviewFeed.settings.feedUrls'] = '';
            } else {
                this.salesChannelRepository
                    .get(currentSalesChannelId, Shopware.Context.api, this.salesChannelCritera)
                    .then((salesChannel) => {
                        if (salesChannel !== null && salesChannel.domains.length > 0) {
                            let domains = this.actualConfigData[currentSalesChannelId]['WebmpGoogleReviewFeed.settings.domains'];
                            let feedUrls = [];
                            domains.forEach(function (domain) {
                                let url = `${domain.url}/webmasterei/google-product-review/${accessToken}/feed`;
                                feedUrls.push(url);
                            })
                            this.actualConfigData[currentSalesChannelId]['WebmpGoogleReviewFeed.settings.feedUrls'] = feedUrls;
                        } else {
                            this.actualConfigData[currentSalesChannelId]['WebmpGoogleReviewFeed.settings.feedUrls'] = '';
                        }
                    });
            }
        }
    },
    watch: {
        selectedDomains: function () {
            console.log('changed! 1');
        }
    }
});
