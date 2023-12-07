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
            <span class="badge text-dark-emphasis">Up: {{ server.uptime }}</span>
            <span class="badge text-dark-emphasis">CPU: <span :class=server.cpu.class>{{ server.cpu.percent }}</span></span>
            <span class="badge text-dark-emphasis">Disk: <span :class=server.disk.class>{{ server.disk.percent }}</span> ({{ server.disk.used }}/{{ server.disk.total }}, {{ server.disk.free }} free)</span>
            <span class="badge text-dark-emphasis">Memory: <span :class=server.memory.class>{{ server.memory.percent }}</span> ({{ server.memory.used }}/{{ server.memory.total }}, {{ server.memory.free }} free / {{ server.memory.cache }} cache)</span>
            <span class="badge text-dark-emphasis">Swap: {{ server.swap.used }}/{{ server.swap.total }}, {{ server.swap.free }} free / {{ server.swap.cache }} cache</span>
            <span class="badge text-dark-emphasis">Tasks: {{ server.thread.running }} running, {{ server.thread.total }} total, {{ server.thread.sleeping }} sleeping, {{ server.thread.stopped }} stopped, {{ server.thread.zombie }} zombie</span>
            <span class="badge text-dark-emphasis">Requests: <span :class=requests.avg_class>{{ requests.avg }}</span>, hits: {{ requests.total }} total ({{ requests.per_second }}/s, {{ requests.per_minute }}/m, {{ requests.per_hour }}/h, {{ requests.per_day }}/d)</span>
          </div>
        </div>
      </div>
      <div class="col-md-4 py-2">
        <LineChat id="chart-server" :data="server.chart.data" :options="server.chart.options"/>
      </div>
      <div class="col-md-4 py-2">
        <LineChat id="chart-request" :data="requests.chart.data" :options="requests.chart.options"/>
      </div>
    </div>
    <div class="row">
      <div class="col-md-4 my-2">
        <Datatables.netVue ref="dt-routeKey" class="table table-sm" :columns="routes.dt.columns" :data="routes.dt.data" :options="routes.dt.options" />
      </div>
      <div class="col-md-8 my-2">
        <Datatables.netVue ref="dt-requests" class="table table-sm" :columns="requests.dt.columns" :data="requests.dt.data" :options="requests.dt.options" />
      </div>
    </div>
  </div>
</template>

<script>

const {Doughnut: DoughnutChat, Line: LineChat} = window.VueChart

export default {
  name: "Dashboard",
  components: {
    DoughnutChat,
    LineChat
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
    resize() {
      let height = window.innerHeight - 325;
      let pageLength = Math.floor(height / 25);
      if (pageLength < 10) pageLength = 10;
      this.dtPageSize = pageLength;

      this.$refs['dt-routeKey'].dt.page.len(pageLength).draw(false);
      this.$refs['dt-requests'].dt.page.len(pageLength).draw(false);
    }
  },
  mounted() {
    window.addEventListener('resize', this.resize);
    this.resize();
  },
  computed: {
    server() {
      let uptime = analysis.data.dashboard.server.current.uptime; // in minutes
      let uptime_days = Math.floor(uptime / 1440);
      let uptime_hours = Math.floor((uptime - (uptime_days * 1440)) / 60);
      let uptime_minutes = Math.floor(uptime - (uptime_days * 1440) - (uptime_hours * 60));

      uptime = '';
      if (uptime_days > 0) uptime += ` ${uptime_days}d`;
      if (uptime_hours > 0) uptime += ` ${uptime_hours}h`;
      if (uptime_minutes > 0) uptime += ` ${uptime_minutes}m`;

      let cpu_percent = analysis.data.dashboard.server.current.cpu;
      let disk_percent = analysis.data.dashboard.server.current.disk_used * 100 / analysis.data.dashboard.server.current.disk_total;
      let memory_percent = analysis.data.dashboard.server.current.mem_used * 100 / analysis.data.dashboard.server.current.mem_total;

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
          free: this.sizeFormat(analysis.data.dashboard.server.current.disk_free),
          used: this.sizeFormat(analysis.data.dashboard.server.current.disk_used),
          total: this.sizeFormat(analysis.data.dashboard.server.current.disk_total, ' GB'),
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
          free: this.sizeFormat(analysis.data.dashboard.server.current.mem_free),
          used: this.sizeFormat(analysis.data.dashboard.server.current.mem_used),
          total: this.sizeFormat(analysis.data.dashboard.server.current.mem_total, ' MB'),
          cache: this.sizeFormat(analysis.data.dashboard.server.current.mem_cache),
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
          percent: this.percentFormat(analysis.data.dashboard.server.current.swa_used * 100 / analysis.data.dashboard.server.current.swa_total),
          free: this.sizeFormat(analysis.data.dashboard.server.current.swa_free),
          used: this.sizeFormat(analysis.data.dashboard.server.current.swa_used),
          total: this.sizeFormat(analysis.data.dashboard.server.current.swa_total, ' MB'),
          cache: this.sizeFormat(analysis.data.dashboard.server.current.swa_cache)
        },
        thread: {
          total: analysis.data.dashboard.server.current.thr_total,
          running: analysis.data.dashboard.server.current.thr_running,
          sleeping: analysis.data.dashboard.server.current.thr_sleeping,
          stopped: analysis.data.dashboard.server.current.thr_stopped,
          zombie: analysis.data.dashboard.server.current.thr_zombie
        },

        chart: {
          data: {
            labels: Object.values(analysis.data.dashboard.server.chart).map(i => i.label),
            datasets: [{
              label: 'CPU',
              data: Object.values(analysis.data.dashboard.server.chart).map(i => i.value.cpu),
              borderWidth: 1,
              pointRadius: 1
            }, {
              label: 'Memory',
              data: Object.values(analysis.data.dashboard.server.chart).map(i => i.value.memory),
              borderWidth: 1,
              pointRadius: 1
            }, {
              label: 'Disk',
              data: Object.values(analysis.data.dashboard.server.chart).map(i => i.value.disk),
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
    requests() {
      return {
        avg: this.secondFormat(analysis.data.dashboard.request.avg),
        avg_class: analysis.data.dashboard.request.avg < .5 ? 'text-success' : (analysis.data.dashboard.request.avg < 1 ? 'text-warning' : 'text-danger'),
        total: analysis.data.dashboard.request.total,
        per_second: analysis.data.dashboard.request.per.second,
        per_minute: analysis.data.dashboard.request.per.minute,
        per_hour: analysis.data.dashboard.request.per.hour,
        per_day: analysis.data.dashboard.request.per.day,

        chart: {
          data: {
            labels: Object.values(analysis.data.dashboard.request.chart).map(i => i.label),
            datasets: [{
              label: 'Requests',
              data: Object.values(analysis.data.dashboard.request.chart).map(i => i.value),
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
            responsive: true,
            searching: false,
            lengthChange: false,
            order: [[0, 'desc']],
            loading: true,
            serverSide: true,
            scrollY: 'calc(100vh - 325px)',
            async ajax(request, callback, api) {
              callback({
                draw: request.draw,
                recordsTotal: 500,
                recordsFiltered: 500,
                data: [],
                error: false
              })
            },
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
            responsive: true,
            searching: false,
            lengthChange: false,
            order: [[2, 'desc']],
            loading: true,
            serverSide: true,
            scrollY: 'calc(100vh - 325px)',
            async ajax(request, callback, api) {
              let data = [];
              for (let i = 0; i < request.length; i++) {
                data.push({ route_key: 'route_key_' + i, count: i, avg: i, min: i, max: i, last: i });
              }

              callback({
                draw: request.draw,
                recordsTotal: 500,
                recordsFiltered: 500,
                data: data,
                error: false
              })
            },
          }
        }
      }
    }
  }
}

</script>

<style>

.dataTables_scrollBody {
  min-height: 255px !important;
}
</style>

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

@media (max-width: 767px) {
  #current-charts {
    flex-direction: row;
  }

  #current-charts > canvas {
    height: auto !important;
    width: calc(100% / 3) !important
  }
}
</style>