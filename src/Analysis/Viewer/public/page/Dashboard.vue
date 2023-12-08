<!--
  - This file is part of vsr extend analysis
  - @author Vitor Reis <vitor@d5w.com.br>
  -->

<template>
  <div class="container-fluid vh-100 w-100 d-flex flex-column align-content-stretch">
    <div class="row">
      <div class="col-md-4 col-12">
        <div class="row">
          <div id="current-charts" class="col-md-2 col-12 p-1 pb-2">
            <DoughnutChat id="chart-cpu" :data="server.cpu.chart.data" :options="server.cpu.chart.options"/>
            <DoughnutChat id="chart-disk" :data="server.disk.chart.data" :options="server.disk.chart.options"/>
            <DoughnutChat id="chart-memory" :data="server.memory.chart.data" :options="server.memory.chart.options"/>
          </div>
          <div id="current" class="col-md-10 col-12 h-100 d-flex flex-column align-items-start justify-content-between py-2">
            <span class="badge text-dark-emphasis">
              Up: {{ server.uptime }}
            </span>
            <span class="badge text-dark-emphasis">
              CPU: <span :class=server.cpu.class>{{ server.cpu.percent }}</span>
            </span>
            <span class="badge text-dark-emphasis">
              Disk: <span :class=server.disk.class>{{ server.disk.percent }}</span> ({{ server.disk.used }}/{{ server.disk.total }}, {{ server.disk.free }} free)
            </span>
            <span class="badge text-dark-emphasis">
              Memory: <span :class=server.memory.class>{{ server.memory.percent }}</span> ({{ server.memory.used }}/{{ server.memory.total }}, {{ server.memory.free }} free / {{ server.memory.cache }} cache)
            </span>
            <span class="badge text-dark-emphasis">
              Swap: {{ server.swap.used }}/{{ server.swap.total }}, {{ server.swap.free }} free / {{ server.swap.cache }} cache
            </span>
            <span class="badge text-dark-emphasis">
              Tasks: {{ server.thread.running }} running, {{ server.thread.total }} total, {{ server.thread.sleeping }} sleeping, {{ server.thread.stopped }} stopped, {{ server.thread.zombie }} zombie
            </span>
            <span class="badge text-dark-emphasis">
              Requests: <span :class=request.avg_class>{{ request.avg }}</span>, hits: {{ request.total }} total ({{ request.per_second }}/s, {{ request.per_minute }}/m, {{ request.per_hour }}/h, {{ request.per_day }}/d)
            </span>
          </div>
        </div>
      </div>
      <div class="col-md-4 py-2">
        <LineChat id="chart-server" :data="server.chart.data" :options="server.chart.options"/>
      </div>
      <div class="col-md-4 py-2">
        <LineChat id="chart-request" :data="request.chart.data" :options="request.chart.options"/>
      </div>
    </div>
    <div class="row" id="dts">
      <div class="col-md-4 my-2">
        <button @click="test" style="position: absolute;top:0">XXX</button>
        <DatatableEx ref="dt-routes" :columns="routes.dt.columns" :options="routes.dt.options" />
      </div>
      <div class="col-md-8 my-2">
        <DatatableEx ref="dt-requests" :columns="request.dt.columns" :options="request.dt.options" />
      </div>
    </div>
  </div>
</template>

<script>
const {Doughnut: DoughnutChat, Line: LineChat} = window.VueChart
const DatatableEx = analysis.component('components/DatatableEx.vue');

export default {
  name: "Dashboard",
  components: {
    DoughnutChat,
    LineChat,
    DatatableEx
  },
  methods: {
    test() {
      // random number 10~1000
      let count = Math.floor(Math.random() * (1000 - 10 + 1) + 10);

      this.$refs['dt-routes'].redraw([
          {route_key: 'GET /', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
          {route_key: 'GET /api/v1', count: 1000, avg: 0.23, min: 0.12, max: 0.45, last: 0.23},
        ],
        count,
        count,
      );
    },

    numberFormat(value, decimals = 2) {
      return value
          .toFixed(decimals)
          .replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1')
          .replace(/\.0+$/, '');

    },
    percentFormat(value) {
      return this.numberFormat(value, 1) + '%';
    },
    sizeFormat(value, suffix = '') {
      return this.numberFormat(value, 1) + suffix;
    },
    secondFormat(value) {
      return this.numberFormat(value, 3) + 's';
    },
  },
  data() {
    return {
      groupBy: 'i',
      report: {
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
          ],
          dt: {
            recordsTotal: 500,
            recordsFiltered: 500,
            data: []
          }
        },
        routes: {
          dt: {
            recordsTotal: 500,
            recordsFiltered: 500,
            data: []
          }
        }
      }
    }
  },

  computed: {
    server() {
      let uptime = this.report.server.current.uptime; // in minutes
      let uptime_days = Math.floor(uptime / 1440);
      let uptime_hours = Math.floor((uptime - (uptime_days * 1440)) / 60);
      let uptime_minutes = Math.floor(uptime - (uptime_days * 1440) - (uptime_hours * 60));

      uptime = '';
      if (uptime_days > 0) uptime += ` ${uptime_days}d`;
      if (uptime_hours > 0) uptime += ` ${uptime_hours}h`;
      if (uptime_minutes > 0) uptime += ` ${uptime_minutes}m`;

      let cpu_percent = this.report.server.current.cpu;
      let disk_percent = this.report.server.current.disk_used * 100 / this.report.server.current.disk_total;
      let memory_percent = this.report.server.current.mem_used * 100 / this.report.server.current.mem_total;

      return {
        uptime,
        cpu: {
          percent: this.percentFormat(cpu_percent),
          class: cpu_percent < 60 ? 'text-success' : (cpu_percent < 80 ? 'text-warning' : 'text-danger'),
          chart: {
            data: {
              labels: ['Free', 'Usage'],
              datasets: [
                {
                  data: [this.numberFormat(100 - parseFloat(cpu_percent), 1), this.numberFormat(cpu_percent, 1)],
                  backgroundColor: ['rgb(127,190,132)', 'rgb(255 138 163)']
                }
              ]
            },
            options: {
              responsive: true,
              plugins: {
                legend: {display: false},
                title: {display: true, text: 'CPU', padding: 5, font: {size: 12}}
              }
            }
          }
        },
        disk: {
          percent: this.percentFormat(disk_percent),
          free: this.sizeFormat(this.report.server.current.disk_free),
          used: this.sizeFormat(this.report.server.current.disk_used),
          total: this.sizeFormat(this.report.server.current.disk_total, ' GB'),
          class: disk_percent < 60 ? 'text-success' : (disk_percent < 80 ? 'text-warning' : 'text-danger'),
          chart: {
            data: {
              labels: ['Free', 'Usage'],
              datasets: [
                {
                  data: [this.numberFormat(100 - disk_percent, 1), this.numberFormat(disk_percent, 1)],
                  backgroundColor: ['rgb(127,190,132)', 'rgb(255 138 163)']
                }
              ]
            },
            options: {
              responsive: true,
              plugins: {
                legend: {display: false},
                title: {display: true, text: 'Disk', padding: 5, font: {size: 12}}
              }
            }
          }
        },
        memory: {
          percent: this.percentFormat(memory_percent),
          free: this.sizeFormat(this.report.server.current.mem_free),
          used: this.sizeFormat(this.report.server.current.mem_used),
          total: this.sizeFormat(this.report.server.current.mem_total, ' MB'),
          cache: this.sizeFormat(this.report.server.current.mem_cache),
          class: memory_percent < 60 ? 'text-success' : (memory_percent < 80 ? 'text-warning' : 'text-danger'),
          chart: {
            data: {
              labels: ['Free', 'Usage'],
              datasets: [
                {
                  data: [this.numberFormat(100 - memory_percent, 1), this.numberFormat(memory_percent, 1)],
                  backgroundColor: ['rgb(127,190,132)', 'rgb(255 138 163)']
                }
              ]
            },
            options: {
              responsive: true,
              plugins: {
                legend: {display: false},
                title: {display: true, text: 'Memory', padding: 5, font: {size: 12}}
              }
            }
          }
        },
        swap: {
          percent: this.percentFormat(this.report.server.current.swa_used * 100 / this.report.server.current.swa_total),
          free: this.sizeFormat(this.report.server.current.swa_free),
          used: this.sizeFormat(this.report.server.current.swa_used),
          total: this.sizeFormat(this.report.server.current.swa_total, ' MB'),
          cache: this.sizeFormat(this.report.server.current.swa_cache)
        },
        thread: {
          total: this.report.server.current.thr_total,
          running: this.report.server.current.thr_running,
          sleeping: this.report.server.current.thr_sleeping,
          stopped: this.report.server.current.thr_stopped,
          zombie: this.report.server.current.thr_zombie
        },

        chart: {
          data: {
            labels: Object.values(this.report.server.chart).map(i => i.label),
            datasets: [{
              label: 'CPU',
              data: Object.values(this.report.server.chart).map(i => i.value.cpu),
              borderWidth: 1,
              pointRadius: 1
            }, {
              label: 'Memory',
              data: Object.values(this.report.server.chart).map(i => i.value.memory),
              borderWidth: 1,
              pointRadius: 1
            }, {
              label: 'Disk',
              data: Object.values(this.report.server.chart).map(i => i.value.disk),
              borderWidth: 1,
              pointRadius: 1
            }]
          },
          options: {
            responsive: true,
            animation: false,
            scales: {
              x: {ticks: {autoSize: false, maxRotation: 90, minRotation: 90, font: {size: 11}}},
              y: {beginAtZero: true, min: 0, max: 100, font: {size: 11}}
            }
          }
        }
      }
    },
    request() {
      return {
        avg: this.secondFormat(this.report.request.avg),
        avg_class: this.report.request.avg < .5 ? 'text-success' : (this.report.request.avg < 1 ? 'text-warning' : 'text-danger'),
        total: this.report.request.total,
        per_second: this.report.request.per.second,
        per_minute: this.report.request.per.minute,
        per_hour: this.report.request.per.hour,
        per_day: this.report.request.per.day,

        chart: {
          data: {
            labels: Object.values(this.report.request.chart).map(i => i.label),
            datasets: [{
              label: 'Requests',
              data: Object.values(this.report.request.chart).map(i => i.value),
              borderWidth: 1,
              pointRadius: 1
            }]
          },
          options: {
            responsive: true,
            animation: false,
            scales: {
              x: {ticks: {autoSize: false, maxRotation: 90, minRotation: 90, font: {size: 11}}},
              y: {beginAtZero: true, font: {size: 11}}
            }
          }
        },
        dt: {
          columns: [
            {data: "date", title: "Date"},
            {data: "end", title: "End Time"},
            {data: "memory", title: "Memory"},
            {data: "profile_count", title: "Count"},
            {data: "http_code", title: "Code"},
            {data: "method", title: "Method"},
            {data: "uri", title: "URI"},
            {data: "error", title: "ERR"}
          ],
          options: {
            order: [[0, 'desc']]
          }
        }
      }
    },
    routes() {
      return {
        dt: {
          columns: [
            {data: "route_key", title: "Route Key"},
            {data: "count", title: "Count"},
            {data: "avg", title: "Avg"},
            {data: "min", title: "Min"},
            {data: "max", title: "Max"},
            {data: "last", title: "Last", orderable: false}
          ],
          options: {
            order: [[2, 'desc']]
          }
        }
      }
    }
  }
}
</script>

<style scoped>
#current,
#chart-server,
#chart-request {
  min-height: 235px;
  max-height: 235px;
  overflow: auto;
}

#current-charts {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  max-height: 235px;
}

#current-charts > canvas {
  height: calc(100% / 3) !important;
  width: auto !important
}

#dts > div {
  height: calc(100vh - 260px);
  min-height: 300px;
}

@media (max-width: 767px) {
  #current-charts {
    flex-direction: row;
  }

  #current-charts > canvas {
    height: auto !important;
    width: calc(100% / 3) !important
  }

  #dts > div {
    max-height: calc(50vh - 1em);
  }
}
</style>