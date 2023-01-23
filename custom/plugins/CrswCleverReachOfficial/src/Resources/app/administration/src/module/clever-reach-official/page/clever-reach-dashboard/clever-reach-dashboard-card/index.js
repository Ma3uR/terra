import template from './clever-reach-dashboard-card.html.twig';
import './clever-reach-dashboard-card.scss';

const { Component } = Shopware;

Component.register('clever-reach-dashboard-card', {
    template,

    inject: [
        'pluginService'
    ],

    props: {
        isFirstEmailBuilt: {
            type: Boolean,
            required: true,
            default: false
        },
        buildEmailUrl: {
            type: String,
            required: true,
            default: ''
        }
    },

    mounted: function () {
        this.attachEventListeners();
    },

    methods: {
        attachEventListeners: function() {
            let createNewsletterButton = document.querySelector('#cr-buildEmail');
            if (createNewsletterButton) {
                createNewsletterButton.addEventListener('click', this.createNewsletterButtonHandler);
            }
        },

        createNewsletterButtonHandler: function () {
            this.setEmailBuilt();
            let win = window.open(this.buildEmailUrl, '_blank');
            win.focus();
        },

        setEmailBuilt: function () {
            let headers = this.pluginService.getBasicHeaders();

            return this.pluginService.httpClient
                .get('/cleverreach/dashboard/setEmailBuilt', {headers})
                .then((response) => {
                    let apiResponse = Shopware.Classes.ApiService.handleResponse(response);
                    if (apiResponse.success) {
                        this.isFirstEmailBuilt = true;
                        window.location.reload();
                    }
                }).catch(error => {

                });
        },
    }
});
