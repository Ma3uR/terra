// Import all necessary Storefront plugins and scss files
import TanmarProductReviews from './product-reviews/product-reviews.plugin';

// Register plugin without selector
const PluginManager = window.PluginManager;
PluginManager.register('TanmarProductReviews', TanmarProductReviews); // PluginManager.register('ExamplePlugin', ExamplePlugin, '[data-example-plugin]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
