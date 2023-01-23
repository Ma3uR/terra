import template from './tr-e-products-component.html.twig';
import './tr-e-products-component.scss';

const { Component, Mixin } = Shopware;

Component.register('tr-e-products-component', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    data() {
        return {
            sliderBoxLimit: 3
        };
    },

    computed: {
        demoProductElement() {
            return {
                config: {
                    boxLayout: {
                        source: 'static',
                        value: 'standard'
                    },
                    displayMode: {
                        source: 'static',
                        value: 'standard'
                    }
                },
                data: {
                    product: {
                        name: 'Lorem ipsum dolor',
                        description: `Lorem ipsum dolor sit amet, consetetur sadipscing elitr,
                    sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat,
                    sed diam voluptua.`.trim(),
                        price: [
                            { gross: 19.90 }
                        ],
                        cover: {
                            media: {
                                url: '/administration/static/img/cms/preview_glasses_large.jpg',
                                alt: 'Lorem Ipsum dolor'
                            }
                        }
                    }
                }
            };
        },

    },

    watch: {
    },

    created() {
        this.createdComponent();
    },

    mounted() {
    },

    methods: {
        createdComponent() {
            this.initElementConfig('tr-e-products');
            this.initElementData('tr-e-products');
        },
        
        getProductEl(product) {
            return {
                config: {
                    boxLayout: {
                        source: 'static',
                        value: 'standard'
                    },
                    displayMode: {
                        source: 'static',
                        value: 'standard'
                    }
                },
                data: {
                    product
                }
            };
        }
    }
});
