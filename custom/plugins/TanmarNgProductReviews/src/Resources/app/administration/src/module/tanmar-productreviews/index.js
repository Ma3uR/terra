import './acl';

import './page/tanmar-productreviews';

import './components/tanmar-productreviews-settings-icon';


const {Module} = Shopware;

Module.register('tanmar-productreviews', {
    type: 'plugin',
    name: 'TanmarNgProductReviews',
    title: 'tanmar-productreviews.general.mainMenuItemGeneral',
    description: 'tanmar-productreviews.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'default-action-settings',

    routes: {
        index: {
            component: 'tanmar-productreviews',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index',
                privilege: 'tanmar_productreviews.viewer'
            }
        }
    },

    settingsItem: {
        group: 'plugins',
        to: 'tanmar.productreviews.index',
        iconComponent: 'tanmar-productreviews-settings-icon',
        backgroundEnabled: true,
        privilege: 'tanmar_productreviews.viewer'
    },

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.extension.config') {
            currentRoute.beforeEnter = (to, from, next) => {
                if (to.params.namespace == 'TanmarNgProductReviews') {
                    next({name: 'tanmar.productreviews.index'});
                } else {
                    next();
                }
            };
        }
        next(currentRoute);
    }
});
