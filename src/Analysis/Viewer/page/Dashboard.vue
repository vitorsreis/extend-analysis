<!--
  - This file is part of vsr extend analysis
  - @author Vitor Reis <vitor@d5w.com.br>
  -->

<template>
  <div class="container-fluid vh-100 w-100 d-flex flex-column align-content-stretch">
    <div class="position-fixed d-flex justify-content-between align-items-center mt-2 me-3"
         style="right:0;top:0;width:85px">
      <span><span :class="'text-'+reportStatus.class">⬤</span> {{ reportStatus.text }}</span>
      <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#settings">
        <i class="fa fa-cog"></i>
      </button>
    </div>
    <div class="row">
      <div class="col-md-4 col-12">
        <div class="row">
          <div id="current-charts" class="col-md-2 col-12 p-1 pb-2">
            <DoughnutChat id="chart-cpu" :data="server.cpu.chart.data" :options="server.cpu.chart.options"/>
            <DoughnutChat id="chart-disk" :data="server.disk.chart.data" :options="server.disk.chart.options"/>
            <DoughnutChat id="chart-memory" :data="server.memory.chart.data" :options="server.memory.chart.options"/>
          </div>
          <div id="current"
               class="col-md-10 col-12 h-100 d-flex flex-column align-items-start justify-content-between py-2">
            <span class="badge text-dark-emphasis">Up: {{ server.uptime }}</span>
            <span class="badge text-dark-emphasis">
              CPU: <span :class="'text-'+server.cpu.color.bg">{{ server.cpu.percent }}</span>
            </span>
            <span class="badge text-dark-emphasis">
              Disk: <span :class="'text-'+server.disk.color.bg">{{ server.disk.percent }}</span>
              ({{ server.disk.used }}/{{ server.disk.total }}, {{ server.disk.free }} free)
            </span>
            <span class="badge text-dark-emphasis">
              Memory: <span :class="'text-'+server.memory.color.bg">{{ server.memory.percent }}</span>
              ({{ server.memory.used }}/{{ server.memory.total }}, {{ server.memory.free }} free,
              {{ server.memory.cache }} cache)
            </span>
            <span class="badge text-dark-emphasis">
              Swap: {{ server.swap.used }}/{{ server.swap.total }}, {{ server.swap.free }} free,
              {{ server.swap.cache }} cache
            </span>
            <span class="badge text-dark-emphasis">
              Tasks: {{ server.thread.running }} running, {{ server.thread.total }} total,
              {{ server.thread.sleeping }} sleeping, {{ server.thread.stopped }} stopped, {{ server.thread.zombie }} zombie
            </span>
            <span class="badge text-dark-emphasis">
              Requests: <span :class="'text-'+request.color.bg">{{ request.avg }}</span>,
              hits: {{ request.total }} ({{ request.per_second }}/s, {{ request.per_minute }}/m,
              {{ request.per_hour }}/h, {{ request.per_day }}/d)
            </span>
            <span class="badge text-danger-emphasis">
              DB Size: <span :class="'text-'+server?.db_size_color.bg">{{ server?.db_size }}</span>
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
      <div class="col-md-4 my-2" @click="request.dt.keys.click">
        <DatatableEx id="dt-keys" ref="dt-keys" :columns="request.dt.keys.columns" :options="request.dt.keys.options"/>
      </div>
      <div class="col-md-8 my-2" @click="request.dt.reqs.click">
        <DatatableEx id="dt-reqs" ref="dt-reqs" :columns="request.dt.reqs.columns" :options="request.dt.reqs.options"/>
      </div>
    </div>
  </div>

  <div class="modal fade" id="settings" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Settings</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <h5>Auto-update settings</h5>
          <div class="row mb-1">
            <label for="input-autoUpdateInterval" class="col-form-label col-sm-4">Interval</label>
            <div class="col-sm-8">
              <div class="input-group">
                <input type="number" min="0" id="input-autoUpdateInterval" class="form-control"
                       v-model="settings.autoUpdateInterval"/>
                <span class="input-group-text">ms</span>
              </div>
            </div>
          </div>

          <h5 class="mt-3">Chart settings</h5>
          <div class="row mb-1">
            <label for="input-chartGroupBy" class="col-form-label col-sm-4">Group by</label>
            <div class="col-sm-8">
              <select id="input-chartGroupBy" class="form-select" v-model="settings.chart.groupBy" @change="updateData">
                <option value="s10">10 Seconds</option>
                <option value="i">Minutes</option>
                <option value="i10">10 Minutes</option>
                <option value="H">Hours</option>
                <option value="d">Days</option>
              </select>
            </div>
          </div>
          <div class="row mb-1">
            <label for="input-chartLength" class="col-form-label col-sm-4">Length</label>
            <div class="col-sm-8">
              <input type="number" min="10" max="1000" id="input-chartLength" class="form-control"
                     v-model="settings.chart.length" @change="updateData"/>
            </div>
          </div>

          <h5 class="mt-3">Tolerance settings</h5>
          <div class="row mb-1">
            <label for="input-tolerance-server-cpu" class="col-form-label col-sm-4">CPU</label>
            <div class="col-sm-8">
              <div class="row g-3">
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-warning">Warning</span>
                    <input type="number" min="0" max="100" id="input-tolerance-server-cpu-warning" class="form-control"
                           v-model="settings.tolerance.server.cpu.warning"/>
                    <span class="input-group-text">%</span>
                  </div>
                </div>
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-danger text-white">Danger</span>
                    <input type="number" min="0" max="100" id="input-tolerance-server-cpu-danger" class="form-control"
                           v-model="settings.tolerance.server.cpu.danger"/>
                    <span class="input-group-text">%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label for="input-tolerance-server-disk" class="col-form-label col-sm-4">Disk</label>
            <div class="col-sm-8">
              <div class="row g-3">
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-warning">Warning</span>
                    <input type="number" min="0" max="100" id="input-tolerance-server-disk-warning" class="form-control"
                           v-model="settings.tolerance.server.disk.warning"/>
                    <span class="input-group-text">%</span>
                  </div>
                </div>
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-danger text-white">Danger</span>
                    <input type="number" min="0" max="100" id="input-tolerance-server-disk-danger" class="form-control"
                           v-model="settings.tolerance.server.disk.danger"/>
                    <span class="input-group-text">%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label for="input-tolerance-server-memory" class="col-form-label col-sm-4">Memory</label>
            <div class="col-sm-8">
              <div class="row g-3">
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-warning">Warning</span>
                    <input type="number" min="0" max="100" id="input-tolerance-server-memory-warning"
                           class="form-control" v-model="settings.tolerance.server.memory.warning"/>
                    <span class="input-group-text">%</span>
                  </div>
                </div>
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-danger text-white">Danger</span>
                    <input type="number" min="0" max="100" id="input-tolerance-server-memory-danger"
                           class="form-control" v-model="settings.tolerance.server.memory.danger"/>
                    <span class="input-group-text">%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label for="input-tolerance-server-db_size" class="col-form-label col-sm-4">DB Size</label>
            <div class="col-sm-8">
              <div class="row g-3">
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-warning">Warning</span>
                    <input type="number" min="0" id="input-tolerance-server-db_size-warning" class="form-control"
                           v-model="settings.tolerance.server.db_size.warning"/>
                    <span class="input-group-text">MB</span>
                  </div>
                </div>
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-danger text-white">Danger</span>
                    <input type="number" min="0" id="input-tolerance-server-db_size-danger" class="form-control"
                           v-model="settings.tolerance.server.db_size.danger"/>
                    <span class="input-group-text">MB</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label for="input-tolerance-request-duration" class="col-form-label col-sm-4">Request Duration</label>
            <div class="col-sm-8">
              <div class="row g-3">
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-warning">Warning</span>
                    <input type="number" min="0" id="input-tolerance-request-duration-warning" class="form-control"
                           v-model="settings.tolerance.request.duration.warning"/>
                    <span class="input-group-text">ms</span>
                  </div>
                </div>
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-danger text-white">Danger</span>
                    <input type="number" min="0" id="input-tolerance-request-duration-danger" class="form-control"
                           v-model="settings.tolerance.request.duration.danger"/>
                    <span class="input-group-text">ms</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label for="input-tolerance-request-memory" class="col-form-label col-sm-4">Request Memory</label>
            <div class="col-sm-8">
              <div class="row g-3">
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-warning">Warning</span>
                    <input type="number" min="0" id="input-tolerance-request-memory-warning" class="form-control"
                           v-model="settings.tolerance.request.memory.warning"/>
                    <span class="input-group-text">MB</span>
                  </div>
                </div>
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-danger text-white">Danger</span>
                    <input type="number" min="0" id="input-tolerance-request-memory-danger" class="form-control"
                           v-model="settings.tolerance.request.memory.danger"/>
                    <span class="input-group-text">MB</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mb-1">
            <label for="input-tolerance-request-profile_count" class="col-form-label col-sm-4">Request Profile
              Count</label>
            <div class="col-sm-8">
              <div class="row g-3">
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-warning">Warning</span>
                    <input type="number" min="0" id="input-tolerance-request-profile_count-warning" class="form-control"
                           v-model="settings.tolerance.request.profile_count.warning"/>
                  </div>
                </div>
                <div class="col">
                  <div class="input-group">
                    <span class="input-group-text bg-danger text-white">Danger</span>
                    <input type="number" min="0" id="input-tolerance-request-profile_count-danger" class="form-control"
                           v-model="settings.tolerance.request.profile_count.danger"/>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
const {Doughnut: DoughnutChat, Line: LineChat} = window.ChartJS;
const DatatableEx = analysis.component('components/DatatableEx.vue');

export function dtPageLength() {
  if (window.innerWidth < 768) return 10;
  let height = window.innerHeight - 325;
  let pageLength = Math.floor(height / 25);
  if (pageLength < 10) pageLength = 10;
  return pageLength;
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
      let result = {bg: 'secondary-subtle', text: 'black'};
      for (let i in colors) {
        if (i !== 'default' && value >= colors[i]) {
          result = i === 'danger'
              ? {bg: 'danger', text: 'white'}
              : {bg: 'warning', text: 'black'};
          break;
        }
      }
      return result;
    },

    resize() {
      let new_len = dtPageLength();

      let old_len_reqs = this.$refs['dt-reqs'].$refs.dt.dt.page.info().length;
      if (old_len_reqs !== new_len) {
        console.log('dt-reqs-page-length-change', old_len_reqs, new_len);
        this.$refs['dt-reqs'].$refs.dt.dt.page.len(new_len);
        this.$refs['dt-reqs'].$refs.dt.dt.draw(false);
      }

      let old_len_keys = this.$refs['dt-keys'].$refs.dt.dt.page.info().length;
      if (old_len_keys !== new_len) {
        console.log('dt-keys-page-length-change', old_len_keys, new_len);
        this.$refs['dt-keys'].$refs.dt.dt.page.len(new_len);
        this.$refs['dt-keys'].$refs.dt.dt.draw(false);
      }

      if (old_len_reqs !== new_len || old_len_keys !== new_len) {
        this.updateData();
      }
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
      this.report = await analysis.action('viewer-data', {
        chartGroupBy: this.settings.chart.groupBy,
        chartLength: this.settings.chart.length,
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

      this.updateDataTimeout = setTimeout(() => this.updateData(), this.settings.autoUpdateInterval);
    }
  },
  data() {
    return {
      updateDataTimeout: null,
      settings: {
        autoUpdateInterval: 1500,
        chart: {
          groupBy: 's10',
          length: 100
        },
        tolerance: {
          server: {
            cpu: {
              danger: 80,
              warning: 60
            },
            disk: {
              danger: 80,
              warning: 60
            },
            memory: {
              danger: 80,
              warning: 60
            },
            db_size: {
              danger: 10 * 1024,
              warning: 5 * 1024
            }
          },
          request: {
            duration: {
              danger: 1000,
              warning: 500
            },
            memory: {
              danger: 30,
              warning: 10
            },
            http_code: {
              danger: 500,
              warning: 400
            },
            profile_count: {
              danger: 1000,
              warning: 500
            },
          }
        }
      },
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
          color: this.toleranceColors(this.settings.tolerance.server.cpu, cpu_percent),
          chart: {
            data: {
              labels: ['Free', 'Usage'],
              datasets: [
                {
                  data: [this.numberFormat(100 - parseFloat(cpu_percent), 1), this.numberFormat(cpu_percent, 1)],
                  backgroundColor: ['rgb(25,135,84)', 'rgba(220,53,69,1)']
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
          color: this.toleranceColors(this.settings.tolerance.server.disk, disk_percent),
          free: this.sizeFormat(this.report.server?.current.disk_free),
          used: this.sizeFormat(this.report.server?.current.disk_used),
          total: this.sizeFormat(this.report.server?.current.disk_total, ' GB'),
          chart: {
            data: {
              labels: ['Free', 'Usage'],
              datasets: [
                {
                  data: [this.numberFormat(100 - disk_percent, 1), this.numberFormat(disk_percent, 1)],
                  backgroundColor: ['rgb(25,135,84)', 'rgba(220,53,69,1)']
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
          color: this.toleranceColors(this.settings.tolerance.server.memory, memory_percent),
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
                  backgroundColor: ['rgb(25,135,84)', 'rgba(220,53,69,1)']
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

        db_size: this.sizeFormat(this.report.server?.current?.db_size || 0, ' MB'),
        db_size_color: this.toleranceColors(this.settings.tolerance.server.db_size, this.report.server?.current?.db_size || 0),

        chart: {
          data: {
            labels: Object.keys(this.report.server?.chart || {}).map(i => this.labelFormat(i, this.settings.chart.groupBy)),
            datasets: [{
              label: 'CPU',
              data: Object.values(this.report.server?.chart || {}).map(i => this.numberFormat(i.cpu, 1)),
              borderWidth: 1,
              pointRadius: 1,
              backgroundColor: 'rgb(220,53,69)',
              borderColor: 'rgb(220,53,69)'
            }, {
              label: 'Memory',
              data: Object.values(this.report.server?.chart || {}).map(i => this.numberFormat(i.mem_used * 100 / this.report.server?.current.mem_total, 1)),
              borderWidth: 1,
              pointRadius: 1,
              backgroundColor: 'rgb(13,110,253)',
              borderColor: 'rgb(13,110,253)'
            }, {
              label: 'Disk',
              data: Object.values(this.report.server?.chart || {}).map(i => this.numberFormat(i.disk_used * 100 / this.report.server?.current.disk_total, 1)),
              borderWidth: 1,
              pointRadius: 1,
              backgroundColor: 'rgb(108,117,125)',
              borderColor: 'rgb(108,117,125)'
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
        color: this.toleranceColors(this.settings.tolerance.request.duration, this.report.request?.current.avg * 1000),
        total: this.numberFormat(this.report.request?.current.total, 0),
        per_second: this.numberFormat(this.report.request?.current.per.second, 0),
        per_minute: this.numberFormat(this.report.request?.current.per.minute, 0),
        per_hour: this.numberFormat(this.report.request?.current.per.hour, 0),
        per_day: this.numberFormat(this.report.request?.current.per.day, 0),

        chart: {
          data: {
            labels: Object.keys(this.report.request?.chart || {}).map(i => this.labelFormat(i, this.settings.chart.groupBy)),
            datasets: [{
              label: 'Requests',
              data: Object.values(this.report.request?.chart || {}).map(i => this.numberFormat(i, 0)),
              borderWidth: 1,
              pointRadius: 1,
              backgroundColor: 'rgb(13,110,253)',
              borderColor: 'rgb(13,110,253)'
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
                className: 'text-center',
                render: (data) => {
                  return this.dateFormat('Y-m-d H:i:s', data);
                }
              },
              {
                data: "duration",
                title: "Duration",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(this.settings.tolerance.request.duration, data * 1000);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              },
              {
                data: "memory",
                title: "Memory",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(this.settings.tolerance.request.memory, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.sizeFormat(data, 'MB')}</span>`;
                }
              },
              {
                data: "profile_count",
                title: "Count",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(this.settings.tolerance.request.profile_count, data);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.numberFormat(data, 0)}</span>`;
                }
              },
              {
                data: "http_code",
                title: "Code",
                className: 'text-center',
                render: (data) => {
                  let color = this.toleranceColors(this.settings.tolerance.request.http_code, data);
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
              order: [[0, 'desc']],
              pageLength: dtPageLength()
            },
            click: (event) => {
              let id = null, target = event.target;

              // Capture change page or change order, and force update data
              if (target.hasAttribute('data-dt-idx') && !target.hasAttribute('aria-current')) {
                setTimeout(() => this.updateData(), 10);
                return;
              }
              if (target.nodeName === 'TH' && target.classList.contains('sorting')) {
                setTimeout(() => this.updateData(), 10);
                return;
              }

              while (target && target.nodeName !== 'TR') {
                target = target.parentNode;
              }
              if (!target) {
                return;
              }

              id = this.$refs['dt-reqs'].$refs.dt.dt.row(target).data()?.id;
              id && window.open(analysis.geturl('request', {id}), '_blank');
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
                  let color = this.toleranceColors(this.settings.tolerance.request.duration, data * 1000);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              },
              {
                data: "min",
                title: "Min",
                className: 'text-center min',
                render: (data) => {
                  let color = this.toleranceColors(this.settings.tolerance.request.duration, data * 1000);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              },
              {
                data: "max",
                title: "Max",
                className: 'text-center max',
                render: (data) => {
                  let color = this.toleranceColors(this.settings.tolerance.request.duration, data * 1000);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              },
              {
                data: "last",
                title: "Last",
                className: 'text-center last',
                orderable: false,
                render: (data) => {
                  let color = this.toleranceColors(this.settings.tolerance.request.duration, data * 1000);
                  return `<span class="badge bg-${color.bg} text-${color.text}">${this.secondFormat(data)}</span>`;
                }
              }
            ],
            options: {
              order: [[2, 'desc']],
              pageLength: dtPageLength()
            },
            click: (event) => {
              let id = null, target = event.target;

              // Capture change page or change order, and force update data
              if (target.hasAttribute('data-dt-idx') && !target.hasAttribute('aria-current')) {
                setTimeout(() => this.updateData(), 10);
                return;
              }
              if (target.nodeName === 'TH' && target.classList.contains('sorting')) {
                setTimeout(() => this.updateData(), 10);
                return;
              }

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
                id = row?.min_id;
              } else if (target.classList.contains('max')) {
                id = row?.max_id;
              } else if (target.classList.contains('last')) {
                id = row?.last_id;
              }

              id && window.open(analysis.geturl('request', {id}), '_blank');
            }
          }
        }
      }
    }
  },
  mounted() {
    this.updateData();
    window.addEventListener('resize', () => this.resize());

    this.settings = {...this.settings, ...JSON.parse(localStorage.getItem('analysis.settings') || "{}")};
    for (let i of document.querySelectorAll('#settings input, #settings select')) {
      i.addEventListener('change', () => localStorage.setItem('analysis.settings', JSON.stringify(this.settings)));
    }
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
  background: rgb(13, 110, 253);
  color: #fff
}

#dt-keys td:nth-child(2),
#dt-keys td:nth-child(3),
#dt-keys td:nth-child(4),
#dt-keys td:nth-child(5),
#dt-keys td:nth-child(6) {
  width: 1%;
  min-width: 64px
}

#dt-reqs td:nth-child(1) {
  width: 1%;
  min-width: 110px
}

#dt-reqs td:nth-child(2),
#dt-reqs td:nth-child(3),
#dt-reqs td:nth-child(4),
#dt-reqs td:nth-child(5),
#dt-reqs td:nth-child(6) {
  width: 1%;
  min-width: 64px
}

#dt-reqs td:nth-child(8) {
  width: 1%
}
</style>

<style scoped>
.input-group-text {
  padding: 0;
  justify-content: center;
  width: 50px;
  font-size: 80%;
  font-weight: bold
}

input + .input-group-text {
  width: 30px
}

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
  min-height: 315px;
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
    max-height: 315px;
    margin-bottom: 30px !important;
  }
}
</style>