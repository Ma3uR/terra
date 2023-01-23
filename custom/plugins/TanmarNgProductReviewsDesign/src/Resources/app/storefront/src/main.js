// Import all necessary Storefront plugins and scss files
import TanmarProductReviewsDesign from './product-reviews-design/product-reviews-design.plugin';


// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('TanmarProductReviewsDesign', TanmarProductReviewsDesign);


//
if (module.hot) {
    module.hot.accept();
}

// .