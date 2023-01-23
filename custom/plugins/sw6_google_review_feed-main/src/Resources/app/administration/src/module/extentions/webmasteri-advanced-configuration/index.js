import template from './webmasteri-input-feed-url.html.twig';
import './webmasteri-input-feed-url.scss';

const { Component, Utils, Mixin } = Shopware;

Component.register('webmasterei-input-feed-url', {
    name: 'webmasterei-input-feed-url',
    template,
    inject: ['systemConfigApiService', 'repositoryFactory', 'WebmpService'],
    props: {
        actualConfigData: Object,
        currentSalesChannelId: String
    },
    mixins: [
        Mixin.getByName('notification')
    ],
    data() {
        return {
            isLoadingGeneration: false,
            salesChannelRepository: this.repositoryFactory.create('sales_channel'),
            availableDomains: this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.domains']
                ? this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.domains']
                : [],
            selectedDomains: this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.currentDomains']
                ? this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.currentDomains']
                : []
        };
    },
    computed: {
        accessToken() {
            return this.currentSalesChannelId
                ? this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.accessToken']
                : '';
        },
        feedUrls() {
            if (!this.currentSalesChannelId) {
                return [];
            }
            let feedUrls = [];
            this.selectedDomains.forEach(function (domain) {
                feedUrls.push(`${domain}/webmasterei/google-product-review/${this.accessToken}/feed`);
            }.bind(this));
            return feedUrls;
        }
    },
    methods: {
        generateAccessKey() {
            this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.currentDomains'] = this.selectedDomains;

            if (!this.selectedDomains) {
                this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.feedUrls'] = [];
                return;
            }

            this.salesChannelRepository
                .get(this.currentSalesChannelId, Shopware.Context.api, this.salesChannelCritera)
                .then((salesChannel) => {
                    if (salesChannel === null || salesChannel.domains.length === 0) {
                        this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.feedUrls'] = [];
                    }

                    this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.accessToken'] = Utils.createId();
                    let feedUrls = [];
                    this.selectedDomains.forEach(function (domain) {
                        feedUrls.push(`${domain}/webmasterei/google-product-review/${this.accessToken}/feed`);
                    }.bind(this))

                    this.actualConfigData[this.currentSalesChannelId]['WebmpGoogleReviewFeed.settings.feedUrls'] = feedUrls;
                });
            // TODO: auto-save
        },

        generateFeed() {
            this.isLoadingGeneration = true;
            this.WebmpService.generateFeed()
                .then((data) => {
                    if (data.success) {
                        this.createNotificationSuccess({
                            title: this.$tc('webmasterei.notification.success.title'),
                            message: this.$tc('webmasterei.notification.success.feedGenerated')
                        });
                    }
                    this.isLoadingGeneration = false;
                })
                .catch((exception) => {
                    this.createNotificationError({
                        title: this.$tc('webmasterei.notification.error.title'),
                        message: exception
                    });
                });
        }
    }
});
