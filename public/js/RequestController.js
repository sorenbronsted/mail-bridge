
import morphdom from './lib/morphdom.js';

export class RequestController {

    constructor() {
        this.method = 'GET';
        this.data = null;
    }

    async send(url) {
        let params = {
            redirect: 'follow',
            method: this.method
        };

        if (this.data && this.data instanceof FormData) {
            if (params.method.toLowerCase() == 'post' || params.method.toLowerCase() == 'put') {
                params.body = this.data;
            }
            else {
                let args = new URLSearchParams(this.data);
                if (args.toString().length > 0) {
                    url = url + '?' + args;
                }
            }
        }

        let response = await fetch(url, params);
        if (!response.ok) {
            throw Error('fetch failed with: ' + response.status);
        }
        const contentType = response.headers.get('content-type').toLowerCase();
        if (contentType == 'application/json') {
            return response.json();
        }
        if (contentType == 'application/octet-stream') {
            return response.blob();
        }
    }

    async load(url) {
        return this.send(new URL(url)).then(data => {
            const dataType = data.constructor.name.toLowerCase();
            if (dataType == 'object') {
                let element = document.getElementById(data.mount);
                if (element == null) {
                    throw Error('Element not found: ' + data.mount);
                }
                morphdom(element, data.html);
            }
            else if (dataType == 'blob') {
                // TODO P2 get the file name in download
                const file = window.URL.createObjectURL(data);
                window.location.assign(file);
            }
        }).catch(error => {
            // TODO P2 ValidationException and general error
            window.alert(error);
        });
    }

}