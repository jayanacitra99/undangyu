/*
| Dashboard bundle — admin panel + client dashboard (docs/05 § 2).
|
| AdminLTE owns the page chrome: it reads the DOM at load and wires the sidebar,
| treeview and card widgets. Vue never mounts over that markup — islands mount
| into their own container divs only, from separate entries under
| resources/js/islands/.
*/
import * as bootstrap from 'bootstrap';
import 'admin-lte';

window.bootstrap = bootstrap;
