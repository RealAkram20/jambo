// First import jQuery
import $ from "jquery";

// Set jQuery globally BEFORE importing Select2
window.$ = window.jQuery = $;

// Import Popper and Bootstrap
import * as Popper from "@popperjs/core";
window.Popper = Popper;

import * as bootstrap from "bootstrap";
window.bootstrap = bootstrap;

// Import our Select2 setup
import { setupSelect2 } from './select2-setup';

// Use the Bootstrap 5 integration package
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
import 'datatables.net-bs5/js/dataTables.bootstrap5.min.js';

import "waypoints/lib/noframework.waypoints.min.js";
        
// Initialize Select2 (the setup function handles DOMContentLoaded internally)
setupSelect2();
