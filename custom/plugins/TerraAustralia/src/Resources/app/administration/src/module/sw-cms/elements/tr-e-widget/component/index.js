import template from './tr-e-widget-component.html.twig';
import './tr-e-widget-component.scss';

const { Component, Mixin } = Shopware;

Shopware.Component.register('tr-e-widget-component', {
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
    
    watch: {
        'element.config.target.value'() {
            this.reloadSrc();
        }
    },
    
    methods: {
        repository(repo) {
            return this.repositoryFactory.create(repo);
        },
        
        reloadSrc() {
            this.src = null;
            
            if( this.element.config.target.value && this.element.config.targetType.value ){
                this.repository(this.element.config.targetType.value)
                    .get(this.element.config.target.value, Shopware.Context.api)
                    .then(one => {
                        this.src = one;
                    });
            }
        },
        
        createdComponent() {
            this.initElementConfig('tr-e-widget');
            this.initElementData('tr-e-widget');
            
            this.reloadSrc();
        },
    }
});
