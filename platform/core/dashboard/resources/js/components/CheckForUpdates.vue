<template>
    <slot v-bind="{ hasNewVersion, message }"></slot>
</template>

<script>
export default {
    props: {
        checkUpdateUrl: {
            type: String,
            default: () => null,
            required: true,
        },
    },

    data() {
        return {
            hasNewVersion: false,
            message: null,
        }
    },
    mounted() {
        this.checkUpdate()
    },

    methods: {
        checkUpdate() {
            // Check if we should make the update check request
            const shouldCheckUpdate = () => {
                const lastCheckTime = localStorage.getItem('system_update_check_time')
                if (!lastCheckTime) {
                    return true
                }

                // Call once every 15 minutes (900000 ms)
                const fifteenMinutesInMs = 15 * 60 * 1000
                return Date.now() - parseInt(lastCheckTime) > fifteenMinutesInMs
            }

            // Try to get cached update data
            const cachedHasNewVersion = localStorage.getItem('system_update_has_new_version') === 'true'
            const cachedMessage = localStorage.getItem('system_update_message')

            if (cachedHasNewVersion && cachedMessage && !shouldCheckUpdate()) {
                this.hasNewVersion = cachedHasNewVersion
                this.message = cachedMessage
                return
            }

            if (!shouldCheckUpdate()) {
                return
            }

            axios
                .get(this.checkUpdateUrl)
                .then(({ data }) => {
                    // Store the current time as the last check time
                    localStorage.setItem('system_update_check_time', Date.now().toString())

                    if (!data.error && data.data.has_new_version) {
                        this.hasNewVersion = true
                        this.message = data.message

                        // Store the update status
                        localStorage.setItem('system_update_has_new_version', 'true')
                        localStorage.setItem('system_update_message', data.message)
                    } else {
                        // Clear any previous update status
                        localStorage.setItem('system_update_has_new_version', 'false')
                        localStorage.removeItem('system_update_message')
                    }
                })
                .catch(() => {
                    // Even on error, we've made the request, so store the time
                    localStorage.setItem('system_update_check_time', Date.now().toString())
                })
        },
    },
}
</script>
