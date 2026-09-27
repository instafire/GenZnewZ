<template>
    <form id="license-form" ref="formRef" @submit.prevent="onSubmit">
        <slot
            v-bind="{
                initialized,
                loading,
                verified,
                license,
                deactivateLicense,
                resetLicense,
            }"
        ></slot>
    </form>
</template>

<script>
export default {
    props: {
        id: {
            type: String,
            default: () => null,
            required: true,
        },
        verifyUrl: {
            type: String,
            default: () => null,
            required: true,
        },
        activateLicenseUrl: {
            type: String,
            default: () => null,
            required: true,
        },
        deactivateLicenseUrl: {
            type: String,
            default: () => null,
            required: true,
        },
        resetLicenseUrl: {
            type: String,
            default: () => null,
            required: true,
        },
    },

    data() {
        return {
            initialized: null,
            loading: true,
            verified: false,
            license: null,
        }
    },

    mounted() {
        this.verifyLicense()
    },

    methods: {
        async verifyLicense() {
            // Check if we should make the license verification request
            const shouldVerifyLicense = () => {
                const lastVerifyTime = localStorage.getItem('license_verification_time')
                const isVerified = localStorage.getItem('license_is_verified') === 'true'

                if (!lastVerifyTime) {
                    return true
                }

                const now = Date.now()
                const lastTime = parseInt(lastVerifyTime)

                // If verified, check once every 3 days
                if (isVerified) {
                    const threeDaysInMs = 3 * 24 * 60 * 60 * 1000
                    return now - lastTime > threeDaysInMs
                } else {
                    // If not verified, check once per day
                    const oneDayInMs = 24 * 60 * 60 * 1000
                    return now - lastTime > oneDayInMs
                }
            }

            // Try to get cached license data
            const cachedLicense = localStorage.getItem('license_data')
            const cachedVerified = localStorage.getItem('license_is_verified') === 'true'

            if (cachedLicense && !shouldVerifyLicense()) {
                try {
                    this.verified = cachedVerified
                    this.license = JSON.parse(cachedLicense)
                    this.initialized = true
                    this.loading = false
                    return Promise.resolve()
                } catch (e) {
                    // If there's an error parsing the cached data, proceed with the request
                }
            }

            return $httpClient
                .makeWithoutErrorHandler()
                .get(this.verifyUrl)
                .then(({ data }) => {
                    this.verified = true
                    this.license = data.data

                    // Store the verification result
                    localStorage.setItem('license_verification_time', Date.now().toString())
                    localStorage.setItem('license_is_verified', 'true')
                    localStorage.setItem('license_data', JSON.stringify(data.data))
                })
                .catch((data) => {
                    if (data.response && data.response.status === 400) {
                        Botble.showError(data.response.data.message)
                    }

                    // Store that we attempted verification but it failed
                    localStorage.setItem('license_verification_time', Date.now().toString())
                    localStorage.setItem('license_is_verified', 'false')
                    localStorage.removeItem('license_data')
                })
                .finally(() => {
                    this.initialized = true
                    this.loading = false
                })
        },

        async onSubmit() {
            const formData = new FormData(this.$refs.formRef)

            return this.doActivateLicense(formData)
        },

        async resetLicense() {
            const formData = new FormData(this.$refs.formRef)

            return this.doResetLicense(formData)
        },

        async deactivateLicense() {
            this.loading = true

            return $httpClient
                .make()
                .post(this.deactivateLicenseUrl)
                .then(() => {
                    this.verified = false

                    // Update localStorage to reflect deactivation
                    localStorage.setItem('license_verification_time', Date.now().toString())
                    localStorage.setItem('license_is_verified', 'false')
                    localStorage.removeItem('license_data')
                })
                .finally(() => {
                    this.loading = false
                })
        },

        async doActivateLicense(formData) {
            this.loading = true

            return $httpClient
                .make()
                .postForm(this.activateLicenseUrl, formData)
                .then(({ data }) => {
                    this.verified = true
                    this.license = data.data
                    Botble.showSuccess(data.message)

                    // Update localStorage to reflect activation
                    localStorage.setItem('license_verification_time', Date.now().toString())
                    localStorage.setItem('license_is_verified', 'true')
                    localStorage.setItem('license_data', JSON.stringify(data.data))
                })
                .finally(() => {
                    this.loading = false
                })
        },

        async doResetLicense(formData) {
            this.loading = true

            return $httpClient
                .make()
                .postForm(this.resetLicenseUrl, formData)
                .then(({ data }) => {
                    this.verified = false

                    Botble.showSuccess(data.message)

                    // Update localStorage to reflect reset
                    localStorage.setItem('license_verification_time', Date.now().toString())
                    localStorage.setItem('license_is_verified', 'false')
                    localStorage.removeItem('license_data')
                })
                .finally(() => {
                    this.loading = false
                })
        },
    },
}
</script>
