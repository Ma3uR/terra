import template from './clever-reach-dashboard-statistics.html.twig'
import './clever-reach-dashboard-statistics.scss';

const { Component } = Shopware;

Component.register('clever-reach-dashboard-statistics', {
    template,

    props: {
        recipientListName: {
            type: String,
            required: true,
            default: ''
        },
        numberOfRecipients: {
            type: Number,
            required: true,
            default: 0
        },
        segments: {
            type: Array,
            required: true,
            default: []
        },
    },

    mounted: function () {
        this.renderSegments();
    },

    methods: {
        renderSegments: function () {
            let segmentsContainer = document.querySelector('#cr-statistics-segments');
            if (segmentsContainer) {
                for (let i = 0; i < this.segments.length; i++) {
                    if (i === 3) {
                        segmentsContainer.appendChild(this.createSegmentElement('...', i));
                        break;
                    }

                    segmentsContainer.appendChild(this.createSegmentElement(this.segments[i], i));
                }
            }
        },

        createSegmentElement: function (segment, i) {
            let segmentElement = document.createElement('div');
            segmentElement.title = segment;
            segmentElement.classList.add('value');
            let index = i + 1;
            let textValue = (segment === '...') ? segment : index + ') ' + segment;

            segmentElement.appendChild(document.createTextNode(textValue));

            return segmentElement;
        }
    }
});