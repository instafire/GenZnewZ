<?php

namespace Botble\Setting\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Setting\Http\Requests\EmailSettingRequest;

class EmailSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        Assets::addScriptsDirectly('vendor/core/core/setting/js/email.js');

        $mailer = old('email_driver', setting('email_driver', config('mail.default')));

        $this
            ->setUrl(route('settings.email.update'))
            ->setSectionTitle(trans('core/setting::setting.panel.email'))
            ->setSectionDescription(trans('core/setting::setting.panel.email_description'))
            ->contentOnly()
            ->setActionButtons(view('core/setting::partials.email.action-buttons', ['form' => $this->getFormOption('id')])->render())
            ->setValidatorClass(EmailSettingRequest::class)
            ->add(
                'email_driver',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('core/setting::setting.email.mailer'))
                    ->choices(apply_filters('core_email_mailers', [
                        'smtp' => 'SMTP',
                        'mailgun' => 'Mailgun',
                        'ses' => 'SES',
                        'postmark' => 'Postmark',
                        'resend' => 'Resend',
                        'log' => 'Log (for testing only, will not send email)',
                        'array' => 'Array (for testing only, will not send email)',
                    ] + (function_exists('proc_open') ? ['sendmail' => 'Sendmail'] : [])))
                    ->selected($mailer)
                    ->placeholder(trans('core/setting::setting.general.select'))
                    ->addAttribute('data-bb-toggle', 'collapse')
                    ->addAttribute('data-bb-target', '.email-fields')
            )
            ->addOpenCollapsible('email_driver', 'smtp', $mailer === 'smtp', ['class' => 'email-fields'])
            ->add(
                'email_port',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('core/setting::setting.email.port'))
                    ->value(old('email_port', setting('email_port', config('mail.mailers.smtp.port'))))
                    ->placeholder(trans('core/setting::setting.email.port_placeholder'))
                    ->helperText(trans('core/setting::setting.email.port_helper'))
            )
            ->add(
                'email_host',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.host'))
                    ->value(old('email_host', setting('email_host', config('mail.mailers.smtp.host'))))
                    ->placeholder(trans('core/setting::setting.email.host_placeholder'))
                    ->helperText(trans('core/setting::setting.email.host_helper'))
                    ->maxLength(255)
            )
            ->add(
                'email_username',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.username'))
                    ->value(old('email_username', setting('email_username', config('mail.mailers.smtp.username'))))
                    ->placeholder(trans('core/setting::setting.email.username_placeholder'))
                    ->helperText(trans('core/setting::setting.email.username_helper'))
                    ->maxLength(120)
            )
            ->add(
                'email_password',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.password'))
                    ->value(old('email_password', setting('email_password', config('mail.mailers.smtp.password'))))
                    ->placeholder(trans('core/setting::setting.email.password_placeholder'))
                    ->helperText(trans('core/setting::setting.email.password_helper'))
                    ->maxLength(255)
                    ->attributes([
                        'type' => 'password',
                    ])
            )
            ->add(
                'email_local_domain',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.local_domain'))
                    ->value(old('email_local_domain', setting('email_local_domain', config('mail.mailers.smtp.local_domain'))))
                    ->placeholder(trans('core/setting::setting.email.local_domain_placeholder'))
                    ->helperText(trans('core/setting::setting.email.local_domain_helper'))
                    ->maxLength(120)
            )
            ->addCloseCollapsible('email_driver', 'smtp')
            ->addOpenCollapsible('email_driver', 'mailgun', $mailer === 'mailgun', ['class' => 'email-fields'])
            ->add(
                'email_mail_gun_domain',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.mail_gun_domain'))
                    ->value(old('email_mail_gun_domain', setting('email_mail_gun_domain')))
                    ->placeholder(trans('core/setting::setting.email.mail_gun_domain_placeholder'))
                    ->helperText(trans('core/setting::setting.email.mail_gun_domain_helper'))
                    ->maxLength(120)
            )
            ->when(! BaseHelper::hasDemoModeEnabled(), function (): void {
                $this->add(
                    'email_mail_gun_secret',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(trans('core/setting::setting.email.mail_gun_secret'))
                        ->value(old('email_mail_gun_secret', setting('email_mail_gun_secret')))
                        ->placeholder(trans('core/setting::setting.email.mail_gun_secret_placeholder'))
                        ->helperText(trans('core/setting::setting.email.mail_gun_secret_helper'))
                        ->maxLength(120)
                );
            })
            ->add(
                'email_mail_gun_endpoint',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.mail_gun_endpoint'))
                    ->value(old('email_mail_gun_endpoint', setting('email_mail_gun_endpoint', 'api.mailgun.net')))
                    ->placeholder(trans('core/setting::setting.email.mail_gun_endpoint_placeholder'))
                    ->helperText(trans('core/setting::setting.email.mail_gun_endpoint_helper'))
                    ->maxLength(120)
            )
            ->addCloseCollapsible('email_driver', 'mailgun')
            ->addOpenCollapsible('email_driver', 'ses', $mailer === 'ses', ['class' => 'email-fields'])
            ->add(
                'email_ses_key',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.ses_key'))
                    ->value(old('email_ses_key', setting('email_ses_key', config('services.ses.key'))))
                    ->placeholder(trans('core/setting::setting.email.ses_key_placeholder'))
                    ->helperText(trans('core/setting::setting.email.ses_key_helper'))
                    ->maxLength(120)
            )
            ->when(! BaseHelper::hasDemoModeEnabled(), function (): void {
                $this->add(
                    'email_ses_secret',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(trans('core/setting::setting.email.ses_secret'))
                        ->value(old('email_ses_secret', setting('email_ses_secret', config('services.ses.secret'))))
                        ->placeholder(trans('core/setting::setting.email.ses_secret_placeholder'))
                        ->helperText(trans('core/setting::setting.email.ses_secret_helper'))
                        ->maxLength(120)
                );
            })
            ->add(
                'email_ses_region',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.ses_region'))
                    ->value(old('email_ses_region', setting('email_ses_region', config('services.ses.region'))))
                    ->placeholder(trans('core/setting::setting.email.ses_region_placeholder'))
                    ->helperText(trans('core/setting::setting.email.ses_region_helper'))
                    ->maxLength(120)
            )
            ->addCloseCollapsible('email_driver', 'ses')
            ->addOpenCollapsible('email_driver', 'postmark', $mailer === 'postmark', ['class' => 'email-fields'])
            ->when(! BaseHelper::hasDemoModeEnabled(), function (): void {
                $this->add(
                    'email_postmark_token',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(trans('core/setting::setting.email.postmark_token'))
                        ->value(old('email_postmark_token', setting('email_postmark_token', config('services.postmark.token'))))
                        ->placeholder(trans('core/setting::setting.email.postmark_token_placeholder'))
                        ->helperText(trans('core/setting::setting.email.postmark_token_helper'))
                        ->maxLength(120)
                );
            })
            ->addCloseCollapsible('email_driver', 'postmark')
            ->addOpenCollapsible('email_driver', 'resend', $mailer === 'resend', ['class' => 'email-fields'])
            ->when(! BaseHelper::hasDemoModeEnabled(), function (): void {
                $this->add(
                    'email_resend_key',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(trans('core/setting::setting.email.resend_key'))
                        ->value(old('email_resend_key', setting('email_resend_key', config('services.resend.key'))))
                        ->placeholder(trans('core/setting::setting.email.resend_key_placeholder'))
                        ->helperText(trans('core/setting::setting.email.resend_key_helper'))
                        ->maxLength(120)
                );
            })
            ->addCloseCollapsible('email_driver', 'resend')
            ->addOpenCollapsible('email_driver', 'sendmail', old('email_driver', $mailer) === 'sendmail', ['class' => 'email-fields'])
            ->add(
                'email_sendmail_path',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.sendmail_path'))
                    ->value(old('email_sendmail_path', setting('email_sendmail_path', config('mail.mailers.sendmail.path'))))
                    ->placeholder(trans('core/setting::setting.email.sendmail_path'))
                    ->helperText(trans('core/setting::setting.email.default') . ': <code>' . config('mail.mailers.sendmail.path') . '</code>')
                    ->maxLength(120)
            )
            ->addCloseCollapsible('email_driver', 'sendmail')
            ->addOpenCollapsible('email_driver', 'log', $mailer === 'log', ['class' => 'email-fields'])
            ->add(
                'email_log_channel',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(trans('core/setting::setting.email.log_channel'))
                    ->choices(array_combine(
                        $logChannels = array_keys(config('logging.channels', [])),
                        $logChannels,
                    ))
                    ->selected(old('email_log_channel', setting('email_log_channel', config('mail.mailers.log.channel'))) ?: 'single')
                    ->placeholder(trans('core/setting::setting.general.select'))
                    ->helperText(trans('core/setting::setting.email.log_channel_helper'))
            )
            ->addCloseCollapsible('email_driver', 'log')
            ->add(
                'email_from_name',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.sender_name'))
                    ->value(old('email_from_name', setting('email_from_name', config('mail.from.name'))))
                    ->placeholder(trans('core/setting::setting.email.sender_name_placeholder'))
                    ->helperText(trans('core/setting::setting.email.sender_name_helper'))
                    ->maxLength(60)
            )
            ->add(
                'email_from_address',
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('core/setting::setting.email.sender_email'))
                    ->value(old('email_from_address', setting('email_from_address', get_admin_email()->first())))
                    ->placeholder(trans('core/setting::setting.email.sender_email_placeholder', ['default' => 'admin@example.com']))
                    ->helperText(trans('core/setting::setting.email.sender_email_helper'))
                    ->maxLength(60)
                    ->wrapperAttributes([
                        'class' => 'mb-0',
                    ])
            );
    }
}
