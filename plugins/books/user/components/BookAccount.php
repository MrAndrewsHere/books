<?php

namespace Books\User\Components;

use Db;
use Exception;
use Flash;
use October\Rain\Auth\AuthException;
use RainLab\User\Components\Account;
use RainLab\User\Components\Session;
use RainLab\User\Facades\Auth;
use Redirect;
use Request;
use ValidationException;

class BookAccount extends Account
{
    public function componentDetails()
    {
        return [
            'name' => 'Book Account Component',
            'description' => 'Extend rainlab user account component',
        ];
    }

    public function onLogout()
    {
        return (new Session())->onLogout();
    }

    public function onSignin()
    {
        try {
            return parent::onSignin();
        } catch (AuthException $authException) {
            throw new ValidationException(['auth' => $authException->getMessage()]);
        }
    }

    public function onAdultAgreementSave()
    {
        $this->user()->update(['see_adult' => post('action') === 'accept', 'asked_adult_agreement' => 1]);

        return Redirect::refresh();
    }

    public function onFetch(): array
    {
        if ($this->user()?->required_post_register) {
            return [
                '#post_register_container' => $this->renderPartial('auth/postRegisterContainer', ['user' => $this->user()]),
            ];
        }

        if ($this->user()?->requiredAskAdult()) {
            return ['#adult_modal_spawn' => $this->renderPartial('auth/adult-modal', ['active' => 1])];
        }

        return [];
    }

    public function onRegisterProxy()
    {
        try {
            return Db::transaction(function () {
                $redirect = $this->onRegister();
                $user = Auth::getUser();
                $user->update(['required_post_register' => 0]);

                return $redirect;
            });
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    public function onPostRegister()
    {
        try {
            $user = Auth::getUser();
            $data = array_diff_assoc(post(), $user->only($user->getFillable()));
            $user->addValidationRule('password', 'nullable');
            $user->addValidationRule('password_confirmation', 'nullable');
            $user->removeValidationRule('password', 'required:create');
            $user->removeValidationRule('email', 'required');
            if ($user->username) {
                $user->removeValidationRule('username', 'required');
            }

            Db::transaction(function () use ($user, $data) {
                $user->update(array_merge($data, ['required_post_register' => 0]));
                if ($data['username'] ?? false) {
                    $user->profile->update(['username' => $data['username']]);
                }
            });

            return $this->makeRedirection();
        } catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }
}
