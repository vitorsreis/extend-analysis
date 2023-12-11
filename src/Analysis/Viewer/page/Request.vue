<!--
  - This file is part of vsr extend analysis
  - @author Vitor Reis <vitor@d5w.com.br>
  -->

<template>
  <div v-if="loading">
    Getting data...
  </div>

  <div v-if="!loading && !report" class="badge bg-danger">
    Request #{{ id }} not found
  </div>

  <div v-if="!loading && report" class="container-fluid vh-100 w-100 d-flex flex-column align-content-stretch">
    <div class="row">
      <div class="col-md-4">
        <table class="table table-sm mb-0" style="table-layout:fixed">
          <tbody>
          <tr>
            <td><b>Request</b></td>
            <td>#{{ report.id }} {{ report.key }}</td>
          </tr>
          <tr>
            <td><b>Response</b></td>
            <td>
              <span :class="'badge bg-' + http_code.color.bg + ' text-' + http_code.color.text">
                {{ http_code.value }}
              </span>&nbsp;
              <span :class="'badge bg-' + duration.color.bg + ' text-' + duration.color.text">
                {{ duration.value }}
              </span>
            </td>
          </tr>
          <tr>
            <td><b>Profile Count</b></td>
            <td>
              <span :class="'badge bg-' + profile_count.color.bg + ' text-' + profile_count.color.text">
                {{ profile_count.value }}
              </span>
            </td>
          </tr>
          <tr>
            <td><b>Memory</b></td>
            <td>
              <span :class="'badge bg-' + memory.color.bg + ' text-' + memory.color.text">
                {{ memory.value }}
              </span>&nbsp;
              <span :class="'badge bg-' + memory_peak.color.bg + ' text-' + memory_peak.color.text">
                {{ memory_peak.value }}
              </span>
            </td>
          </tr>
          <tr>
            <td><b>Error</b></td>
            <td>
              <div class="bg-secondary-subtle">
                <table class="table table-sm mb-0">
                  <tbody>
                  <tr v-for="(value, key) in report.error">
                    <td><b>{{ key }}</b></td>
                    <td>#{{ value.code }} {{ value.message }} on file {{ value.file }}:{{ value.line }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td><b>Extra</b></td>
            <td>
              <div class="bg-secondary-subtle">
                <table class="table table-sm mb-0">
                  <tbody>
                  <tr v-for="(value, key) in report.extra">
                    <td><b>{{ key }}</b></td>
                    <td>{{ value }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td colspan="2" class="hr"></td>
          </tr>
          <tr>
            <td><b>Date</b></td>
            <td>{{ analysis.format.date('Y-m-d H:i:s', report.start) }}</td>
          </tr>
          <tr>
            <td><b>URL</b></td>
            <td>{{ report.method }} {{ report.url }}</td>
          </tr>
          <tr>
            <td><b>IP</b></td>
            <td>{{ report.ip }}</td>
          </tr>
          <tr>
            <td><b>Referer</b></td>
            <td>{{ report.referer }}</td>
          </tr>
          <tr>
            <td><b>User-Agent</b></td>
            <td>{{ report.useragent }}</td>
          </tr>
          <tr>
            <td colspan="2" class="hr"></td>
          </tr>
          <tr>
            <td><b>GET</b></td>
            <td>
              <div class="bg-secondary-subtle">
                <table class="table table-sm mb-0">
                  <tbody>
                  <tr v-for="(value, key) in report.get">
                    <td><b>{{ key }}</b></td>
                    <td>{{ value }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td><b>POST</b></td>
            <td>
              <div class="bg-secondary-subtle">
                <table class="table table-sm mb-0">
                  <tbody>
                  <tr v-for="(value, key) in report.post">
                    <td><b>{{ key }}</b></td>
                    <td>{{ value }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td><b>RAW_POST</b></td>
            <td>
              <div class="bg-secondary-subtle">
                {{ report.raw_post }}
              </div>
            </td>
          </tr>
          <tr>
            <td><b>HEADERS</b></td>
            <td>
              <div class="bg-secondary-subtle">
                <table class="table table-sm mb-0">
                  <tbody>
                  <tr v-for="(value, key) in report.headers">
                    <td><b>{{ key }}</b></td>
                    <td>{{ value }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td><b>COOKIES</b></td>
            <td>
              <div class="bg-secondary-subtle">
                <table class="table table-sm mb-0">
                  <tbody>
                  <tr v-for="(value, key) in report.cookies">
                    <td><b>{{ key }}</b></td>
                    <td>{{ value }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td><b>SERVER</b></td>
            <td>
              <div class="bg-secondary-subtle">
                <table class="table table-sm mb-0">
                  <tbody>
                  <tr v-for="(value, key) in report.server">
                    <td><b>{{ key }}</b></td>
                    <td>{{ value }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
          <tr>
            <td><b>INC. FILES</b></td>
            <td>
              <div class="bg-secondary-subtle">
                <table class="table table-sm mb-0">
                  <tbody>
                  <tr v-for="(value, key) in report.inc_files">
                    <td><b>{{ key }}</b></td>
                    <td>{{ value }}</td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
      <div class="col-md-8 d-flex justify-content-center position-relative">
        <div class="profiles position-absolute p-5" v-html="profile" style="top:0;left:0"/>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Request",
  data() {
    return {
      id: null,
      loading: true,
      report: false
    }
  },
  mounted() {
    let params = new URLSearchParams(window.location.search);
    this.id = params.get('id') || false;
    if (!this.id) this.loading = false;
    else this.load(this.id);
  },
  methods: {
    async load(id) {
      this.report = await analysis.action('request-data', {id})
      this.loading = false;
    },
    draw(profile, last_time) {
      let html = '', starting = last_time === false, duration, color;

      html += '<div class="profile">';
      if (last_time) {
        duration = profile.start - last_time;
        color = analysis.format.tolerance(analysis.settings.tolerance.request.duration, duration * 1000);
        html += `<div class="interval text-${color.bg}">${analysis.format.second(duration)}</div>`;
      }

      duration = profile.duration;
      color = analysis.format.tolerance(analysis.settings.tolerance.request.duration, duration * 1000);
      if (color.bg === 'secondary-subtle') color = {bg: 'secondary', text: 'white'};
      if (profile.parent_id === -2) color = {bg: 'dark', text: 'white'};
      html += `<div class="badge bg-${color.bg} text-${color.text}">`;
      html += `${profile.key}`;
      if (profile.parent_id !== -2) html += `<br>${analysis.format.second(duration)}`;
      html += `</div>`;
      if (profile.children && profile.children.length > 0) {
        html += '<div class="profile-children">';

        last_time = profile.start;
        for (let i of profile.children) {
          html += this.draw(i, last_time);
          last_time = i.end;
        }

        if (starting) {
          html += this.draw({
            start: profile.end,
            end: profile.end,
            key: 'End',
            parent_id: -2
          }, last_time);
        }
        html += '</div>';
      }

      html += '</div>';

      return html;
    }
  },
  computed: {
    http_code() {
      return {
        color: analysis.format.tolerance(analysis.settings.tolerance.http_code, this.report.http_code),
        value: this.report.http_code
      }
    },
    duration() {
      return {
        color: analysis.format.tolerance(analysis.settings.tolerance.request.duration, this.report.duration * 1000),
        value: analysis.format.second(this.report.duration)
      }
    },
    profile_count() {
      return {
        color: analysis.format.tolerance(analysis.settings.tolerance.request.profile_count, this.report.profile_count),
        value: this.report.profile_count
      }
    },
    memory() {
      return {
        color: analysis.format.tolerance(analysis.settings.tolerance.request.memory, this.report.memory),
        value: analysis.format.size(this.report.memory, 'MB')
      }
    },
    memory_peak() {
      return {
        color: analysis.format.tolerance(analysis.settings.tolerance.request.memory_peak, this.report.memory_peak),
        value: analysis.format.size(this.report.memory_peak, 'MB')
      }
    },

    profile() {
      let result = {};

      for (let i of Object.values(this.report.profile).reverse()) {
        if (typeof result[i.parent_id] === 'undefined') result[i.parent_id] = {
          id: i.parent_id,
          children: []
        }

        result[i.parent_id].children.push({
          ...i,
          children: (typeof result[i.index] !== 'undefined' ? result[i.index].children : []).reverse()
        });
        delete result[i.index];
      }

      result = result[-1]?.children || [];

      return this.draw(result[0], false);
    }
  }
}
</script>

<style scoped>
.container-fluid > .row > div {
  min-height: 100vh;
  max-height: 100vh;
  overflow: auto
}

table td:nth-child(1) {
  width: 80px;
  text-align: right
}

table table td:nth-child(1) {
  width: 1%
}

.bg-secondary-subtle {
  max-width: 100%;
  max-height: 300px;
  overflow: auto;
  display: inline-flex
}

.bg-secondary-subtle * {
  background: var(--bs-secondary-bg-subtle) !important
}

td.hr {
  background: var(--bs-secondary-bg-subtle);
  padding: 5px
}
</style>

<style>
.profile {
  display: inline-flex;
  flex-direction: column;
  flex-wrap: wrap;
  text-align: center;
  position: relative;
  min-width: 100px;
  max-width: 100%;
}

.profile:has(+.profile):before,
.profile:has(+.profile):after {
  content: "";
  position: absolute;
}

.profile:has(+.profile):before {
  content: "↑";
  font-size: 40px;
  right: -3.5px;
  top: -15px;
  color: rgb(108, 117, 125);
  z-index: 1
}

.profile:has(+.profile):after {
  border: 3px solid rgb(108, 117, 125);
  border-top-width: 0;
  border-left-width: 0;
  bottom: 5px;
  right: 5px;
  top: 5px;
  width: 10px
}

.profile .badge {
  margin: 0 15px;
  text-align: left;
}

.profile .interval {
  background: #eee;
  margin: 7px 0 13px;
  font-size: 10px;
  font-weight: bold;
}

.profile .interval:before {
  content: "↓";
  position: absolute;
  font-size: 40px;
  top: -20px;
  left: 50%;
  transform: translateX(-50%);
  color: rgb(108, 117, 125);
  z-index: -1
}

.profile:has(.bg-danger):before,
.profile:has(.bg-danger):after,
.profile .interval.text-danger:before {
  border-color: rgb(220, 53, 69);
  color: rgb(220, 53, 69);
}


.profile:has(.bg-warning):before,
.profile:has(.bg-warning):after,
.profile .interval.text-warning:before {
  border-color: rgb(255, 193, 7);
  color: rgb(255, 193, 7);
}
</style>