<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\SmartObject;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Neon\Neon;
use Nette\Utils\Arrays;
use Nette\Security\Passwords;

/**
 * Třída pro práci s datovými soubory
 * 
 * Datové i definiční soubory se ukládají ve formátu neon: https://doc.nette.org/cs/neon
 * 
 * Umístění datových souborů se řídí parametrem dataPath ze souboru common.neon
 * Datové soubory jsou v této složce
 * Seznam všech datových souborů s popisem je v datovém souboru _tables.neon ve formátu:
 *      název_souboru:
 *          - "Název datové tabulky"
 *          - "Popis datové tabulky"
 *
 * Definice vlastností jednotlivých datových souborů jsou v podsložce Definition, 
 * soubory s definicemi se jmenují stejně jako datové soubory
 * 
 * U sloupců v datových souborech se definují tyto vlastnosti:
 * 	    title: název sloupce
 *	    datatype: datový typ
 *	    length: délka řetězce nebo čísla, null, pro typ ENUM čárkou oddělená seznam hodnot
 *	    required: je požadována hodnota
 *	    default: výchozí hodnota při vytvoření nového záznamu
 *	    unique: hodnota je unikátní
 *	    hidden: nezobrazí se ve výstupech formulářích
 *	    readonly: jen pro čtení
 *	    browse: nezobrazuje ve výstupech
 *
 * Pro datový typ TEXT lze používat pro formátování textu značkovací jazyk Texy https://texy.info/cs/
 */
final class DataManager {
    /**
     * Nastavení konstant pro datové typy 
     */ 
     const STRING    = 'string';
     const TEXT      = 'text';
     const INT       = 'int';
     const FLOAT     = 'float';
     const DATE      = 'date';
     const TIME      = 'time';
     const BOOL      = 'bool';
     const ENUM      = 'enum';
     const GUID      = 'guid';
     const PWD       = 'pwd';
     const DIR       = 'dir';
 
     /** @var String */
     protected $dataPath;

     /** seznam tabulek s vlastnostmi */
     private array $tableList;

     public function __construct(String $dataPath) {
        $this->dataPath = $dataPath;
        $this->tableList = $this->readTables();
    }

    /**
     * To danou tabulku vrátí pole s vlastnostmi tabulky (properties), seznamem sloupců (columns) a jednotlivými záznamy (rows)
     * 
     * @param array $tbl - název tabulky
     * @return array [properties], [columns], [rows]
     */
    public function getTableData($tbl): array {
        $retArray['properties'] = $this->tableList[$tbl];
        $arrData = $this->readData($tbl);
        $retArray['columns'] = $arrData['dataDefinition'];
        $retArray['rows'] = $this->fillRows($arrData);
        return $retArray;
    }

    /**
     * Uloží definici sloupců tabulky do definičního neon souboru
     * 
     * @param string $filename - název def. souboru
     * @param array $columns - pole s vlastnostmi sloupců tabulky
     * @throws - soubor se nepodařilo uložit
     * 
     * @ToDo    - zatím se nepoužívá, není frontend pro úpravu vlastností¨
     *          - vstupní parametr by mohlo být pouze pole $table, kde je vše, včetně názvu definičního souboru
     */
    public function saveDefinition (string $filename, array $columns): void {
        try{
            FileSystem::write($filename, Neon::encode($columns, Neon::BLOCK)); 
        }
        catch(\Exception $e){
            throw new \Exception('Definici tabulky se nepodařilo uložit (' . $e->getMessage() . ')');
        }
    }

    /**
     * Uloží data tabulky do neon souboru
     * 
     * @param string $filename - název datového souboru
     * @param array $columns - pole sloupců tabulky a jejich vlastností
     * @param array $rows - pole s daty
     * @throws - soubor se nepodařilo uložit
     * 
     * @ToDo    - vstupní parametr by mohlo být pouze pole $table, kde je vše potřebné
     */
    public function saveData (string $filename, array $columns, array $rows){
        foreach($rows as $row => $values){
            foreach($values as $key => $value){
                $line[] = $this::convTo($value, $columns[$key]['datatype']);
            }
            $arrData[$row] = $line;
            unset($line);
        }
        try{
            FileSystem::write($filename, Neon::encode($arrData)); 
        }
        catch(\Exception $e){
            throw new \Exception('Řádky tabulky se nepodařilo uložit (' . $e->getMessage() . ')');
        }
    }

    /**
     * Načte definici sloupců a datové záznamy pro danou tabulku a vrátí je v poli
     * 
     * @param arrary $tbl - název tabulky
     * @return array [['columns'], ['rows']]
     * @throws - tabulka není v seznamu tabulek '_tables.neon'
     */
    private function readData(string $tbl) {
        try {
            Arrays::get($this->tableList, $tbl);
        } catch (\Exception $e) {
            throw new \Exception('Tabulka ' . $tbl . ' neexistuje! (' . $e->getMessage() . ')');   
        }

        try {
            $arrData['dataContext'] = Neon::decode(FileSystem::read($this->tableList[$tbl]['filename']));
            $arrData['dataDefinition'] = Neon::decode(FileSystem::read($this->tableList[$tbl]['definition']));
        }
        catch (\Exception $e) {
            throw new \Exception('Tabulku ' . $tbl . ' se nepodařilo načíst! (' . $e->getMessage() . ')');
        }
        return $arrData;
    }

    /**
     * Vrátí pole jednotlivých záznamů
     * 
     * @param array $arrData - pole se sloupci a řádky dané tabulky
     * @return array - pole s datovými řádky 
     */
    private function fillRows(array $arrData): array {        
        if (!$arrData['dataContext'])
            return array();
        else {    
            foreach ($arrData['dataContext'] as $uid => $line) {
                $i = 0;
                foreach($arrData['dataDefinition'] as $name => $property) {
                    $row[$name] = $this::convFrom($line[$i], $property['datatype']); 
                    $i++;
                }
                $retArray[$uid] = $row;
                unset($row);
            }
            return $retArray;
        }
    }

    /**
     * Načte ze souboru _tables.neon seznam všech datových souborů - tabulek a jejich vlastností
     * 
     * @return array
     * @throws - pokud neexistuje soubor '_tables.neon'
     */
    private function readTables(): array {
        try {
            $tables = Neon::decode(FileSystem::read($this->dataPath . '_tables.neon'));
        }
        catch (\Exception $e) {
            throw new \Exception('Definici ' . $this->dataPath . '_tables.neon' . ' se nepodařilo načíst! (' . $e->getMessage() . ')');
        }

        foreach ($tables as $table => $properties) {
            if (file_exists($this->dataPath . $table . '.neon')) {
                $fileInfo = new \SplFileInfo($this->dataPath . $table . '.neon');
            }
            $retArray[$table] = [
                'name' => $table,
                'title' => $properties[0], 
                'desc' => $properties[1],
                'filename' => $this->dataPath . $fileInfo->getFilename(), 
                'definition' => $this->dataPath . 'Definition/' . $fileInfo->getFilename(),
                'mdate' => $fileInfo->getMTime()           
            ];   
        }       
        return $retArray;
    }

    /**
     * Zkonvertuje hodnotu z formátu ve kterém je uložena v datech na string pro uložení v NEON souboru
     * 
     * @param ? $value - hodnota
     * @param string $format - vstupní formát dat
     * @return string $value
     */
    static function convFrom ($value, string $format):string {
        switch ($format) {
            case self::DATE :
                return $value ? $value->format('Y-m-d') : '';
            case self::TIME :
                return $value ? $value->format('Y-m-d H:i:s') : '';
            case self::BOOL :
                return $value?'Ano':'Ne';
            case self::INT :
                return strval($value);    
            case self::PWD :
                return $value;
            default:
                return $value;
        }
    }

    /**
     * Zkonvertuje string uložená v NEON na hodnotu ve formátu ve kterém je uložena v datech
     * 
     * @param  $value - hodnota
     * @param string $format - vstupní formát dat
     * @return string $value
     */
    static function convTo ($value, string $format){
        switch ($format) {
            case self::DATE :
                if(is_string($value)){
                    $value = new \DateTimeImmutable($value);
                }
                return $value;
            case self::TIME :
                if(is_string($value)){
                    $value = new \DateTimeImmutable($value);
                }
                return $value;
            case self::BOOL :
                return $value == 'Ano' ? true : false;
            case self::INT :
                return intval($value);    
            case self::FLOAT :
                return floatval($value);
            case self::PWD :
                $passwords = new Passwords();
                return strlen($value) > 30 ? $value : $passwords->hash($value);
            default:
                return strval($value) ;
        }        
    }    
}