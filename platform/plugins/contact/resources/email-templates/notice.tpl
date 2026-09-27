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
                                    <img src="{{ 'mail' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/contact::contact.email_templates.notice_title' | trans }}</h1>
                </td>
            </tr>
            <tr>
                <td>
                    <table cellpadding="0" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="bb-content">
                                    <p>{{ 'plugins/contact::contact.email_templates.notice_greeting' | trans }}</p>

                                    <h4>{{ 'plugins/contact::contact.email_templates.notice_message_details' | trans }}</h4>

                                    <table class="bb-table" cellspacing="0" cellpadding="0">
                                        <thead>
                                            <tr>
                                                <th width="80px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {% if contact_name %}
                                                <tr>
                                                    <td>{{ 'plugins/contact::contact.email_templates.field_name' | trans }}</td>
                                                    <td class="bb-font-strong bb-text-left"> {{ contact_name }} </td>
                                                </tr>
                                            {% endif %}
                                            {% if contact_subject %}
                                                <tr>
                                                    <td>{{ 'plugins/contact::contact.email_templates.field_subject' | trans }}</td>
                                                    <td class="bb-font-strong bb-text-left"> {{ contact_subject }} </td>
                                                </tr>
                                            {% endif %}
                                            {% if contact_email %}
                                                <tr>
                                                    <td>{{ 'plugins/contact::contact.email_templates.field_email' | trans }}</td>
                                                    <td class="bb-font-strong bb-text-left"> {{ contact_email }} </td>
                                                </tr>
                                            {% endif %}
                                            {% if contact_address %}
                                                <tr>
                                                    <td>{{ 'plugins/contact::contact.email_templates.field_address' | trans }}</td>
                                                    <td class="bb-font-strong bb-text-left"> {{ contact_address }} </td>
                                                </tr>
                                            {% endif %}
                                            {% if contact_phone %}
                                                <tr>
                                                    <td>{{ 'plugins/contact::contact.email_templates.field_phone' | trans }}</td>
                                                    <td class="bb-font-strong bb-text-left"> {{ contact_phone }} </td>
                                                </tr>
                                            {% endif %}
                                            {% for key, value in contact_custom_fields %}
                                            <tr>
                                                <td>{{ key }}:</td>
                                                <td class="bb-font-strong bb-text-left"> {{ value }} </td>
                                            </tr>
                                            {% endfor %}
                                            {% if contact_content %}
                                                <tr>
                                                    <td colspan="2">{{ 'plugins/contact::contact.email_templates.field_content' | trans }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="bb-font-strong"><i>{{ contact_content }}</i></td>
                                                </tr>
                                            {% endif %}
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="bb-content bb-text-center bb-pt-0 bb-pb-xl" align="center">
                                    <p>{{ 'plugins/contact::contact.email_templates.notice_reply_instruction' | trans({'contact_email': contact_email}) }}</p> <br />
                                    <a href="mailto:{{ contact_email }}" class="bb-btn bb-bg-blue bb-border-blue">{{ 'plugins/contact::contact.email_templates.notice_answer_button' | trans }}</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>

{{ footer }}
