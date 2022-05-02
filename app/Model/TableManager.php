<?php
declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\SmartObject;
use Nette\Utils\Arrays;
use Ramsey\Uuid\Uuid;

/**
 * Třída pro práci s virtuální datovou tabulkou vytvořenou z datového souboru
 */
final class TableManager{

    /** @var DataManager */
    public $dataManager;

    /** 
     * Vlastnosti aktuální tabulky 
     *  Struktura:
     *         [name => systémové jméno tabulky, je to i název NEON souboru bez přípony,
     *          title => název tabulky, 
     *          desc => popis tabulky,
     *          filename => název datového souboru NEON včetně relativní cesty, 
     *          definition => název definičního souboru NEON včetně relativní cesty,
     *          mdate' => datum polední úpravy datového souboru]
    */
    public $properties;

    /** 
     * Pole sloupců a jejich vlastností aktuální tabulky 
     *  Struktura:
     *          [systémové jméno sloupce => array [
     *                              title => název sloupce
     *                              datatype => datový typ (konstanta v DatManageru)
     *                              length => délka, pro typ ENUM čárkou oddělená seznam hodnot
     *                              required => povinné pole
     *                              default => výchozí hodnota 
     *                              unique => unikátní hodnoty A/N
     *                              hidden => skryté pole nezobrazuje se ve výstupech a formulářích
     *                              readonly => pouze na čtení
     *                              browse => nezobrazuje ve výstupech
     *          ]  
    */
    public $columns;

    /** 
     * Pole datových záznamů aktuální tabulky 
     *  Struktura: 
     *              [iud => array [column name => value, column name => value, ... ]]
    */
    public $rows;

    /**
     * Třída pro práci s vlastními a daty vybrané tabulky
     * 
     * @param DataManager - třída pro práci s datovými soubory
     */
    public function __construct(DataManager $dataManager) {
        $this->dataManager = $dataManager;
    }      

    /**
     * Načte data a vlastnosti pro danou tabulku
     * 
     * @param string $tblName - název datové tabulky
     */
    public function readData (string $tblName): void {
        $arrData = $this->dataManager->getTableData($tblName);
        $this->properties = $arrData['properties'];
        $this->columns = $arrData['columns'];
        $this->rows = $arrData['rows'];
    }
    
    /**
     * Uloží vlastností sloupců aktuální tabulky do souboru NEON 
     */
    private function saveColumns (): void {
        $this->dataManager->saveDefinition($this->properties['definition'], $this->columns);
    }

    /**
     * Uloží datové záznamy aktuální tabulky do souboru NEON 
     */
    private function saveRows (): void {
        $this->dataManager->saveData($this->properties['filename'], $this->columns, $this->rows);
    }

    /**
     * Vrátí pole s datovými záznamy aktuální tabulky
     * 
     * @return array - pole s řadky datových záznamů
     */    
    public function getRows(): array {
        return $this->rows;
    }
    
    /**
     * Vyhledá záznam podle UID a vrátí pole s tímto záznamem
     * 
     * @param string $uid - id záznamu
     * @return array - pole s vybraným řádkem datových záznamů
     */
    public function getRow($uid): array {
        return $this->rows[$uid];
    }

    /**
     * Najde a smaže záznam podle UID, změnu zapíše do datového NEON souboru
     * 
     * @param string $uid - id záznamu
     */
    public function deleteRow($uid): void {
        if($this->existRow($uid)){
            Arrays::pick($this->rows, $uid);
            $this->saveRows();
        }
    }

    /**
     * Najde řádek dat podle UID, hodnotu přepíše a změnu zapíše do datového NEON souboru
     * 
     * @param string $uid - id záznamu
     * @param array $row - pole se změněnými daty
     */
    public function updateRow(string $uid = null, array $row): void {       
        //bdump($row);
        if(Uuid::isValid($uid)){
            //změna hodnot existujícího řádku
            if($this->existRow($uid)){
                //bdump($row);
                foreach($row as $column => $value){
                    /*if($this->columns[$column]['datatype'] == $this->dataContext::TEXT)
                    {
                        $this->rows[$uid][$column] = $texy->process($value);
                    } else {*/
                        $this->rows[$uid][$column] = $value;
                    //}
                }
                   
                $this->saveRows();
            }
        } else {
            // přidání nového řádku na konec
            Arrays::insertAfter($this->rows, null, [Uuid::uuid4()->toString() => $row]);            
            $this->saveRows();
        }
    }

    /**
     * Setřídí data podle zadaného sloupce a směru
     * 
     * @param string $column - název sloupce podle kterého se záznamy setřídí
     * @param string $direction - výchozí ASC nebo DESC
     */
    public function sortRows(string $column, string $direction='ASC') : void {
        $sortarray = array();
        foreach ($this->rows as $key => $row)
        {
            $sortarray[$key] = $row[$column];
        }
        if($direction == 'ASC')
            array_multisort($sortarray, SORT_ASC, $this->rows);        
        else    
            array_multisort($sortarray, SORT_DESC, $this->rows);        
    }

    /**
     * Vrátí pole s datovými záznamy aktuální tabulky, které splňují logickou podmínku
     * 
     * @param string $pattern -regulární výraz
     * @return array - pole s vybranými řádky datových záznamů
     * 
     * Zatím se nepoužívá, není otestované
     */    
    public function getRowsCon($pattern): array {
        return Arrays::grep($this->rows, $pattern);
    }

    /**
     * Vrátí pole s s prvním záznamem aktuální tabulky podle zadané hodnoty vybraného sloupce
     * 
     * @param string $column - název sloupce pro filtrování
     * @param $value - hodnota pro filtrování záznamů
     * @return array - pole s nalezeným záznamem:
     *                  [uid, array[values]]
     *               - array() pokud nenajde
     */    
    public function findRow(string $column, $value) : array {
        $retRow = array();
        foreach ($this->rows as $key => $row)
        {
            if($row[$column] == $value){
                return [$key, $this->getRow($key)];
            }
        }
        return $retRow;
    }

    /**
     * Filtruje pole s datovými záznamy aktuální tabulky, filtrované podle zadané hodnoty vybraného sloupce
     * 
     * @param string $column - název sloupce pro filtrování
     * @param $value - hodnota pro filtrování záznamů
     * 
     * Zatím se nepoužívá, není otestované
     */    
    public function filterRows(string $column, $value) : void {
        //bdump($this->rows);
        $sortarray = array();
        foreach ($this->rows as $key => $row)
        {
            if($row[$column] != $value )
                unset($this->rows[$key]) ;
        }
    }

    /**
     * Otestuje existenci záznamu podle UID a vrátí logickou hodnotu
     * 
     * @param string $uid
     * @return bool true/fale - existuje/neexistuje
     */
    private function existRow(string $uid): bool {
        try {
            Arrays::get($this->rows, $uid);
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Záznam uid = ' . $iud . ' v tabulce ' . $this->properties['name'] . ' neexistuje! (' . $e->getMessage() . ')');   
            return false;
        }
    }

}
