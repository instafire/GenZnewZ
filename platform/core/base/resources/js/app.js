import { axios, HttpClient } from './utilities'

window._ = require('lodash')

window.axios = axios

window.$httpClient = new HttpClient()

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
    },
})

$(() => {
    setTimeout(() => {
        if (typeof siteAuthorizedUrl === 'undefined' || typeof isAuthenticated === 'undefined' || !isAuthenticated) {
            return
        }

        const $reminder = $('[data-bb-toggle="authorized-reminder"]')

        if ($reminder.length) {
            return
        }

        // Check if we should make the membership authorization request
        const shouldMakeAuthRequest = () => {
            const lastAuthTime = localStorage.getItem('membership_authorization_time')
            if (!lastAuthTime) {
                return true
            }

            // Call once every 3 days (259200000 ms)
            const threeDaysInMs = 3 * 24 * 60 * 60 * 1000
            return Date.now() - parseInt(lastAuthTime) > threeDaysInMs
        }

        if (!shouldMakeAuthRequest()) {
            return
        }

        $httpClient
            .makeWithoutErrorHandler()
            .get(siteAuthorizedUrl, { verified: true })
            .then(() => {
                // Store the current time as the last authorization time
                localStorage.setItem('membership_authorization_time', Date.now().toString())
            })
            .catch((error) => {
                if (!error.response || error.response.status !== 200) {
                    return
                }

                if (! error.response.data.data?.html) {
                    return
                }

                $(error.response.data.data.html).prependTo('body')
                $(document).find('.alert-license').slideDown()

                // Even on error, we've made the request, so store the time
                localStorage.setItem('membership_authorization_time', Date.now().toString())
            })
    }, 1000)
})
