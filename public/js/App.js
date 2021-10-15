
import { LinkController } from './LinkController.js';
import { ControllerManager } from './ControllerManager.js';

class App {
    constructor() {
        //TODO P2 Link controller should be managed by ControllerManager
        this.linkController = new LinkController();
        this.ctrlManager = new ControllerManager();
    }

    run() {
        let elem = document.getElementsByTagName('body').item(0);
        this.linkController.observe(elem);
        this.ctrlManager.observe(elem);
        this.linkController.load(location.origin + '/account/user');
    }
}

const app = new App();
app.run();


