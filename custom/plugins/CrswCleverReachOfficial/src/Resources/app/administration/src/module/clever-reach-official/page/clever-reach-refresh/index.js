import template from './clever-reach-refresh.html.twig';
import './clever-reach-refresh.scss';
import '../clever-reach-dashboard/clever-reach-dashboard-error/clever-reach-dashboard-error.scss';

const { Component } = Shopware;

Component.register('clever-reach-refresh', {
    template,

    inject: [
        'pluginService'
    ],

    data() {
        return {
            userId: null,
            showIframe: false,
            type: 'refresh',
            isLoading: true
        };
    },

    created: function () {
        this.fetchRefreshConfiguration();
    },

    mounted: function () {
        this.attachEventListeners();
    },

    methods: {
        attachEventListeners: function() {
            let authenticateNowButton = document.querySelector('#cr-log-account');
            if (authenticateNowButton) {
                authenticateNowButton.addEventListener('click', this.showIframeAndHideTokenExpiredPage);
            }
        },

        showIframeAndHideTokenExpiredPage: function () {
            this.showIframe = true;
        },

        fetchRefreshConfiguration() {
            let headers = this.pluginService.getBasicHeaders();
            return this.pluginService.httpClient
                .get('/cleverreach/refresh', {headers})
                .then((response) => {
                    this.isLoading = false;
                    let apiResponse = Shopware.Classes.ApiService.handleResponse(response);
                    this.userId = apiResponse.id;
                }).catch(error => {

                });
        },
    }
});
