import template from './tr-e-manufacturers-component.html.twig';
import './tr-e-manufacturers-component.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-manufacturers-component', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
        };
    },

    computed: {
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-manufacturers');
            this.initElementData('tr-e-manufacturers');
        }
    }
});
