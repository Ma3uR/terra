import template from './biloba-intl-translation-log-detail.html.twig';

const { Criteria } = Shopware.Data;

Shopware.Component.register('biloba-intl-translation-log-detail', {
    template,

    inject: [
        'repositoryFactory'
    ],

    data() {
        // log muss mit der Table im Migration übereinstimmen
        return {
            log: null,
            context: null,
            repository: null,
            isLoading: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    created() {
        this.getEntry();
    },
    
    computed: {
        entryRepository() {
            return this.repositoryFactory.create('biloba_intl_translation_log');
        },
        entryCriteria() {
            return new Criteria();
        },
    },

    methods: {
        getEntry() {
            this.isLoading = true;

            return this.entryRepository
                .get(this.$route.params.id, Shopware.Context.api)
                .then((entity) => {
                    this.log = entity;
                    this.context = JSON.stringify(entity.context);
                    this.total = entity.total;
                    this.isLoading = false;
                });
        },
        updateTotal({ total }) {
            this.total = total;
        }
    }
});