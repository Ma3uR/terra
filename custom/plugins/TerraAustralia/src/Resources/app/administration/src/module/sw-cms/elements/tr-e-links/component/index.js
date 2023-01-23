import template from './tr-e-links-component.html.twig';
import './tr-e-links-component.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-links-component', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-links');
            this.initElementData('tr-e-links');
            
            if (this.element.config.links.value.length > 0) {
                this.$set(this.element.data, 'links', this.element.config.links.value);
            }
        }
    }
});
