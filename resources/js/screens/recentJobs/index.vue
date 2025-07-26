<script>
import JobRow from './job-row.vue';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
    data() {
        return {
            ready: false,
            loadingNewEntries: false,
            hasNewEntries: false,
            page: 1,
            perPage: 50,
            totalPages: 1,
            jobs: [],
            dateRange: [], // [startDate, endDate]
            usesDateSearch: window.Horizon.search_by_date,
            scheme: localStorage.getItem('scheme') ?? 'system',
            isDark: false,

        };
    },

    components: {
        JobRow,
        VueDatePicker
    },

    computed: {
        isDark() {
            if (this.scheme === 'dark') return true;
            if (this.scheme === 'light') return false;
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
    },


    mounted() {
        this.updateIsDark();

        this.updatePageTitle();

        window.addEventListener('storage', (e) => {
            if (e.key === 'scheme') {
                this.scheme = e.newValue ?? 'system';
                this.updateIsDark();
            }
        });




        // Başlangıç ve bitiş tarihleri: bugün 00:00 - 23:59
        const today = new Date();
        const start = new Date(today);
        start.setHours(0, 0, 0, 0);
        const end = new Date(today);
        end.setHours(23, 59, 0, 0);
        this.dateRange = [start, end];

        this.loadJobs();
    },
    watch: {
        '$route'() {
            this.updatePageTitle();
            this.page = 1;
            this.dateRange = [];
            this.loadJobs();
        },
        scheme(newVal) {
            localStorage.setItem('scheme', newVal);
            this.updateIsDark();
        }
    },

    methods: {
        updateIsDark() {
            if (this.scheme === 'dark') {
                this.isDark = true;
            } else if (this.scheme === 'light') {
                this.isDark = false;
            } else {
                this.isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
        },

        updatePageTitle() {
            document.title = this.$route.params.type === 'pending'
                ? 'Horizon – Pending Jobs'
                : this.$route.params.type === 'silenced'
                    ? 'Horizon – Silenced Jobs'
                    : 'Horizon – Completed Jobs';
        },

        onDateChange() {
            this.page = 1;
            this.loadJobs(-1);
        },

        loadJobs(starting = -1, refreshing = false) {
            if (!refreshing) this.ready = false;

            const params = {
                starting_at: starting,
                limit: this.perPage,
            };

            // Tarih aralığı varsa parametrelere ekle
            if (this.dateRange?.[0]) params.date_from = this.formatDate(this.dateRange[0]);
            if (this.dateRange?.[1]) params.date_to = this.formatDate(this.dateRange[1]);

            this.$http.get(
                Horizon.basePath + '/api/jobs/' + this.$route.params.type,
                { params }
            ).then(response => {
                if (!this.$root.autoLoadsNewEntries &&
                    refreshing &&
                    this.jobs.length &&
                    response.data.jobs[0]?.id !== this.jobs[0]?.id
                ) {
                    this.hasNewEntries = true;
                } else {
                    this.jobs = response.data.jobs;
                    this.totalPages = Math.ceil(response.data.total / this.perPage);
                }
                this.ready = true;
            });
        },

        formatDate(date) {
            const pad = (n) => (n < 10 ? '0' + n : n);
            const d = new Date(date);
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
        },

        loadNewEntries() {
            this.jobs = [];
            this.loadJobs(-1, false);
            this.hasNewEntries = false;
        },

        refreshJobsPeriodically() {
            if (this.page !== 1) return;
            this.loadJobs(-1, true);
        },

        previous() {
            this.loadJobs((this.page - 2) * this.perPage - 1);
            this.page -= 1;
            this.hasNewEntries = false;
        },

        next() {
            this.loadJobs(this.page * this.perPage - 1);
            this.page += 1;
            this.hasNewEntries = false;
        },
    }
};
</script>

<template>
    <div>
        <poll @poll="refreshJobsPeriodically" />

        <div class="card overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between">

                <div>
                    <h2 class="h6 m-0" v-if="$route.params.type === 'pending'">Pending Jobs</h2>
                    <h2 class="h6 m-0" v-if="$route.params.type === 'completed'">Completed Jobs</h2>
                    <h2 class="h6 m-0" v-if="$route.params.type === 'silenced'">Silenced Jobs</h2>
                </div>

                <div class="d-flex align-items-center">
                    <VueDatePicker v-model="dateRange" range multi-calendars :enable-time-picker="true"
                        :format="'yyyy-MM-dd HH:mm'" placeholder="Select a date" class="me-3"
                        @update:model-value="onDateChange" teleport="body" :dark="isDark" />
                </div>
            </div>

            <div v-if="!ready"
                class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg class="icon spin me-2 fill-text-color" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path
                        d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z">
                    </path>
                </svg>
                <span>Loading...</span>
            </div>

            <div v-if="ready && jobs.length === 0"
                class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>There aren't any jobs.</span>
            </div>

            <table v-if="ready && jobs.length > 0" class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th class="text-end" v-if="$route.params.type === 'pending'">Queued</th>
                        <th v-if="$route.params.type !== 'pending'">Queued</th>
                        <th v-if="$route.params.type !== 'pending'">Completed</th>
                        <th class="text-end" v-if="$route.params.type !== 'pending'">Runtime</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                        <td colspan="100" class="text-center card-bg-secondary py-1">
                            <small>
                                <a href="#" v-if="!loadingNewEntries" @click.prevent="loadNewEntries">Load New
                                    Entries</a>
                            </small>
                            <small v-if="loadingNewEntries">Loading...</small>
                        </td>
                    </tr>
                    <component v-for="job in jobs" :key="job.id" is="job-row" :job="job"></component>
                </tbody>
            </table>

            <div v-if="ready && jobs.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-sm" :disabled="page === 1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-sm" :disabled="page >= totalPages">Next</button>
            </div>
        </div>
    </div>
</template>