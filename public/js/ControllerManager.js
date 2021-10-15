
import { FormController } from './FormController.js';

export class ControllerManager {

    constructor() {
        this.observer = new MutationObserver(this._mutation.bind(this));
        this.controllers = new Map();
    }

    observe(element) {
        const config = { attributes: true, childList: true, subtree: true };
        this.observer.observe(element, config);
    }

    _mutation(mutations) {
        mutations.forEach(element => {
            element.addedNodes.forEach(element => {
                this._addController(element);
            });
            element.removedNodes.forEach(element => {
                this._removeController(element);
            });
        });
    }

    _addController(node) {
        if (node.nodeName == 'FORM' && node.dataset && node.dataset.controller == FormController.name) {
            let ctrl = new FormController(node);
            this.controllers.set(ctrl.name, ctrl);
            //console.log('Added: ' + ctrl.name);
        }
        if (node.children) {
            [...node.children].forEach(child => {
                this._addController(child);
            });
        }
    }

    _removeController(node) {
        if (node.nodeName == 'FORM' && node.dataset && node.dataset.controller == FormController.name) {
            this.controllers.delete(node.getAttribute('name'));
            //console.log('Removed: ' + node.getAttribute('name'));
        }
        if (node.children) {
            [...node.children].forEach(child => {
                this._removeController(child);
            });
        }
    }
}