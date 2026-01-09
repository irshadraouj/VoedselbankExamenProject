<?php

class Password_reset
{
    public function form()
    {
        $action_id = ee()->functions->fetch_action_id('Password_reset', 'reset_password');
        if (! $action_id) {
            return '';
        }

        $return = ee()->TMPL->fetch_param('return');
        $form_id = ee()->TMPL->fetch_param('form_id', 'password-reset-form');
        $form_class = ee()->TMPL->fetch_param('form_class', '');

        $hidden = [
            'ACT' => $action_id,
        ];

        if (! empty($return)) {
            $hidden['RET'] = ee()->functions->create_url($return);
        }

        $data = [
            'id' => $form_id,
            'class' => $form_class,
            'hidden_fields' => $hidden,
        ];

        $form_open = ee()->functions->form_declaration($data);
        $tagdata = ee()->TMPL->tagdata;

        return $form_open . $tagdata . '</form>';
    }

    public function reset_password()
    {
        $return_success_link = ee()->functions->determine_return();
        $return_error_link = ee()->functions->determine_error_return();

        if (ee()->session->userdata('is_banned') === true) {
            return ee()->output->show_form_error(['general' => 'Niet geautoriseerd.'], 'submission');
        }

        $identifier = trim((string) ee()->input->post('identifier'));
        if ($identifier === '') {
            return ee()->output->show_form_error(['identifier' => 'Vul een gebruikersnaam of e-mail in.'], 'submission');
        }

        $member = ee('Model')->get('Member')->filter('username', $identifier)->first();
        if (! $member) {
            $member = ee('Model')->get('Member')->filter('email', $identifier)->first();
        }

        if (! $member) {
            return ee()->output->show_form_error(['identifier' => 'Gebruiker niet gevonden.'], 'submission');
        }

        if ($member->isBanned()) {
            return ee()->output->show_form_error(['general' => 'Niet geautoriseerd.'], 'submission');
        }

        $password = (string) ee()->input->post('password');
        $password_confirm = (string) ee()->input->post('password_confirm');

        if ($password === '') {
            return ee()->output->show_form_error(['password' => 'Vul een nieuw wachtwoord in.'], 'submission');
        }

        if ($password_confirm === '') {
            return ee()->output->show_form_error(['password_confirm' => 'Bevestig het wachtwoord.'], 'submission');
        }

        $validation_rules = [
            'password' => 'validPassword|passwordMatchesSecurityPolicy|matches[password_confirm]'
        ];

        $validation_data = [
            'username' => $member->username,
            'password' => $password,
            'password_confirm' => $password_confirm,
        ];

        $validation = ee('Validation')->make($validation_rules)->validate($validation_data);
        if ($validation->isNotValid()) {
            return ee()->output->show_form_error($validation, 'submission');
        }

        $member->hashAndUpdatePassword($password);
        $member->save();

        $site_name = stripslashes(ee()->config->item('site_name'));
        $return = $return_success_link ?: ee()->functions->fetch_site_index();

        $data = [
            'title' => 'Wachtwoord gewijzigd',
            'heading' => 'Wachtwoord gewijzigd',
            'content' => 'Je wachtwoord is aangepast. Log opnieuw in met je nieuwe wachtwoord.',
            'link' => [$return, $site_name],
            'redirect' => $return,
            'rate' => '4',
        ];

        return ee()->output->show_message($data, true, $return_error_link);
    }
}
