# Changelog

## [Unreleased]

### Changed
- **Versão atual**: v0.6.1  
  **Versão sugerida após a mudança**: vX.Y.Z  
  **Descrição**: texto objetivo do que mudou e impacto (pode listar itens separados por ponto e vírgula).

## 0.6.1 - 2026-01-14

#### Protocol: 42248b7

### Breaking
- N/A

### Removed
- N/A

### Added
- Endpoint AJAX dedicado `war_links_get` para carregar link completo por ID
- Helper `sanitize_list_ids()` aceita array/JSON e remove duplicados
- `wp_enqueue_media()` em frontend (manager + affiliate-list)

### Changed
- Lógica inteligente de preservação em `update_link`: campos opcionais só processados se enviados via POST
- Preservação automática de valores existentes no BD quando campo não vem no POST
- Aplicado `sanitize_list_ids()` em create_link e update_link para validação robusta

### Fixed
- Perda de dados ao editar link sem preencher todos os campos (ex: editor inline)
- Botões de ação agora azuis (#2271b1) com `opacity: 1 !important` para garantir visibilidade
- Corrige visibilidade de botões em temas com overrides CSS agressivos

## 0.6.0 - 2026-01-14

#### Protocol: 6b96274

### Breaking
- N/A

### Removed
- N/A

### Added
- Meta fields: `war_description` (textarea) e `war_buttons` (array de botões com label/url)
- Editor inline de links via modal AJAX (sem redirecionar para wp-admin)
- Suporte a múltiplos botões de destino por link de afiliado
- Renderização de descrição nos cards/listas de afiliados
- Toggle de debug nas configurações de Integrações (ativa/desativa popups sem editar código)
- CSS completo para modal de edição inline responsivo
- Integração wp.media no modal de edição inline
- Campos de imagem, descrição, listas e botões no modal de edição
- Função `sanitize_buttons()` em class-ajax-links.php para validação de arrays de botões
- Sincronização automática: `war_redirect_url` sempre recebe a URL do primeiro botão

### Changed
- Modal de edição: abre inline ao invés de abrir wp-admin em nova aba
- Botões de afiliado: agora suporta múltiplos botões customizados por link
- Descrição renderizada em ambas as visualizações (list/card)
- `WAR_Config::is_debug()` agora lê opção `debug_enabled` de `war_integrations_settings`
- AJAX endpoint `war_links_list` retorna `description` e `buttons`
- AJAX endpoint `war_links_update` salva `description` e `buttons`
- Template link-manager.php expandido com campos de imagem, descrição e editor de botões
- Fallback: se `war_buttons` vazio, usa `war_redirect_url` como botão único "Acessar"

### Fixed
- Botões/ícones opacos em alguns temas (adicionado `opacity: 1 !important`)

## 0.5.1 - 2026-01-14

#### Protocol: a091a73

### Breaking
- N/A

### Removed
- N/A

### Added
- Submenu "Listas" no painel admin (Affiliate Links > Listas) apontando para taxonomia war_list
- Coluna "Shortcode" na tela de Listas do admin exibindo `[war_affiliate_list slug="..."]` copiável
- Enfileiramento automático de `wp_enqueue_media()` no admin para metabox de imagem funcionar
- CSS para itens sem imagem (`.war-list-item--no-image`) que oculta placeholder e ajusta layout

### Changed
- Metabox de imagem agora abre Media Library corretamente ao clicar em "Selecionar Imagem"
- Links sem imagem não exibem mais placeholder vazio; layout se ajusta automaticamente

### Fixed
- Biblioteca de mídia do WordPress não abria no metabox de imagem (faltava wp_enqueue_media)
- Submenu "Listas" não aparecia no menu Affiliate Links
- Shortcode das listas não era exibido na interface admin
- Placeholders vazios apareciam para links sem imagem configurada

## 0.5.0 - 2026-01-14

#### Protocol: 3ae1582

### Breaking
- N/A

### Removed
- N/A

### Added
- Taxonomia `war_list` para agrupar links de afiliados em listas personalizadas
- Campo de imagem global por link integrado com Media Library do WordPress (metabox na sidebar)
- Shortcode `[war_affiliate_list slug="lista-slug"]` para exibir listas em páginas/posts
- Visualizações alternáveis: lista (foto pequena à esquerda) e cards (grid com fotos grandes)
- Modal de zoom de imagem com overlay escurecido (fechar com ESC ou clique)
- Modo admin embutido: drag-drop reordenação, botões editar/deletar direto na lista pública
- Endpoints AJAX: `war_links_save_order`, `war_get_lists`, `war_list_get_links`
- Assets completos: `affiliate-list.css` e `affiliate-list.js` com responsividade total

### Changed
- Endpoints CRUD AJAX (`war_links_list`, `war_links_create`, `war_links_update`) agora suportam `image_url` e associação com listas (taxonomia `war_list`)
- Links podem ser associados a múltiplas listas via checkboxes no admin
- Preferência de visualização (lista/card) salva no localStorage do navegador
- Visitantes veem apenas botões "Acessar" e "Copiar"; admins veem controles completos

### Fixed
- N/A

## 0.4.7 - 2026-01-13

#### Protocol: b683077

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Chat IG: modo full screen no mobile (sem travar a página quando minimizado), corrige media query que quebrava o padding de segurança do header/footer e aplica máscara circular na foto do FAB minimizado.

## 0.4.6 - 2026-01-13

#### Protocol: 1671230

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Corrige chat IG: evita conflito de CSS/JS entre bolhas de mensagem e bolha flutuante (renomeia para FAB), garante exibição do card de resposta, melhora sticky header/footer com padding de segurança e scroll contido no painel (mobile).

## 0.4.5 - 2026-01-13

#### Protocol: 2217c8d

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Implementa melhorias de UI/UX do PRD v0.4.5: keywords visíveis no card do painel e feedback visual ao salvar; chat com header/footer fixos e sombra leve no footer; toggles no admin para webhook (fallback) e minimizar chat em bolha (defaults desabilitados).

## 0.4.4 - 2026-01-13

#### Protocol: e441d1a

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Publica no GitHub (develop) as mudanças de assets/inc para que Git Updater reflita corretamente
- Cache-busting por `filemtime()` nos assets do painel/chat
- Logs de build/debug (front + AJAX) para validar QR Code e Keywords em ambiente com cache/CDN

### UI (Cards)
- N/A

### Fixed
- N/A

## 0.4.3 - 2026-01-12

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Adiciona cache-busting por filemtime nos assets do painel/chat
- Adiciona logs de build/debug (front + AJAX) para facilitar validação das features de QR Code e keywords

### UI (Cards)
- N/A

### Fixed
- N/A

### Meta
- 430c529 - chore(release): v0.4.3

## 0.4.2 - 2026-01-12

### Breaking
- N/A

### Removed
- N/A

### Added
- N/A

### Changed
- Painel `[war_link_manager]` passa a exibir keywords no card e adiciona animação de confirmação ao salvar
- Chat `[war_ig_chat]` mantém header/footer fixos com drop-shadow no footer e opção de minimizar para bolha flutuante
- Integrações ganha toggles para habilitar webhook (fallback) e minimizar chat (ambos desabilitados por padrão)

### UI (Cards)
- N/A

### Fixed
- N/A

### Meta
- 500ba6d - chore(release): v0.4.2

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


