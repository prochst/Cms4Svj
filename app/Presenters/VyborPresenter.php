<?php

declare(strict_types=1);

namespace app\Presenters;

use Nette;
use Nette\Application\Responses;
use Nette\Utils\FileSystem;
use App\Model\DataManager;
use App\Model\TableManager;
use App\Model\RoleManager;
use App\Model\FileManager;

final class VyborPresenter extends Nette\Application\UI\Presenter
{
    /** @var FileManager */
    private $fileManager;

   /** @var DataManager */
   private $dataManager;
    
   /** @var TableManager */
   private $tableMng;
   private $info;

   /** Datové soubory, které jsou potřeba */
   private string $tblInfo = 'svj_info';
   private string $tblPage = 'vybor_page';
   
   /**
    * @param Filemanager
    * @param DataManager
    */
   public function __construct(Filemanager $fileManager, DataManager $dataManager) {
       parent::__construct();
       $this->fileManager = $fileManager;
       $this->tableMng = new TableManager($dataManager);
   }  

   protected function startup(): void {
       /* Ověření uživatel a jeho role */
        parent::startup();
        if (!$this->user->isLoggedIn()) {
            $this->redirect('Login:default', ['backlink' => $this->storeRequest()]);
        }
        if (!$this->user->isAllowed(RoleManager::ROLE_VYBOR)) {
            $this->flashMessage('K této stránce nemáte oprávnění', 'error');
            $this->redirect('Svj:default');
        }     
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
            $this->redirect('Svj:');
        }
    }

    public function renderDefault(): void {
        $this->initTable($this->tblInfo);
        $this->info = clone $this->tableMng;
        $this->initTable($this->tblPage);

        $this->template->fileMng = $this->fileManager;
        $this->template->texy = new \Texy\Texy;
        $this->template->subsite = 'vybor';
        $this->template->info = $this->info;
        $this->template->table = $this->tableMng;       
    }

}