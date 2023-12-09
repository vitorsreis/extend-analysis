<!--
  - This file is part of vsr extend analysis
  - @author Vitor Reis <vitor@d5w.com.br>
  -->

<template>
  <div class="container-fluid vh-100 w-100 d-flex flex-column align-content-stretch">
    <div class="position-fixed d-flex justify-content-between align-items-center mt-2 me-2" style="right:0;top:0;width:120px">
      <span><span :class="'text-'+reportStatus.class">⬤</span> {{ reportStatus.text }}</span>
      <select v-model="groupBy" class="form-select form-select-sm" style="width:60px;display:inline;float:right">
        <option value="s10">10s</option>
        <option value="i">1m</option>
        <option value="i10">10m</option>
        <option value="H">1h</option>
        <option value="d">1d</option>
      </select>
    </div>
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
            <span class="badge text-dark-emphasis">CPU: <span :class="'text-'+server.cpu.color.bg">{{ server.cpu.percent }}</span></span>
            <span class="badge text-dark-emphasis">Disk: <span :class="'text-'+server.disk.color.bg">{{ server.disk.percent }}</span> ({{ server.disk.used }}/{{ server.disk.total }}, {{ server.disk.free }} free)</span>
            <span class="badge text-dark-emphasis">Memory: <span :class="'text-'+server.memory.color.bg">{{ server.memory.percent }}</span> ({{ server.memory.used }}/{{ server.memory.total }}, {{ server.memory.free }} free / {{ server.memory.cache }} cache)</span>
            <span class="badge text-dark-emphasis">Swap: {{ server.swap.used }}/{{ server.swap.total }}, {{ server.swap.free }} free / {{ server.swap.cache }} cache</span>
            <span class="badge text-dark-emphasis">Tasks: {{ server.thread.running }} running, {{ server.thread.total }} total, {{ server.thread.sleeping }} sleeping, {{ server.thread.stopped }} stopped, {{ server.thread.zombie }} zombie</span>
            <span class="badge text-dark-emphasis">Requests: <span :class="'text-'+request.color.bg">{{ request.avg }}</span>, hits: {{ request.total }} total ({{ request.per_second }}/s, {{ request.per_minute }}/m, {{ request.per_hour }}/h, {{ request.per_day }}/d)</span>
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
        <DatatableEx id="dt-keys" ref="dt-keys" :columns="request.dt.keys.columns" :options="request.dt.keys.options"
                     :force="() => updateData()" @click="request.dt.keys.view"/>
      </div>
      <div class="col-md-8 my-2">
        <DatatableEx id="dt-reqs" ref="dt-reqs" :columns="request.dt.reqs.columns" :options="request.dt.reqs.options"
                     :force="() => updateData()" @click="request.dt.reqs.view"/>
      </div>
    </div>
  </div>
</template>

<script>
const {Doughnut: DoughnutChat, Line: LineChat} = window.ChartJS;
const DatatableEx = analysis.component('components/DatatableEx.vue');

const tolerance = {
  server: {
    cpu: {
      80: {bg: 'danger', text: 'white'},
      60: {bg: 'warning', text: 'black'},
      default: {bg: 'secondary', text: 'white'}
    },
    disk: {
      80: {bg: 'danger', text: 'white'},
      60: {bg: 'warning', text: 'black'},
      'default': {bg: 'secondary', text: 'white'}
    },
    memory: {
      80: {bg: 'danger', text: 'white'},
      60: {bg: 'warning', text: 'black'},
      default: {bg: 'secondary', text: 'white'}
    }
  },
  request: {
    duration: {
      1: {bg: 'danger', text: 'white'},
      0.5: {bg: 'warning', text: 'black'},
      default: {bg: 'secondary', text: 'white'}
    },
    memory: {
      30: {bg: 'danger', text: 'white'},
      10: {bg: 'warning', text: 'black'},
      default: {bg: 'secondary', text: 'white'}
    },
    http_code: {
      500: {bg: 'danger', text: 'white'},
      400: {bg: 'warning', text: 'black'},
      default: {bg: 'secondary', text: 'white'}
    },
    profile_count: {
      1000: {bg: 'danger', text: 'white'},
      500: {bg: 'warning', text: 'black'},
      default: {bg: 'secondary', text: 'white'}
    },
  }
}


export default {
  name: "Dashboard",
  components: {
    DoughnutChat,
    LineChat,
    DatatableEx
  },
  methods: {
    numberFormat(value, decimals = 2) {
      if (value === undefined || value === null) value = 0;
      return value
          .toFixed(decimals)
          .replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1');
    },
    percentFormat(value) {
      if (value === 0) return '-';
      return this.numberFormat(value, 1) + '%';
    },
    sizeFormat(value, suffix = '') {
      return this.numberFormat(value, 1) + suffix;
    },
    secondFormat(value) {
      return this.numberFormat(value, 3) + 's';
    },
    dateFormat(format, timestamp = 0) {
      let date = new Date(timestamp * 1000);
      let d = date.getDate().toString().padStart(2, '0');
      let m = (date.getMonth() + 1).toString().padStart(2, '0');
      let Y = date.getFullYear().toString().padStart(4, '0');
      let H = date.getHours().toString().padStart(2, '0');
      let i = date.getMinutes().toString().padStart(2, '0');
      let s = date.getSeconds().toString().padStart(2, '0');

      return format
          .replace('Y', Y)
          .replace('m', m)
          .replace('d', d)
          .replace('H', H)
          .replace('i', i)
          .replace('s', s);
    },
    labelFormat(value, groupBy) {
      switch (groupBy) {
        case 's10':
          return this.dateFormat(`H:i:s`, value);
        case 'i':
        case 'i10':
          return this.dateFormat(`H:i`, value);
        case 'H':
          return this.dateFormat(`H`, value);
        case 'd':
          return this.dateFormat(`d/m`, value);
        default:
          return this.dateFormat(`d/m/Y`, value);
      }
    },
    toleranceColors(colors, value) {
      let result = colors.default;
      for (let i in colors) {
        if (i !== 'default' && value >= i) {
          result = colors[i];
          break;
        }
      }
      return result;
    },

    async updateData() {
      if (this.updateDataTimeout) {
        clearTimeout(this.updateDataTimeout);
        this.updateDataTimeout = null;
      }

      if (typeof this.$refs['dt-reqs'] === 'undefined' || typeof this.$refs['dt-keys'] === 'undefined') {
        this.updateDataTimeout = setTimeout(() => this.updateData(), 10);
        return;
      }

      this.reportStatus.class = 'warning';
      this.reportStatus.text = '...';

      let dtReqsOrder = this.$refs['dt-reqs'].$refs.dt.dt.order()[0][0] || false;
      if (dtReqsOrder) {
        dtReqsOrder = this.$refs['dt-reqs'].$refs.dt.dt.settings()[0].aoColumns[dtReqsOrder].data;
      } else {
        dtReqsOrder = 'start';
      }

      let dtKeysOrder = this.$refs['dt-keys'].$refs.dt.dt.order()[0][0] || false;
      if (dtKeysOrder) {
        dtKeysOrder = this.$refs['dt-keys'].$refs.dt.dt.settings()[0].aoColumns[dtKeysOrder].data;
      } else {
        dtKeysOrder = 'avg';
      }

      let start_time = new Date().getTime();
      this.report = await analysis.action('data', {
        chartGroupBy: this.groupBy,
        chartLength: 100,
        dtReqsStart: this.$refs['dt-reqs']?.$refs.dt.dt.page.info().start || 0,
        dtReqsLength: this.$refs['dt-reqs']?.$refs.dt.dt.page.info().length || 10,
        dtReqsOrder,
        dtReqsDir: this.$refs['dt-reqs']?.$refs.dt.dt.order()[0][1] || 'desc',
        dtKeysStart: this.$refs['dt-keys']?.$refs.dt.dt.page.info().start || 0,
        dtKeysLength: this.$refs['dt-keys']?.$refs.dt.dt.page.info().length || 10,
        dtKeysOrder,
        dtKeysDir: this.$refs['dt-keys']?.$refs.dt.dt.order()[0][1] || 'desc',
      });

      this.$refs['dt-keys'].redraw(
          this.report.request?.dt.keys.data,
          this.report.request?.dt.keys.recordsTotal,
          this.report.request?.dt.keys.recordsFiltered
      );
      this.$refs['dt-reqs'].redraw(
          this.report.request?.dt.reqs.data,
          this.report.request?.dt.reqs.recordsTotal,
          this.report.request?.dt.reqs.recordsFiltered
      );

      let end_time = new Date().getTime();

      this.reportStatus.class = 'success';
      this.reportStatus.text = ` ${this.secondFormat((end_time - start_time) / 1000)}`;

      this.updateDataTimeout = setTimeout(() => this.updateData(), 2000);
    }
  },
  data() {
    return {
      updateDataTimeout: null,

      groupBy: 's10',

      report: {},
      reportStatus: {
        text: '...',
        class: 'warning'
      }
    }
  },

  computed: {
    server() {
      let uptime = this.report.server?.current?.uptime || 0; // in minutes
      let uptime_days = Math.floor(uptime / 1440);
      let uptime_hours = Math.floor((uptime - (uptime_days * 1440)) / 60);
      let uptime_minutes = Math.floor(uptime - (uptime_days * 1440) - (uptime_hours * 60));

      uptime = '';
      if (uptime_days > 0) uptime += ` ${uptime_days}d`;
      if (uptime_hours > 0) uptime += ` ${uptime_hours}h`;
      if (uptime_minutes > 0) uptime += ` ${uptime_minutes}m`;
      if (uptime === '') uptime = '-';

      let cpu_percent = this.report.server?.current?.cpu || 0;
      let disk_percent = (this.report.server?.current?.disk_used * 100 / this.report.server?.current?.disk_total) || 0;
      let memory_percent = (this.report.server?.current?.mem_used * 100 / this.report.server?.current?.mem_total) || 0;

      return {
        uptime,
        cpu: {
          percent: this.percentFormat(cpu_percent),
          color: this.toleranceColors(tolerance.server.cpu, cpu_percent),
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
          color: this.toleranceColors(tolerance.server.disk, disk_percent),
          free: this.sizeFormat(this.report.server?.current.disk_free),
          used: this.sizeFormat(this.report.server?.current.disk_used),
          total: this.sizeFormat(this.report.server?.current.disk_total, ' GB'),
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
          color: this.toleranceColors(tolerance.server.memory, memory_percent),
          free: this.sizeFormat(this.report.server?.current.mem_free),
          used: this.sizeFormat(this.report.server?.current.mem_used),
          total: this.sizeFormat(this.report.server?.current.mem_total, ' MB'),
          cache: this.sizeFormat(this.report.server?.current.mem_cache),
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
          percent: this.percentFormat(this.report.server?.current.swa_used * 100 / this.report.server?.current.swa_total),
          free: this.sizeFormat(this.report.server?.current.swa_free),
          used: this.sizeFormat(this.report.server?.current.swa_used),
          total: this.sizeFormat(this.report.server?.current.swa_total, ' MB'),
          cache: this.sizeFormat(this.report.server?.current.swa_cache)
        },
        thread: {
          total: this.report.server?.current.thr_total,
          running: this.report.server?.current.thr_running,
          sleeping: this.report.server?.current.thr_sleeping,
          stopped: this.report.server?.current.thr_stopped,
          zombie: this.report.server?.current.thr_zombie
        },

        chart: {
          data: {
            labels: Object.keys(this.report.server?.chart || {}).map(i => this.labelFormat(i, this.groupBy)),
            datasets: [{
              label: 'CPU',
              data: Object.values(this.report.server?.chart || {}).map(i => this.numberFormat(i.cpu, 1)),
              borderWidth: 1,
              pointRadius: 1
            }, {
              label: 'Memory',
              data: Object.values(this.report.server?.chart || {}).map(i => this.numberFormat(i.mem_used * 100 / this.report.server?.current.mem_total, 1)),
              borderWidth: 1,
              pointRadius: 1
            }, {
              label: 'Disk',
              data: Object.values(this.report.server?.chart || {}).map(i => this.numberFormat(i.disk_used * 100 / this.report.server?.current.disk_total, 1)),
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
        avg: this.secondFormat(this.report.request?.current.avg),
        color: this.toleranceColors(tolerance.request.duration, this.report.request?.current.avg),
        total: this.numberFormat(this.report.request?.current.total, 0),
        per_second: this.numberFormat(this.report.request?.current.per.second, 0),
        per_minute: this.numberFormat(this.report.request?.current.per.minute, 0),
        per_hour: this.numberFormat(this.report.request?.current.per.hour, 0),
        per_day: this.numberFormat(this.report.request?.current.per.day, 0),

        chart: {
          data: {
            labels: Object.keys(this.report.request?.chart || {}).map(i => this.labelFormat(i, this.groupBy)),
            datasets: [{
              label: 'Requests',
              data: Object.values(this.report.request?.chart || {}).map(i => this.numberFormat(i, 0)),
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
          reqs: {
            columns: [
              {
                data: "start",
                title: "Date",
                render: (data) => {
                  return this.dateFormat('Y-m-d H:i:s', data);
                }
              },
              {
                data: "duration",
                title: "End Time",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(tolerance.request.duration, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              },
              {
                data: "memory",
                title: "Memory",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(tolerance.request.memory, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.sizeFormat(data, 'MB')}</span>`;
                }
              },
              {
                data: "profile_count",
                title: "Count",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(tolerance.request.profile_count, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.numberFormat(data, 0)}</span>`;
                }
              },
              {
                data: "http_code",
                title: "Code",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(tolerance.request.http_code, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${data}</span>`;
                }
              },
              {
                data: "method",
                title: "Method",
                className: 'text-end'
              },
              {
                data: "url",
                title: "URL"
              },
              {
                data: "error_count",
                title: `<i class="fa fa-exclamation-circle"></i>`,
                render: (data) => {
                  if (data === 0) return '';
                  return `<span class="badge bg-danger text-white">${data}</span>`;
                }
              }
            ],
            options: {
              order: [[0, 'desc']]
            },
            view: (event) => {
              let request_id = null, target = event.target;
              while (target && target.nodeName !== 'TR') {
                target = target.parentNode;
              }
              if (!target) {
                return;
              }

              request_id = this.$refs['dt-reqs'].$refs.dt.dt.row(target).data()?.id;
              request_id && window.open(analysis.geturl('request', {request_id}), '_blank');
            }
          },
          keys: {
            columns: [
              {
                data: "key",
                title: "Request Key",
                render(data) {
                  return data;
                }
              },
              {
                data: "hits",
                title: "Hits",
                className: 'text-center',
                render: (data) => {
                  return this.numberFormat(data, 0);
                }
              },
              {
                data: "avg",
                title: "Avg",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(tolerance.request.duration, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              },
              {
                data: "min",
                title: "Min",
                className: 'text-center min',
                render: (data) => {
                  let color = this.toleranceColors(tolerance.request.duration, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              },
              {
                data: "max",
                title: "Max",
                className: 'text-center max',
                render: (data) => {
                  let color = this.toleranceColors(tolerance.request.duration, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              },
              {
                data: "last",
                title: "Last",
                className: 'text-center last',
                orderable: false,
                render: (data) => {
                  let color = this.toleranceColors(tolerance.request.duration, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              }
            ],
            options: {
              order: [[2, 'desc']]
            },
            view: (event) => {
              let request_id = null, target = event.target;
              if (!target.classList.contains('badge')) {
                return;
              }

              while (target && target.nodeName !== 'TD') {
                target = target.parentNode;
              }
              if (!target) {
                return;
              }

              let row = this.$refs['dt-keys'].$refs.dt.dt.row(target).data();
              if (target.classList.contains('min')) {
                request_id = row?.min_id;
              } else if (target.classList.contains('max')) {
                request_id = row?.max_id;
              } else if (target.classList.contains('last')) {
                request_id = row?.last_id;
              }

              request_id && window.open(analysis.geturl('request', {request_id}), '_blank');
            }
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

<style>
#dt-keys .min .badge,
#dt-keys .max .badge,
#dt-keys .last .badge,
#dt-reqs tbody tr {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
}

#dt-keys .min .badge:hover,
#dt-keys .max .badge:hover,
#dt-keys .last .badge:hover,
#dt-reqs tbody tr:hover .badge {
  transform: scale(1.15);
}

#dt-reqs tbody tr:hover {
  transform: scale(1.01);
}

#dt-reqs tbody tr:hover td {
  background: #eee
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