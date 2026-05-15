# tg-document-reader

**Togolese Identity Documents OCR Processor** — Extrait et valide les données des pièces d'identité togolaises par OCR.

![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-777BB4) ![Licence](https://img.shields.io/badge/license-MIT-green)

---

## Table des matières

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Documents supportés](#documents-supportés)
- [Prérequis système](#prérequis-système)
- [Installation](#installation)
- [Utilisation](#utilisation)
  - [Syntaxe de base](#1-syntaxe-de-base)
  - [CNI (Carte Nationale d'Identité)](#2-cni-carte-nationale-didentité)
  - [Permis de conduire](#3-permis-de-conduire)
  - [Passeport](#4-passeport)
  - [Détection automatique](#5-détection-automatique-du-type-de-document)
  - [Accès aux données extraites](#6-accès-aux-données-extraites)
- [Architecture du package](#architecture-du-package)
  - [Structure des dossiers](#structure-des-dossiers)
  - [Diagramme de dépendances](#diagramme-de-dépendances)
- [Tests](#tests)
- [Dépendances](#dépendances)
- [Licence](#licence)

---

## Présentation

`tg-document-reader` est une bibliothèque PHP qui utilise **Tesseract OCR** pour lire automatiquement les informations sur les pièces d'identité togolaises :

- **Carte Nationale d'Identité (CNI)**
- **Passeport** (avec décodage MRZ format TD3)
- **Permis de conduire**

La bibliothèque extrait, structure et valide l'ensemble des champs présents sur chaque document.

---

## Fonctionnalités

- **Extraction OCR** via Tesseract (langue française)
- **Architecture pilotée par driver** : chaque type de document a son propre parseur
- **Décodage MRZ** : support des formats TD1 (CNI, 3 lignes) et TD3 (passeport, 2 lignes)
- **Validation MRZ** selon la norme ICAO 9303 (clés de contrôle)
- **Croisement des données** recto/verso (CNI, permis)
- **Dictionnaire des localités togolaises** intégré
- **Drapeaux de validation** individuels par champ (`ValueStat`)
- **Détection d'expiration** du document
- **Détection automatique** du type de document via OCR

---

## Documents supportés

| Document | Faces | MRZ | Formats image supportés |
|---|---|---|---|
| CNI | Recto + Verso | TD1 (3 lignes, `I<...`) | JPEG, PNG, PDF |
| Passeport | Recto uniquement | TD3 (2 lignes, `P<...`) | JPEG, PNG, PDF |
| Permis de conduire | Recto + Verso | Aucun | JPEG, PNG, PDF |

---

## Prérequis système

| Logiciel | Rôle | Installation |
|---|---|---|
| **PHP** >= 8.1 | Langage d'exécution | [php.net](https://www.php.net/downloads) |
| **Composer** | Gestionnaire de dépendances PHP | [getcomposer.org](https://getcomposer.org/) |
| **Tesseract OCR** | Moteur OCR | [GitHub UB-Mannheim](https://github.com/UB-Mannheim/tesseract/wiki) (Windows), `sudo apt install tesseract-ocr` (Linux), `brew install tesseract` (macOS) |
| **Données linguistiques françaises** | Reconnaissance du français | Inclus dans `tessdata/fra.traineddata` |
| **Ghostscript** | Conversion PDF → image (optionnel) | [ghostscript.com](https://www.ghostscript.com/download/gsdnld.html) |
| **Extension PHP GD ou Imagick** | Manipulation d'images | Incluse par défaut (GD) |

---

## Installation

```bash
composer require devsyril/tg-document-reader
composer install
```

---

## Utilisation

### 1. Syntaxe de base

```php
<?php
require_once 'vendor/autoload.php';

use TgDocumentProcessor\Features\DocumentProcessorFactory;
use TgDocumentProcessor\Models\DocumentType;

// Configurer Tesseract
$tesseractPath = 'C:\Program Files\Tesseract-OCR\tesseract.exe'; // Windows
// $tesseractPath = '/usr/bin/tesseract';                         // Linux / macOS

// Créer la factory (l'ImageReader est créé automatiquement)
$factory = new DocumentProcessorFactory();

// Créer un processeur pour le type de document souhaité
$processor = $factory->create(DocumentType::CNI);
```

### 2. CNI (Carte Nationale d'Identité)

```php
use TgDocumentProcessor\Tools\ImageReader;

// Avec ImageReader personnalisé
$imageReader = new ImageReader($tesseractPath, 'fra', __DIR__ . '/tessdata');
$factory = new DocumentProcessorFactory($imageReader);
$processor = $factory->create(DocumentType::CNI);

// Depuis deux images (recto + verso)
$result = $processor->process('recto.jpeg', 'verso.jpeg');

// Depuis un PDF
$result = $processor->process('carte_identite.pdf');

// Depuis une seule image contenant les deux faces
$result = $processor->process('recto_verso.jpeg');

echo $result->get('card_number')->value;     
echo $result->get('last_name')->value;        
echo $result->get('first_name')->value;       
echo $result->get('birth_date')->value;       
echo $result->get('birth_place')->value;      
echo $result->get('blood_type')->value;       
echo $result->get('expiry_date')->value;     
```

**Champs CNI** : `card_number`, `last_name`, `first_name`, `birth_date`, `sex`, `birth_place`, `birth_prefecture`, `profession`, `issue_date`, `expiry_date`, `police_office_number`, `size`, `blood_type`, `address`, `tel`, `particular_sign`, `document_number`, `father_first_name`, `father_last_name`, `mother_first_name`, `mother_last_name`, `person_to_contact_name`, `person_to_contact_address`, `person_to_contact_tel`, `country`.

### 3. Permis de conduire

```php
$imageReader = new ImageReader($tesseractPath, 'fra', __DIR__ . '/tessdata');
$factory = new DocumentProcessorFactory($imageReader);
$processor = $factory->create(DocumentType::DRIVER_LICENSE);

// Depuis deux images (recto + verso)
$result = $processor->process('permis_recto.jpeg', 'permis_verso.jpeg');

// Depuis un PDF
$result = $processor->process('permis.pdf');

echo $result->get('license_number')->value;   
echo $result->get('last_name')->value;        
echo $result->get('first_name')->value;        
echo $result->get('birth_date')->value;        
echo $result->get('categories')->value;        
echo $result->get('expiry_date')->value;       
```

**Champs permis** : `last_name`, `first_name`, `birth_date`, `sex`, `birth_place`, `issue_date`, `expiry_date`, `license_number`, `categories`, `genre`, `blood_type`, `nationality`, `address`, `restrictions`, `cni_or_passport_number`.

### 4. Passeport

```php
$imageReader = new ImageReader($tesseractPath, 'fra', __DIR__ . '/tessdata');
$factory = new DocumentProcessorFactory($imageReader);
$processor = $factory->create(DocumentType::PASSPORT);

// Depuis une seule image (pas de verso)
$result = $processor->process('passeport.png');

// Depuis un PDF
$result = $processor->process('passeport.pdf');

echo $result->get('passport_number')->value;  
echo $result->get('last_name')->value;         
echo $result->get('first_name')->value;       
echo $result->get('birth_date')->value;        
echo $result->get('expiry_date')->value;       
echo $result->get('issuing_country')->value;  
echo $result->get('nationality')->value;      
```

**Champs passeport (MRZ)** : `passport_number`, `last_name`, `first_name`, `birth_date`, `sex`, `expiry_date`, `issuing_country`, `nationality`, `personal_number`.

**Champs passeport (texte visible)** : `full_name_visible`, `given_names_visible`, `birth_date_visible`, `birth_place_visible`, `issue_date_visible`, `expiry_date_visible`, `profession_visible`, `authority_visible`.

### 5. Détection automatique du type de document

```php
$imageReader = new ImageReader($tesseractPath, 'fra', __DIR__ . '/tessdata');
$factory = new DocumentProcessorFactory($imageReader);

// La factory analyse l'image et choisit le bon driver automatiquement
$processor = $factory->autoDetect('document.jpeg');
$result = $processor->process('document.jpeg');

echo $result->type->name; // CNI, PASSPORT ou DRIVER_LICENSE
```

### 6. Accès aux données extraites

Chaque champ est un objet `ValueStat` contenant :
- `value` (`?string`) — la valeur extraite
- `stat` (`?bool`) — le statut de validation (`true` = valide, `false` = invalide, `null` = non vérifié)

```php
$result = $processor->process('recto.jpeg', 'verso.jpeg');

// Accès direct à un champ
$cardNumber = $result->get('card_number');
echo $cardNumber->value; 
echo $cardNumber->stat;

// Raccourci pour la valeur seule
echo $result->getValue('last_name'); 

// Indicateurs globaux
echo $result->isExpired ? 'Expiré' : 'Valide';
echo $result->isValid ? 'MRZ OK' : 'MRZ invalide';
```

---

## Architecture du package

### Structure des dossiers

```
tg_document_reader/
├── composer.json
├── phpunit.xml
├── README.md
├── data/
│   ├── cni/                    # Images de test CNI
│   ├── driver-licence/         # Images de test permis
│   ├── passport/               # Images de test passeport
│   └── Local.txt               # Dictionnaire des localités togolaises
├── tessdata/
│   └── fra.traineddata         # Données linguistiques françaises Tesseract
├── src/
│   ├── Contracts/
│   │   ├── DocumentProcessorInterface.php  # Contrat générique pour tout driver
│   │   ├── ImageReaderInterface.php
│   │   └── PdfConverterInterface.php
│   ├── Dictionnaries/
│   │   └── Dics.php             # Dictionnaires et constantes
│   ├── Drivers/
│   │   ├── Cni/                 # Driver CNI
│   │   │   ├── CniProcessor.php
│   │   │   ├── Models/
│   │   │   ├── Parsers/
│   │   │   └── Validators/
│   │   ├── DriverLicense/       # Driver permis de conduire
│   │   │   ├── LicenseProcessor.php
│   │   │   ├── Models/
│   │   │   ├── Parsers/
│   │   │   └── Validators/
│   │   └── Passport/            # Driver passeport
│   │       ├── PassportProcessor.php
│   │       ├── Models/
│   │       ├── Parsers/
│   │       └── Validators/
│   ├── Features/
│   │   └── DocumentProcessorFactory.php  # Factory/Registry
│   ├── Models/
│   │   ├── DocumentResult.php   # Résultat générique
│   │   ├── DocumentType.php     # Enum des types supportés
│   │   └── ValueStat.php        # Conteneur valeur + statut
│   └── Tools/
│       ├── FileManager.php
│       ├── ImageReader.php      # Wrapper Tesseract OCR
│       ├── MrzParser.php        # Utilitaires MRZ (ICAO 9303)
│       └── PdfConverter.php     # Convertisseur PDF → image
├── tests/
│   ├── fixtures/
│   │   ├── recto_ocr.txt        # OCR attendu CNI recto
│   │   ├── verso_ocr.txt        # OCR attendu CNI verso
│   │   ├── recto.jpeg
│   │   └── verso.jpeg
│   ├── Integration/
│   │   ├── CniFixturesTest.php
│   │   └── CniPipelineTest.php
│   └── Unit/                    # Tests unitaires par driver
└── vendor/
```

### Diagramme de dépendances

```
                    ┌─────────────────────────────┐
                    │  DocumentProcessorFactory    │
                    │  (Features)                  │
                    └──────────┬──────────────────┘
                               │
          ┌────────────────────┼────────────────────┐
          ▼                    ▼                    ▼
  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
  │ CniProcessor  │    │LicenseProces.│    │PassportProce.│
  │ (Drivers/Cni) │    │(Drivers/Lic.)│    │(Drivers/Pass)│
  └───────┬───────┘    └───────┬───────┘    └───────┬───────┘
          │                    │                    │
          ▼                    ▼                    ▼
  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐
  │  ImageReader  │    │  ImageReader  │    │  ImageReader  │
  │  PdfConverter │    │  PdfConverter │    │  PdfConverter │
  └──────────────┘    └──────────────┘    └──────────────┘
          │                    │                    │
          └────────────────────┼────────────────────┘
                               ▼
                    ┌─────────────────────┐
                    │   DocumentResult    │
                    │   (Modèle commun)   │
                    └─────────────────────┘
```

Chaque driver :
1. Reçoit les chemins d'images (1 ou 2 selon le type)
2. Exécute l'OCR via `ImageReader`
3. Parse les lignes texte avec son parseur dédié
4. Valide les données avec son validateur dédié
5. Retourne un `DocumentResult` normalisé

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
| `tests/Unit/` | `Cni*Test`, `License*Test`, `Passport*Test`, `MrzParserTest`, `DicsTest`, `DocumentProcessorFactoryTest` | Tests unitaires par driver |
| `tests/Integration/` | `CniFixturesTest`, `CniPipelineTest` | Tests d'intégration avec données OCR réelles |

48 tests au total, couvrant :
- Parsing recto/verso CNI
- Validation MRZ TD1 (CNI)
- Parsing permis de conduire recto + verso
- Parsing passeport avec MRZ TD3
- Détection automatique du type de document
- Utilitaires MRZ (clés de contrôle ICAO 9303)

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

Ce projet est distribué sous licence **MIT**.
