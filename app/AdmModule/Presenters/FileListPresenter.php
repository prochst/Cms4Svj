<?php

declare(strict_types=1);

namespace App\AdmModule\Presenters;

use Nette;
use App\Model\FileManager;
use Nette\Application\UI\Form;
use Nette\Utils\Html;

/**
 * Souborový manažer pro správu dokumentů, které se zobrazují ve Frontendu
 */
final class FileListPresenter extends BaseAdmPresenter
{
    /** @var FileManager */
    private $fileManager;
    
    /** @parray  */
    private array $curDir;

    /** 
     * @param FileManager
     */
    public function __construct(FileManager $fileManager) {
        parent::__construct();
        $this->fileManager = $fileManager;
        $this->curDir =  ['Name'=>'Soubory','Path'=>''];
    }     

    public function renderDefault(array $dir=['Name'=>'Soubory','Path'=>'']): void {
        $this->curDir = $dir;
        $content = $this->fileManager->getDirContent($this->curDir['Path']);
        $dirTree = $this->fileManager->getDirTree();
        $this->template->content = $content;
        $this->template->dirTree = $dirTree;
        $this->template->curDir = $this->curDir;

    } 

    /**
     * Formuláře dialogových oken pro operace s afresáři a soubory
     */
    protected function createComponentUploadForm(): Form {
        //bdump($this->curDir);
        $form = new Nette\Application\UI\Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = Html::el('div')->class('modal-form');
        $renderer->wrappers['group']['container'] = null;
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('row');
        $renderer->wrappers['control']['container'] = Html::el('div')->class('col is-marginless padding-cell');

        $form->addHidden('curDirName', $this->curDir['Name']);
        $form->addHidden('curDirPath', $this->curDir['Path']);
        $form->addUpload('upload', '');
        $form->addSubmit('send', Html::el('i')->class('material-icons')->title('Nahrát soubor')->setText('file_upload'))
            ->setHtmlAttribute('class', 'button success icon-only small');
        $form->addSubmit('cancel', Html::el('i')->class('material-icons')->title('Zpět')->setText('cancel'))
            ->setHtmlAttribute('class', 'button error icon-only small')
            ->onClick[] = function (Form $form, Array $values) {
                $this->redirect('FileList:default', array(['Name'=>$values['curDirName'],'Path'=>$values['curDirPath']]));
            };
        $form->onSuccess[] = function (Form $form, Array $values) {
            $fullName = $this->fileManager->fileRoot . $values['curDirPath']  . '/' . $values['upload']->name; 
            if (!file_exists($fullName)) {
                try {
                    //bdump($values['upload']);
                    $file = $values['upload'];
                    $file->move($fullName);
                    
                    $this->flashMessage('Soubor ' . $values['curDirPath'] . '/' . $values['upload']->name . ' byl uložen');
                    //$this->redirect('FileList:default', array(['Name'=>$values['curDirName'],'Path'=>$values['curDirPath']]));
                } catch (\Exeption $e) {
                    $this->flashMessage('Soubor se nepodařilo načíst a uložit!','error');
                }
            } else {
                $this->flashMessage('Soubor ' . $values['curDirPath'] . '/' . $values['upload']->name . ' již existuje','error');
            }
        };
        return $form;
    }   
    
    protected function createComponentAddDirForm(): Form {
        //bdump($this->curDir);
        $form = new Nette\Application\UI\Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = Html::el('div')->class('modal-form');
        $renderer->wrappers['group']['container'] = null;
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('row');
        $renderer->wrappers['control']['container'] = Html::el('div')->class('col is-marginless padding-cell');

        $form->addHidden('curDirName', $this->curDir['Name']);
        $form->addHidden('curDirPath', $this->curDir['Path']);
        $form->addText('dirName', '')
            ->setHtmlAttribute('placeholder','Zadej jméno složky');
        $form->addSubmit('save', Html::el('i')->class('material-icons')->title('Vytvořit složku')->setText('save'))
            ->setHtmlAttribute('class', 'button success icon-only small');
        $form->addSubmit('cancel', Html::el('i')->class('material-icons')->title('Zpět')->setText('cancel'))
            ->setHtmlAttribute('class', 'button error icon-only small')
            ->onClick[] = function (Form $form, Array $values) {
                $this->redirect('FileList:default', array(['Name'=>$values['curDirName'],'Path'=>$values['curDirPath']]));
            };
        $form->onSuccess[] = function (Form $form, Array $values) {
            $fullName = $this->fileManager->fileRoot . $values['curDirPath'] . '/' . $values['dirName']; 
            if (!file_exists($fullName)) {
                try {
                    mkdir($fullName, 0777, true); 
                    $this->flashMessage('Složka ' . $this->curDir['Path'] . '/' . $values['dirName'] . ' byla vytvořena');
                } catch (\Exeption $e) {
                    $this->flashMessage('Složku se nepodařilo vytvořit!','error');
                }
            } else {
                $this->flashMessage('Složka již existuje','error');
            }
        };
        return $form;
    }

    protected function createComponentDelDirForm(): Form {
        //bdump($this->curDir);
        $form = new Nette\Application\UI\Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = Html::el('div')->class('modal-form');
        $renderer->wrappers['group']['container'] = null;
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('row');
        $renderer->wrappers['control']['container'] = Html::el('div')->class('col is-marginless padding-cell');

        $form->addHidden('curDirName', $this->curDir['Name']);
        $form->addHidden('curDirPath', $this->curDir['Path']);
        $form->addSubmit('ok', Html::el('i')->class('material-icons')->title('Smazat složku')->setText('delete'))
            ->setHtmlAttribute('class', 'button error icon-only small');
        $form->addSubmit('cancel', Html::el('i')->class('material-icons')->title('Zpět')->setText('cancel'))
            ->setHtmlAttribute('class', 'button success icon-only small')
            ->onClick[] = function (Form $form, Array $values) {
                $this->redirect('FileList:default', array(['Name'=>$values['curDirName'],'Path'=>$values['curDirPath']]));
            };
        $form->onSuccess[] = function (Form $form, Array $values) {
            $fullName = $values['curDirPath']; 
            
            try {
                $this->fileManager->deleteDir($fullName);
                $this->flashMessage('Složka ' . $fullName . ' byla smazána');
                $this->redirect('FileList:default');                    
            } catch (\Exeption $e) {
                $this->flashMessage('Složku ' . $fullName . ' se nepodařilo smazat!','error');
            }
        };
        return $form;
    }
    
    protected function createComponentDelFileForm(): Form {
        $form = new Nette\Application\UI\Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = Html::el('div')->class('modal-form');
        $renderer->wrappers['group']['container'] = null;
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('row');
        $renderer->wrappers['control']['container'] = Html::el('div')->class('col is-marginless padding-cell');

        $form->addHidden('curDirName', $this->curDir['Name']);
        $form->addHidden('curDirPath', $this->curDir['Path']);
        $form->addText('fileName', '')
        ->setHtmlAttribute('id','delfilename')
        ->setHtmlAttribute('readonly');
        $form->addSubmit('ok', Html::el('i')->class('material-icons')->title('Smazat soubor')->setText('delete'))
            ->setHtmlAttribute('class', 'button error icon-only small');
        $form->addSubmit('cancel', Html::el('i')->class('material-icons')->title('Zpět')->setText('cancel'))
            ->setHtmlAttribute('class', 'button success icon-only small')
            ->onClick[] = function (Form $form, Array $values) {
                $this->redirect('FileList:default', array(['Name'=>$values['curDirName'],'Path'=>$values['curDirPath']]));
            };
        $form->onSuccess[] = function (Form $form, Array $values) {
            $fullName = $values['curDirPath'] . '/' . $values['fileName']; 
            //bdump($fullName);
            try {
                $this->fileManager->deleteFile($fullName);
                $this->flashMessage('Soubor ' . $fullName . ' byl smazána');
            } catch (\Exeption $e) {
                $this->flashMessage('Soubor ' . $fullName . ' se nepodařilo smazat!','error');
            }
        };
        return $form;
    }    

    protected function createComponentRenFileForm(): Form {
        //bdump($this->curDir);
        $form = new Nette\Application\UI\Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = Html::el('div')->class('modal-form');
        $renderer->wrappers['group']['container'] = null;
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('row');
        $renderer->wrappers['control']['container'] = Html::el('div')->class('col is-marginless padding-cell');

        $form->addHidden('curDirName', $this->curDir['Name']);
        $form->addHidden('curDirPath', $this->curDir['Path']);
        $form->addHidden('oldfileName')
            ->setHtmlAttribute('id','oldfilename');
        $form->addText('fileName', '')
        ->setHtmlAttribute('id','newfilename');
        $form->addSubmit('ok', Html::el('i')->class('material-icons')->title('Přejmenovat soubor')->setText('drive_file_rename_outline   '))
            ->setHtmlAttribute('class', 'button success icon-only small');
        $form->addSubmit('cancel', Html::el('i')->class('material-icons')->title('Zpět')->setText('cancel'))
            ->setHtmlAttribute('class', 'button error icon-only small')
            ->onClick[] = function (Form $form, Array $values) {
                $this->redirect('FileList:default', array(['Name'=>$values['curDirName'],'Path'=>$values['curDirPath']]));
            };
        $form->onSuccess[] = function (Form $form, Array $values) {
            $fullName = $values['curDirPath'] . '/' . $values['fileName']; 
            //bdump($fullName);
            if ($values['fileName'] == $values['oldfileName']) {
                $this->flashMessage('Název je stejný jako původní!','error');
            } elseif (file_exists($this->fileManager->fileRoot . $fullName)) {
                $this->flashMessage('Soubor s názvem ' . $fullName . ' již existuje','error');
            } else {
                try {
                    rename($this->fileManager->fileRoot . $values['curDirPath'] . '/' . $values['oldfileName'], $this->fileManager->fileRoot . $fullName);
                    $this->flashMessage('Soubor ' . $fullName . ' byl přejmenován');
                } catch (\Exeption $e) {
                    $this->flashMessage('Soubor ' . $fullName . ' se nepodařilo přejmenovat!','error');
                }
            }
        };
        return $form;
    }    

    protected function createComponentRenDirForm(): Form {
        //bdump($this->curDir);
        $form = new Nette\Application\UI\Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = Html::el('div')->class('modal-form');
        $renderer->wrappers['group']['container'] = null;
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('row');
        $renderer->wrappers['control']['container'] = Html::el('div')->class('col is-marginless padding-cell');

        $form->addHidden('curDirName', $this->curDir['Name']);
        $form->addHidden('curDirPath', $this->curDir['Path']);
        $form->addText('dirName', '');
        $form->addSubmit('ok', Html::el('i')->class('material-icons')->title('Přejmenovat složku')->setText('drive_file_rename_outline   '))
            ->setHtmlAttribute('class', 'button success icon-only small');
        $form->addSubmit('cancel', Html::el('i')->class('material-icons')->title('Zpět')->setText('cancel'))
            ->setHtmlAttribute('class', 'button error icon-only small')
            ->onClick[] = function (Form $form, Array $values) {
                $this->redirect('FileList:default', array(['Name'=>$values['curDirName'],'Path'=>$values['curDirPath']]));
            };
        $form->onSuccess[] = function (Form $form, Array $values) {
            $fullName = $this->fileManager->getParentDir($values['curDirPath']) . '/' . $values['dirName']; 
            //bdump($fullName);
            if ($values['dirName'] == $values['curDirName']) {
                $this->flashMessage('Název je stejný jako původní!','error');
            } elseif (file_exists($this->fileManager->fileRoot . $fullName)) {
                $this->flashMessage('Složka s názvem ' . $fullName . ' již existuje','error');
            } else {
                try {
                    rename($this->fileManager->fileRoot . $values['curDirPath'], $this->fileManager->fileRoot . $fullName);
                    $this->flashMessage('Soubor ' . $fullName . ' byl přejmenován');
                    $this->redirect('FileList:default', array(['Name'=>$values['dirName'],'Path'=>$fullName]));
                } catch (\Exeption $e) {
                    $this->flashMessage('Soubor ' . $fullName . ' se nepodařilo přejmenovat!','error');
                }
            }
        };
        return $form;
    }
}
