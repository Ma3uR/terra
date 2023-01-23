import template from './tr-e-contacts-widget-component.html.twig';
import './tr-e-contacts-widget-component.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-contacts-widget-component', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    computed: {
    },

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
            this.initElementConfig('tr-e-contacts-widget');
            this.initElementData('tr-e-contacts-widget');
        }
    }
});
