import template from './biloba-intl-translation-log-list.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Shopware.Component.register('biloba-intl-translation-log-list', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Mixin.getByName('listing')
    ],


    data() {
        return {
            entries: null,
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
		
    },

    computed: {
        entryRepository() {
            return this.repositoryFactory.create('biloba_intl_translation_log');
        },
        entryCriteria() {
            return new Criteria();
        },
        entryColumns() {
            return [{
                property: 'id',
                dataIndex: 'id',
                routerLink: 'biloba.intl.translation.log.detail',
                label: 'Id',
                allowResize: false,
                primary: true
            },
            {
                property: 'initiator',
                dataIndex: 'initiator',
                label: 'Initiator',
                allowResize: false,
                primary: true
            },
            {
                property: 'entityId',
                dataIndex: 'entityId',
                label: 'EnityId',
                allowResize: false,
                primary: true
            },
            {
                property: 'entityType',
                dataIndex: 'entityType',
                label: 'EntityType',
                allowResize: false,
                primary: true
            },
            {
                property: 'type',
                dataIndex: 'type',
                label: 'Type',
                allowResize: false,
                primary: true
            },
            {
                property: 'status',
                dataIndex: 'status',
                label: 'Status',
                allowResize: false,
                primary: true
            }];
        }
    },

    methods: {
        onChangeLanguage(languageId) {
            this.getList(languageId);
        },

        getList() {
            this.isLoading = true;

            return this.entryRepository
                .search(this.entryCriteria, Shopware.Context.api)
                .then((searchResult) => {
                    this.entries = searchResult;
                    this.total = searchResult.total;
                    this.isLoading = false;
                });
        },
        
        updateTotal({ total }) {
            this.total = total;
        }
    }
});