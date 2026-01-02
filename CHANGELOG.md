# Changelog

## [Unreleased]

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- N/A

### Fixed
- N/A

### Meta
- N/A

## 0.1.0 - 2026-01-02

### Breaking
- N/A

### Removed
- N/A

### Added
- Painel do shortcode `[war_link_manager]` com UI inspirada no Bitly: view de criação (Create) + lista em cards (links + ações).
- Contador simples de cliques por link (`war_clicks_total`), exibido no painel e incrementado a cada visita ao `/go/{slug}`.
- Popup de debug fixo no front quando `WAR_DEBUG_MODE=true`:
  - Logs de requisições/respostas AJAX do painel (para copiar/colar).
  - Eventos de redirecionamento na página intermediária (destino, delay, slug, clicksTotal).

### Changed
- Shortcode manager agora garante enqueue de assets/nonce mesmo em builders (ex.: Elementor), evitando falhas de carregamento do painel.
- Endpoints de listagem via AJAX agora incluem `clicks_total` em cada item.

### Fixed
- Painel do shortcode passou a refletir corretamente o que foi criado no wp-admin (listagem/CRUD funcionando via AJAX em páginas do Elementor).

### Meta
- c5edb7c - chore(release): v0.1.0

## 0.0.1 - 2026-01-02

### Breaking
- N/A

### Removed
- N/A

### Added
- CPT `war_link` com rewrite público em `/go/{slug}`.
- Metabox para configurar URL de redirecionamento (meta `war_redirect_url`) via wp-admin.
- Página intermediária pública (somente loader CSS) com `noindex/nofollow` e redirecionamento automático após ~3s.
- Assets base em `assets/css` e `assets/js`.
- Shortcode `[war_link_manager]` com painel de gestão no front (admin only) + CRUD via AJAX.
- Workflow GitHub Actions para auto tag e auto release em push na branch `develop`.

### Changed
- N/A

### Fixed
- N/A

### Meta
- N/A


