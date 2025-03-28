import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

import Swal from "sweetalert2";
import PrimeVue from "primevue/config";
import Aura from "@primeuix/themes/aura";
import { ToastService } from "primevue";
import "primeicons/primeicons.css";

// importaciones de componentes dee primevu
import { Toast } from "primevue";
import Button from "primevue/button";
import Toolbar from "primevue/toolbar";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import FileUpload from "primevue/fileupload";
import Textarea from "primevue/textarea";
import InputNumber from "primevue/inputnumber";
import Select from "primevue/select";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);
        //configuracion global dee sweetalert2
        app.config.globalProperties.$swal = Swal;
        app.use(PrimeVue, {
            theme: {
                preset: Aura,
            },
        });
        app.use(ToastService);
        //definimos los componentes de primevue de forma global
        app.component('Toast', Toast);
        app.component('Button', Button);
        app.component('Toolbar', Toolbar);
        app.component('DataTable', DataTable);
        app.component('Column', Column);
        app.component('Dialog', Dialog);
        app.component('InputText', InputText);
        app.component('IconField', IconField);
        app.component('InputIcon', InputIcon);
        app.component('FileUpload', FileUpload);
        app.component('Textarea', Textarea);
        app.component('InputNumber', InputNumber);
        app.component('Select', Select);
        app.component('', );
        app.component('', );
        app.component('', );
        app.component('', );
        app.component('', );
        app.component('', );
        app.mount(el);
        return app;
    },
    progress: {
        color: "#4B5563",
    },
});
