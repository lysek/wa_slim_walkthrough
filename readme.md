# Ukázka použití frameworku Slim

Tento projekt vznikl pro předmět WA na PEF MENDELU. Tento průvodce ukáže základní použití mikroframeworku Slim pro
vytvoření aplikace, která zhruba odpovídá části zadání z předmětu APV (tedy evidence osob a jejich adres).

Proti plnotučným frameworkům se Slim na první pohled jeví jako nedotažený projekt, ale to je záměr. Tento framework
řeší v podstatě jen tzv. routing, předání dat ze vstupu do skriptu a řádnou HTTP odpověď. Jeho hlavní výhoda je snadné
použití a možnost namíchat si vlastní oblíbené knihovny. Nejlépe jej použijete jako backend pro REST rozhraní, kde ani
není potřeba řešit šablony a generování formulářů.

# UPDATE 2018
Tento návod je už nepřesný a slévají se zde různé přístupy. Nejlepší bude použít nový návod popsaný v knize
[The Making of Web Application](https://akela.mendelu.cz/~lysek/tmwa/walkthrough-slim/). Tento návod může dobře
posloužit jako dokumentace myšlenkového pochodu, který vedl ke kostře aplikace použité v uvedené knize, kterou najdete
na [BitBucketu](https://bitbucket.org/apvmendelu/slim-based-project/src/master/).

## Úvod

### Platné pro:
- Slim 3.7.x - [dokumentace](https://www.slimframework.com/docs/)
- Apache 2.x a PHP 7.x
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

### Databáze
Slim jako takový nepodporuje žádné ORM, ani nemá vlastní DB vrstvu, je nutné tedy databázi připojit např.
pomocí [PDO](http://php.net/manual/en/book.pdo.php) knihovny z PHP.

Nejprve je nutné nastavit připojení k databázi v souboru `/src/settings.php` nebo lépe v souboru `/.env` (abychom měli
oddělenou konfiguraci pro různá prostředí):

Soubor `/src/settings.php` načítá nastavení z `/.env`. Pokud nechcete `/.env` používat, vyplňte hodnoty přímo
a pokračujte k vytvoření instance PDO.

```php
...
'database' => [
	'host' => getenv('DB_HOST'),
	'user' => getenv('DB_USER'),
	'pass' => getenv('DB_PASS'),
	'name' => getenv('DB_NAME'),
]
...
```

Soubor `/.env` obsahuje lokální nastavení a je v Gitu ignorován (tzn. je zahrnut v souboru `/.gitignore`):

	DB_HOST=localhost
	DB_USER=user
	DB_PASS=pass
	DB_NAME=wa_slim

Aby nám soubor `/.env` fungoval a nastavení z něj se načetly, musíme stáhnout rozšíření pro načítání jeho obsahu:
`composer require vlucas/phpdotenv`. Potom je nutné načíst tyto proměnné, toto můžeme provést hned na začátku
sobuoru `/src/settings.php`:

```php
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
```

Nakonec zaregistrujeme PDO do [*dependency containeru*](https://www.slimframework.com/docs/concepts/di.html) naší
aplikace v `/src/dependencies.php`. Tyto závislosti lze potom snadno získat v obsluhách rout.

```php
$container['db'] = function ($c) {
	$settings = $c->get('settings')['database'];
	$pdo = new PDO("mysql:host=" . $settings['host'] . ";dbname=" . $settings['name'], $settings['user'], $settings['pass']);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	$pdo->query("SET NAMES 'utf8'");
	return $pdo;
};
```

Databázi můžete naimportovat ze souboru [`/db_struktura.sql`](https://github.com/lysek/wa_slim_walkthrough/blob/master/db_struktura.sql).

### Šablony

**UPDATE 2018** Pro knihovnu Latte jsme pro vás vytvořili už nachystaný adaptér na adrese [https://github.com/ujpef/latte-view](https://github.com/ujpef/latte-view).
Postupujte podle návodu v dokumentaci této knihovny a tuto sekci přeskočte, popisuje vytvoření něčeho velmi podobného,
nicméně popisuje i autloading přes Composer, který je potřeba např. pro modelovou vrstvu.

Slim opět nemá žádný šablonovací systém, ve výchozím stavu podporuje šablony v čistém PHP, což je nevyhovující i pro
malé projekty. Je tedy nutné stáhnout pomocí Composeru např. knihovnu Latte: `composer require latte/latte`.

Pro zobrazení šablon je nutné napsat malý adaptér a podobně jako v případě databáze je dobré zaregistrovat Latte jako
službu. Adaptér můžeme umístit do složky `/classes`, kterou vytvoříme.

```php
<?php

use \Psr\Http\Message\Response as Response;

class LatteView {

	private $latte;
	private $pathToTemplates;

	function __construct(Latte\Engine $latte, $pathToTemplates) {
		$this->latte = $latte;
		$this->pathToTemplates = $pathToTemplates;
	}

	function render(Response $response, $name, array $params = []) {
		$name = $this->pathToTemplates . '/' . $name;
		$output = $this->latte->renderToString($name, $params);
		$response->getBody()->write($output);
		return $response;
	}

}
```

Tento adaptér potom zaregistrujeme opět do *dependency containeru* aplikace v `/src/dependencies.php`, všimněte si, že
jsme ze souboru `/src/settings.php` převzali nakonfigurovanou cestku k šablonám. Cestu k cache jsme nastavili "natvrdo",
ale může samozřejmě být také v settings - tuto složku je nutné vytvořit, přidat do `/.gitignore` a nastavit do ní právo
zápisu všem uživatelům (např. příkazem `chmod 0777 cache` nebo přes WinSCP/FileZillu). Cache pro šablony je volitelná.
Samozřejmě je nutné původní `$container['renderer']` s PHP šablonami smazat.

```php
$container['renderer'] = function($c) {
	$settings = $c->get('settings')['renderer'];
	$engine = new Latte\Engine();
	$engine->setTempDirectory(__DIR__ . '/../cache');
	$latteView = new LatteView($engine, $settings['template_path']);
	return $latteView;
};
```

Abychom nemuseli soubory ze složky `/classes` includovat ručně, je možné využít konfiguraci `composeru` v souboru
`/composer.json` a nastavit nahrávání souborů ze složky `/classes` ve stylu [PSR-0](http://www.php-fig.org/psr/psr-0/):

```json`
...
"autoload" : {
	"psr-0": {
		"": "classes/"
	}
},
...
```

### Výpis osob
Nyní máme funkční šablony, vyzkoušíme tedy předání nějakých dat z DB přímo do šablony, v souboru `/src/routes.php`
načteme osoby a pošleme je prostřednictvím adaptéru do Latte šablony. Proměnná `$app` vzniká v souboru `/public/index.php` a je
to jakýsi kontejner celé aplikace. Aplikace je sestavena z obsluh různých HTTP metod a URL adres - jde tedy o
[routing s parametry](https://www.slimframework.com/docs/objects/router.html). Je možné vytvořit i routu na libovolnou
HTTP metodu pomocí `$app->any('/route', function(...) {...});` když je jedno, kterou HTTP metodu prohlížeč použije.

```php
$app->get('/', function ($request, $response, $args) {
	$stmt = $this->db->query("SELECT * FROM persons ORDER BY last_name");
	$persons = $stmt->fetchAll();
	return $this->renderer->render($response, 'index.latte', [
		'persons' => $persons
	]);
});
```

V šabloně je samozřejmě přístupná proměnná `$persons`, která je naplněna daty z DB (pokud žádná nemáte, zkuste ručně vložit
nějaké řádky do tabulky `persons`. Komunikace s databází by samozřejmě měla být v `try {} catch() {}` bloku.

Všimněte si, že každá obsluha routy získává objekt popisující [vstup](https://www.slimframework.com/docs/objects/request.html)
a [výstup](https://www.slimframework.com/docs/objects/response.html) a argumenty (proměnné z URL). Aby nám mohlo IDE
pomáhat, je dobré přidat k argumentům funkce i typ, který mají mít: `Psr\Http\Message\Request` a
`Psr\Http\Message\Response`, nejlépe pomocí `use`:

```php
use Psr\Http\Message\Request;
use Psr\Http\Message\Response;
$app->get('/', function (Request $request, Response $response, $args) {
	...
});
```

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/1811121632546d322fb81068f04ac096c7f6131f)

### Přidání osoby
Do rout pridame jednu GET a jednu POST akci. Pro vykreslení formuláře nic spciálního nepotřebujeme:

```php
$app->get('/pridat', function(Request $request, Response $response, $args) {
	return $this->renderer->render($response, 'create.latte');
});
```

Formulář jako takový je vytvořen v HTML. Přidání osoby je klasciké vložení dat přes PDO, zajímavý je postup
získání dat z těla POST požadavku - používá se metoda `$request->getParsedBody()`, která na základě nastavené
HTTP hlavičky převede obsah na asociativní pole. Po vložení dat do databáze je dobré přesměrovat návštěvníka
na výpis osob, což se provede přidáním hlavičky *Location* do HTTP odpovědi.

```php
$app->post('/ulozit', function(Request $request, Response $response, $args) {
	try {
		$data = $request->getParsedBody();
		$stmt = $this->db->prepare('INSERT INTO persons (first_name, last_name, nickname) VALUES (:fn, :ln, :nn)');
		$stmt->bindValue(':fn', $data['first_name']);
		$stmt->bindValue(':ln', $data['last_name']);
		$stmt->bindValue(':nn', $data['nickname']);
		$stmt->execute();
		return $response->withHeader('Location', 'vypis');
	} catch (PDOException $e) {
		if($e->getCode() == 23000) {
			return $this->renderer->render($response, 'create.latte', ["duplicate" => true]);
		} else {
			die($e->getMessage());
		}
	}
});
```

Náš formuář zatím neřeší, zda byla vyplněny všechny povinné údaje (spoléhá na atribut `required`), ani neřeší předání
vyplněných dat do šablony v případě chyby. Nicméně je ošetřen chybový stav při vložení duplicitních dat a to
znovuzobrazením prízdného formuláře. URL `vypis` je nastavena jako volitelný text (v hranatých závorkách) ve
výchozí routě pro výpis osob:

```php
$app->get('/[vypis]', function (...) {...});
```

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/3a12105040202578b3f012afba16da3a59c3e2d3)

### Přidání osoby s výběrem adresy
Do formuláře pro vytvoření osoby je nutné přidat roletku s adresami, rozšíříme tedy routu pro zobrazení formuláře o
výběr dat adres z DB. Tento výběr raději realizujeme jako funkci, protože ji budeme potřebovat i v případě
znovuzobrazení formuláře při pokusu o vložení duplicitního záznamu.

```php
function loadLocations(PDO $db) {
	try {
		$stmt = $db->query('SELECT * FROM locations ORDER BY city');
		return $stmt->fetchAll();
	} catch(PDOException $e) {
		die($e->getMessage());
	}
}

$app->get('/pridat', function(Request $request, Response $response, $args) {
	return $this->renderer->render($response, 'create.latte', [
		'locations' => loadLocations($this->db)
	]);
});
```

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/274dfa47d5eaf93382501ae14a4e3ae3058e69cb)

### Zobrazení dat zadaných uživatelem při chybném odeslání formuláře
Data do formuláře si můžeme předat pomocí vnořeného asoc. pole:

```php
$app->get('/pridat', function(Request $request, Response $response, $args) {
	return $this->renderer->render($response, 'create.latte', [
		'form' => [
			'first_name' => '',
			'last_name' => '',
			'nickname' => '',
			'id_location' => ''
		],
		'locations' => loadLocations($this->db)
	]);
});
```

V případě, že je formulář odeslán chybně/duplicitně vyplněn, můžeme tyto data snadno přepsat polem s hodnotami z těla
HTTP POST požadavku a můžeme i přidat vysvětlující hlášku:

```php
$app->post('/ulozit', function(Request $request, Response $response, $args) {
	$data = $request->getParsedBody();
	$hlaska = '';
	if (!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['nickname'])) {
		...
		$hlaska = 'Takovato osoba jiz existuje';
		...
	} else {
		$hlaska = 'Nebyly vyplneny vsechny povinne informace';
	}
	return $this->renderer->render($response, 'create.latte', [
		'hlaska' => $hlaska,
		'form' => $data,
		'locations' => loadLocations($this->db)
	]);
});
```

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/f86f9bd7bf4bd4da7fbb2a01d835417d19b43d01)

Alternativně je možné proměnnou `$data` uložit do `$_SESSION` a přesměrovat na routu `/pridat`, kde zařídíme
volitelné předání dat ze `$_SESSION` do dat formuláře.

### Smazání osoby
Mazání osob provedeme přidáním formuláře s potvrzením na výpisu osob:

```html
<form action="smazat/{$p['id']}" onsubmit="return confirm('Opravdu smazat osobu?')" method="post">
	<input type="submit" value="Smazat" />
</form>
```

ID osoby pro smazání bude přímo v URL a bude podle toho vypadat i routa, po smazání přesměrujeme na výpis osob. Je dobré
si všimnout jak je postavena URL pro přesměrování - jelikož se o přesměrování stará webový prohlížeč na základě HTTP
hlavičky, a ten si myslí, že jsme ve složce `/smazat`, je nutné z této složky přejít o úroveň nahoru a zde otevřít
routu `vypis`, proto `../vypis`.

```php
$app->post('/smazat/{id}', function(Request $request, Response $response, $args) {
	try {
		$stmt = $this->db->prepare('DELETE FROM persons WHERE id = :id');
		$stmt->bindValue(':id', $args['id']);
		$stmt->execute();
		return $response->withHeader('Location', '../vypis');
	} catch (PDOException $e) {
		die($e->getMessage());
	}
});
```

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/974f532327911eafdcd541b0fa5c784db0558101)

### Editace osoby
Editace a vytvoření nové osoby bude velice podobné. Problém je, že jakmile nasměrujeme prohlížeč na URL `editace/{id}`,
začne si myslet, že všechny relativní cesty vedou od složky `editace`, např. odkaz v menu `<a href="vypis">...</a>`
povede na neexistující adresu `editace/vypis`. Proto je nutné zavést `<base href="...">` značku v `<head>`. Pomocí
této značky budou všechny relativní adresy (začínající jinak než `http://` a `/`) prefixovány hodnotou v `href` atributu
`<base>` značky. Takže pokud máte aplikaci nahranou např. ve složce `slim_demo`, měla by vaše `<base>` značka vypadat
takto: `<base href="/slim_demo/public/">`.

Vhodné místo k detekci aktuální cesty k aplikaci je [*middleware*](https://www.slimframework.com/docs/concepts/middleware.html),
který se spouští vždy před/po vlastní obsluze routy. Je také nutné přidat do našeho adaptéru pro Latte možnost vložit
proměnnou do každé renderované šablony. *Middleware* je i vhodné místo pro ověření, zda je uživatel přihlášen nebo ne.

```php
$app->add(function (Request $request, Response $response, callable $next) {
	$currentPath = dirname($_SERVER['PHP_SELF']);
	$this->view->addParams([
		'base_path' => $currentPath == '/' ? $currentPath : $currentPath . '/'
	]);
	return $next($request, $response);
});
```

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/974f532327911eafdcd541b0fa5c784db0558101)

### Bootstrap
[Bootstrap](http://getbootstrap.com) přidáme z CDN, podobně i jeho závislost na jQuery. Jako obvykle je potřeba
přidat třídu `form-control` na vstupní pole formulářů a zabalit celou strukturu aplikace do prvku s třídou `container`.

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/86d6be9d344e2d7338f3ed1d0bfc71cbf140fbfa)

### Přihlašování uživatel
Přihlašování vyžaduje obvykle vytvoření tabulky s uživateli v databázi, zde si ukážeme jen základní přihlášení a ověření
pomocí middleware a uživatelský účet uložíme staticky do `src/settings.php` (login i heslo je "admin"):

```php
'auth' => [
	'user' => 'admin',
	'pass' => 'd033e22ae348aeb5660fc2140aec35850c4da997'
],
```

Obvykle se očekává, že v aplikaci bude několik rout, které budou přístupné pouze pro přihlášené, je proto dobré tyto
routy [seskupit](https://www.slimframework.com/docs/objects/router.html#route-groups) a navázat na ně middleware, který
bude ověřovat přihlášení v session (tedy bude aktivován jen pro tuto skupinu). První parametr metody `group()` je
URL prefix pro všechny routy uvnitř, v callbacku potom definujeme jednotlivé routy na `$this` (místo `$app`):

```php
$app->group('/user', function () {

	$this->get('/profil', function(Request $request, Response $response) {});

	$this->get('/odhlasit', function(Request $request, Response $response) {});

})->add(function(Request $request, Response $response, callable $next) {
	if(!empty($_SESSION['logged_in'])) {
		$this->renderer->addParams(['logged_in' => true]);
		return $next($request, $response);
	} else {
		return $response->withStatus(401)->withHeader('Location', '../vypis');
	}
});
```

Pomocí metody `addParams` na třídě `LatteView` můžeme do šablony poslat informaci o tom, že je uživatel přihlášen a tím
např. zařídit skrytí přihlašovacího tlačítka v menu.

Samotné přihlášení je obyčejný POST požadavek, který musí být veřejný a jen ověří uživatele podle loginu a hesla.
Tomuto ještě předchází vykreslení přihlašovacího formuláře. Úspěšné přihlášení je zaznamenáno do session:

```php
$app->get('/prihlasit', function(Request $request, Response $response) {
	return $this->renderer->render($response, 'login.latte');
});

$app->post('/prihlasit', function(Request $request, Response $response) {
	$data = $request->getParsedBody();
	if($data['login'] == $this->settings['auth']['user'] && sha1($data['pass']) == $this->settings['auth']['pass']) {
		$_SESSION['logged_in'] = true;
		return $response->withHeader('Location', 'user/profil');
	}
	return $response->withHeader('Location', 'prihlasit');
});
```

Na routy chráněné pomocí middleware se dá dostat jen po přihlášení, jinak je uživatel přesměrován jinam.

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/175bfd1be6b65e565f3ac55c3fc80a19b8a6a144)

## Rozšíření o REST cesty
REST rozhraní je to, pro co byl framework Slim primárně navržen. Zkusíme vytvořit pomocí jQuery jednoduchý skript,
který bude AJAXem tahat data z backendu. Konkrétně půjde o načtení detailu osoby do popup okna. Knihovna jQuery už
je v projektu připojena kvůli Bootstrapu, není nutné ji tedy přidávat. Do výpisu osob přidáme prvek s atributem
`data-person-info`, krom toho, že jej použijeme k předání ID osoby, bude sloužit i k vyvolání vlastního popupu.
Jmenovaný atribut použijeme jako CSS selektor.

```html
<span class="glyphicon glyphicon-info-sign" data-person-info="{$['id_person']}"></span>
```

Ve složce `public` vytvoříme např. složku `js` a do ní vložíme soubor `person_detail.js`. Tento potom připojíme
buď v hlavičce v souboru `templates/layout.latte` nebo někde v souboru s výpisem osob `templates/index.latte`:

```html
<script type="text/javascript" src="js/person_detail.js"></script>
```

Pro prvotní ověření jen obsloužíme událost click na element s data atributem v souboru `person_detail.js`:

```javascript
$(document).ready(function() {
	$('[data-person-info]').click(function() {
		alert(this.dataset.personInfo);
	});
});
```

V backendu nachystáme API endpoint pro zjištění informací o osobě podle ID. Routa vypadá podobně jako ostatní, ale
odpověď není generována přes Latte adaptér, ale pomocí metody `withJSON`, která vezme asociativní pole a převede jej
na JSON strukturu.

```php
$app->get('/api/osoba/{id}', function (Request $request, Response $response, $args) {
	try {
		$stmt = $this->db->prepare('SELECT * FROM persons WHERE id = :id');
		$stmt->bindValue(':id', $args['id']);
		$stmt->execute();
		$person = $stmt->fetch(PDO::FETCH_ASSOC);
		if($person) {
			return $response->withJSON($person);
		} else {
			return $response->withJSON(['message' => 'Person not found.'], 404);
		}
	} catch (PDOException $e) {
		return $response->withJSON(['message' => $e->getMessage()], 500);
	}
});
```

Na frontendu z této URL stáhneme data pomocí funkce [`getAJAX()`](http://api.jquery.com/jQuery.getJSON/) z jQuery.

```javascript
$('[data-person-info]').click(function() {
	$.getJSON('api/osoba/' + this.dataset.personInfo, function(response) {
		console.log(response);
		alert(response.first_name + ' ' + response.last_name + '\r\n' + response.nickname);
	});
});
```

Sledujte v konzoli a síťové konzoli vývojářských nástrojů, co se děje.

[Zdrojové kódy](https://github.com/lysek/wa_slim_walkthrough/commit/e804241c5ed3e0aaab83e4beaa195cc0fc82c07c)

## Názvy rout
V dokumentaci wrapperu [Latte View](https://github.com/ujpef/latte-view) je popsáno, jak vytvořit makro pro šablony
`{link routeName}`, které poslouží pro generování cest v HTML šablonách.

```php
$app->get('/neco', function(Request $request, Response $response, $args) {
    //...        
})->setName('routeName');
```

```html
<a href="{link routeName}">Klikem se vyvolá routa /neco</a>
```

Makro generuje pouze URL (eventualně umí vložit parametry místo placeholderů), ale query parametry už přidáváte ručně. 

```php
$app->get('/neco', function(Request $request, Response $response, $args) {
    $id = $request->getQueryParam('id');
    //...        
})->setName('routeName');
```

```html
<a href="{link routeName}?id={$id}">Klikem se vyvolá routa /neco?id=123</a>
```

Místo proměnné base path lze použít `{link index}`.

```php
$app->get('/', function(Request $request, Response $response, $args) {
    //...        
})->setName('index');
```

```html
<head>
	<base href="{link index}">
</head>
```

Případně lze base značku vypustit a generovat cesty ke statickým souborům přes toto makro.

```html
<head>
	<script type="text/javascript" src="{link index}js/person_detail.js"></script>
	<!-- nebo -->
	<script type="text/javascript" src="{$base_path}js/person_detail.js"></script>
</head>
```

## Poznámky
Je vidět, že aplikace se poměrně rychle rozrůstá, proto by nebylo špatné, rozdělit routy do více souborů (pomocí funkce
`include()` nebo `require()`).

### Rozjetí projektu na jiném stroji (po stažení z Gitu)
Příkazem `git clone http://adresa.repositare.cz/nazev.git slozka` se vám stáhne z Gitu kopie projektu. Jelikož jsou
některé důležité soubory a složky nastavené v souboru `.gitignore`, je potřeba primárně spustit příkaz
`composer install`, aby se stáhl vlastní framework a jeho knihovny. Poté nastavit konfigurace v `/.env`, který
vytvoříte jako kopii souboru `.env.example`.