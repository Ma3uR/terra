import template from './clever-reach-initialSync-list.html.twig';
import './clever-reach-initialSync-list.scss';

const { Component } = Shopware;

Component.register('clever-reach-initialSync-list', {
    template,

    inject: [
        'pluginService'
    ],

    data() {
        return {
            progress: {
                createSubscriberList: 0,
                addFields: 0,
                importSubscribers: 0
            },
            status: {
                createSubscriberList: 'disabled',
                addFields: 'disabled',
                importSubscribers: 'disabled'
            },
            interval: null,
            isDisabled: {
                createSubscriberList: true,
                addFields: true,
                importSubscribers: true
            },
            classStatus: {
                createSubscriberList: 'cr-icofont-circle',
                addFields: 'cr-icofont-circle',
                importSubscribers: 'cr-icofont-circle'
            },

            integrationList: ''
        };
    },

    mounted: function () {
        this.checkStatus();
    },

    methods: {
        checkStatus: function () {
            const headers = this.pluginService.getBasicHeaders();

            this.pluginService.httpClient
                .get('/cleverreach/initialSync', {headers})
                .then((response) => {
                    let initialSyncConfig = Shopware.Classes.ApiService.handleResponse(response);
                    if (initialSyncConfig.taskStatuses) {
                        this.refreshTaskItems(initialSyncConfig.taskStatuses);
                    }

                    this.integrationList = initialSyncConfig.integrationList;

                    if (initialSyncConfig.status !== 'completed' && initialSyncConfig.status !== 'failed') {
                        if (initialSyncConfig.status === 'in_progress') {
                            this.setInitialSyncStartTimestamp();
                        }

                        var handler = this.checkStatus;
                        setTimeout(handler, 250);
                    } else {
                        let route = {
                            name: 'clever.reach.official.index',
                            params: {
                                page: 'dashboard'
                            },
                        };

                        this.removeStoredCookies();
                        this.$router.replace(route);
                    }
                }).catch(error => {
            });
        },

        removeStoredCookies: function () {
            this.deleteCookie('initialSyncStartTimestamp');
            this.deleteCookie('bannerClosedPressed');
        },

        setInitialSyncStartTimestamp: function() {
            let initialSyncStartTimestamp = this.readCookie('initialSyncStartTimestamp');
            if (!initialSyncStartTimestamp) {
                this.saveCookie('initialSyncStartTimestamp', Date.now());
            }
        },

        refreshTaskItems: function (tasksItemsInfo) {
            this.refreshCreateSubscriberListGroup(tasksItemsInfo.subscriberList);
            this.refreshAddFieldsGroup(tasksItemsInfo.addFields);
            this.refreshImportSubscribersGroup(tasksItemsInfo.recipientSync);

            return true;
        },

        refreshCreateSubscriberListGroup: function (createSubscriberListInfo) {
            this.progress.createSubscriberList = createSubscriberListInfo.progress;
            this.status.createSubscriberList = createSubscriberListInfo.status;
            this.isDisabled.createSubscriberList = this.progress.createSubscriberList === 0;
            this.classStatus.createSubscriberList = this.getClassStatusName(this.progress.createSubscriberList);
        },

        refreshAddFieldsGroup: function (addFieldsInfo) {
            this.progress.addFields = addFieldsInfo.progress;
            this.status.addFields = addFieldsInfo.status;
            this.isDisabled.addFields = this.progress.addFields === 0;
            this.classStatus.addFields = this.getClassStatusName(this.progress.addFields);
        },

        refreshImportSubscribersGroup: function (importSubscribersInfo) {
            this.progress.importSubscribers = importSubscribersInfo.progress;
            this.status.importSubscribers = importSubscribersInfo.status;
            this.isDisabled.importSubscribers = this.progress.importSubscribers === 0;
            this.classStatus.importSubscribers = this.getClassStatusName(this.progress.importSubscribers);
        },

        getClassStatusName: function(progress) {
            switch (progress) {
                case 0:
                    return 'cr-icofont-circle';
                case 100:
                    return 'cr-icofont-check';
                default:
                    return  'cr-icofont-loader'
            }
        },

        saveCookie: function (key, value) {
            document.cookie = [key, '=', JSON.stringify(value), '; domain=.', window.location.host.toString(), '; path=/;'].join('');
        },

        readCookie: function (key) {
            let result = document.cookie.match(new RegExp(key + '=([^;]+)'));
            result && (result = JSON.parse(result[1]));

            return result;
        },

        deleteCookie: function (key) {
            document.cookie = [key, '=; expires=Thu, 01-Jan-1970 00:00:01 GMT; path=/; domain=.', window.location.host.toString()].join('');
        }
    }
});
