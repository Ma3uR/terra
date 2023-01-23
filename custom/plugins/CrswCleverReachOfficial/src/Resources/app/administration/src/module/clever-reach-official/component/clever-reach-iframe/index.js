import template from './clever-reach-iframe.html.twig';
import './clever-reach-iframe.scss';

const { Component } = Shopware;

Component.register('clever-reach-iframe', {
    template,

    inject: [
        'pluginService'
    ],

    props: {
        type: {
            type: String,
            required: true,
            default: 'auth'
        }
    },

    data() {
        return {
            authUrl: '',
            isLoading: true
        };
    },

    created: function () {
        this.fetchAuthUrl();
    },

    methods: {

        fetchAuthUrl: function() {
            const headers = this.pluginService.getBasicHeaders();

            return this.pluginService.httpClient
                .get('/cleverreach/iframe/url/' + this.type, {headers})
                .then((response) => {
                    this.isLoading = false;
                    this.authUrl = Shopware.Classes.ApiService.handleResponse(response).authUrl;
                });
        }
    }
});