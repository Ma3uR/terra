import template from './tr-e-slider-component.html.twig';
import './tr-e-slider-component.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-slider-component', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    props: {
        activeMedia: {
            type: [Object, null],
            required: false,
            default: null
        }
    },

    data() {
        return {
            columnCount: 7,
            columnWidth: 90,
            sliderPos: 0,
            imgPath: '/administration/static/img/cms/preview_mountain_large.jpg',
            imgSrc: ''
        };
    },

    computed: {
        gridAutoRows() {
            return `grid-auto-rows: ${this.columnWidth}`;
        },

        uploadTag() {
            return `cms-element-media-config-${this.element.id}`;
        },

        sliderItems() {
            if (this.element.data && this.element.data.sliderItems && this.element.data.sliderItems.length > 0) {
                return this.element.data.sliderItems;
            }

            return [];
        },

        contextAssetPath() {
            return Shopware.Context.api.assetsPath;
        }
    },

    watch: {
        'element.data.sliderItems': {
            handler() {
                if (this.sliderItems.length > 0) {
                    this.imgSrc = this.sliderItems[0].media.url;
                    this.$emit('active-image-change', this.sliderItems[0].media);
                } else {
                    this.imgSrc = `${this.contextAssetPath}${this.imgPath}`;
                }
            },
            deep: true
        },

        activeMedia() {
            this.sliderPos = this.activeMedia.sliderIndex;
            this.imgSrc = this.activeMedia.url;
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-slider');
            this.initElementData('tr-e-slider');
            
            if (this.element.data && this.element.data.sliderItems && this.element.data.sliderItems.length > 0) {
                this.imgSrc = this.sliderItems[0].media.url;
                this.$emit('active-image-change', this.sliderItems[this.sliderPos].media);
            } else {
                this.imgSrc = `${this.contextAssetPath}${this.imgPath}`;
            }
        }
    }
});
