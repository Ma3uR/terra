import template from './sw-category-detail.html.twig';

const { StateDeprecated } = Shopware;

Shopware.Component.override('sw-category-detail', {
    template,

    created() {
        
        this.syncService = Shopware.Service('syncService');
        this.httpClient = this.syncService.httpClient;
        this.repository = this.repositoryFactory.create('biloba_intl_translation_config');
        this.getConfig();
    },

    data() {
        return {
            isTranslating: false,
            isConfigAvailable: false,
            isTranslationDisabled: true,
            config: null
        };
    },

    computed: {
        languageStore() {
            return StateDeprecated.getStore('language');
        },
    },

    methods: {
        onChangeLanguage(languageId) {
            this.isConfigAvailable = false;
            this.isTranslationDisabled = true;
            
            this.currentLanguageId = languageId;
            this.setCategory();
            this.getConfig();
        },
        getConfig() {
            return this.httpClient.post(
                `/_action/biloba-intl-translation/get-config`,
                { languageId: this.languageStore.getCurrentId() },
                { headers: this.syncService.getBasicHeaders()}
            ).then((response) => {
                if(response.data.id) {
                    this.config = response.data;
                    this.isConfigAvailable = true;
                }
            }).finally(() => {
                this.isTranslationDisabled = ( this.isConfigAvailable == false);
            });
        },
    	onTranslate: function() {
            this.isTranslating = true;

    		this.httpClient.post(
                `/_action/biloba-intl-translation-translation`,
                {entity: 'category', entityId: this.category.id, languageId: this.languageStore.getCurrentId()},
                {headers: this.syncService.getBasicHeaders() }
            ).then((response) => {

                if(response.data.status && response.data.status == 'error') {

                    this.createNotificationError({
                        title: this.$tc('biloba-intl-translation.general.notificationTranslatedError.title'),
                        message: response.data.message
                    });
                } else {

                    for(let key in response.data) {
                        this.category[key] = response.data[key];
                    }

                    this.createNotificationInfo({
                        title: this.$tc('biloba-intl-translation.general.notificationTranslated.title'),
                        message: this.$tc('biloba-intl-translation.general.notificationTranslated.message')
                    });
                }
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('biloba-intl-translation.general.notificationTranslatedError.title'),
                    message: this.$tc('biloba-intl-translation.general.notificationTranslatedError.message')
                });
            }).finally(() => {
                this.isTranslating = false;
            });
    	}
    }
});