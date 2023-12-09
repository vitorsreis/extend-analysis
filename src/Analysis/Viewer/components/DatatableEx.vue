<!--
  - This file is part of vsr extend analysis
  - @author Vitor Reis <vitor@d5w.com.br>
  -->

<template>
  <Datatables.netVue ref="dt" class="table table-sm" :columns="columns" :options="options"/>
</template>

<script>
export function getlen() {
  let height = window.innerHeight - 325;
  let pageLength = Math.floor(height / 25);
  if (pageLength < 10) pageLength = 10;
  return pageLength;
}

export const cursor = {
  current: 0,
  next: 0
}

export default {
  name: "DatatableEx",
  props: {
    columns: {
      type: Array,
      default: [],
      required: true
    },
    options: {
      type: Object,
      default: {},
      required: false
    },
    draw: {
      type: Object,
      default: {
        data: [],
        recordsTotal: 0,
        recordsFiltered: 0,
        error: false
      },
      required: false
    },
    loading: {
      type: Boolean,
      default: true,
      required: false
    },
    force: {
      type: Function,
      default: () => {},
      required: false
    }
  },
  setup(props) {
    props.options = {
      responsive: true,
      autoWidth: false,
      paging: true,
      pageLength: getlen(),

      searching: false,
      lengthChange: false,

      serverSide: true,
      processing: true,

      ajax(request, callback, api) {
        if (!props.loading) {
          cursor.next = request.draw;
          props.loading = true;
        } else {
          if (cursor.current !== request.draw) {
            cursor.next = request.draw;
            return;
          }
        }

        callback({
          draw: request.draw,
          recordsTotal: props.draw.recordsTotal || 0,
          recordsFiltered: props.draw.recordsFiltered || 0,
          data: (props.draw.data || []).slice(0, api?._iDisplayLength || 0),
          error: props.draw.error || false
        });
      },

      ...props.options
    }
  },
  mounted() {
    window.addEventListener('resize', () => this.resize());
    this.resize();
  },
  watch: {
    loading(newValue, oldValue) {
      this.redraw()
    }
  },

  methods: {
    redraw(data = undefined, recordsTotal = undefined, recordsFiltered = undefined, error = false) {
      if (data !== undefined) {
        cursor.current = cursor.next;
        this.draw.data = data;
        this.draw.recordsTotal = recordsTotal;
        this.draw.recordsFiltered = recordsFiltered;
        this.draw.error = error;
        this.$props.loading = false;
      }
      this.$refs.dt.dt.draw(false);
    },
    resize() {
      if (!this?.$refs?.dt?.dt) return;
      this.$refs.dt.dt.page.len(getlen());
    }
  }
}
</script>

<style>
.datatable {
  height: 100%;
}

.datatable .dataTables_wrapper {
  display: flex;
  flex-direction: column;
  height: 100%;
  justify-content: space-evenly
}

.dataTables_wrapper > .dt-row + .row {
  height: 34px
}

.dataTables_wrapper > .dt-row {
  height: calc(100% - 34px)
}

.dataTables_processing {
  border: 0;
  background: transparent
}

.dataTables_empty {
  vertical-align: middle
}
</style>