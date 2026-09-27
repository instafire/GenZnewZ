class CacheManagement {
    init() {
        $(document).on('click', '.btn-clear-cache', (event) => {
            event.preventDefault()

            let _self = $(event.currentTarget)

            Botble.showButtonLoading(_self)

            $httpClient
                .make()
                .post(_self.data('url'), { type: _self.data('type') })
                .then(({ data }) => {
                    Botble.showSuccess(data.message)

                    // Refresh the page to update cache size display
                    if (_self.data('type') === 'clear_cms_cache') {
                        setTimeout(() => {
                            window.location.reload()
                        }, 1000)
                    }
                })
                .finally(() => Botble.hideButtonLoading(_self))
        })
    }
}

$(() => {
    new CacheManagement().init()
})
