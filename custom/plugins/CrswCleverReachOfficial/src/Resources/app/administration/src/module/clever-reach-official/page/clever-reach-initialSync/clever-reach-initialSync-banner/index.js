import template from './clever-reach-initialSync-banner.html.twig';
import './clever-reach-initialSync-banner.scss';

const { Component } = Shopware;

Component.register('clever-reach-initialSync-banner', {
    template,

    inject: [
        'pluginService'
    ],

    data() {
        return {
            isLoading: true,
            ssoLink: '',
            isClosed: true,
        };
    },

    created: function () {
        this.fetchSSOLink();
        this.checkIfBannerShouldBeDisplayed();
    },

    mounted: function () {
        this.attachEventListeners();
    },

    methods: {
        attachEventListeners: function() {
            let closeLink = document.querySelector('#cr-initial-sync-banner-close');
            if (closeLink) {
                closeLink.addEventListener('click', this.hideInitialSyncBanner);
            }
        },

        hideInitialSyncBanner: function () {
            this.isClosed = true;
            this.saveCookie('bannerClosedPressed', 'yes');
        },

        fetchSSOLink: function () {
            const headers = this.pluginService.getBasicHeaders();

            this.pluginService.httpClient
                .get('/cleverreach/initialSync/config', {headers})
                .then((response) => {
                    this.ssoLink = Shopware.Classes.ApiService.handleResponse(response).emailUrl;
                    this.isLoading = false;

                }).catch(error => {
            });
        },

        buildEmail: function () {
            let win = window.open(this.ssoLink, '_blank');
            win.focus();
        },

        checkIfBannerShouldBeDisplayed: function () {
            let initialSyncStartTimestamp = this.readCookie('initialSyncStartTimestamp');
            if (initialSyncStartTimestamp && (((Date.now() - initialSyncStartTimestamp) / 1000) > 30)) {
                let bannerClosedPressedValue = this.readCookie('bannerClosedPressed');
                let isBannerClosedPressed = false;
                if (bannerClosedPressedValue) {
                    isBannerClosedPressed = (bannerClosedPressedValue === 'yes');
                }

                this.isClosed = isBannerClosedPressed;
                if (!this.isClosed) {
                    var attachListeners = this.attachEventListeners;
                    setTimeout(attachListeners, 1000);
                }

            } else {
                var handler = this.checkIfBannerShouldBeDisplayed;
                setTimeout(handler, 1000);
            }
        },

        saveCookie: function (key, value) {
            document.cookie = [key, '=', JSON.stringify(value), '; domain=.', window.location.host.toString(), '; path=/;'].join('');
        },

        readCookie: function (key) {
            let result = document.cookie.match(new RegExp(key + '=([^;]+)'));
            result && (result = JSON.parse(result[1]));

            return result;
        }
    }
});
