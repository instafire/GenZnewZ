import '@tabler/core/js/src/autosize'
import '@tabler/core/js/src/dropdown'
import '@tabler/core/js/src/tooltip'
import '@tabler/core/js/src/popover'
import '@tabler/core/js/src/switch-icon'
import '@tabler/core/js/src/tab'
import * as bootstrap from 'bootstrap'
import * as tabler from '@tabler/core/js/src/tabler'

globalThis.bootstrap = bootstrap
globalThis.tabler = tabler

import setupProgress from './base/progress'

setupProgress({
    showSpinner: true,
})
