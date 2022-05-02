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
 * Obecný preasenter pro zobrazení a editaci 
 * víceřádkového datového souboru
 */
final class DataListPresenter extends BaseAdmPresenter
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
     * Smazání vybraného záznamu z datového souboru
     * 
     * @param string $tblName - název datoého souboru
     * @param string $uid - UID záznamu, který se má smazat
     */
    public function actionRemove(string $tblName, string $uid = null): void {
        $this->initTable($tblName);
        try {
            $this->tableMng->deleteRow($uid);
            $this->flashMessage('Záznam byl odstraněn.');
        } 
        catch  (\Exception $e) {
            $this->flashMessage('Záznam se nepodařilo odstranit.');    
        }
        $this->redirect('DataList:default', $tblName);
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
        if ($uid) {
            if (!($row = $this->tableMng->getRow($uid))) {
                $this->flashMessage('Záznam nebyl nalezen.');
            } else {
                $row['uid'] = $uid;
                try {
                    $this['editorForm']->setDefaults($row);
                } catch (\Exception $e) {
                    $this->flashMessage($e->getMessage(), 'error');
                }
            }
        } 
    }

    protected function createComponentEditorForm(): Form {
        $form = $this->dataEditForm->create($this->tableMng, $this->fileMng);
        $form->onSuccess[] = function (Form $form, Array $values) {
            $tblName = Arrays::pick($values, 'tblName');
            $uid = Arrays::pick($values, 'uid');
            //doplnění read only hodnot, které formulář nevrací
            $values['update'] = date("Y-m-d 0:0:0");
            try {
                $this->tableMng->updateRow($uid, $values);
                $this->flashMessage('Záznam byl uložen.','info');
                $this->redirect('DataList:default', $tblName);
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Záznam  již existuje.','error');
            }
        };
        return $form;
    }    
}