import template from './clever-reach-tab.html.twig';
import './clever-reach-tab.scss';

const { Component } = Shopware;

Component.register('clever-reach-tab', {
    template,

    props: {
        customerId: {
            type: String,
            required: true,
            default: ''
        }
    }
});