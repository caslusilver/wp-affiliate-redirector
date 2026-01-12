# Changelog

## [Unreleased]

## 0.4.1 - 2026-01-12

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- N/A

### UI (Cards)
- N/A

### Fixed
- Corrige empacotamento do plugin: inclui o arquivo `inc/admin/class-qrcode-ajax.php` no repositório e no ZIP de release (evita fatal na ativação).

### Meta
- 2cb9a2d - chore(release): v0.4.1

## 0.3.0 - 2026-01-03

### Breaking
- N/A

### Removed
- N/A

### Added
- Shortcode `[war_ig_chat]`: simulador de Direct (Instagram-like) com mensagens, “digitando…”, card de resposta e auto-scroll.
- Submenu **Integrações** (Affiliate Links → Integrações) com configurações globais do Chat IG (webhook, header, ícone enviar, textos, secret, rate limit).
- Endpoint AJAX `war_ig_chat_send` (proxy do webhook) com nonce, rate limit por sessão e assinatura HMAC opcional.

### Changed
- N/A

### UI (Cards)
- N/A

### Fixed
- N/A

### Meta
- 36086fe - chore(release): v0.3.0

## 0.2.5 - 2026-01-03

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- N/A

### UI (Cards)
- Cards do painel: removida a meta abaixo do link público (não exibe mais `#id • slug` nem a URL de destino).
- Link público agora é exibido como `dominio/caminho` (sem `https://`), mantendo o `href` completo.
- Contador de cliques agora exibe ícone de gráfico ao lado (`IMG_8972.png`) e mantém alinhamento compacto.
- Truncamento com `…` (ellipsis) para título/link e aumento de fonte do link público em ~20% para facilitar a cópia.

### Fixed
- N/A

### Meta
- 2a648e1 - chore(release): v0.2.5

## 0.2.6 - 2026-01-03

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- N/A

### UI (Cards)
- Link público (visual) agora usa prefixo fixo **`af.link/{slug}`** (apenas identidade visual), mantendo o `href` real do seu domínio.
- Ações de card (Editar/Excluir) agora usam ícones minimalistas (sem stroke) e os ícones foram empacotados em `assets/icons/` para distribuição offline.

### Fixed
- N/A

### Meta
- 1b27215 - chore(release): v0.2.6

## 0.2.7 - 2026-01-03

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- N/A

### UI (Cards)
- Coluna de métricas (CLICKS) agora tem largura fixa e não é afetada por títulos/links longos (principalmente em mobile vertical).

### Fixed
- N/A

### Meta
- 0049b93 - chore(release): v0.2.7

## 0.2.2 - 2026-01-02

### Breaking
- N/A

### Removed
- N/A

### Added
- Menu **Affiliate Links** agora é um menu topo no wp-admin com submenus **Links** e **Styles** (não fica mais em Configurações).
- Adicionado `link_color` no Styles e aplicado via CSS variables (`--war-link`) para corrigir contraste e permitir customização.

### Changed
- Painel do shortcode: ações **Voltar/Criar** como texto minimalista no topo.
- Copiar usa o ícone `copy.webp` e mostra **“Copiado ✓”** no lugar por ~1s.

### Fixed
- N/A

### Meta
- 87f2722 - chore(release): v0.2.2

## 0.2.3 - 2026-01-02

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Isolamento de estilos do painel contra overrides agressivos do tema (prioridade de cor/links/botões dentro de `.war-manager`).
- Ajuste do layout do header da tela de criação para garantir **Voltar | Título | Criar** sempre visível.
- Ajuste do feedback **Copiado ✓** para não estourar a moldura e reduzir o tamanho do texto (~50%).

### Fixed
- N/A

### Meta
- 97d0d75 - chore(release): v0.2.3

## 0.2.4 - 2026-01-02

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Botões do painel agora forçam prioridade de cor (Editar/Excluir/Atualizar) para não ficarem brancos com overrides do tema.
- Ação de criação no topo da lista agora usa o símbolo **“+”**.
- Ações **Voltar** e **Criar/Atualizar** foram movidas para o rodapé do formulário (fluxo de preenchimento de cima para baixo).

### Fixed
- N/A

### Meta
- c2566bf - chore(release): v0.2.4

## 0.2.1 - 2026-01-02

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Bump de versão do plugin para `0.2.1`.

### Fixed
- Contador de cliques agora só incrementa quando o destino é válido (evita contabilizar 404).
- Popup de debug fixo também na página intermediária de redirecionamento quando `WAR_DEBUG_MODE=true`.

### Meta
- N/A

## 0.2.0 - 2026-01-02

### Breaking
- N/A

### Removed
- N/A

### Added
- Página de configurações no wp-admin para definir cores do painel (WP Color Picker).
- Novo layout do painel: título \"Affiliate Links\" centralizado, botão \"+\" e ícone de copiar ao lado do link curto.

### Changed
- Campos do formulário reorganizados e traduzidos (pt-br) e remoção do \"Select domain\".
- Slug agora é sugerido automaticamente a partir do título (editável).

### Fixed
- Melhorias de contraste do texto dos botões/ações no card em fundo branco (via CSS variables).

### Meta
- 339a625 - chore(release): v0.2.0

## 0.1.1 - 2026-01-02

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- N/A

### Fixed
- Correção crítica no `assets/js/manager.js` que sobrescrevia o `$` do jQuery e quebrava o painel.
- Ativação do `WAR_DEBUG_MODE` (dev) para exibir popup/logs e facilitar diagnóstico.
- Ajuste do botão "Atualizar Cache" do Git Updater para o mesmo padrão do `packing-panel-woo-dev` (UI + spinner + notices).
- Correção do workflow GitHub Actions (YAML + permissões) para criar tag/release automaticamente.

### Meta
- 505242b - chore(release): v0.1.1

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


