<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Model\DataManager;
use App\Model\TableManager;
use App\Forms\SignInFormFactory;


final class SVJPresenter extends BaseFrontPresenter
{
   /** @var DataManager */
   private $dataManager;
    
   /** @var SignInFormFactory */
   private $signInForm;

    
   /** @var TableManager */
   private $tableMng;
   private $info;
   private $news;

   /** Datové soubory, které jsou potřeba */
   private string $tblInfo = 'svj_info';
   private string $tblPage = 'main_page';
   private string $tblNews = 'news';
   
   
   /**
    * @param DataManager
    * @param SignInFormFactory
    */
   public function __construct(DataManager $dataManager, SignInFormFactory $signInForm) {
       parent::__construct();
       $this->tableMng = new TableManager($dataManager);
       $this->signInForm = $signInForm;
   }  
   
    /**
     * Načte do správce dat data a definice pro daný datový soubor
     * 
     * @param $tblName - název datového souboru
     */ 
    private function initTable (string $tblName ) {
        try{
            $this->tableMng->readData($tblName);
        }
        catch (\Exception $e){
            $this->flashMessage($e->getMessage());
            $this->redirect('Adm:');
        }
    }

    public function renderDefault(): void {
        $this->initTable($this->tblNews);
        $this->news = clone $this->tableMng;
        $this->news->filterRows('show', 'Ano');
        $this->news->sortRows('publish_date', 'Desc');
        $this->initTable($this->tblInfo);
        $this->info = clone $this->tableMng;
        $this->initTable($this->tblPage);
        $this->template->texy = new \Texy\Texy;
        $this->template->subsite = false;
        $this->template->info = $this->info;
        $this->template->table = $this->tableMng;
        $this->template->news = $this->news;
    } 

}
    
