import Plugin from 'src/plugin-system/plugin.class';

export default class TanmarProductReviewsDesign extends Plugin {
    
    init() {
        var me = this;
        
        me.registerEvents();
        me.subscribeOnAfterAjaxSubmit();

    }
    
    registerEvents(){
        
        var more = document.querySelector('.is-tanmar-product-reviews-design .tanmar-product-reviews-design-read-more-counter');
        if (more) {
            more.addEventListener('click', function () {
                var sel = '.is-tanmar-product-reviews-design .tanmar-product-reviews-design-list-content.is--hidden';
                document.querySelectorAll(sel).forEach(function (e) {
                    e.classList.remove('is--hidden');
                });
                more.classList.add('is--hidden');
            });
        }
        
    }
    
    subscribeOnAfterAjaxSubmit(){
        var me = this;
        
        window.PluginManager.getPlugin('FormAjaxSubmit').get('instances').forEach(that => {
            that.$emitter.subscribe('onAfterAjaxSubmit', function(CustomEvent){
                var me = this;
                if(CustomEvent.detail.response.indexOf('product-detail-review-language-form') > 0){
                    me.onAfterAjaxSubmit(CustomEvent);
                }
            }.bind(me));
        });
    }
    
    onAfterAjaxSubmit(){
        var me = this;
        
        me.registerEvents();
        me.subscribeOnAfterAjaxSubmit();
        
    }
    
}