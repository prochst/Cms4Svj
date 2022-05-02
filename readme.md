# Cms4Svj

Jednoduché **CMS** pro správu a publikování základních informací Sdružení vlastníků bytových jednotek.
Obsahuje veřejnou část, část pro členy SVJ a část pro členy výboru SVJ. Součástí je i administrativní rozhraní pro zprávu obsahu, dokumentů a uživatelů.

- [Cms4Svj](#cms4svj)
  - [Technologie](#technologie)
  - [Jak začít](#jak-začít)
    - [Co je potřeba](#co-je-potřeba)
    - [Instalace](#instalace)
  - [Používání](#používání)
    - [Struktura projektu](#struktura-projektu)
    - [Administrace](#administrace)
      - [Obsah](#obsah)
    - [Nastavení](#nastavení)
    - [Data](#data)
    - [Soubory](#soubory)
    - [Vzhled](#vzhled)
  - [Licence](#licence)
  - [Kontakt](#kontakt)
  - [Nette Web Project](#nette-web-project)
    - [Requirements](#requirements)
    - [Installation](#installation)
    - [Web Server Setup](#web-server-setup)

## Technologie

Projekt je napsán v PHP pomocí frameworku [Nette](#nette)
Data se ukládají souborově. Definice i vlastní data jsou ve formátu [NEON](https://doc.nette.org/cs/neon/format)

## Jak začít

### Co je potřeba

Pro spuštění webové aplikace je potřeba prostředí s webovým serverem Apache nebo Nginx a PHP 7.2 nebo vyšší

### Instalace

Celý projekt nakopírujete do kořenové složky webu.

Předefinované uživatelské účty pro přihlášení do presentace i do administrativní části
Admin - 12345
Editor - 54321

## Používání

Presentace SVJ má tři části:

- Hlavní stránku
- Stránku **Společenství** přístupnou pouze pro přihlášené uživatele, kteří nají roli pro frontend `Člen` nebo `Výbor`
- Stránku **Výbor** přístupnou pouze pro přihlášené uživatele, kteří nají oprávnění pro frontend `Výbor`

### Struktura projektu

- app [aplikační složka Nette, obsahuje php soubory, šablony, data, a uložené soubory]
  - AdmModule [administrační modul]
  - Data [datové soubory]
    - Definition [definiční soubory tabulek]
  - Files [uživatelské soubory a dokumenty spravované v aplikaci ]
  - Form [definice použitých formulářů]
  - Models [třídy pro správu dat, souborů a stránek]
  - Presenters [presentery jednotlivých stránek]
     - templates [šablony pro zobrazení stránek]
  - Router [směrování na jednotlivé stránky a tvorba URL]
- bin
- config [configurační soubory farameworku Nette]
- log [log aplikace]
- temp [dočasné soubory a cache]
- www [root složka webové presentace, dostupná jako <http://server.name>]

### Administrace

Administrace je na adrese <http://server.name>/adm>
Administrace má dvě části:

#### Obsah

Umožňuje editorovi spravovat obsah jednotlivých stránek, kontaktní informace SVJ, Novinky na hlavní stránce, a uložené soubory a dokumenty.
Přístupné pro uživatele s backend rolí `Editor`

### Nastavení

Sekce pro správu seznamu členů SVJ a výboru a uživatelských účtů pro přístup k frontendu i administraci.
Přístupné pro uživatele s backend rolí `Admin`

### Data

S daty se pracuje jako s databázovými tabulkami, jen jsou data a definice uložena v souborech ve formátu [NEON](https://doc.nette.org/cs/neon/format).
Data jsou uložena ve složce `app/Data`, název složky je uložen v konfiguračním souboru `common.neon` jako parametr.
V této složce je i soubor `_tables.neon` který obsahuje seznam všech datových tabulek, které aplikace používá. V seznamu je uložen název souboru bez přípony, název a popis datové tabulky.
Definice jednotlivých dat jsou uloženy v podložce `Definition`, název soubory a definicí a s daty je vždy shodný.

U sloupců v datových souborech se definují tyto vlastnosti:

    `title`: název sloupce
    
    `datatype`: datový typ
    
    `length`: délka řetězce nebo čísla, null, pro typ ENUM čárkou oddělená seznam hodnot
    
    `required`: je požadována hodnota
    
    `default`: výchozí hodnota při vytvoření nového záznamu
    
    `unique`: hodnota je unikátní
    
    `hidden`: nezobrazí se ve výstupech formulářích
    
    `readonly`: jen pro čtení
    
    `browse`: nezobrazuje ve výstupech

 Pro datový typ `TEXT` lze používat pro formátování textu značkovací jazyk [Texy](https://texy.info/cs/)

### Soubory

Soubory jsou uloženy ve složce `app/Files`, název složky je uložen v konfiguračním souboru `common.neon` jako parametr.

Počáteční struktura, na kterou se odkazují stránky:
- Dokumenty
  - Společenství
    - Hospodaření (výkazy hospodaření SVJ)
    -Ostatní (ostatní dokumenty pro členy SVJ)
    - Revize (revizní zprávy)
    - Společenství (stanovy, zápisy ze shromáždění, ..)
  - Účetní doklady
    - 2021
    - 2022
  - Veřejné (veřejné dokumenty na hlavní stránce)
  - Výbor
    - Dohody (dohody o provedení práce s členy SVJ)
    - Služby (ceny a vyúčtování za služby - teplo, voda energie)
    - Smlouvy (smlouvy SVJ)
    - Zápisy (zápisy se schůzí výboru)
- Novinky '(dokumenty do aktualit na hlavní stránce)

### Vzhled

K formátování jednotlivých stránek je použit šablonovací systém [Latte](https://latte.nette.org/cs/guide)
Pro CSS je použit micro CSS framework [CHOTA](https://jenil.github.io/chota)

## Licence

Distributed under the MIT License. See license.txt for more information.

## Kontakt

Autor: Standa Procházka - prochst@gmmail.com
Projekt: [GitHub](https://github.com/prochst/svj4cms)

## Nette Web Project

This is a simple, skeleton application using the [Nette](https://nette.org). This is meant to
be used as a starting point for your new projects.

[Nette](https://nette.org) is a popular tool for PHP web development.
It is designed to be the most usable and friendliest as possible. It focuses
on security and performance and is definitely one of the safest PHP frameworks.

If you like Nette, **[please make a donation now](https://nette.org/donate)**. Thank you!

### Requirements

- Web Project for Nette 3.1 requires PHP 7.2

### Installation

The best way to install Web Project is using Composer. If you don't have Composer yet,
download it following [the instructions](https://doc.nette.org/composer). Then use command:

 composer create-project nette/web-project path/to/install
 cd path/to/install

Make directories `temp/` and `log/` writable.

### Web Server Setup

The simplest way to get started is to start the built-in PHP server in the root directory of your project:

 php -S localhost:8000 -t www

Then visit `http://localhost:8000` in your browser to see the welcome page.

For Apache or Nginx, setup a virtual host to point to the `www/` directory of the project and you
should be ready to go.

**It is CRITICAL that whole `app/`, `config/`, `log/` and `temp/` directories are not accessible directly
via a web browser. See [security warning](https://nette.org/security-warning).**
