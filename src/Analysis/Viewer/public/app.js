/*
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */

import * as Vue from 'vue';
import VueSfc from 'vue3-sfc-loader';
import 'bootstrap';
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-bs5';
import * as ChartJS from 'chart.js';
import * as VueChart from "vue-chartjs";

console.log(VueSfc);

DataTable.use(DataTablesCore);
ChartJS.Chart.register(...ChartJS.registerables);
window.ChartJS = VueChart;

window.analysis = {
    href: window.location.href,
    geturl(action, params = {}) {
        const url = new URL(this.href);
        url.searchParams.set('d5whub-extend-analysis', action);
        for (const [key, value] of Object.entries(params)) url.searchParams.set(key, value);
        return url.href;
    },
    async action(action, params = {}, data = {}, json = true) {
        return await fetch(this.geturl(action, params), {
            method: data ? 'POST' : 'GET',
            body: data ? JSON.stringify(data) : null
        }).then(response => json ? response.json() : response.text());
    },

    component(file) {
        return Vue.defineAsyncComponent(async () => await VueSfc.loadModule(file + ".vue", {
            moduleCache: {vue: Vue},
            getFile: async () => await this.action('file', {file}, false, false),
            addStyle(textContent) {
                const style = Object.assign(document.createElement('style'), {textContent});
                const ref = document.head.getElementsByTagName('style')[0] || null;
                document.head.insertBefore(style, ref);
            },
        }));
    }
};

let url = new URL(window.location.href), main;
if (url.searchParams.get('d5whub-extend-analysis') === 'request') {
    main = analysis.component('/page/Request.vue');
} else {
    main = analysis.component('/page/Dashboard.vue');
}


const app = Vue.createApp(main);
app.use(DataTable);
app.config.globalProperties.analysis = window.analysis = Vue.reactive(window.analysis);

document.body.id = 'app';
app.mount('#app');
