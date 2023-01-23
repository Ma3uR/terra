const { Module } = Shopware;

Module.register('webmasterei-settings', {
    type: "plugin",
    name: "webmastereiSettings.general.name",
    title: "webmastereiSettings.general.title",
    description: "webmastereiSettings.general.description",
    color: '#23ac70',

    routes: {
        config: {
            component: 'webmasterei-plugin-config',
            path: 'config/:namespace',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    }
});
