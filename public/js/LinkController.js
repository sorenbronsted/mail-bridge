
import { RequestController } from './RequestController.js';

export class LinkController extends RequestController {

    constructor() {
        super();
    }

    /**
     * Click on href
     * @param {*} event
     * @returns void
     */
    onClick(event) {
        const href = this._href(event.target);
        if (href == null) {
            return;
        }

        let url = new URL(href);
        if (url.pathname == '/') {
            location.reload();
            return;
        }
        event.preventDefault();
        
        this.load(href).then(() => {
            let dataset = event.target.dataset;
            if (dataset.link == null) {
                return;
            }
            if (dataset.link == 'show') {
                let target = document.getElementById(dataset.linkTarget);
                if (target && target.classList.contains('d-none')) {
                    target.classList.remove('d-none');
                }
            }
        });
    }

    /**
     * Observe for any bubbling anchor events
     * @param {HtmlElement} element
     */
    observe(element) {
        element.onclick = this.onClick.bind(this);
    }

    _href(element) {
        if (element == null) {
            return null;
        }

        if (element.href == null) {
            return this._href(element.parentElement);
        }
        return element.href;
    }
}