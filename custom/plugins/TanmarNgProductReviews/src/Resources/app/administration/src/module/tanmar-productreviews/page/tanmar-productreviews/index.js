import template from './tanmar-productreviews.html.twig';

const {Component, Defaults} = Shopware;
const {Criteria} = Shopware.Data;
const {hasOwnProperty} = Shopware.Utils.object;

Component.register('tanmar-productreviews', {
    template,

    inject: [
        'TanmarNgProductReviewsTestmailApiService',
        'repositoryFactory',
        'acl'
    ],

    mixins: [
        'notification'
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            isTestSuccessful: false,
            clientIdFilled: false,
            salesChannels: [],
            config: null,
            isSetDefaultPaymentSuccessful: true,
            isSettingDefaultPaymentMethods: false,
            savingDisabled: false,
            messageBlankErrorState: null,
            salesChannelId: null
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        hasError() {
            return false;
        },

        domain() {
            return `TanmarNgProductReviews.config`;
        }
    },

    watch: {
        config: {
            handler() {
                const defaultConfig = this.$refs.configComponent.allConfigs.null;
                this.salesChannelId = this.$refs.configComponent.selectedSalesChannelId;
                this.$refs.systemConfig.onSalesChannelChanged(this.salesChannelId);
            },
            deep: true
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            const criteria = new Criteria();
            criteria.addFilter(Criteria.equalsAny('typeId', [
                Defaults.storefrontSalesChannelTypeId,
                Defaults.apiSalesChannelTypeId
            ]));

            this.salesChannelRepository.search(criteria, Shopware.Context.api).then(res => {
                res.add({
                    id: null,
                    translated: {
                        name: this.$tc('sw-sales-channel-switch.labelDefaultOption')
                    }
                });

                this.salesChannels = res;
            }).finally(() => {
                this.isLoading = false;
            });

            this.messageBlankErrorState = {
                code: 1,
                detail: this.$tc('swag-paypal.messageNotBlank')
            };
        },

        onSave() {
            if (this.hasError) {
                return;
            }

            this.save();
        },

        save() {
            this.isLoading = true;

            this.$refs.systemConfig.saveAll().then(() => {
                this.isSaveSuccessful = true;
                this.createNotificationSuccess({
                    message: this.$tc('sw-extension-store.component.sw-extension-config.messageSaveSuccess')
                });
            }).catch((err) => {
                this.createNotificationError({
                    message: err
                });
            }).finally(() => {
                this.isLoading = false;
            });
        },

        testInvitation() {
            this.isLoading = true;

            this.TanmarNgProductReviewsTestmailApiService.testInvitation(
                    this.$refs.configComponent.selectedSalesChannelId
                    ).finally(() => {
                this.isLoading = false;
            });
        },

        testNotification() {
            this.isLoading = true;

            this.TanmarNgProductReviewsTestmailApiService.testNotification(
                    this.$refs.configComponent.selectedSalesChannelId
                    ).finally(() => {
                this.isLoading = false;
            });
        },

        testCoupon() {
            this.isLoading = true;

            this.TanmarNgProductReviewsTestmailApiService.testCoupon(
                    this.$refs.configComponent.selectedSalesChannelId
                    ).finally(() => {
                this.isLoading = false;
            });
        },

        preventSave(mode) {
            if (!mode) {
                this.savingDisabled = false;
                return;
            }

            this.savingDisabled = true;
        }
    }
});
