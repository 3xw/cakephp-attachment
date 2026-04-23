# Upgrading to v6

v6 cible **CakePHP 5** et passe le plugin en **API-only** (JSON). Toute l'UI
HTML (templates, vues admin, helper) est retirée, ainsi que le code Vue 2
legacy (`resources/assets/`).

## Breaking changes

### Platform

- PHP `>=8.2` (était `>=8.1`)
- CakePHP `^5.0`
- `cakephp/migrations` `^5.0` → les migrations étendent `Migrations\BaseMigration`
  (pas `AbstractMigration`)
- PHPUnit `^10.5` pour les tests

### Fichiers supprimés

- `templates/**` — toute la couche HTML admin (`Admin/Atags`, `Admin/AtagTypes`)
- `resources/assets/**` — Vue 2 + vuex + SCSS (remplacé par le front Nuxt côté
  consumer)
- `src/View/Helper/AttachmentHelper.php` — helper HTML inutilisé côté API
- `src/Controller/Admin/` — `AtagsController` / `AtagTypesController` HTML-only
- `src/Listener/SNSListener.php` — AWS SNS bridge inutilisé
- `src/Filesystem/Compressor/LambdaCompressor.php` — AWS Lambda compressor
- `src/Filesystem/Protect/AwsSignedUrlsProtection.php` — CloudFront signed URLs
  (on garde `AwsS3PreSignedUrlsProtection`)

### Routes

- Le scope `prefix('Admin')` est supprimé. Toutes les actions passent par
  `/attachment/*` (JSON).
- Nouvelles routes resource : `Atags`, `AtagTypes`, `Attachments` (en plus de
  `Aarchives` déjà présent).
- `POST /attachment/download/files` (zip multi-fichiers in-PHP) est **supprimé**.
  Le zip est désormais généré par un microservice Rust externe (cf. consumer
  `media.ofrou.ch` #11 / `services/zipper`). `getZipToken()` reste pour émettre
  le JWT attendu par le zipper.

### Consumer : côté app

- Remplacer `'3xw/cakephp-attachment': '^5.3'` par `'^6.0'` dans `composer.json`.
- Supprimer toute référence à `AttachmentHelper` dans `AppView::initialize()`.
- Les routes `/admin/attachment/*` n'existent plus → migrer l'UI vers les routes
  JSON `/attachment/*` (ou `/api/*` côté app).
- Pour le zip multi-fichiers : POST `/attachment/download/get-zip-token` →
  redirection navigateur vers `/download/zip` (nginx → service Rust).

## Ce qui reste identique

- Noms de tables : `attachments`, `atags`, `atag_types`, `attachments_atags`,
  `aarchives`
- Clés de config : `Trois/Attachment.*`
- Adapters filesystem : Local, S3, OpenStack, WebDAV, Ftp, External — tous
  conservés. Le choix se fait par `profile` dans `config/attachment.php`.
- Commands console : `at_profile`, `at_get_image_sizes`,
  `at_create_missing_translations`
- Behaviors : `Fly`, `ATag`, `Aarchive`, `Embed`, `External`, `UserID`,
  `UserATags`

## Migration DB

La migration `20170110150755_Attachment.php` a été réécrite pour étendre
`Migrations\BaseMigration` (API migrations 5.x). Aucun changement de schéma.
