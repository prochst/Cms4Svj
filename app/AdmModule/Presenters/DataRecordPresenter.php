<?php

declare(strict_types=1);

namespace App\AdmModule\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Arrays;
use App\Model\RoleManager;
use App\Model\DataManager;
use App\Model\TableManager;
use App\Forms\DataEditFormFactory;
use App\Model\FileManager;


/**
 * Obecný presenter pro zobrazení a editaci 
 * jednořádkového datového souboru
 */
final class DataRecordPresenter extends BaseAdmPresenter
{
    /** @var DataManager */
    private $dataManager;
    
    /** @var TableManager */
    private $tableMng;

    /** @var DataEditFormFactory */
    private $dataEditForm;

    /** @var FileManager */
    private $fileMng;    

    /**
     * @param DataManager
     * @param DataEditFormFactory
     * @param FileManager
     */
    public function __construct(DataManager $dataManager, DataEditFormFactory $dataEditForm, FileManager $fileMng) {
        parent::__construct();
        $this->tableMng = new TableManager($dataManager);
        $this->dataEditForm = $dataEditForm;
        $this->fileMng = $fileMng;
    }  
    
    /**
     * Načte do správce dat data a definice pro daný datový soubor
     * 
     * @param $tblName - název datového souboru
     */ 
    private function initTable (string $tblName ) {
        if (str_contains(RoleManager::RES_DENYTBL, $tblName) && !$this->user->isAllowed(RoleManager::RES_NASTAV)) {
            $this->redirect('Login:noacl');
        }
        try{
            $this->tableMng->readData($tblName);
        }
        catch (\Exception $e){
            $this->flashMessage($e->getMessage());
            $this->redirect('Adm:');
        }

    }
    
    public function renderDefault(string $tblName): void {
        $this->initTable($tblName);
        $this->template->table = $this->tableMng;
        $this->template->texy = new \Texy\Texy;
    }
    
     /**
     * Editace vybraného záznamu z datového souboru
     * 
     * @param string $tblName - název datoého souboru
     * @param string $uid - UID záznamu, který se má editovat
     */
    public function actionEditor(string $tblName, string $uid = null) {
        $this->initTable($tblName);
        $this->template->table = $this->tableMng;
        //stránka má jen jeden záznam
        if (empty($this->tableMng->getRows())) {
            $this->flashMessage('Záznam nebyl nalezen.','error');
        } else {
            $uid = key($this->tableMng->getRows());
            $row = $this->tableMng->getRow($uid);
            $row['uid'] = $uid;
            try {
                $this['editorForm']->setDefaults($row);
            } catch (\Exception $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }
        } 
    }
    
    protected function createComponentEditorForm(): Form {
        $form = $this->dataEditForm->create($this->tableMng, $this->fileMng);
        $form->onSuccess[] = function (Form $form, Array $values) {
            $tblName = Arrays::pick($values, 'tblName');
            $uid = Arrays::pick($values, 'uid');
            try {
                $this->tableMng->updateRow($uid, $values);
                $this->flashMessage('Záznam byl uložen.','info');
                $this->redirect('DataRecord:default', $tblName);
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Záznam  již existuje.','error');
            }
        };
        return $form;
    }    

}