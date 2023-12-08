<!--
  - This file is part of vsr extend analysis
  - @author Vitor Reis <vitor@d5w.com.br>
  -->

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
        <DatatableEx ref="dt-routes" :columns="routes.dt.columns" :options="routes.dt.options" />
      </div>
      <div class="col-md-8 my-2">
        <DatatableEx ref="dt-requests" :columns="request.dt.columns" :options="request.dt.options" />
      </div>
    </div>
  </div>
</template>

<script>
const {Doughnut: DoughnutChat, Line: LineChat} = window.ChartJS;
const DatatableEx = analysis.component('components/DatatableEx.vue');

export default {
  name: "Dashboard",
  components: {
    DoughnutChat,
    LineChat,
    DatatableEx
  },
  methods: {
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
    async updateData() {

      let start_time = new Date().getTime();
      this.report = await analysis.action('data', {
        chartGroupBy: 'i',
        chartLength: 100,
        dtReqsStart: this.$refs['dt-requests']?.$refs.dt.dt.page.info().start || 0,
        dtReqsLength: this.$refs['dt-requests']?.$refs.dt.dt.page.info().length || 10,
        dtReqsOrder: 'start',
        dtKeysStart: this.$refs['dt-routes']?.$refs.dt.dt.page.info().start || 0,
        dtKeysLength: this.$refs['dt-routes']?.$refs.dt.dt.page.info().length || 10,
        dtKeysOrder: 'avg'
      });
      console.log();

      this.$refs['dt-routes'].redraw(
          this.report.request.dt.keys.data,
          this.report.request.dt.keys.recordsTotal,
          this.report.request.dt.keys.recordsFiltered
      );
      this.$refs['dt-requests'].redraw(
          this.report.request.dt.reqs.data,
          this.report.request.dt.reqs.recordsTotal,
          this.report.request.dt.reqs.recordsFiltered
      );

      let end_time = new Date().getTime();
      console.log('updateData', (end_time - start_time) / 1000);

      setTimeout(() => this.updateData(), 500);
    }
  },
  data() {
    return {
      groupBy: 'i',
      report: {
        server: {
          current: {
            uptime: 0,
            cpu: 0,
            thr_total: 0,
            thr_running: 0,
            thr_sleeping: 0,
            thr_stopped: 0,
            thr_zombie: 0,
            mem_total: 0,
            mem_free: 0,
            mem_used: 0,
            mem_cache: 0,
            swa_total: 0,
            swa_free: 0,
            swa_used: 0,
            swa_cache: 0,
            disk_total: 0,
            disk_free: 0,
            disk_used: 0,
          },
          chart: []
        },
        request: {
          current: {
            avg: 0.23,
            total: 10000,
            per: {
              second: 3,
              minute: 180,
              hour: 10800,
              day: 259200,
            }
          },
          chart: [],
          dt: {
            keys: {
              data: [],
              recordsTotal: 0,
              recordsFiltered: 0
            },
            reqs: {
              data: [],
              recordsTotal: 0,
              recordsFiltered: 0
              }
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
            labels: Object.keys(this.report.server.chart).reverse().map(i => i),
            datasets: [{
              label: 'CPU',
              data: Object.values(this.report.server.chart).reverse().map(i => i.cpu),
              borderWidth: 1,
              pointRadius: 1
            }, {
              label: 'Memory',
              data: Object.values(this.report.server.chart).reverse().map(i => i.mem_used * 100 / this.report.server.current.mem_total),
              borderWidth: 1,
              pointRadius: 1
            }, {
              label: 'Disk',
              data: Object.values(this.report.server.chart).reverse().map(i => i.disk_used * 100 / this.report.server.current.disk_total),
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
        avg: this.secondFormat(this.report.request.current.avg),
        avg_class: this.report.request.current.avg < .5 ? 'text-success' : (this.report.request.current.avg < 1 ? 'text-warning' : 'text-danger'),
        total: this.report.request.current.total,
        per_second: this.report.request.current.per.second,
        per_minute: this.report.request.current.per.minute,
        per_hour: this.report.request.current.per.hour,
        per_day: this.report.request.current.per.day,

        chart: {
          data: {
            labels: Object.keys(this.report.request.chart).reverse().map(i => i),
            datasets: [{
              label: 'Requests',
              data: Object.values(this.report.request.chart).reverse().map(i => i),
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
            {data: "start", title: "Date"},
            {data: "duration", title: "End Time"},
            {data: "memory", title: "Memory"},
            {data: "profile_count", title: "Count"},
            {data: "http_code", title: "Code"},
            {data: "method", title: "Method"},
            {data: "url", title: "URL"},
            {data: "error_count", title: "ERR"}
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
            {data: "key", title: "Request Key"},
            {data: "hits", title: "Hits"},
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
  },
  mounted() {
    this.updateData();
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