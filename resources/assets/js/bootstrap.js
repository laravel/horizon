// bs
window.$ = window.jQuery = require('jquery')
require('bootstrap')

// vue
window.Vue = require('vue')
window.Bus = new Vue({name: 'bus'})

Vue.config.errorHandler = (err, vm, info) => {
    console.error(err)
}
Vue.component('loader', require('./components/Status/Loader.vue'))

// axios
window.axios = require('axios')
axios.interceptors.response.use(
    (response) => response,
    (error) => Promise.reject(error.response)
)

// vue-tippy
Vue.use(require('vue-tippy'), {
    arrow: true,
    touchHold: true,
    inertia: true,
    performance: true,
    flipDuration: 0,
    popperOptions: {
        modifiers: {
            preventOverflow: {enabled: false},
            hide: {enabled: false}
        }
    }
})
