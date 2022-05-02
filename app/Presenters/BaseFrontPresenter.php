<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\AbortExceptio;
use App\Model\RoleManager;


abstract class BaseFrontPresenter extends Nette\Application\UI\Presenter
{

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