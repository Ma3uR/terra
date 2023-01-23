import template from './tr-e-widget-data-component.html.twig';
import './tr-e-widget-data-component.scss';

const { Component, Mixin } = Shopware;

Shopware.Component.register('tr-e-widget-data-component', {
    template,
    
    inject: ['repositoryFactory'],
    
    mixins: [
        Mixin.getByName('cms-element')
    ],
    
    data() {
        return {
            src: null
        };
    },
    
    computed: {
    },
    
    created() {
        this.createdComponent();
    },
    
    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-widget-data');
            this.initElementData('tr-e-widget-data');
        },
    }
});
