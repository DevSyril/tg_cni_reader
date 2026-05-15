# tg-cni-reader

**Togolese National ID Card (CNI) OCR Processor** — Extrait et valide les données des cartes d'identité nationales togolaises par OCR.

![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-777BB4) ![Licence](https://img.shields.io/badge/license-MIT-green)

---

## Table des matières

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Prérequis système](#prérequis-système)
- [Installation](#installation)
- [Utilisation](#utilisation)
  - [Syntaxe de base](#1-syntaxe-de-base)
  - [Traitement à partir de deux images (recto + verso)](#2-traitement-à-partir-de-deux-images-recto--verso)
  - [Traitement à partir d'un PDF](#3-traitement-à-partir-dun-pdf)
  - [Accès aux données extraites](#4-accès-aux-données-extraites)
  - [Interprétation des statuts de validation](#5-interprétation-des-statuts-de-validation)
- [Architecture du package](#architecture-du-package)
  - [Structure des dossiers](#structure-des-dossiers)
  - [Diagramme de dépendances](#diagramme-de-dépendances)
- [API complète](#api-complète)
  - [TgIdProcessor\Features\CniProcess](#tgidprocessorfeaturescniprocess)
  - [TgIdProcessor\Models](#tgidprocessormodels)
  - [TgIdProcessor\Tools](#tgidprocessortools)
  - [TgIdProcessor\Processors](#tgidprocessorprocessors)
  - [TgIdProcessor\Dictionnaries](#tgidprocessordictionnaries)
  - [TgIdProcessor\Contracts](#tgidprocessorcontracts)
- [Validation des données](#validation-des-données)
- [Tests](#tests)
- [Dépendances](#dépendances)
- [Licence](#licence)

---

## Présentation

`tg-cni-reader` est une bibliothèque PHP qui utilise **Tesseract OCR** pour lire automatiquement les informations sur la **Carte Nationale d'Identité (CNI) togolaise**.

Elle accepte :
- Une image du **recto** de la carte
- Une image du **verso** de la carte
- Un **PDF** contenant les deux faces

La bibliothèque extrait, structure et valide l'ensemble des champs présents sur la carte, y compris la **zone MRZ** (Machine Readable Zone).

---

## Fonctionnalités

- **Extraction OCR** des textes du recto et du verso via Tesseract (langue française)
- **Parsing des champs** : numéro de carte, nom, prénom, date de naissance, sexe, profession, dates d'émission/expiration, taille, groupe sanguin, adresse, téléphone, parents, personne à prévenir, signatures, etc.
- **Décodage et validation de la zone MRZ** selon la norme ICAO 9303 (clés de contrôle)
- **Croisement des données** recto / verso (vérification de cohérence)
- **Validation contextuelle** :
  - Numéros de téléphone togolais
  - Groupes sanguins valides
  - Localités togolaises (via un dictionnaire intégré)
  - Format du numéro de carte (11 chiffres, 2 tirets)
  - Taille dans une plage raisonnable (120–250 cm)
  - Détection de l'expiration de la carte
- **Drapeaux de validation** individuels par champ (`ValueStat`)
- **Indicateur global** : carte expirée (`isExpired`) et carte invalide (`isInvalid`)

---

## Prérequis système

Avant d'installer le package, assurez-vous d'avoir :

| Logiciel | Rôle | Installation |
|---|---|---|
| **PHP** >= 8.1 | Langage d'exécution | [php.net](https://www.php.net/downloads) |
| **Composer** | Gestionnaire de dépendances PHP | [getcomposer.org](https://getcomposer.org/) |
| **Tesseract OCR** | Moteur de reconnaissance optique de caractères | [GitHub UB-Mannheim](https://github.com/UB-Mannheim/tesseract/wiki) (Windows), `sudo apt install tesseract-ocr` (Linux), `brew install tesseract` (macOS) |
| **Données linguistiques françaises** pour Tesseract | Reconnaissance du français | Inclus dans `tessdata/fra.traineddata`, ou installer via `sudo apt install tesseract-ocr-fra` |
| **Ghostscript** | Conversion PDF → image (optionnel, requis uniquement pour `processCNIPdf`) | [ghostscript.com](https://www.ghostscript.com/download/gsdnld.html) (Windows), `sudo apt install ghostscript` (Linux), `brew install ghostscript` (macOS) |
| **Extension PHP GD ou Imagick** | Manipulation d'images | Incluse par défaut avec PHP (GD), ou `pecl install imagick` |

---

## Installation

```bash
# 1. Installer via Composer le dépôt
composer require devsyril/tg_cni_reader

# 2. Installer les dépendances PHP
composer install

# 3. Vérifier que Tesseract est accessible
tesseract --version

# 4. Vérifier que Ghostscript est accessible (si utilisation PDF)
gs --version
```

Le fichier de données linguistiques françaises (`tessdata/fra.traineddata`) est inclus dans le dépôt. Vous pouvez également utiliser votre propre dossier Tesseract en configurant la variable d'environnement `TESSDATA_PREFIX`.

---

## Utilisation

### 1. Syntaxe de base

```php
<?php
require_once 'vendor/autoload.php';

use TgIdProcessor\Features\CniProcess;
use TgIdProcessor\Tools\ImageReader;
use TgIdProcessor\Tools\PdfConverter;
use TgIdProcessor\Processors\Processor;
use TgIdProcessor\Processors\FrontProcessor;
use TgIdProcessor\Processors\BackProcessor;
use TgIdProcessor\Processors\DigitChecker;
use TgIdProcessor\Processors\Analyser;

// 1. Configurer le chemin de Tesseract
$tesseractPath = 'C:\Program Files\Tesseract-OCR\tesseract.exe'; // Windows
// $tesseractPath = '/usr/bin/tesseract';                        // Linux / macOS

// 2. Instancier les dépendances
$imageReader   = new ImageReader($tesseractPath, 'fra', __DIR__ . '/tessdata');
$pdfConverter  = new PdfConverter(__DIR__ . '/data');
$digitChecker  = new DigitChecker();
$backProcessor = new BackProcessor($digitChecker);
$frontProcessor = new FrontProcessor();
$processor     = new Processor($frontProcessor, $backProcessor);
$analyser      = new Analyser();

// 3. Créer le processeur principal
$cni = new CniProcess($imageReader, $pdfConverter, $processor, $analyser);
```

### 2. Traitement à partir de deux images (recto + verso)

```php
$card = $cni->processCNI('recto.jpeg', 'verso.jpeg');
```

Le paramètre `$backPath` est optionnel. Si non fourni, le verso sera ignoré (données limitées).

### 3. Traitement à partir d'un PDF

```php
$card = $cni->processCNIPdf('carte_identite.pdf');
```

Le PDF est automatiquement converti en image PNG (recadrée et agrandie) avant d'être soumis à l'OCR.

### 4. Accès aux données extraites

Chaque champ est un objet `ValueStat` contenant :
- `value` (`?string`) — la valeur extraite
- `stat` (`?bool`) — le statut de validation (`true` = valide, `false` = invalide, `null` = non vérifié)

```php
// --- Données du recto ---
echo "N° carte : "          . $card->front->cardNumber->value       . "\n";
echo "Nom : "                . $card->front->lastName->value         . "\n";
echo "Prénom : "             . $card->front->firstName->value        . "\n";
echo "Date naissance : "     . $card->front->birthDate->value        . "\n";
echo "Sexe : "               . $card->front->sex->value              . "\n";
echo "Lieu naissance : "     . $card->front->birthLocation->value    . "\n";
echo "Préfecture : "         . $card->front->birthPrefecture->value  . "\n";
echo "Profession : "         . $card->front->profession->value       . "\n";
echo "Date émission : "      . $card->front->issueDate->value        . "\n";
echo "N° police : "          . $card->front->policeOfficeNumber->value . "\n";
echo "Date expiration : "    . $card->front->expiryDate->value       . "\n";

// --- Données du verso ---
echo "Taille : "             . $card->back->size->value              . "\n";
echo "Groupe sanguin : "     . $card->back->bloodType->value         . "\n";
echo "Adresse : "            . $card->back->address->value           . "\n";
echo "Téléphone : "          . $card->back->tel->value               . "\n";
echo "Père (prénom) : "      . $card->back->fatherFirstName->value   . "\n";
echo "Père (nom) : "         . $card->back->fatherLastName->value    . "\n";
echo "Mère (prénom) : "      . $card->back->motherFirstName->value   . "\n";
echo "Mère (nom) : "         . $card->back->motherLastName->value    . "\n";
echo "Personne à prévenir : ". $card->back->personToContactName->value . "\n";
echo "N° document MRZ : "    . $card->back->mrzDocumentNumber->value . "\n";
echo "Pays MRZ : "           . $card->back->country->value           . "\n";

// --- Indicateurs globaux ---
echo "Carte expirée : "      . ($card->isExpired ? 'Oui' : 'Non')    . "\n";
echo "Carte invalide : "     . ($card->isInvalid  ? 'Oui' : 'Non')   . "\n";
```

### 5. Interprétation des statuts de validation

```php
// Vérifier la cohérence entre recto et MRZ
echo "Nom conforme MRZ : "   . ($card->front->lastName->stat   ? 'OK' : 'ÉCHEC') . "\n";
echo "Prénom conforme MRZ : ". ($card->front->firstName->stat  ? 'OK' : 'ÉCHEC') . "\n";
echo "Date naiss. conforme : ". ($card->front->birthDate->stat  ? 'OK' : 'ÉCHEC') . "\n";
echo "Sexe conforme MRZ : "  . ($card->front->sex->stat       ? 'OK' : 'ÉCHEC') . "\n";
echo "Expiration conforme : " . ($card->front->expiryDate->stat ? 'OK' : 'ÉCHEC') . "\n";

// Vérifier la validité des données du verso
echo "Groupe sanguin valide : "   . ($card->back->bloodType->stat   ? 'OK' : 'ÉCHEC') . "\n";
echo "Téléphone valide : "        . ($card->back->tel->stat         ? 'OK' : 'ÉCHEC') . "\n";
echo "Localité valide : "         . ($card->back->address->stat     ? 'OK' : 'ÉCHEC') . "\n";
echo "Taille plausible : "        . ($card->back->size->stat        ? 'OK' : 'ÉCHEC') . "\n";
echo "Pays correct (TOGO) : "     . ($card->back->country->stat     ? 'OK' : 'ÉCHEC') . "\n";

// Vérifier le numéro de carte
echo "N° carte valide : "         . ($card->front->cardNumber->stat ? 'OK' : 'ÉCHEC') . "\n";
```

---

## Architecture du package

### Structure des dossiers

```
tg_cni_reader/
├── composer.json              # Définition du package Composer
├── composer.lock              # Verrouillage des versions
├── phpunit.xml                # Configuration PHPUnit
├── README.md                  # Cette documentation
├── data/
│   ├── Local.txt              # Dictionnaire des localités togolaises (JSON)
│   ├── recto.jpeg             # Exemple d'image recto
│   └── verso.jpeg             # Exemple d'image verso
├── tessdata/
│   └── fra.traineddata        # Données linguistiques françaises pour Tesseract
├── src/
│   ├── Contracts/             # Interfaces (contrats)
│   ├── Dictionnaries/         # Dictionnaires et constantes
│   ├── Features/              # Point d'entrée principal
│   ├── Models/                # Modèles de données (ValueStat, Front, Back, Card, etc.)
│   ├── Processors/            # Logique métier (OCR parsing, validation, analyse)
│   └── Tools/                 # Utilitaires (ImageReader, PdfConverter, FileManager)
├── tests/
│   ├── fixtures/              # Fichiers de test (images, PDF, OCR attendus)
│   ├── Integration/           # Tests d'intégration
│   └── Unit/                  # Tests unitaires
└── vendor/                    # Dépendances Composer
```

### Diagramme de dépendances

```
                    ┌─────────────────────┐
                    │    CniProcess        │  Point d'entrée
                    │  (Features)          │
                    └──────┬──────┬───────┘
                           │      │
              ┌────────────┘      └────────────┐
              ▼                                 ▼
      ┌───────────────┐                ┌───────────────┐
      │  ImageReader   │                │  PdfConverter  │
      │  (Tools)       │                │  (Tools)       │
      └───────┬───────┘                └───────┬───────┘
              │                                 │
              ▼                                 ▼
      ┌───────────────────────────────────────────────┐
      │              Processor (Processors)             │
      └──────────────────────┬────────────────────────┘
                             │
              ┌──────────────┴──────────────┐
              ▼                              ▼
     ┌─────────────────┐          ┌────────────────────┐
     │  FrontProcessor  │          │   BackProcessor    │
     │  (Processors)    │          │   (Processors)     │
     └────────┬─────────┘          └────────┬───────────┘
              │                              │
              │                     ┌────────▼────────┐
              │                     │  DigitChecker    │
              │                     │  (Processors)    │
              │                     └────────┬─────────┘
              │                              │
              └──────────────┬───────────────┘
                             ▼
                    ┌─────────────────┐
                    │    Analyser      │
                    │  (Processors)    │
                    └────────┬────────┘
                             ▼
                    ┌─────────────────┐
                    │   Card (Model)  │  ← Résultat
                    └─────────────────┘
```

---

## API complète

### TgIdProcessor\Features\CniProcess

Point d'entrée principal du package.

```php
class CniProcess implements CniProcessInterface
{
    public function __construct(
        ImageReaderInterface $imageReader,
        PdfConverterInterface $pdfConverter,
        ProcessorInterface $processor,
        AnalyserInterface $analyser
    );

    public function processCNI(string $frontPath, ?string $backPath = null): Card;
    public function processCNIPdf(string $filePath): Card;
}
```

| Méthode | Paramètres | Retour | Description |
|---|---|---|---|
| `processCNI` | `$frontPath` (chemins JPEG recto), `$backPath` (chemin JPEG verso, optionnel) | `Card` | Exécute l'OCR sur les deux faces et retourne les données validées |
| `processCNIPdf` | `$filePath` (chemin PDF) | `Card` | Convertit le PDF en image, puis exécute `processCNI` |

---

### TgIdProcessor\Models

#### `ValueStat`

Conteneur pour une valeur extraite et son statut de validation.

```php
class ValueStat
{
    public ?string $value;
    public ?bool $stat;

    public function __construct(?string $value = null, ?bool $stat = null);
}
```

#### `Front`

Données extraites du recto de la carte. Toutes les propriétés sont des `ValueStat`.

| Propriété | Type | Description |
|---|---|---|
| `cardNumber` | `ValueStat` | Numéro de carte (format: `XX-XXXXXXX-XX`) |
| `lastName` | `ValueStat` | Nom de famille |
| `firstName` | `ValueStat` | Prénom(s) |
| `birthDate` | `ValueStat` | Date de naissance |
| `sex` | `ValueStat` | Sexe (M/F) |
| `birthLocation` | `ValueStat` | Lieu de naissance |
| `birthPrefecture` | `ValueStat` | Préfecture de naissance |
| `profession` | `ValueStat` | Profession |
| `issueDate` | `ValueStat` | Date d'émission |
| `policeOfficeNumber` | `ValueStat` | Numéro de bureau de police |
| `expiryDate` | `ValueStat` | Date d'expiration |

Accès alternatif : `$front->dateOfBirth` retourne la valeur de `birthDate` (via `__get`).

#### `Back`

Données extraites du verso de la carte. Toutes les propriétés sont des `ValueStat`.

| Propriété | Type | Description |
|---|---|---|
| `size` | `ValueStat` | Taille en cm |
| `bloodType` | `ValueStat` | Groupe sanguin |
| `address` | `ValueStat` | Adresse / localité |
| `tel` | `ValueStat` | Numéro de téléphone |
| `particularSign` | `ValueStat` | Signes particuliers |
| `documentNumber` | `ValueStat` | Numéro de document |
| `fatherFirstName` | `ValueStat` | Prénom du père |
| `fatherLastName` | `ValueStat` | Nom du père |
| `motherFirstName` | `ValueStat` | Prénom de la mère |
| `motherLastName` | `ValueStat` | Nom de la mère |
| `personToContactName` | `ValueStat` | Nom de la personne à prévenir |
| `personToContactAddress` | `ValueStat` | Adresse de la personne à prévenir |
| `personToContactTel` | `ValueStat` | Téléphone de la personne à prévenir |
| `country` | `ValueStat` | Pays (MRZ) |
| `mrzDocumentNumber` | `ValueStat` | N° document (ligne MRZ) |
| `mrzBirthDate` | `ValueStat` | Date naissance (MRZ) |
| `mrzSex` | `ValueStat` | Sexe (MRZ) |
| `mrzExpiryDate` | `ValueStat` | Date expiration (MRZ) |
| `mrzLastName` | `ValueStat` | Nom (MRZ) |
| `mrzFirstName` | `ValueStat` | Prénom (MRZ) |

#### `Card`

Résultat complet du traitement.

```php
class Card
{
    public Front $front;
    public Back $back;
    public ?bool $isExpired;   // true si la carte est expirée (MRZ date < aujourd'hui)
    public ?bool $isInvalid;   // true si les contrôles MRZ ont échoué
}
```

#### `CardData` et `Owner`

Modèles simplifiés pour un accès direct aux données sans objets `ValueStat`.

```php
class CardData
{
    public ?Owner $owner;
    public ?string $cardNumber;
    public ?string $policeOfficeNumber;
    public ?string $country;
    public ?string $issueDate;
    public ?string $expiryDate;
    public ?string $documentNumber;
}

class Owner
{
    public ?string $firstName, $lastName, $sex, $tel;
    public ?string $birthDate, $birthLocal, $birthPrefecture;
    public ?string $profession, $particularSign, $address;
    public ?string $bloodType, $size;
    public ?string $fatherFirstName, $fatherLastName;
    public ?string $motherFirstName, $motherLastName;
    public ?string $personToContactName, $personToContactAddress, $personToContactTel;
}
```

---

### TgIdProcessor\Tools

#### `ImageReader` (`implements ImageReaderInterface`)

Exécute Tesseract OCR sur une image et retourne les lignes de texte.

```php
class ImageReader implements ImageReaderInterface
{
    public function __construct(
        string $tesseractPath = '',   // Chemin vers l'exécutable Tesseract
        string $language = 'fra',     // Langue OCR
        string $tessdataPrefix = ''   // Chemin vers le dossier tessdata
    );

    public function readTextOnImage(string $path): array;
    // Retourne un tableau de lignes de texte reconnu par OCR
}
```

#### `PdfConverter` (`implements PdfConverterInterface`)

Convertit un PDF en image PNG pour le soumettre à l'OCR.

```php
class PdfConverter implements PdfConverterInterface
{
    public function __construct(
        string $outputDir = '',   // Dossier de sortie pour l'image générée
        int $resolution = 200     // Résolution de conversion (DPI)
    );

    public function transformPdfToImage(string $pdfPath): string;
    // Retourne le chemin de l'image PNG générée
}
```

#### `FileManager`

Gestion de fichiers (copie, sauvegarde, suppression).

```php
class FileManager
{
    public function __construct(string $dataDir = '');

    public function saveFile(string $sourcePath, ?string $fileName = null): string;
    // Copie un fichier vers le dossier data, avec upscale optionnel

    public function deleteFile(string $path): void;
}
```

---

### TgIdProcessor\Processors

#### `Processor` (`implements ProcessorInterface`)

Façade qui délègue le traitement recto/verso aux processeurs spécialisés.

```php
class Processor implements ProcessorInterface
{
    public function __construct(
        FrontProcessorInterface $frontProcessor,
        BackProcessorInterface $backProcessor
    );

    public function processFront(Front $front, array $text): Front;
    public function processBack(Back $back, array $text): array; // retourne [$back, $check]
}
```

#### `FrontProcessor` (`implements FrontProcessorInterface`)

Parse les lignes OCR du recto pour remplir un objet `Front`.

```php
class FrontProcessor implements FrontProcessorInterface
{
    public function processFront(Front $frontInfo, array $textToList): Front;
}
```

- Détecte le numéro de carte par expression régulière (`/\d{2}-\d{7}-\d{2}/`)
- Extrait le nom, prénom, date de naissance, sexe, profession, date d'émission, date d'expiration
- Identifie le lieu et la préfecture de naissance

#### `BackProcessor` (`implements BackProcessorInterface`)

Parse les lignes OCR du verso pour remplir un objet `Back`.

```php
class BackProcessor implements BackProcessorInterface
{
    public function __construct(DigitCheckerInterface $digitChecker);

    public function processBack(Back $backInfo, array $textToList): array; // retourne [$backInfo, $check]
}
```

- Nettoie et isole les lignes MRZ
- Extrait : taille, groupe sanguin, adresse, téléphone, parents, personne à prévenir
- Délègue à `DigitChecker` la validation des clés MRZ

#### `DigitChecker` (`implements DigitCheckerInterface`)

Valide les clés de contrôle de la zone MRZ selon la norme ICAO 9303.

```php
class DigitChecker implements DigitCheckerInterface
{
    public function check(array $backText, Back $backData): bool;
}
```

Algorithme de clé de contrôle :
- Poids : `[7, 3, 1]` appliqués cycliquement
- Caractères autorisés : `0-9`, `A-Z`, `<`
- `<` vaut `0`, `A` vaut `10`, ..., `Z` vaut `35`

#### `Analyser` (`implements AnalyserInterface`)

Croise et valide l'ensemble des données recto/verso et produit l'objet `Card` final.

```php
class Analyser implements AnalyserInterface
{
    public function compare(Front $front, Back $back): Card;
}
```

Validations effectuées (liste exhaustive) :

| Validation | Champ(s) concerné(s) | Critère |
|---|---|---|
| Cohérence nom | `lastName` / `mrzLastName` | Identité entre recto et MRZ |
| Cohérence prénom | `firstName` / `mrzFirstName` | Identité entre recto et MRZ |
| Cohérence date naissance | `birthDate` / `mrzBirthDate` | Correspondance recto ↔ MRZ |
| Cohérence sexe | `sex` / `mrzSex` | Correspondance recto ↔ MRZ |
| Cohérence expiration | `expiryDate` / `mrzExpiryDate` | Correspondance recto ↔ MRZ |
| Clés MRZ | `mrz*` | Validation ICAO 9303 via `DigitChecker` |
| Groupe sanguin | `bloodType` | Doit faire partie de : A+, A-, B+, B-, AB+, AB-, O+, O- |
| Téléphone | `tel` | Doit commencer par 70, 79, 90, 91, 92, 93, 96, 97, 98, 99 (préfixes mobiles Togo) |
| Localité | `address`, `birthLocation` | Doit exister dans `data/Local.txt` |
| Pays | `country` | Doit être "TOGO" |
| Taille | `size` | Doit être entre 120 et 250 cm |
| Numéro carte | `cardNumber` | Format `XX-XXXXXXX-XX` (11 chiffres, 2 tirets) |
| Expiration | `isExpired` | Date d'expiration MRZ < date courante |
| Invalidation | `isInvalid` | `true` si `DigitChecker` a échoué OU incohérence majeure |

---

### TgIdProcessor\Dictionnaries

#### `Dics`

Dictionnaires et constantes de validation.

```php
class Dics
{
    const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    const PHONE_NUMBER_STARTERS = ['70', '79', '90', '91', '92', '93', '96', '97', '98', '99'];
    const PARTICULAR_SIGNS = ['néant', 'neant', 'aucun', 'non'];
    const COUNTRY = 'TOGO';

    public static function localExist(string $localName): bool;
    // Vérifie si une localité existe dans le dictionnaire (data/Local.txt)

    public static function setLocalContentForTests(string $content): void;
    // Surcharge le dictionnaire pour les tests

    public static function resetLocalContent(): void;
    // Réinitialise le dictionnaire
}
```

Le fichier `data/Local.txt` contient une structure JSON hiérarchique des localités togolaises (régions, préfectures, communes, cantons, districts, etc.).

---

### TgIdProcessor\Contracts

Interfaces pour permettre l'injection de dépendances et faciliter les tests unitaires.

| Interface | Méthodes |
|---|---|
| `CniProcessInterface` | `processCNI(string $frontPath, ?string $backPath): Card`, `processCNIPdf(string $filePath): Card` |
| `ImageReaderInterface` | `readTextOnImage(string $path): array` |
| `PdfConverterInterface` | `transformPdfToImage(string $pdfPath): string` |
| `ProcessorInterface` | `processFront(Front $front, array $text): Front`, `processBack(Back $back, array $text): array` |
| `FrontProcessorInterface` | `processFront(Front $frontInfo, array $textToList): Front` |
| `BackProcessorInterface` | `processBack(Back $backInfo, array $textToList): array` |
| `DigitCheckerInterface` | `check(array $backText, Back $backData): bool` |
| `AnalyserInterface` | `compare(Front $front, Back $back): Card` |

---

## Validation des données

Le processus de validation suit ces étapes :

```
OCR (Tesseract) → Parsing recto (FrontProcessor)
                → Parsing verso (BackProcessor + DigitChecker)
                → Analyse croisée (Analyser)
                → Objet Card avec champs validés
```

**Étape 1 — OCR** : Tesseract lit le texte des images et retourne des lignes brutes.

**Étape 2 — Parsing recto** : `FrontProcessor` identifie et extrait chaque champ du recto.

**Étape 3 — Parsing verso** : `BackProcessor` extrait les champs du verso et `DigitChecker` valide la zone MRZ :
- Vérifie la présence de 3 lignes MRZ
- Calcule les clés de contrôle pour chaque champ MRZ (numéro document, date naissance, date expiration)
- Compare la clé calculée avec la clé lue

**Étape 4 — Analyse croisée** : `Analyser` compare les données recto/MRZ et applique toutes les validations contextuelles (groupe sanguin, téléphone, localité, taille, pays, format carte, expiration).

**Étape 5 — Résultat** : Un objet `Card` avec :
- Chaque champ doté d'un `ValueStat` (valeur + statut de validation)
- `$card->isExpired` : `true` si la date MRZ est dépassée
- `$card->isInvalid` : `true` si `DigitChecker` a échoué ou incohérence majeure

---

## Tests

```bash
# Lancer tous les tests
composer test

# Ou directement avec PHPUnit
vendor/bin/phpunit
```

### Structure des tests

| Dossier | Contenu | Description |
|---|---|---|
| `tests/Unit/` | `AnalyserTest`, `CniProcessTest`, `DicsTest`, `DigitCheckerTest` | Tests unitaires avec données OCR simulées |
| `tests/Integration/` | `FullPipelineTest`, `RealDataTest`, `RealImagePipelineTest` | Tests d'intégration avec images réelles et Tesseract |

Les tests d'intégration utilisant des images réelles (`RealImagePipelineTest`) nécessitent :
- Tesseract OCR installé sur le système
- Les fichiers de fixtures présents dans `tests/fixtures/` (`recto.jpeg`, `verso.jpeg`)

Si ces prérequis ne sont pas satisfaits, les tests concernés sont automatiquement ignorés via `$this->markTestSkipped()`.

---

## Dépendances

### Production

| Package | Version | Rôle |
|---|---|---|
| `php` | `>=8.1` | Langage d'exécution |
| `thiagoalessio/tesseract_ocr` | `^2.13` | Wrapper PHP pour Tesseract OCR |
| `spatie/pdf-to-image` | `^3.0` | Conversion PDF → image |
| `intervention/image` | `^3.0` | Manipulation d'images |
| `ramsey/uuid` | `^4.7` | Génération d'UUID |

### Développement

| Package | Version | Rôle |
|---|---|---|
| `phpunit/phpunit` | `^11.0` | Framework de tests unitaires |

---

## Licence

Ce projet est distribué sous licence **MIT**. Voir le fichier `LICENSE` pour plus d'informations.
