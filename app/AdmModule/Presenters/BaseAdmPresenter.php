<?php

declare(strict_types=1);

namespace App\AdmModule\Presenters;

use Nette;
use Nette\Application\AbortExceptio;
use App\Model\RoleManager;


abstract class BaseAdmPresenter extends Nette\Application\UI\Presenter
{
    /**
     * Pro všechny potomky otestuje zda je přihlášený uživatel
     * a pokud ne přesměruje na přihlašovací stránku
     */
    protected function startup(): void {
        parent::startup();
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Login:default', ['backlink' => $this->storeRequest()]);
        }
        if (!$this->user->isAllowed(RoleManager::RES_OBSAH)) {
            $this->redirect('Login:noacl');
        }     
    }

    /**
     * Nastaví pro všechny potomky objekt přihlášeného uživatele do šablony
     */
    public function beforeRender(): void {
        parent::beforeRender();
        if ($this->user->isLoggedIn()) {
            $this->template->user = $this->user;
        }
    }
}