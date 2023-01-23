import template from './sw-settings-language-detail.html.twig';

Shopware.Component.override('sw-settings-language-detail', {
    template,

    inject: [
        'repositoryFactory'
    ],
    
    // mixins: [
    //     Mixin.getByName('')
    // ],
    
    data() {
        return {
            isApiMissing: false,
            isConfigEnabled: false,
            isLanguageSupported: false,
            configId: null,
            config: {},
            isLoading: false,
            repository: null,
            processSuccess: false,
            translationApis: []
        };
    },


    created() {
        var self = this;
        this.syncService = Shopware.Service('syncService');
        this.httpClient = this.syncService.httpClient;

        this.repository = this.repositoryFactory.create('biloba_intl_translation_config');
        this.getTranslationApis();

        // get or create config
        this.getConfigId().then(function(){
            if(self.configId) {
                self.getConfig();
            } else {
                self.createConfig();
            }
        });

        this.checkIfLangSupported();
    },

    watch: {
        languageId: function() {
            this.checkIfLangSupported();
        }
    },
    
    methods: {
        getTranslationApis() {
            let self = this;

            // Return all existing variations from the server
            return this.httpClient.post(
                `/_action/biloba-intl-translation-translation/get-translation-apis`,
                { languageId: this.languageId },
                { headers: this.syncService.getBasicHeaders() }
            ).then((response) => {
                this.translationApis = response.data;

                if(this.translationApis.length > 0) {
                    this.isApiMissing = false;
                } else {
                    this.isApiMissing = true;
                }
            }).catch(() => {
                this.isApiMissing = true;
                this.translationApis =  [];
            }).finally(() => {
                this.isConfigEnabled = (this.isLanguageSupported && this.languageId && !this.isApiMissing);
            });
        },
        createConfig() {
            this.config = this.repository.create(Shopware.Context.api);
            this.config.targetLanguageId = this.languageId;
        },
        getConfig() {
            this.repository
                .get(this.configId, Shopware.Context.api)
                .then((entity) => {
                   this.config = entity;
                });
        },
        getConfigId() {
            return this.httpClient.post(
                `/_action/biloba-intl-translation/get-config`,
                { languageId: this.languageId },
                { headers: this.syncService.getBasicHeaders()}
            ).then((response) => {
                // saving config
                this.configId = response.data.id;
            });
        },
        checkIfLangSupported() {
            if(this.languageId) {
                return this.httpClient.post(
                    `/_action/biloba-intl-translation/is-language-supported`,
                    { languageId: this.languageId },
                    { headers: this.syncService.getBasicHeaders()}
                ).then((response) => {
                    this.isLanguageSupported = response.data.supported;
                }).finally(() => {
                    this.isConfigEnabled = (this.isLanguageSupported && this.languageId && !this.isApiMissing);
                });
            }
        },
        onSave() {
            this.isLoading = true;
            this.languageRepository.save(this.language, Shopware.Context.api).then(() => {
                //this.isLoading = false;
                //this.isSaveSuccessful = true;
                if (!this.languageId) {
                    this.$router.push({ name: 'sw.settings.language.detail', params: { id: this.language.id } });
                }
            }).then(() => {
                this.loadEntityData();
            }).then(() => {
                if(this.languageId && this.config.sourceLanguageId) {
                    this.config.targetLanguageId = this.languageId;
                    return this.repository
                        .save(this.config, Shopware.Context.api)
                        .then(() => {
                            if(!this.configId) {
                                this.getConfigId().then(() => {
                                    this.getConfig();
                                });
                            }
                            else {
                                this.getConfig();
                            }
                        });
                }
            }).finally(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;
            });
        },

		saveFinish() {
			this.processSuccess = false;
        },
        
        isInvalidSourceLanguage(item) {
            if(this.languageId == item.id) {
                return true;
            } else {
                return false;
            }
        }
    }
});
