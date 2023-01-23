import template from './tr-e-links-config.html.twig';
import './tr-e-links-config.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-links-config', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],
    
    inject: ['repositoryFactory'],

    data() {
        return {
            mediaModalIsOpen: false
        };
    },
    
    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        uploadTag() {
            return `tr-e-links-cover-config-${this.element.id}`;
        },

        previewSource() {
            
            if (this.element.data && this.element.data.cover && this.element.data.cover.id) {
                return this.element.data.cover;
            }

            return this.element.config.cover.value;
        },
        
        links() {
            if (this.element.config && this.element.config.links && this.element.config.links.value) {
                return this.element.config.links.value;
            }

            return [];
        }
    },
    
    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-links');
            this.initElementData('tr-e-links');
        },
        
        addLink() {
            this.element.config.links.value.push({
                label: this.$tc('global.cms.elements.tr-links.labelLink'),
                link: 'javascript:void(0)',
                deleted: false
            });
            
            this.$set(this.element.data, 'links', this.links);
        },
        
        onChange() {
            this.$set(this.element.data, 'links', this.links);
        },
        
        onDelete(el) {
            el.deleted = true;
            
            this.reloadLinks(el);
        },
        
        reloadLinks(el) {
            let nlinks = new Array();
            
            this.element.config.links.value.forEach((e) => {
                if( !e.deleted || false === e.deleted) {
                    nlinks.push(e);
                }
            });
            
            this.element.config.links.value = nlinks;
            this.$set(this.element.data, 'links', nlinks);
        },
        
        /* Cover handling */
        async onImageUpload({ targetId }) {
            const mediaEntity = await this.mediaRepository.get(targetId, Shopware.Context.api);

            this.element.config.cover.value = mediaEntity.id;

            this.updateElementData(mediaEntity);
        },

        onImageRemove() {
            this.element.config.cover.value = null;

            this.updateElementData();
        },

        onCloseModal() {
            this.mediaModalIsOpen = false;
        },

        onSelectionChanges(mediaEntity) {
            const media = mediaEntity[0];
            this.element.config.cover.value = media.id;

            this.updateElementData(media);
        },

        updateElementData(media = null) {
            this.$set(this.element.data, 'coverId', media === null ? null : media.id);
            this.$set(this.element.data, 'cover', media);
        },

        onOpenMediaModal() {
            this.mediaModalIsOpen = true;
        }
    }
});
