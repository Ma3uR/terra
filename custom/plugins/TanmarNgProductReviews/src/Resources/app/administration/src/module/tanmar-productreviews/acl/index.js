Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'tanmar_productreviews',
    key: 'tanmar_productreviews',
    roles: {
        viewer: {
            privileges: [
                'sales_channel:read',
                'sales_channel_payment_method:read',
                'system_config:read'
            ],
            dependencies: []
        },
        editor: {
            privileges: [
                'sales_channel:update',
                'sales_channel_payment_method:create',
                'sales_channel_payment_method:update',
                'system_config:update',
                'system_config:create',
                'system_config:delete'
            ],
            dependencies: [
                'tanmar_productreviews.viewer'
            ]
        }
    }
});

Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: null,
    key: 'sales_channel',
    roles: {
        viewer: {
            privileges: [
                'tanmar_productreviews_pos_sales_channel:read',
                'tanmar_productreviews_pos_sales_channel_run:read',
                'tanmar_productreviews_pos_sales_channel_run:update',
                'tanmar_productreviews_pos_sales_channel_run:create'
            ]
        },
        editor: {
            privileges: [
                'tanmar_productreviews_pos_sales_channel:update',
                'tanmar_productreviews_pos_sales_channel_run:delete'
            ]
        },
        creator: {
            privileges: [
                'tanmar_productreviews_pos_sales_channel:create'
            ]
        },
        deleter: {
            privileges: [
                'tanmar_productreviews_pos_sales_channel:delete'
            ]
        }
    }
});
