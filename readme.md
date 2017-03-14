# Ukázka použití frameworku Slim

Tento projekt vznikl pro předmět WA na PEF MENDELU. Tento průvodce ukáže základní použití mikroframeworku Slim pro
vytvoření aplikace, která zhruba odpovídá části zadání z předmětu APV (tedy evidence osob a jejich adres).

## Úvod

### Platné pro:
- Slim 3.7.x - [dokumentace](https://www.slimframework.com/docs/)
- Apache 2.x a PHP 7.0
- NetBeans (volitelné)

### Co je nutné udělat před vlastní prací
- nainstalovat [Composer](https://getcomposer.org/) tak, aby šel spouštět příkazem `composer` z příkazového řádku
- volitelně i [NodeJS](https://nodejs.org/) a balíčkovací systém npm (také by mělo jít spustit přes příkazový řádek)
- volitelně i [Git](https://git-scm.com/)

## Vlastní walkthrough

### Instalace a zahájení projektu
- vytvořte si složku pro projekt
- stažení frameworku pomocí Composeru, použít příkaz `composer create-project slim/slim-skeleton .` (vč. tečky na
  konci - tzn. do aktuálního adresáře). Složka, kam projekt vytváříte **musí** být prázdná.
- nyní je dobré založit projekt v NetBeans nebo jiném IDE
- na lokálním webovém serveru zkontrolovat, že aplikace běží (otevřít složku [http://locahost/slim_demo/public](http://locahost/slim_demo/public),
  mělo by se zobrazit jméno frameworku s odkazem na stránky - welcome obrazovka).

Může být nutné nastavit direktivu `RewriteBase` v souboru `/public/.htaccess` na aktuální cestu k aplikaci
např. `RewriteBase /~user/aplikace/public`.

Důležité adresáře a soubory:

- `/public` - veřejná část aplikace, to co je opravdu přístupné přes HTTP
	- `index.php` - vstupní soubor
- `/src` - vlastní zdrojové kódy aplikace
	- `dependencies.php` - globální závislosti, např. připojení na databázi
	- `middleware.php` - zde můžete nadefinovat tzv. *middleware*
	- `routes.php` - zde bude implementována vlastní aplikace
	- `settings.php` - nastavení aplikace, která můžete libovolně rozšířit
- `/templates` - základní šablony v čistém PHP, nahradíme šablonami Latte

## Poznámky

### Rozjetí projektu na jiném stroji (po stažení z Gitu)
Příkazem `git clone http://adresa.repositare.cz/nazev.git slozka` se vám stáhne z Gitu kopie projektu. Jelikož jsou
některé důležité soubory a složky nastavené v souboru `.gitignore`, je potřeba primárně spustit příkaz
`composer install`, aby se stáhl vlastní framework a jeho knihovny.

# TODO
- sablony latte
- zpracovani formulare
- nasazeni bootstrapu

`composer create-project slim/slim-skeleton .`