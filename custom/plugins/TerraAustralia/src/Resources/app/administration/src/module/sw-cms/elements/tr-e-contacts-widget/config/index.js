import template from './tr-e-contacts-widget-config.html.twig';
import './tr-e-contacts-widget-config.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-contacts-widget-config', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    inject: ['repositoryFactory'],

    data() {
        return {
            mediaModalIsOpen: false,
            initialFolderId: null
        };
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        uploadTag() {
            return `tr-e-contacts-widget-background-config-${this.element.id}`;
        },

        previewSource() {
            
            if (this.element.data && this.element.data.background && this.element.data.background.id) {
                return this.element.data.background;
            }

            return this.element.config.background.value;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-contacts-widget');
            this.initElementData('tr-e-contacts-widget');
        },

        async onImageUpload({ targetId }) {
            const mediaEntity = await this.mediaRepository.get(targetId, Shopware.Context.api);

            this.element.config.background.value = mediaEntity.id;

            this.updateElementData(mediaEntity);
        },

        onImageRemove() {
            this.element.config.background.value = null;

            this.updateElementData();
        },

        onCloseModal() {
            this.mediaModalIsOpen = false;
        },

        onSelectionChanges(mediaEntity) {
            const media = mediaEntity[0];
            this.element.config.background.value = media.id;

            this.updateElementData(media);
        },

        updateElementData(media = null) {
            this.$set(this.element.data, 'backgroundId', media === null ? null : media.id);
            this.$set(this.element.data, 'background', media);
        },

        onOpenMediaModal() {
            this.mediaModalIsOpen = true;
        }
    }
});
