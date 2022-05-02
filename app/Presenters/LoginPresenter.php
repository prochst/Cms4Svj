<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Forms\SignInFormFactory;
use App\Model\DataManager;
use App\Model\TableManager;



final class LoginPresenter extends Nette\Application\UI\Presenter
{
    /** @persistent */
    public $backlink = '';
   
    /** @var SignInFormFactory */
    private $signInFactory;

    /** @var DataManager */
   private $dataManager;
    
   /** @var TableManager */
   private $tableMng;
   private $info;

   /** Datové soubory, které jsou potřeba */
   private string $tblInfo = 'svj_info';
   private string $tblPage = 'main_page';

    /**
     * @param DataManager
     * @param SignInFormFactory
     */
    public function __construct(DataManager $dataManager, SignInFormFactory $signInFactory) {
        parent::__construct();
        $this->tableMng = new TableManager($dataManager);
        $this->signInFactory = $signInFactory;
    }     

    private function initTable (string $tblName ) {
        try{
            $this->tableMng->readData($tblName);
        }
        catch (\Exception $e){
            $this->flashMessage($e->getMessage());
            $this->redirect('Svj:');
        }
    }

    public function renderDefault(): void {
        $this->initTable($this->tblInfo);
        $this->info = clone $this->tableMng;
        $this->initTable($this->tblPage);

        $this->template->texy = new \Texy\Texy;
        $this->template->subsite = false;
        $this->template->info = $this->info;
        $this->template->table = $this->tableMng;
        $this['signInForm'];            
    } 

    protected function createComponentSignInForm(): Form
    {
        return $this->signInFactory->create(function (): void {
            $this->restoreRequest($this->backlink);
            $this->redirect('Svj:default');
        });
    }

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->redirect('Svj:default');
    }  
}
