{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
            <tr>
                <td class="bb-content bb-pb-0" align="center">
                    <table class="bb-icon bb-icon-lg bb-bg-blue" cellspacing="0" cellpadding="0">
                        <tbody>
                        <tr>
                            <td valign="middle" align="center">
                                <img src="{{ 'lock-open' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon">
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'core/acl::auth.settings.email.templates.password_reminder.email_title' | trans }}</h1>
                </td>
            </tr>
            <tr>
                <td class="bb-content bb-text-center">
                    <p>{{ 'core/acl::auth.settings.email.templates.password_reminder.email_message' | trans }}</p>
                </td>
            </tr>
            <tr>
                <td class="bb-content bb-text-center bb-pt-0 bb-pb-xl">
                    <table cellspacing="0" cellpadding="0">
                        <tbody>
                            <tr>
                                <td align="center">
                                    <table cellpadding="0" cellspacing="0" border="0" class="bb-bg-blue bb-rounded bb-w-auto">
                                        <tbody>
                                        <tr>
                                            <td align="center" valign="top" class="lh-1">
                                                <a href="{{ reset_link }}" class="bb-btn bb-bg-blue bb-border-blue">
                                                    <span class="btn-span">{{ 'core/acl::auth.settings.email.templates.password_reminder.button_text' | trans }}</span>
                                                </a>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="bb-content bb-text-muted bb-pt-0 bb-text-center">
                    {{ 'core/acl::auth.settings.email.templates.password_reminder.trouble_text' | trans({'reset_link': reset_link}) }}
                </td>
            </tr>
        </tbody>
    </table>
</div>

{{ footer }}