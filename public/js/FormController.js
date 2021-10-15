
import { RequestController } from './RequestController.js';

export class FormController extends RequestController {

    constructor(form) {
        super();
        this.form = form;
        if (!this.form.hasAttribute('name')) {
            const name = 'F' + Math.floor((Math.random() * 10000) + 1);
            this.form.setAttribute('name', name);
        }

        this.form.onsubmit = this.submit.bind(this);
    }

    get name() {
        return this.form.getAttribute('name');
    }

    submit(event) {
        event.preventDefault();
        this.data = new FormData(event.target);
        // Ensure that all elements with a name and value is present regardless of input type
        for (const elem of event.target) {
            if (elem.name.length > 0 && elem.value.length > 0 && !this.data.has(elem.name)) {
                this.data.append(elem.name, elem.value);
            }
        }
        this.method = event.target.method;
        this.load(this.form.action);
    }
}