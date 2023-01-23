import template from './biloba-intl-translation-plugin-configuration-button-check-api-keys.html.twig';

Shopware.Component.register('check-api-keys', {
    template,

    mixins: [
        Shopware.Mixin.getByName('notification')
    ],

    created() {
        this.syncService = Shopware.Service('syncService');
        this.httpClient = this.syncService.httpClient;
    
    },
    
    methods:{
        checkApi(e) {
            let identifier = e.currentTarget.getAttribute('data-identifier');

            // giving identifier and headers as object parameters
            return this.httpClient.post(
                `/_action/biloba-intl-translation-checkApi`,
                { identifier: identifier },
                { headers: this.syncService.getBasicHeaders() }
            ).then((response) => {
                
                if(response.data.status == 'ok') {
                    this.createNotificationSuccess({
                        title: this.$tc('biloba-intl-translation.plugin_config.notificationSuccess.title'),
                        message: this.$tc('biloba-intl-translation.plugin_config.notificationSuccess.message')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('biloba-intl-translation.plugin_config.notificationError.title'),
                        message: this.$tc('biloba-intl-translation.plugin_config.notificationError.message')
                    });
                }
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('biloba-intl-translation.plugin_config.notificationError.title'),
                    message: this.$tc('biloba-intl-translation.plugin_config.notificationError.message')
                });
            });
        },
    }
});