import template from './tr-e-contacts-component.html.twig';
import './tr-e-contacts-component.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-contacts-component', {
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
            this.initElementConfig('tr-e-contacts');
            this.initElementData('tr-e-contacts');
        }
    }
});
