import template from './clever-reach-welcome.html.twig';
import './clever-reach-welcome.scss';

const { Component } = Shopware;

Component.register('clever-reach-welcome', {
    template,

    data() {
        return {
            customerId: 123456,
            showIframe: false,
            type: 'auth'
        };
    },
});
