<?php

declare(strict_types=1);

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Security\User;

/**
 * Továrna na přihlašovací formulář.
 */
final class SignInFormFactory
{
    use Nette\SmartObject;

    /** @var User Uživatel. */
    private User $user;

    /**
     * Konstruktor s injektovanou továrnou na formuláře a uživatelem.
     * @param User        $user    automaticky injektovaný object uživatel
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Vytváří a vrací přihlašovací formulář.
     * @param callable $onSuccess specifická funkce, která se vykoná po úspěšném odeslání formuláře
     * @return Form přihlašovací formulář
     */
    public function create(callable $onSuccess): Form
    {
        $form = new Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = Html::el('div')->class('login-form');
        //$renderer->wrappers['group']['container'] = null;
        //  $renderer->wrappers['group']['label'] = 'h3';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('row');
        $renderer->wrappers['control']['container'] = Html::el('div')->class('col bg-primary is-marginless padding-cell-lg text-right');
        //$renderer->wrappers['label']['container'] = Html::el('div')->class('col-2 bg-primary text-light is-marginless padding-cell' );

        $form->addText('username')
            ->setRequired('Zadej prosím své uživatelské jméno.')
            ->setAttribute('placeholder', 'Uživatelské jméno');
        $form->addPassword('password')
            ->setRequired('Zadej prosím své heslo.')
            ->setAttribute('placeholder', 'Heslo');
        $form->addSubmit('send', Html::el('i')->class('material-icons')->title('Přihlásit')->setText('login'))
        ->setHtmlAttribute('class', 'button success icon-only small');

        
        $form->onSuccess[] = function (Form $form, \stdClass $values) use ($onSuccess): void {
            try {
                $this->user->login($values->username, $values->password);
            } catch (Nette\Security\AuthenticationException $e) {
                $form->addError('Zadané jméno nebo heslo není správně.');
                return;
            }
            $onSuccess();
        };
        return $form;
    }
}