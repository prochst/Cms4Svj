<?php
declare(strict_types=1);

namespace App\Forms;

use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateControl;
use Nextras\FormComponents\Controls\DateTimeControl;
use Nette\Utils\Html;
use Nette\Utils\Arrays;
use App\Model\TableManager;
use App\Model\FileManager;

/**
 * Továrna pro vytvoření formuláře na editaci daného záznamu z daného datového souboru
 */
class DataEditFormFactory {

    private TableManager $tableMng;
    private FileManager $fileMng;
    

    /**
     * Vytvoří formulář pro editaci záznamu
     * 
     * @param TableManager $tableMng - datový manažer s daty daného datového souboru
     * @param FileManager $fileMng - správce uložených souborů, pro vykládání cest ke složkám a odkazů
     */
    public function create(TableManager $tableMng, FileManager $fileMng): Form {
        $this->tableMng = $tableMng;
        $this->fileMng = $fileMng;
        $form = new Form;
        $renderer = $form->getRenderer();
        $renderer->wrappers['form']['container'] = Html::el('div')->class('edit-form');
        $renderer->wrappers['group']['container'] = null;
        $renderer->wrappers['group']['label'] = 'h3';
        $renderer->wrappers['pair']['container'] = Html::el('div')->class('row bd-grey');
        //$renderer->wrappers['pair']['.odd'] = 'odd';
        //$renderer->wrappers['controls']['container'] = 'dl';
        $renderer->wrappers['control']['container'] = Html::el('div')->class('col is-marginless padding-cell');
        //$renderer->wrappers['control']['.odd'] = 'odd';
        $renderer->wrappers['label']['container'] = Html::el('div')->class('col-2 bg-primary text-light is-marginless padding-cell' );
        $renderer->wrappers['label']['suffix'] = ':'; 
        $form->addHidden('tblName', $this->tableMng->properties['name']);
        $form->addHidden('uid');
        foreach($this->tableMng->columns as $column => $properties) {
            if(!$properties['hidden']){
                switch ($properties['datatype']) {
                    case $this->tableMng->dataManager::DATE :
                        $form[$column] = new DateControl ($properties['title']);
                        $this->setFormAttr($form, $column, $properties);
                        break;
                    case $this->tableMng->dataManager::TIME :
                        $form[$column] = new DateTimeControl ($properties['title']);
                        $this->setFormAttr($form, $column, $properties);
                        break;
                    case $this->tableMng->dataManager::INT :
                        $form->addInteger($column,$properties['title'])
                            ->addRule(Form::MAX, 'Číslo musí být menší než '. pow(10, $properties['length']), pow(10, $properties['length']));
                        $this->setFormAttr($form, $column, $properties);
                        break;
                    case $this->tableMng->dataManager::FLOAT :
                        $form->addText($column,$properties['title'])
                            ->addRule(Form::FLOAT,'Vložte čílo')                        
                            ->addRule(Form::MAX, 'Číslo musí být menší než '. pow(10, $properties['length']), pow(10, $properties['length']));
                        $this->setFormAttr($form, $column, $properties);
                        break;
                    case $this->tableMng->dataManager::BOOL :
                        $options = ['Ano' => 'Ano', 'Ne' => 'Ne'];
                        $form->addRadioList($column,$properties['title'], $options);                        
                        $properties['default'] = $properties['default'] ? 'Ano' : 'Ne';
                        $this->setFormAttr($form, $column, $properties);
                        break;    
                    case $this->tableMng->dataManager::ENUM :
                        $enums = explode(',', preg_replace('/\s+/','',$properties['length']));
                        unset($options);
                        foreach($enums as $enum) {
                            $options[$enum] = $enum;
                        }
                        $form->addSelect($column,$properties['title'], $options);
                        $this->setFormAttr($form, $column, $properties);
                        break;    
                    case $this->tableMng->dataManager::DIR :
                        $enums = $this->fileMng->getDirList();
                        unset($options);
                        foreach($enums as $enum) {
                            $options[$enum] = $enum;
                        }
                        $form->addSelect($column,$properties['title'], $options);
                        $this->setFormAttr($form, $column, $properties);
                        break;    
                    case $this->tableMng->dataManager::TEXT :                        
                        $form->addTextArea($column,$properties['title'])
                            ->setHtmlAttribute('rows="'. $properties['length']/30 .'" cols="50"');
                        $this->setFormAttr($form, $column, $properties);
                        break;
                    case $this->tableMng->dataManager::PWD :                        
                        $form->addText($column, $properties['title'])
                            ->addRule($form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků', 5)
                            ->addRule($form::PATTERN, 'Musí obsahovat číslici', '.*[0-9].*');                        $this->setFormAttr($form, $column, $properties);
                        $this->setFormAttr($form, $column, $properties);
                        break;
                    default:
                        $form->addText($column,$properties['title'])
                            ->setHtmlAttribute('size="'. $properties['length'] .'"')
                            ->setMaxLength($properties['length'])
                            ->addRule(Form::MAX_LENGTH, 'Povolená délka textu je '. $properties['length'], $properties['length']);
                        $this->setFormAttr($form, $column, $properties);
                        break;
                }
            }
        }
        $form->addSubmit('save', Html::el('i')->class('material-icons')->title('Uložit')->setText('save'))
            ->setHtmlAttribute('class', 'button success icon-only small');
        return $form;
    }

    private function setFormAttr(Form $form, string $column, array $properties): void {
        // povinna hodnota
        if($properties['required'])
            $form[$column]->setRequired('Hodnota ' . $properties['title'] . ' je povinná');    
        // výchozí hodnota pro nový záznam
        if($properties['default'])
            $form[$column]->setDefaultValue($properties['default']);    
        // přistup k poli (nefunguje u select, radio, checkbox) 
        if (($properties['readonly'])) 
           $form[$column]->setDisabled();
    } 
}