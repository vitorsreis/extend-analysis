/*
 * This file is part of vsr extend analysis
 * @author Vitor Reis <vitor@d5w.com.br>
 */


import * as Vue from 'vue';
import VueSfc from 'vue3-sfc-loader';

import 'bootstrap';
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-bs5';

DataTable.use(DataTablesCore);
window.DataTable = DataTable;

import * as ChartJS from 'chart.js';

window.ChartJS = ChartJS;
window.ChartJS.Chart.register(...window.ChartJS.registerables);

import * as VueChart from "vue-chartjs";

window.VueChart = VueChart;

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
            getFile: async () => await this.action('public-file', {file}, false, false),
            addStyle(textContent) {
                const style = Object.assign(document.createElement('style'), {textContent});
                const ref = document.head.getElementsByTagName('style')[0] || null;
                document.head.insertBefore(style, ref);
            },
        }));
    },
    data: {
        dashboard: {
            groupBy: 'i',
            server: {
                current: {
                    uptime: 180,
                    cpu: 50,

                    thr_total: 100,
                    thr_running: 50,
                    thr_sleeping: 30,
                    thr_stopped: 15,
                    thr_zombie: 5,
                    mem_total: 8192,
                    mem_free: 1024.9,
                    mem_used: 7167.1,
                    mem_cache: 597.9,
                    swa_total: 4096,
                    swa_free: 469.2,
                    swa_used: 3626.8,
                    swa_cache: 607.9,

                    disk_total: 400,
                    disk_free: 150,
                    disk_used: 250,
                },
                chart: [
                    {label: "10:32", "value": {cpu: "35.8", memory: "43.3", disk: "43.5"}},
                    {label: "10:40", "value": {cpu: "35", memory: "43.6", disk: "43.6"}},
                    {label: "10:50", "value": {cpu: "37.4", memory: "43.6", disk: "43.6"}},
                    {label: "11:00", "value": {cpu: "38.3", memory: "43.5", disk: "43.7"}},
                    {label: "11:10", "value": {cpu: "34.5", memory: "44.6", disk: "43.8"}},
                    {label: "11:20", "value": {cpu: "35.4", memory: "43.3", disk: "43.8"}},
                    {label: "11:30", "value": {cpu: "34.5", memory: "43.1", disk: "43.9"}},
                    {label: "11:40", "value": {cpu: "30.6", memory: "42.1", disk: "44"}},
                    {label: "11:50", "value": {cpu: "32.3", memory: "41.5", disk: "44"}},
                    {label: "12:00", "value": {cpu: "23.2", memory: "41.3", disk: "44"}},
                    {label: "12:10", "value": {cpu: "18.3", memory: "39.8", disk: "44"}},
                    {label: "12:20", "value": {cpu: "19.4", memory: "40.6", disk: "44"}},
                    {label: "12:30", "value": {cpu: "18", memory: "39.3", disk: "44"}},
                    {label: "12:40", "value": {cpu: "19.7", memory: "39.1", disk: "44"}},
                    {label: "12:50", "value": {cpu: "18.2", memory: "39", disk: "44"}},
                    {label: "13:00", "value": {cpu: "18.9", memory: "39.3", disk: "44"}},
                    {label: "13:10", "value": {cpu: "18.1", memory: "38.9", disk: "44"}},
                    {label: "13:20", "value": {cpu: "24", memory: "40.1", disk: "44.1"}},
                    {label: "13:30", "value": {cpu: "30.3", memory: "40.8", disk: "44.1"}},
                    {label: "13:40", "value": {cpu: "34.1", memory: "42.4", disk: "44.2"}},
                    {label: "13:50", "value": {cpu: "31.9", memory: "41.9", disk: "44.2"}},
                    {label: "14:00", "value": {cpu: "33", memory: "41.6", disk: "44.3"}},
                    {label: "14:10", "value": {cpu: "32.4", memory: "42.1", disk: "44.3"}},
                    {label: "14:20", "value": {cpu: "27.4", memory: "45.7", disk: "44.3"}}
                ]
            },
            request: {
                avg: 0.23,
                total: 10000,
                per: {
                    second: 3,
                    minute: 180,
                    hour: 10800,
                    day: 259200,
                },
                chart: [
                    {label: "10:31", value: "1629"},
                    {label: "10:40", value: "2026"},
                    {label: "10:50", value: "2068"},
                    {label: "11:00", value: "2116"},
                    {label: "11:10", value: "1748"},
                    {label: "11:20", value: "1903"},
                    {label: "11:30", value: "1684"},
                    {label: "11:40", value: "1539"},
                    {label: "11:50", value: "1513"},
                    {label: "12:00", value: "815"},
                    {label: "12:10", value: "613"},
                    {label: "12:20", value: "707"},
                    {label: "12:30", value: "659"},
                    {label: "12:40", value: "692"},
                    {label: "12:50", value: "585"},
                    {label: "13:00", value: "588"},
                    {label: "13:10", value: "544"}
                ]
            }
        },
        count: 0
    }
};

let url = new URL(window.location.href), main;
if (url.searchParams.get('d5whub-extend-analysis') === 'request') {
    main = analysis.component('Request.vue');
} else {
    main = analysis.component('Dashboard.vue');
}

window.Vue = Vue;
// window.Vue.$bvConfig = {};

const app = Vue.createApp(main);
app.use(DataTable);
app.config.globalProperties.analysis = window.analysis = Vue.reactive(window.analysis);

document.body.id = 'app';
app.mount('#app');
