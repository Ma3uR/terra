import template from './webmasterei-plugin-config.html.twig';

const { Mixin } = Shopware;

Shopware.Component.extend('webmasterei-plugin-config', 'sw-plugin-config', {
    template,

    inject: ['systemConfigApiService'],

    computed: {
        domain() {
            return `WebmpGoogleReviewFeed.settings`;
        }
    },
});
