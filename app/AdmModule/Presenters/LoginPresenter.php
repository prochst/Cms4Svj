<?php

declare(strict_types=1);

namespace App\AdmModule\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Forms\SignInFormFactory;

/**
 * Stránka pro přihlášení do administrátoské části
 */
final class LoginPresenter extends Nette\Application\UI\Presenter
{
    /** @persistent */
    public $backlink = '';
   
    /** @var SignInFormFactory */
    private $signInForm;

    /**
     * @param SignInFormFactory
     */
    public function __construct(SignInFormFactory $signInForm) {
        parent::__construct();
        $this->signInForm = $signInForm;
    }     

    public function renderDefault(): void {
        $this['signInForm'];            
    } 

    protected function createComponentSignInForm(): Form
    {
        return $this->signInForm->create(function (): void {
            $this->restoreRequest($this->backlink);
            $this->redirect('Adm:default');
        });
    }

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->redirect('Adm:default');
    }    
}
