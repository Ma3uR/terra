import template from './tr-e-title-config.html.twig';

const { Component, Mixin } = Shopware;

Component.register('tr-e-title-config', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-title');
        },
    }
});
