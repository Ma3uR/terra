import template from './clever-reach-initialSync-item.html.twig';
import './clever-reach-initialSync-item.scss';

const { Component } = Shopware;

Component.register('clever-reach-initialSync-item', {
    template,

    props: {
        displayText: {
            type: String,
            required: true,
            default: ''
        },
        progress: {
            type: Number,
            required: true,
            default: 0
        },
        disabled: {
            type: Boolean,
            required: true,
            default: true
        },

        classStatus: {
            type: String,
            required: true,
            default: 'cr-icofont-circle'
        }
    },
});