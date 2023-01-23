import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import LoadingIndicator from 'src/utility/loading-indicator/loading-indicator.util';
import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';

export default class TanmarProductReviews extends Plugin {
    init() {
        'use strict';

        window.tanmarDebug = this;

        this.starLegend = {
            1: this.getStarLabel(1),
            2: this.getStarLabel(2),
            3: this.getStarLabel(3),
            4: this.getStarLabel(4),
            5: this.getStarLabel(5)
        };

        this._client = new HttpClient(window.accessKey);

        this.registerEvents();
    }

    getStarLabel(id){
        var obj = document.getElementById('tanmar-product-reviews-stars-legend'+id);
        if(obj !== null){
            return obj.innerHTML;
        }
        return '';
    }

    registerEvents() {
        var me = this;

        var stars = document.querySelectorAll('.stars .star');
        stars.forEach(star => {
            star.addEventListener('mouseenter', me.starEnter.bind(me));
            star.addEventListener('touchstart', me.starEnter.bind(me));
            star.addEventListener('mouseleave', me.starLeave.bind(me));
            star.addEventListener('touchend', me.starLeave.bind(me));
            star.addEventListener('click', me.starClick.bind(me));
        });

        document.querySelectorAll('form.tanmarreviews').forEach(form => {
            form.addEventListener('submit', me.formOnSubmit.bind(me));
        });

        document.querySelectorAll('.topname input[type="checkbox"]').forEach(input => {
            input.addEventListener('change', me.checkboxOnChange.bind(me));
        });

    }

    formOnSubmit(e){
        var me = this;

        // dont submit
        e.preventDefault();

        var form = e.target;

        var root = form.closest('.product');

        var loadingDiv = document.createElement('div');
        loadingDiv.classList.add('loading');
        loadingDiv.innerHTML = LoadingIndicator.getTemplate();
        root.appendChild(loadingDiv);

        var data = {};

        for (var i = 0; i < form.elements.length; i++) {
            var field = form.elements[i];
            if (!field.name || field.disabled || field.type === 'file' || field.type === 'reset' || field.type === 'submit' || field.type === 'button'){
                continue;
            }
            if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
                data[field.name] = field.value;
            }
        }


        data['_csrf_token'] = document.getElementById('csrf').value;

        me._client.post(form.getAttribute('action'), JSON.stringify(data), me.formOnSubmitSuccess.bind([me,loadingDiv]));

        return false;
    }

    formOnSubmitSuccess(e){
        var loadingDiv = this[1];
        var response = JSON.parse(e);

        if (typeof response != 'undefined' && typeof response.type != 'undefined') {

            if (response.type == 'success') {

                loadingDiv.classList.remove('loading');
                loadingDiv.classList.add('done');

                var alertTemplate = '<div role="alert" class="alert alert-success alert-has-icon">';
                alertTemplate += '<span class="icon icon-info"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">';
                alertTemplate += '<path fill="#758CA3" fill-rule="evenodd" d="M12,7 C12.5522847,7 13,7.44771525 13,8 C13,8.55228475 12.5522847,9 12,9 C11.4477153,9 11,8.55228475 11,8 C11,7.44771525 11.4477153,7 12,7 Z M13,16 C13,16.5522847 12.5522847,17 12,17 C11.4477153,17 11,16.5522847 11,16 L11,11 C11,10.4477153 11.4477153,10 12,10 C12.5522847,10 13,10.4477153 13,11 L13,16 Z M24,12 C24,18.627417 18.627417,24 12,24 C5.372583,24 6.14069502e-15,18.627417 5.32907052e-15,12 C-8.11624501e-16,5.372583 5.372583,4.77015075e-15 12,3.55271368e-15 C18.627417,5.58919772e-16 24,5.372583 24,12 Z M12,2 C6.4771525,2 2,6.4771525 2,12 C2,17.5228475 6.4771525,22 12,22 C17.5228475,22 22,17.5228475 22,12 C22,6.4771525 17.5228475,2 12,2 Z"></path>';
                alertTemplate += '</svg></span><div class="alert-content-container"><div class="alert-content">';

                alertTemplate += response.msg;
                alertTemplate += '</div></div></div>';

                loadingDiv.innerHTML = alertTemplate; //'<div class="box">' + response.msg + '</div>';

                var product = loadingDiv.closest('.product');
                product.style.height = product.getBoundingClientRect().height + 'px';
                setTimeout(function(){
                    this.classList.add('ready');
                }.bind(product),10);

                if (typeof response.voucher != 'undefined' && response.voucher) {
                    location.reload(true);
                }
            } else {
                loadingDiv.parentNode.removeChild(loadingDiv);
                const pseudoModal = new PseudoModalUtil(response.msg);
                pseudoModal.open();
            }
        }

    }

    starEnter(e){
        var me = this;

        var star = e.target;
        var starParent = star.parentElement;
        if (!starParent.classList.contains('selected')) {
            var v = star.getAttribute('data-v');

            starParent.querySelectorAll('span').forEach(span => {
                span.classList.remove('hover')
            });

            for (var i = 1; i <= parseInt(v, 10); i++) {
                starParent.querySelectorAll('.star[data-v="' + i + '"]').forEach(span => {
                    span.classList.add('hover');
                });
            }

            starParent.querySelectorAll('.star-legend').forEach(legend => {
                legend.innerHTML = me.starLegend[v];
            });

        }
    }

    starLeave(e){
        var star = e.target;
        var starParent = star.parentElement;

        if (!starParent.classList.contains('selected')) {

            starParent.querySelectorAll('span').forEach(span => {
                span.classList.remove('hover')
            });
            starParent.querySelectorAll('.star-legend').forEach(legend => {
                legend.innerHTML = '';
            });
        }

    }

    starClick(e){
        var me = this;

        var star = e.target.closest('.star');
        var starParent = star.parentElement;
        var v = star.getAttribute('data-v');

        starParent.querySelectorAll('span').forEach(span => {
            span.classList.remove('selected','hover');
        });

        for (var i = 1; i <= parseInt(v, 10); i++) {
            starParent.querySelectorAll('.star[data-v="' + i + '"]').forEach(span => {
                span.classList.add('selected');
            });
        }

        starParent.classList.add('selected');

        starParent.querySelectorAll('.radiorating input[value="' + v + '"]').forEach(input => {
            input.checked = true;
        });

        starParent.querySelectorAll('.star-legend').forEach(legend => {
            legend.innerHTML = me.starLegend[v];
        });

    }

    checkboxOnChange(e){

        var obj = e.target;
        var meChecked = obj.checked;

        document.querySelectorAll('.topname input[type="checkbox"]').forEach(input => {
            input.checked = meChecked;
        });

    }
}