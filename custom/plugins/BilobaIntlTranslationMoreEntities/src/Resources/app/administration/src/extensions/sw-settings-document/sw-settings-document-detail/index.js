import template from './sw-settings-document-detail.html.twig';
import LocalStore from 'src/core/data/LocalStore';

const { StateDeprecated } = Shopware;

Shopware.Component.override('sw-settings-document-detail', {
    template,

    created() {
        
        this.syncService = Shopware.Service('syncService');
        this.httpClient = this.syncService.httpClient;
        this.repository = this.repositoryFactory.create('biloba_intl_translation_config');
        this.createdComponent();
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

    //ToDo
    methods: {
        // onChangeLanguage(languageId) {
        //     this.isConfigAvailable = false;
        //     this.isTranslationDisabled = true;
            
        //     this.currentLanguageId = languageId;
        //     this.loadEntityData();
        //     this.getConfig();
        // },
        createdComponent() {
            this.isLoading = true;
            this.isConfigAvailable = false;
            this.isTranslationDisabled = true;

            if (this.$route.params.id && this.documentConfig.isLoading !== true) {
                this.documentConfigId = this.$route.params.id;
                this.loadEntityData();
                // this.getConfig();
            }
            this.documentConfigSalesChannelsStore = new LocalStore();
            this.isLoading = false;
        },
        getConfig() {
            return this.httpClient.post(
                `/_action/biloba-intl-translation/get-config`,
                { languageId: Shopware.Context.api.languageId },
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
        // ToDo fix Call to a member function getTranslations() on null refers to GenericEntityHandler.php line 88
        onTranslate: function() {
            this.isTranslating = true;

    		this.httpClient.post(
                `/_action/biloba-intl-translation-translation`,
                {entity: 'document', entityId: this.documentConfig.id, languageId: this.languageStore.getCurrentId()},
                {headers: this.syncService.getBasicHeaders() }
            ).then((response) => {

                if(response.data.status && response.data.status == 'error') {

                    this.createNotificationError({
                        title: this.$tc('biloba-intl-translation.general.notificationTranslatedError.title'),
                        message: response.data.message
                    });
                } else {

                    for(let key in response.data) {
                        this.documentConfig[key] = response.data[key];
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