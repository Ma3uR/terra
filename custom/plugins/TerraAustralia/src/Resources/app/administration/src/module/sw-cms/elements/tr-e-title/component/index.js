import template from './tr-e-title-component.html.twig';
import './tr-e-title-component.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-title-component', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    watch: {
        cmsPageState: {
            deep: true,
            handler() {
                this.$forceUpdate();
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-title');
            this.initElementData('tr-e-title');
        }
    }
});
