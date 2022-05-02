<?php
declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\SmartObject;
use Nette\Utils\Strings;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;

/**
 * Třída pro práci se soubory uloženými v aplikaci
 * 
 * Soubory se ukládají do složky definované parametrem filePath ze souboru common.neon
 * 
 */
final class FileManager{

    const FILE      = 'file';
    const DIR       = 'dir';
    const ALL       = 'all';

    /** @var String */
    public $fileRoot;
    
    public $sortProperty;

    /**
     * @param DataManager
     */
    public function __construct(String $filePath) {
        $this->fileRoot = $filePath;
        
    } 

    /**
     * Načte obsah daného adresáře
     * Obsah je ve dvou polích #dirs - adresáře  a $files - soubory
     * Pro načtení obsahu obou polí se používá funkce getContentArr
     * Obsah je setříděn podle Jména
     * 
     *  @param string $path - adresář s relativní cestou
     *  @param string $filter - připravený paramet pro filtorvání obsahu
     *  @return array[dirs[],files[]]
     * 
     * ToDo -   1. přidat parametr pro třídění obsahu
     */
    public function getDirContent(string $path = '', string $filter = null): array {        
        $dirs = array();
        $files = array();
        $dirContent = array();
        
        $path = $this->exists($path);
        $fullpath = $this->fileRoot . $path;

        $dirContent['parent'] = $this->getParentDir($path);
        $dir = new \DirectoryIterator($fullpath);
        
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $dirs += $this->getContentArr($fileinfo, $filter)[self::DIR];
                $files += $this->getContentArr($fileinfo, $filter)[self::FILE];
            }
        }
        $dirContent[self::DIR] = $this->sortByProperty($dirs, 'Name');
        $dirContent[self::FILE] = $this->sortByProperty($files, 'Name');

        return $dirContent;
    }

    /**
     * Načte obsah daného adresáře a rekurzivně i všech podadresářů
     * Obsah je ve dvou polích #dirs - adresáře  a $files - soubory
     * Pro načtení obsahu obou polí se používá funkce getContentArr
     * Obsah je setříděn podle relativní cesty
     * !! Tato funkce se zatím nepoužívá
     * 
     *  @param string $path - adresář s relativní cestou
     *  @param string $filter - připravený paramet pro filtorvání obsahu
     *  @return array[dirs[],files[]]
     * 
     * ToDo -   1. přidat parametr pro třídění obsahu
     */    
    public function getAllContent(string $path = '', string $filter = null): array {      
        $dirs = array();
        $files = array();
        $dirContent = array();

        $path = $this->exists($path);
        $fullpath = $this->fileRoot . $path;

        $dirContent['parent'] = $this->getParentDir($path);            
        
        $iterator = new \RecursiveDirectoryIterator($fullpath);
        $iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
        $content = new \RecursiveIteratorIterator($iterator, \  RecursiveIteratorIterator::SELF_FIRST);
        foreach ($content as $fileinfo) {
            $dirs += $this->getContentArr($fileinfo, $filter)[self::DIR];
            $files += $this->getContentArr($fileinfo, $filter)[self::FILE];
        }   
        
        $dirContent[self::DIR] = $this->sortByProperty($dirs, 'Path');
        $dirContent[self::FILE] = $this->sortByProperty($files, 'Path');

        //bdump($dirContent);
        return $dirContent;
    }

    /**
     * Rekurzivní funkce pro procházení podadresářů a načítání vlastností
     * 
     * @param string $dir - jméno adresáře s relativní cestou z $fileRoot, který se prohledá
     * 
     * Volá se z getDirTree, který vytvoří kořenový záznak
     */
    private function getDirTreeRec(string $path = ''): array {
        $dirs = array();
        $properties = array();
        
        $path = $this->exists($path);
        $fullpath = $this->fileRoot . $path;

        $dir = new \DirectoryIterator($fullpath);       

        foreach ( $dir as $node )
        {
            if ( $node->isDir() && !$node->isDot() ){
                $properties['Name'] = $this->getWebName($node);
                $properties['Path'] = $this->getShortPath($node->getPathName());
                $properties['Content'] = $this->getDirTreeRec( $properties['Path']);
                $dirs[$node->getFilename()] = $properties;
            }
                
        }
        return $dirs;        
    }

    /**
     * Vytvoří pole se stromem adresářů v adresáři $fileRoot
     * 
     * Každý prvek obsahuje:
     *      Name - název adresáře
     *      Path - název adresáře včetně relativní cesty z $fileRoot
     *      Content - pole podadresářů
     * 
     * Volá rekurzivní funkci pro načítání podadresářů getDirTreeRec
     */
    public function getDirTree(): array {
        $tree = array();
        $properties = array();

        $properties['Name'] = 'Soubory';
        $properties['Path'] = '';
        $properties['Content'] = $this->getDirTreeRec();
        $tree['Soubory'] = $properties;
        return $tree;        

    } 

    /**
     * Rekurzivní funkce pro procházení podadresářů a vytvoření seznamu všech podadresářů
     * 
     * @return array string - pole s relatiní cestou všech adreářů
     * 
     */
    public function getDirList(string $path = ''): array {
        $dirs = array();   
        $path = $this->exists($path);
        $fullpath = $this->fileRoot . $path;

        $dir = new \DirectoryIterator($fullpath);       

        foreach ( $dir as $node )
        {
            if ( $node->isDir() && !$node->isDot() ){
                array_push($dirs, $this->getShortPath($node->getPathName()));
                $dirs = array_merge($dirs, $this->getDirList($this->getShortPath($node->getPathName())));
            }                
        }
        return $dirs;        
    }

    /**
     * Setřídí pole souborů bebo adresářů podle jedné z vlastností
     * 
     * @param array $items - pole souborů nebo adresářů, každá položka je polem vlastností
     *                       které jsou načteny funkcí setFileProperty
     * @param string $property - název vlastnosti, použité pro setřídění
     * @param string $direction - směr třídění, výchozé ASC
     * 
     * @return array - setříděné pole souborů nebo adresářů
     */
    public function sortByProperty(array $items, string $property, string $direction='ASC'): array {
        $this->sortProperty = $property;
        if($direction == 'ASC') {
            uasort($items, function ($a, $b) {
                return $a[$this->sortProperty] <=> $b[$this->sortProperty];
            });
        } else {
            uasort($items, function ($a, $b) {
                return $b[$this->sortProperty] <=> $a[$this->sortProperty];
            });
        }        
        return $items;
    }

    /**
     * Smaže soubor
     * @param string $file - název souboru včetně relativní cesty z $fileRoot
     * 
     * @throws  - Soubor se napodařilo smazat
     *          - Soubor neexistuje
     */
    public function deleteFile(string $file): void {
        $filePath = $this->fileRoot . $file;
        //bdump($filename);
        if(file_exists($filePath)) {
            if (!unlink($filePath)) { 
                throw new \Exception('Soubor ' . $file . ' se nepodařilo smazat!');    
            }
        }else{
            throw new \Exception('Soubor ' . $file . ' nexistuje, nemohl být smazán!');    
        }
    }

    /**
     * Smaže adresář včetně jeho obsahu (souborů i podadresářů)
     * @param string $dir - název adresáře včetně relativní cesty z app/files/
     * 
     * @throws  - Adresář se napodařilo smazat
     *          - Adresář neexistuje
     */
    public function deleteDir (string $dir): void {
        $dirPath = $this->fileRoot . $dir;
        if(file_exists($dirPath)){
            try {  
                //$this->setAccessPermition($dirPath);              
                chmod($dirPath, 0777);
                $dir = new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new \RecursiveIteratorIterator($dir,
                            \RecursiveIteratorIterator::CHILD_FIRST);
                foreach($files as $file) {
                    chmod($file->getRealPath(), 0777);
                    if ($file->isDir()){
                        $dir_handle = opendir($file->getRealPath());
                        closedir($dir_handle);
                        rmdir($file->getRealPath());
                    } else {
                        unlink($file->getRealPath());
                    }
                }
                $dir_handle = opendir($dirPath);
                closedir($dir_handle);
                rmdir($dirPath);
            } catch (\Exeption $e) {
                throw new \Exception('Složku ' . $dirPath . ' se nepodařilo smazat!');
            } 
        } else {
            throw new \Exception('Složka ' . $dirPath . ' neexistuje!');
        }
    }

    /**
     * Výkonná funkce pro načtení obsah daného adresáře
     * Volá se v veřejných funkcí getDirContent a getAllContent
     * Obsah je v poly content, které obsahuje dvě pole #dirs - adresáře  a $files - soubory
     * U adresárě i souboru se uloží v poly tyto hodnoty:
     *              Name, Size (v kB), Ext, Path (relativní), MTime
     * Pro načtení hodnot je použita funkce setFileProperty
     * 
     *  @param Fileinfo $fileinfo - objekt souboru nebo adresáře
     *  @param string $filter - připravený paramet pro filtorvání obsahu
     * 
     *  @return array $content[dirs[],files[]]
     */
    private function getContentArr($fileinfo, string $filter = null): array {
        $content = array();
        $dirs = array();
        $files = array();
        if($filter){
            if (Strings::contains(Strings::lower($fileinfo->getFileName()), Strings::lower($filter)))
                if($fileinfo->getType() == self::DIR)
                    $dirs[$fileinfo->getFileName()] = $this->setFileProperty($fileinfo);                    
                else
                    $files[$fileinfo->getFileName()] = $this->setFileProperty($fileinfo);                    
        } else {
            if($fileinfo->getType() == self::DIR)
                $dirs[$fileinfo->getFileName()] = $this->setFileProperty($fileinfo);                    
            else
                $files[$fileinfo->getFileName()] = $this->setFileProperty($fileinfo);                    
        } 
        $content[self::DIR] = $dirs;
        $content[self::FILE] = $files;        
        return $content;
    }

    /**
     * Uloží do pole vlasstnosti daného adresáře nebo souboru
     * U adresárě i souboru se uloží v poli tyto hodnoty:
     *              Name, Size (v kB), Ext, Path (relativní), MTime
     * Volá se z funkce getContentArr
     * 
     *  @param Fileinfo $fileinfo - objekt souboru nebo adresáře
     * 
     *  @return array $properties[]
     */    
    private function setFileProperty($fileinfo): array {
        $properties = array();
        //$properties['Name'] = $this->getWebName($fileinfo);
        $properties['Name'] = $fileinfo->getFileName();
        $properties['Size'] = $fileinfo->getSize() % 1000 . 'kB';
        $properties['Ext'] = $fileinfo->getExtension();
        $properties['Path'] = $this->getShortPath($fileinfo->getPath());
        $properties['MTime'] = date('Y-m-d H:i', $fileinfo->getMTime());
        return $properties;
    }

    /**
     * Vrátí relativní cestu pro web z absolutní cesty v OS
     * Zároveň mění formát na linuxový se /
     * 
     * @param string $path - plná cesta v OS
     * 
     * @return string - relativní cesta
     * 
     */
    private function getShortPath(string $path): string {
        $path = str_replace("\\", "/", FileSystem::normalizePath($path));
        $path = Strings::after($path, $this->fileRoot)?Strings::after($path, $this->fileRoot):'';
        return $path;
    }
    
    /**
     * Vrátí název souboru bez cesty a přípony
     * 
     * @param Fileinfo $fileinfo
     * 
     * @return string jméno souboru bez přípony
     */
    private function getWebName($fileinfo): string {
        return Strings::webalize(Strings::substring($fileinfo->getFileName(),0, Strings::indexOf($fileinfo->getFileName(), $fileinfo->getExtension(),-1)));
    }
    
    /**
     * Vrátí relativní cestu nadřazeného adresáře
     * 
     * @param string $path - relativní cesta k adresáři, ke kterému hledáme rodiče
     * 
     * @return string - relativní cesta k nadřazenému adresáři
     */
    public function getParentDir(string $path): string {
        if ($path =='')
            return '';
        if (Strings::substring($path,0,1) != '/')
            $path = '/' . $path;

        $path = Strings::substring(FileSystem::normalizePath(FileSystem::joinPaths($path, '..')), 1);
        $fullpath = $this->fileRoot . $path;
        if(file_exists($fullpath)) {            
            return $path;
        } else  {
            return '';
        }        
    }

    /**
     * Otestuje zda soubor nebo adresář existuje a pokud ano vrátí jej s relativní cestou v linuxovém formátu
     * 
     * @param string $path - adresář nebo soubor s relativní cestou
     * 
     * @return string - název s relativní cestou v linuxovém formátu nebo '' pokud neeistuje
     */
    public function exists($path): string {
        $path = str_replace("\\", "/", FileSystem::normalizePath($path));
        $fullpath = $this->fileRoot . $path;
        if(file_exists($fullpath)) {
            return $path;
        } else  {
            throw new \Exception('Cesta: ' . $path . ' neexistuje!');   
            return '';
        }        
    }

}