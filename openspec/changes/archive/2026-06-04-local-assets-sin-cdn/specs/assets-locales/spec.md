## ADDED Requirements

### Requirement: Assets locales sin CDN
El sistema SHALL servir todos los assets CSS, JS, fuentes y webfonts desde el directorio local `assets/`, sin dependencia de CDNs externas.

#### Scenario: Carga de Bootstrap CSS desde local
- **WHEN** el navegador solicita Bootstrap CSS
- **THEN** el servidor sirve `assets/css/bootstrap.min.css` sin consultar CDN

#### Scenario: Carga de jQuery desde local
- **WHEN** el navegador solicita jQuery JS
- **THEN** el servidor sirve `assets/js/jquery-3.6.0.min.js` sin consultar CDN

#### Scenario: Carga de Font Awesome desde local
- **WHEN** el navegador solicita Font Awesome CSS
- **THEN** el servidor sirve `assets/css/fontawesome-all.min.css` y sus webfonts desde `assets/webfonts/` sin consultar CDN

#### Scenario: CSP sin orígenes CDN
- **WHEN** el servidor envía la CSP
- **THEN** `script-src`, `style-src`, `font-src`, `img-src` solo contienen `'self'` (sin URLs de CDNs externas)

#### Scenario: Operación sin internet
- **WHEN** el sistema se despliega en un entorno sin conectividad
- **THEN** todos los assets se cargan correctamente desde el servidor local

### Requirement: Consistencia de versiones
El sistema SHALL usar una versión única por librería en todos los templates.

#### Scenario: Font Awesome versión única
- **WHEN** cualquier vista referencia Font Awesome
- **THEN** usa la versión 6.5.1 desde `assets/css/fontawesome-all.min.css`

#### Scenario: Bootstrap versión única
- **WHEN** cualquier vista referencia Bootstrap
- **THEN** usa la versión 5.1.3 desde `assets/css/bootstrap.min.css` y `assets/js/bootstrap.bundle.min.js`
