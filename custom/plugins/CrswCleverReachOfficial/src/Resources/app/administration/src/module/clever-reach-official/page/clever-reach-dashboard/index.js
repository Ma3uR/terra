import template from './clever-reach-dashboard.html.twig';
import '../../component/clever-reach-tab'
import './clever-reach-dashboard-card'
import './clever-reach-dashboard-statistics'
import './clever-reach-dashboard-error'

const { Component } = Shopware;

Component.register('clever-reach-dashboard', {
    template,

    inject: [
        'pluginService'
    ],

    data() {
        return {
            customerId: '',
            isFirstEmailBuilt: false,
            isImportStatisticDisplayed: false,
            isInitialSyncFailed: false,
            errorDescription: false,
            buildEmailUrl: '',
            isLoading: true,
            numberOfRecipients: 0,
            recipientListName: 'Shopware 6',
            segments: []
        };
    },

    created: function () {
        this.fetchDashboardConfiguration();
    },

    methods: {
        fetchDashboardConfiguration() {
            let headers = this.pluginService.getBasicHeaders();

            return this.pluginService.httpClient
                .get('/cleverreach/dashboard', {headers})
                .then((response) => {
                    let apiResponse = Shopware.Classes.ApiService.handleResponse(response);
                    this.isLoading = false;
                    this.isFirstEmailBuilt = apiResponse.isFirstEmailBuilt;
                    this.isImportStatisticDisplayed = apiResponse.isImportStatisticDisplayed;
                    this.isInitialSyncFailed = apiResponse.isInitialSyncFailed;
                    this.errorDescription = apiResponse.errorDescription;
                    this.numberOfRecipients = apiResponse.numberOfSyncedRecipients;
                    this.customerId = apiResponse.customerId;
                    this.buildEmailUrl = apiResponse.emailUrl;
                    this.recipientListName = apiResponse.integrationListName;
                    this.segments = apiResponse.segments;
                }).catch(error => {

                });
        },
    }
});
