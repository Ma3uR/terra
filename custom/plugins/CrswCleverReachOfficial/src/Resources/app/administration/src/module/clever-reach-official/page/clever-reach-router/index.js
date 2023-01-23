import template from './clever-reach-router.html.twig';

const { Component } = Shopware;

Component.register('clever-reach-router', {
    template,

    inject: [
        'context',
        'pluginService'
    ],

    data() {
        return {
            isLoading: true
        };
    },

    mounted: function () {
        this.getCurrentRoute({});
        this.loadExternalLink();
    },

    watch: {
        $route(to, from) {
            if (to.fullPath.includes('clever/reach/official')) {
                let query = {};

                if (to.hasOwnProperty('query') && Object.keys(to.query).length > 0) {
                    query = to.query;
                } else if (from.hasOwnProperty('query') && Object.keys(from.query).length > 0) {
                    query = from.query;
                }
                this.getCurrentRoute(query);
            }
        }
    },

    methods: {
        getCurrentRoute: function (query) {
            const headers = this.pluginService.getBasicHeaders();

            return this.pluginService.httpClient
                .get('/cleverreach/router', {headers})
                .then((response) => {
                    this.isLoading = false;
                    let routeName = Shopware.Classes.ApiService.handleResponse(response).routeName;
                    let route = {
                        name: 'clever.reach.official.index',
                        params: {
                            page: routeName
                        },
                        query: query
                    };

                    this.$router.replace(route);
                }).catch(error => {

                });
        },
        loadExternalLink: function() {
            let link = document.createElement('link');
            link.href = 'https://use.fontawesome.com/releases/v5.5.0/css/all.css';
            link.rel = 'stylesheet';
            link.type = 'text/css';

            document.head.appendChild(link);
        }
    }
});
