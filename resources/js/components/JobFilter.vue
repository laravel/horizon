<script type="text/ecmascript-6">
export default {
    props: ["status"],
    data() {
        return {
            jobNameSearch: null,
            createdAtFromSearch: null,
            createdAtToSearch: null,
            test: null,
        }
    },
    methods: {
        loadJobNames(jobName) {
            return new Promise(resolve => {
                if (jobName.length < 3) {
                    return resolve([])
                }

                let queryParams = new URLSearchParams({status: this.status, name: jobName}).toString()

                this.$http.get(Horizon.basePath + '/api/jobs/search?' + queryParams)
                    .then(response => resolve(response.data))
            })
        },

        setJobName(selectedJobName) {
            this.jobNameSearch = selectedJobName
            this.fireEventUpdated()
        },

        clear() {
            this.$refs["jobNameField"].setValue(null)
            this.jobNameSearch = null
            this.createdAtFromSearch = null
            this.createdAtToSearch = null

            this.fireEventUpdated()
        },

        fireEventUpdated() {
            this.$emit("updated", {
                job_name: this.jobNameSearch,
                created_at_from: this.createdAtFromSearch,
                created_at_to: this.createdAtToSearch,
            })
        }
    }
}
</script>

<template>
    <div class="card p-2 mb-2">
        <div class="row">
            <div class="col-5">
                <label>Jobs name: </label>

                <autocomplete
                    ref="jobNameField"
                    :search="loadJobNames"
                    placeholder="Search for a Job name"
                    aria-label="Search for a Job name"
                    :debounceTime="500"
                    @submit="setJobName"
                ></autocomplete>
            </div>
            <div class="col-3">
                <label>Created at from: </label>
                <input class="datetime" type="datetime-local" v-model="createdAtFromSearch" @change="fireEventUpdated">
            </div>
            <div class="col-3">
                <label>Created at to: </label>
                <input class="datetime" type="datetime-local" v-model="createdAtToSearch" @change="fireEventUpdated">
            </div>

            <div class="col-1 pt-2">
                <label></label>
                <button class="btn btn-danger mt-4" @click="clear">X</button>
            </div>
        </div>
    </div>
</template>
