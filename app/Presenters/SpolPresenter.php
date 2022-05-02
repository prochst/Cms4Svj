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

final class SpolPresenter extends Nette\Application\UI\Presenter
{
    /** @var FileManager */
    private $fileManager;

    /** @var DataManager */
   private $dataManager;
    
   /** @var TableManager */
   private $tableMng;
   private $info;
   private $members;
   private $vybor;

   /** Datové soubory, které jsou potřeba */
   private string $tblInfo = 'svj_info';
   private string $tblPage = 'clen_page';
   private string $tblMembers = 'svj_members';
   private string $tblVybor = 'svj_vybor';
   
   
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
        if (!$this->user->isAllowed(RoleManager::RES_CLEN)) {
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
        $this->initTable($this->tblMembers);
        $this->members = clone $this->tableMng;
        $this->members->sortRows('byt');
        $this->initTable($this->tblVybor);
        $this->vybor = clone $this->tableMng;
        $this->initTable($this->tblPage);

        $this->template->fileMng = $this->fileManager;
        $this->template->texy = new \Texy\Texy;
        $this->template->subsite = 'spol';
        $this->template->info = $this->info;
        $this->template->members = $this->members;
        $this->template->vybor = $this->vybor;
        $this->template->table = $this->tableMng;       

    }

    /**
     * Stáhne soubor se zobrazeného seznamu souborů
     * 
     * @param string $filePath - relativní cesta k souboru
     */
    public function actionFile(string $filePath): void {
        $fullpath = $this->fileManager->fileRoot . $filePath;
        if(file_exists($fullpath)) {
            $this->sendResponse(new Responses\FileResponse($fullpath));
        }
        else {
            $this->flashMessage('Soubor '. $filePath . ' neexistuje', 'error');
            $this->redirect('spol:');            
        }
    }
}