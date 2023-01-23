import template from './clever-reach-dashboard-error.html.twig';
import './clever-reach-dashboard-error.scss';

const { Component } = Shopware;

Component.register('clever-reach-dashboard-error', {
    template,
    inject:[
        'pluginService'
    ],

    props: {
        errorDescription: {
            type: String,
            required: true,
            default: ''
        },
    },

    mounted: function () {
        this.attachEventListeners();
    },

    methods: {
        attachEventListeners: function() {
            let createNewsletterButton = document.querySelector('#cr-retrySync');
            if (createNewsletterButton) {
                createNewsletterButton.addEventListener('click', this.retrySync);
            }
        },

        retrySync: function () {
            let headers = this.pluginService.getBasicHeaders();

            return this.pluginService.httpClient
                .get('/cleverreach/dashboard/retry', {headers})
                .then((response) => {
                    let apiResponse = Shopware.Classes.ApiService.handleResponse(response);
                    if (apiResponse.success) {
                        window.location.reload();
                    }
                }).catch(error => {

                });
        }
    }
});